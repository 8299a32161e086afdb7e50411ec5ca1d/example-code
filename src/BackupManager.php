<?php

namespace Example\Backup;

use Example\Backup\Configuration\BackupConfiguration;
use Example\Backup\Service\BackupService;
use Example\Backup\Service\BackupServiceFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\OpenStack;

class BackupManager
{
    const OPENSTACK_STORE_NAME = 'redacted';
    const OPENSTACK_STORE_REGION = 'redacted';
    const OPENSTACK_CONTAINER = 'example-backup';
    const BACKUP_SHORT_RETENTION_POLICY = '1 week';
    const BACKUP_LONG_RETENTION_POLICY = '13 months';

    /** @var $manager MountManager */
    private $manager;

    /** @var $backupConfiguration BackupConfiguration */
    private $backupConfiguration;

    /** @var $backupServices BackupService[] */
    private $backupServices;

    public function __construct()
    {
        $this->backupConfiguration = new BackupConfiguration();

        $local = new Filesystem(new Local($this->backupConfiguration->getTempDirectory()));
        $client = new OpenStack($this->backupConfiguration->getOpenstackUrl(), $this->backupConfiguration->getOpenstackConfiguration());
        $store = $client->objectStoreService(self::OPENSTACK_STORE_NAME, self::OPENSTACK_STORE_REGION);
        $container = $store->getContainer(self::OPENSTACK_CONTAINER);
        $backup = new Filesystem(new RackspaceAdapter($container));

        $this->manager = new MountManager([
            'local' => $local,
            'backup' => $backup,
        ]);

        $this->registerBackupServices();
    }

    private function registerBackupServices()
    {
        //TODO: Reconsider moving this to an external configuration file that is to be parsed
        $this->backupServices = [
            BackupServiceFactory::build('Example\Backup\Service\ExampleServerOneBackupService', $this->backupConfiguration),
            BackupServiceFactory::build('Example\Backup\Service\ExampleServerTwoBackupService', $this->backupConfiguration),
        ];
    }

    public function runBackup()
    {
        //Call cleanup to ensure a clean state
        $this->cleanup();

        //Run backups tasks on registered services.
        foreach ($this->backupServices as $backupService) {
            $backupService->backup();
            //TODO: Log or report upon failure of executing the backup configuration
        }

        //Move archived results of various backup services into a general archive, ready for long term storage.
        $this->archive();

        //Move archive to storage.
        $this->store();
        //TODO: Log or report upon failure

        //Remove old backups if required.
        $this->enforceStorageRetentionPolicy();
    }

    private function archive()
    {
        //Move al separately generated service archives into a general archive with current date, exclude .gitkeep files.
        $command = sprintf('tar -czf %s -C %s . --exclude .gitkeep', $this->backupConfiguration->getArchiveOutputPath(), $this->backupConfiguration->getTempDirectory());
        exec($command);
    }

    private function store()
    {
        //Check if archive has been created.
        if (!is_file($this->backupConfiguration->getArchiveOutputPath())) {
            return;
        }

        $this->storeAtLongTermStorageProvider();
        $this->storeAtLocalBackup();
    }

    private function storeAtLongTermStorageProvider()
    {
        $fromLocation = 'local://' . $this->backupConfiguration->getArchiveName();
        $toLocation = 'backup://' . $this->backupConfiguration->getDate() . '/backup/' . $this->backupConfiguration->getArchiveName();

        $this->manager->copy($fromLocation, $toLocation);
    }

    private function storeAtLocalBackup()
    {
        $fromLocation = $this->backupConfiguration->getArchiveOutputPath();
        $toLocation = $this->backupConfiguration->getLocalBackupDirectory();

        exec('cp ' . $fromLocation . ' ' . $toLocation);
    }

    /**
     * Enforces the storage retention policy (first day of last 13 months, and the last 7 days (week)).
     *
     * NOTE: Partially reused existing code from previous export script.
     */
    private function enforceStorageRetentionPolicy()
    {
        $dirs = $this->manager->listContents('backup://', false);

        foreach ($dirs as $dir) {
            if ($dir['type'] == 'dir' && preg_match('/^\d{8}$/', $dir['filename'])) {
                $fileDateTime = new \DateTime($dir['filename']);
                if ($fileDateTime < new \DateTime('-' . self::BACKUP_SHORT_RETENTION_POLICY) && ($fileDateTime->format('d') != 1 || $fileDateTime < new \DateTime('-' . self::BACKUP_LONG_RETENTION_POLICY))) {
                    $this->manager->deleteDir("backup://{$dir['filename']}");
                }
            }
        }
    }

    /**
     * Remove all content in the temp directory (except for hidden files (e.g. .gitkeep)).
     */
    private function cleanup()
    {
        exec(sprintf('rm -rf %s/*', $this->backupConfiguration->getTempDirectory()));
    }
}

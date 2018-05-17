<?php

namespace Example\Backup\Service;

use Example\Backup\Configuration\BackupConfiguration;

abstract class BackupService implements BackupServiceInterface
{
    //Default values which are to be overridden by children
    const OUTPUT_DIRECTORY_NAME = 'default';
    const REMOTE_SERVER_ADDRESS = '0.0.0.0';
    const REMOTE_SERVER_SSH_USER = 'user';
    const REMOTE_SERVER_SSH_PORT = 22;

    const EXECUTION_STRATEGY_SSH = 'ssh';
    const EXECUTION_STRATEGY_LOCAL = 'local';

    protected $backupConfiguration;

    public function __construct(BackupConfiguration $backupConfiguration)
    {
        $this->backupConfiguration = $backupConfiguration;
    }

    /**
     * Ensures the existence of service output path.
     *
     * @return bool
     */
    public function ensureOutputDirectoryExistence()
    {
        $outputDirectoryPath = $this->getOutputDirectory();

        return is_dir($outputDirectoryPath) || mkdir($outputDirectoryPath, 0755, true);
    }

    /**
     * Composes mysqldump command that is to be executed on the target machine.
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     *
     * @return string
     */
    protected function composeMysqldumpCommand(string $host, string $user, string $password, string $database)
    {
        return sprintf('/usr/bin/mysqldump --host=%s --user=%s --password=%s --databases %s', $host, $user, $password, $database);
    }

    /**
     * @param string $command
     * @param string $destinationFilename
     *
     * @return string
     */
    protected function composeOutputTransferCommand(string $command, string $destinationFilename)
    {
        if (self::EXECUTION_STRATEGY_SSH === $this->getExecutionStrategy()) {
            return sprintf('ssh -i %s %s@%s -p %d "%s" > %s', $this->backupConfiguration->getIdentityFileLocation(), static::REMOTE_SERVER_SSH_USER, static::REMOTE_SERVER_ADDRESS, static::REMOTE_SERVER_SSH_PORT, $command, $this->getOutputDirectory() . '/' . $destinationFilename);
        } else {
            return sprintf('%s > %s', $command, $this->getOutputDirectory() . '/' . $destinationFilename);
        }
    }

    /**
     * Returns the output directory where files ought to be stored (until cleanup is executed).
     *
     * @return string
     */
    protected function getOutputDirectory()
    {
        return $this->backupConfiguration->getTempDirectory() . DIRECTORY_SEPARATOR . static::OUTPUT_DIRECTORY_NAME;
    }
}

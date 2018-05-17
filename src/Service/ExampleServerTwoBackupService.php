<?php

namespace Example\Backup\Service;

class ExampleServerTwoBackupService extends BackupService
{
    //Overriding parent constants
    const OUTPUT_DIRECTORY_NAME = 'example-server-one';
    const REMOTE_SERVER_ADDRESS = '0.0.0.0';
    const REMOTE_SERVER_SSH_USER = 'user';
    const REMOTE_SERVER_SSH_PORT = 22;

    //Custom configuration
    const NGINX_CONFIGURATION_PATH = '/etc/nginx/sites-available';
    const GITLAB_CONFIGURATION_PATH = '/etc/gitlab';
    const GITLAB_BACKUP_PATH = '/var/opt/gitlab/backups/';

    /**
     *
     * @throws \Exception
     * @return bool
     */
    public function backup()
    {
        if (!$this->ensureOutputDirectoryExistence()) {
            return false;
        }

        try {
            $this->backupNginxConfiguration();
            $this->backupGitlabConfiguration();
            $this->backupGitlabData();
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Creates a backup of the NGINX configuration from the configured directory.
     */
    private function backupNginxConfiguration()
    {
        $command = $this->composeOutputTransferCommand('tar -czf - ' . self::NGINX_CONFIGURATION_PATH, 'nginx-configuration.tar.gz');
        exec($command);
    }

    /**
     * Creates a backup archive of the gitlab configuration in the gitlab directory.
     */
    private function backupGitlabConfiguration()
    {
        $command = $this->composeOutputTransferCommand('tar -czf - ' . self::GITLAB_CONFIGURATION_PATH, 'gitlab-configuration.tar.gz');
        exec($command);
    }

    /**
     * NOTE: scandir code was taken from a previously used backup-script, and mostly not written by me (Silas that is).
     *
     * @fixme Should be rewritten (either execute commands on remote host, or distinguish better between remote/local backup services.)
     */
    private function backupGitlabData()
    {
        exec("gitlab-rake gitlab:backup:create CRON=1");
        $newestCtime = 0;
        $newestFile = null;

        foreach (scandir(self::GITLAB_BACKUP_PATH) as $file) {
            $fullPath = self::GITLAB_BACKUP_PATH . $file;
            if (is_file($fullPath) && filectime($fullPath) > $newestCtime && pathinfo($fullPath, PATHINFO_EXTENSION) === 'tar') {
                $newestCtime = filectime($fullPath);
                $newestFile = $fullPath;
            }
        }

        //Because gitlab already outputs as tar, we only need to gzip and move to backup directory
        exec('gzip ' . $newestFile);
        exec('mv ' . $newestFile . '.gz ' . $this->getOutputDirectory() . '/' . 'gitlab-data.tar.gz');
    }

    public function getExecutionStrategy()
    {
        return self::EXECUTION_STRATEGY_LOCAL;
    }
}

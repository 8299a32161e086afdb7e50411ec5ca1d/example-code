<?php

namespace Example\Backup\Service;

class ExampleServerOneBackupService extends BackupService
{
    //Overriding parent constants
    const OUTPUT_DIRECTORY_NAME = 'example-server-one';
    const REMOTE_SERVER_ADDRESS = '0.0.0.0';
    const REMOTE_SERVER_SSH_USER = 'user';
    const REMOTE_SERVER_SSH_PORT = 22;

    //Custom configuration
    const NGINX_CONFIGURATION_PATH = '/etc/nginx/sites-available';
    const STATIC_FILES_PATH = '/var/www/example-project/static';

    //NOTE: Originally located outside of repository, moved inside for demonstration purposes.
    const DATABASE_USER = 'example-user';
    const DATABASE_PASSWORD = 'example-password';
    const DATABASE_NAME_EXAMPLE_PROJECT_ONE = 'example_project_one';
    const DATABASE_NAME_EXAMPLE_PROJECT_TWO = 'example_project_two';

    /**
     *
     * @throws \Exception
     * @return bool
     */
    public function backup()
    {
        if (!$this->ensureOutputDirectoryExistence()) {
            //TODO: throw exception
            return false;
        }

        try {
            $this->backupNginxConfiguration();
            $this->backupExampleProjectOneStaticFiles();
            $this->backupExampleProjectOneDatabase();
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
     * Creates a backup of the static files of example project one.
     */
    private function backupExampleProjectOneStaticFiles()
    {
        $command = $this->composeOutputTransferCommand('tar -czf - ' . self::STATIC_FILES_PATH, self::DATABASE_NAME_EXAMPLE_PROJECT_ONE.'-static-files.tar.gz');
        exec($command);
    }

    /**
     * Export database of example project one.
     */
    private function backupExampleProjectOneDatabase()
    {
        $command = $this->composeMysqldumpCommand(self::REMOTE_SERVER_ADDRESS, self::DATABASE_USER, self::DATABASE_PASSWORD, self::DATABASE_NAME_EXAMPLE_PROJECT_ONE);
        $temporaryFile = $this->getOutputDirectory() . '/database_' . self::DATABASE_NAME_EXAMPLE_PROJECT_ONE . '.sql.gz';
        exec($command . ' | gzip -c > ' . $temporaryFile);
    }

    public function getExecutionStrategy()
    {
        return self::EXECUTION_STRATEGY_SSH;
    }
}

<?php

namespace Example\Backup\Service;

use Example\Backup\Configuration\BackupConfiguration;

/**
 * Class BackupServiceFactory
 *
 * @package Example\Backup\Service
 */
class BackupServiceFactory
{

    /**
     * Simple factory method for creating Backup services.
     *
     * @param string $class The class name of the service
     * @param BackupConfiguration $configuration
     *
     * @return mixed
     */
    public static function build(string $class, BackupConfiguration $configuration)
    {
        return new $class($configuration);
    }
}

<?php

namespace Example\Backup\Configuration;

use Symfony\Component\Dotenv\Dotenv;

class BackupConfiguration
{
    const BASE_DIRECTORY_RELATIVE_LEVELS_UPWARD = 3;
    const OUTPUT_BASENAME = 'example_backup';

    private $datetime;
    private $baseDirectory;
    private $identityFileLocation;
    private $localBackupDirectory;
    private $openstackUrl;
    private $openstackUsername;
    private $openstackPassword;
    private $openstackTenantId;

    public function __construct()
    {
        $this->baseDirectory = dirname(__FILE__, self::BASE_DIRECTORY_RELATIVE_LEVELS_UPWARD);

        if (!file_exists($this->baseDirectory . DIRECTORY_SEPARATOR . '.env')) {
            throw new \Exception('Environment configuration file (.env) not found.');
        }

        $dotenv = new Dotenv();
        $dotenv->load($this->baseDirectory . DIRECTORY_SEPARATOR . '.env');

        $this->datetime = new \DateTime();
        $this->openstackUrl = getenv('OPENSTACK_URL');
        $this->openstackUsername = getenv('OPENSTACK_USERNAME');
        $this->openstackPassword = getenv('OPENSTACK_PASSWORD');
        $this->openstackTenantId = getenv('OPENSTACK_TENANT_ID');
        $this->identityFileLocation = getenv('SSH_IDENTITY_FILE');
        $this->localBackupDirectory = getenv('LOCAL_BACKUP_DIRECTORY');
    }

    public function getDate()
    {
        return $this->datetime->format('Ymd');
    }

    public function getTime()
    {
        return $this->datetime->format('Ymdhis');
    }

    public function getTempDirectory()
    {
        return $this->baseDirectory . DIRECTORY_SEPARATOR . 'tmp';
    }

    public function getBaseDirectory()
    {
        return $this->baseDirectory;
    }

    public function getOpenstackUrl()
    {
        return $this->openstackUrl;
    }

    public function getIdentityFileLocation()
    {
        return $this->identityFileLocation;
    }

    public function getLocalBackupDirectory()
    {
        return $this->localBackupDirectory;
    }

    public function getArchiveName()
    {
        return self::OUTPUT_BASENAME . '_' . $this->getTime() . '.tar.gz';
    }

    public function getArchiveOutputPath()
    {
        return $this->getTempDirectory() . DIRECTORY_SEPARATOR . $this->getArchiveName();;
    }

    public function getOpenstackConfiguration()
    {
        return [
            'username' => $this->openstackUsername,
            'password' => $this->openstackPassword,
            'tenantId' => $this->openstackTenantId
        ];
    }
}

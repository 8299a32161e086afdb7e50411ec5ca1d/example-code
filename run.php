<?php

require 'vendor/autoload.php';

use Example\Backup\BackupManager;

$backupManager = new BackupManager();
$backupManager->runBackup();
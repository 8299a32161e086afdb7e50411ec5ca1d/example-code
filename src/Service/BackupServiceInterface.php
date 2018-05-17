<?php

namespace Example\Backup\Service;

interface BackupServiceInterface
{
    public function backup();
    public function getExecutionStrategy();
}

#!/usr/bin/env php
<?php
use Symfony\Component\Console\Application;
use Backup\Command\BackupCommand;
use Backup\Command\RemoteBackupCommand;

require_once 'vendor/autoload.php';
$application = new Application('CpBackup', '1.0.dev');
$application->add(new BackupCommand());
$application->add(new RemoteBackupCommand());
$application->run();

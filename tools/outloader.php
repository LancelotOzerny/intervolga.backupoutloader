<?php
use \Lancy\BackupOutloader\Backup\BackupController;
use \Lancy\BackupOutloader\Connection\FtpConnection;

set_time_limit(0);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

if (\Bitrix\Main\Loader::includeModule('lancy.backupoutloader') === false)
{
    echo 'Module not included';
    die();
}

// ###############################################################
// #    GET DATA
// ###############################################################
$options = [
    'connection_host' => COption::GetOptionString('lancy.backupoutloader', 'connection_host'),
    'connection_port' => COption::GetOptionString('lancy.backupoutloader', 'connection_port'),
    'connection_login' => COption::GetOptionString('lancy.backupoutloader', 'connection_login'),
    'connection_password' => COption::GetOptionString('lancy.backupoutloader', 'connection_password'),
    'connection_passive_mode' => COption::GetOptionString('lancy.backupoutloader', 'connection_passive_mode'),

    'outload_count' => COption::GetOptionString('lancy.backupoutloader', 'outload_count'),
    'outload_path' => COption::GetOptionString('lancy.backupoutloader', 'outload_path'),
    'outload_remove_current' => COption::GetOptionString('lancy.backupoutloader', 'outload_remove_current'),
    'outload_remove_all' => COption::GetOptionString('lancy.backupoutloader', 'outload_remove_all'),
];

// ###############################################################
// #    CHECK START DATA
// ###############################################################
$error = 'Проверьте настройку данных!';

foreach (['connection_host', 'connection_port', 'connection_login', 'connection_password'] as $option)
{
    if (isset($options[$option]) === false || $options[$option] === '') die($error);
}

// ###############################################################
// #    CONNECTIONS
// ###############################################################
$ftp = new FtpConnection($options['connection_host'], $options['connection_port']);
$ftp->login(
    $options['connection_login'],
    $options['connection_password'],
    isset($options['connection_passive_mode']) && $options['connection_passive_mode'] === 'Y' ? true : false,
);

// ###############################################################
// #    PREPARE
// ###############################################################
if ($options['outload_remove_all'] === 'before')
{
    BackupController::Instance()->deleteAll();
}
$backupsBefore = BackupController::Instance()->getList();

if ($options['outload_path'] !== '')
{
    $folder = $options['outload_path'];
    $ftp->createDir($folder);
    $ftp->go($folder);
}

$delete = [];
$backupDirs = [];
$currentItems = $ftp->getContent();
foreach ($currentItems as $item)
{
    if ($ftp->isDir($item))
    {
        $backupDirs[] = $item;
    }
}

if (isset($options['outload_count']) && intval($options['outload_count']) > 0)
{
    $toDeleteCount = (1 + count($backupDirs) - intval($options['outload_count']));
    if ($toDeleteCount > 0)
    {
        asort($backupDirs);

        foreach ($backupDirs as $dir)
        {
            $delete[] = $dir;
            if (--$toDeleteCount <= 0)
            {
                break;
            }
        }
    }
}

foreach ($delete as $delFile)
{
    $ftp->delete($delFile);
}
$folder = 'backup from ' . date('Y-m-d');

if($ftp->exist($folder))
{
    $ftp->delete($folder);
}

$ftp->createDir($folder);
$ftp->go($folder);

// ###############################################################
// #    CREATE
// ###############################################################
BackupController::Instance()->create();

$backupsAfter = BackupController::Instance()->getList();
$currentBackup = '';

foreach ($backupsAfter as $backup)
{
    if (in_array($backup, $backupsBefore) === false)
    {
        $currentBackup = $backup;
        break;
    }
}

// ###############################################################
// #    OUTLOAD
// ###############################################################
$backupParts = BackupController::Instance()->getAllParts($currentBackup);
foreach ($backupParts as $part)
{
    $ftp->send(BackupController::Instance()->path . '/' . $part, $part);
}

// ###############################################################
// #    CLEAN
// ###############################################################
if ($options['outload_remove_all'] === 'after')
{
    $backupsAfter = BackupController::Instance()->getList();
    foreach ($backupsAfter as $backup)
    {
        if (in_array($backup, $backupsBefore))
        {
            BackupController::Instance()->delete($backup);
            break;
        }
    }
}
else if ($options['outload_remove_current'] === 'yes')
{
    BackupController::Instance()->delete($currentBackup);
}

echo 'Выгрузка завершена!';
?>
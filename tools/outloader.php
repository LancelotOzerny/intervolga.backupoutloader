<?php
use \Intervolga\BackupOutloader\Backup\BackupController;
use \Intervolga\BackupOutloader\Connection\FtpConnection;
use \Intervolga\BackupOutloader\Log\Logger;
use Bitrix\Main\Localization\Loc;

set_time_limit(0);

require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/tar_gz.php';

if (\Bitrix\Main\Loader::includeModule('intervolga.backupoutloader') === false)
{
    die(Loc::getMessage('BACKUP_OUTLOAD.ERROR.MODULE_INCLUDE'));
}
$logger = new Logger();
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.MODULE_INCLUDE'));

// ###############################################################
// #    GET DATA
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.GET_OPTION_DATA'));
$options = [
    'connection_host' => COption::GetOptionString('intervolga.backupoutloader', 'connection_host'),
    'connection_port' => COption::GetOptionString('intervolga.backupoutloader', 'connection_port'),
    'connection_login' => COption::GetOptionString('intervolga.backupoutloader', 'connection_login'),
    'connection_password' => COption::GetOptionString('intervolga.backupoutloader', 'connection_password'),
    'connection_passive_mode' => COption::GetOptionString('intervolga.backupoutloader', 'connection_passive_mode'),

    'outload_count' => COption::GetOptionString('intervolga.backupoutloader', 'outload_count'),
    'outload_path' => COption::GetOptionString('intervolga.backupoutloader', 'outload_path'),
    'outload_remove_current' => COption::GetOptionString('intervolga.backupoutloader', 'outload_remove_current'),
    'outload_remove_all' => COption::GetOptionString('intervolga.backupoutloader', 'outload_remove_all'),
    'outload_additional' => COption::GetOptionString('intervolga.backupoutloader', 'outload_additional'),
];

// ###############################################################
// #    CHECK START DATA
// ###############################################################
$error = Loc::getMessage('BACKUP_OUTLOAD.ERROR.CHECK_SETTING');

foreach (['connection_host', 'connection_port', 'connection_login', 'connection_password'] as $option)
{
    if (isset($options[$option]) === false || $options[$option] === '')
    {
        $logger->Log($error);
        die($error);
    }
}

// ###############################################################
// #    CHECK ADDITIONAL
// ###############################################################
$additionalDirs = explode("\n", $options['outload_additional']);

foreach ($additionalDirs as $key => $additional)
{
    $arr = explode('/', $additional);

    $additionalDirs[$key] = [
        'name' => trim(end($arr)),
        'path' => $_SERVER['DOCUMENT_ROOT'] . trim($additional),
        'exists' => is_dir($_SERVER['DOCUMENT_ROOT']  . trim($additional)) ? 'Y' : 'N',
    ];
}

// ###############################################################
// #    CONNECTIONS
// ###############################################################
$ftp = new FtpConnection([
    'HOST' => $options['connection_host'],
    'PORT' => $options['connection_port'],
    'LOGIN' => $options['connection_login'],
    'PASSWORD' => $options['connection_password'],
    'PASSIVE' => $options['connection_passive_mode'],
]);

if ($ftp->checkTryConnection())
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.FTP_AUTHORISE_SUCCESSFUL'));
}
else
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ERROR.FTP_AUTHORISE'));
    die(Loc::getMessage('BACKUP_OUTLOAD.ERROR.FTP_AUTHORISE'));
}

// ###############################################################
// #    PREPARE
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.PREPARE_TO_OUTLOAD'));
if ($options['outload_remove_all'] === 'before')
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.DELETE_ALL_BACKUP_BEFORE'));
    BackupController::Instance()->deleteAll();
}
$backupsBefore = BackupController::Instance()->getList();

if ($options['outload_path'] !== '')
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.PREPARE_CONTAINER_FOLDER'));
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

$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.DELETE_LIMIT'));
foreach ($delete as $delFile)
{
    $ftp->delete($delFile);
}
$folder = 'backup from ' . date('Y-m-d');

// ###############################################################
// #    WARNING: ДАЛЕЕ ДОБАВИТЬ ОПЦИЮ ЗАМЕНЫ ИЛИ ОТМЕНЫ ВЫГРУЗКИ
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.PREPARE_TO_DIR_OUTLOAD'));
if($ftp->exist($folder))
{
    $ftp->delete($folder);
}

$ftp->createDir($folder);
$ftp->go($folder);

// ###############################################################
// #    CREATE
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.START_BACKUP_CREATE'));
$backupCreateResult = BackupController::Instance()->create();

if ($backupCreateResult !== true)
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ERROR.BACKUP_CREATE') . $backupCreateResult);
    die(Loc::getMessage('BACKUP_OUTLOAD.ERROR.BACKUP_CREATE') . $backupCreateResult);
}
else
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.SUCCESS_BECKUP_CREATED'));
}

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
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.START_OUTLOAD'));
$backupParts = BackupController::Instance()->getAllParts($currentBackup);
$ftp->connect();
foreach ($backupParts as $part)
{
    $send = $ftp->send(BackupController::Instance()->path . '/' . $part, $part);

    if ($send === false)
    {
        if ($ftp->isConnected() === false)
        {
            $logger->Log('Пропало соединение с сервером. Подклчение не обнаружено');
        }

        $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ERROR.TRANSFER_ERROR'));
        die(Loc::getMessage('BACKUP_OUTLOAD.ERROR.TRANSFER_ERROR'));
    }
}
$ftp->close();

// ###############################################################
// #    CLEAN
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.START_CLEAN'));
if ($options['outload_remove_all'] === 'after')
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.DELETE_ALL_BACKUP_AFTER'));
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
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.DELETE_NEW_BACKUP'));
    BackupController::Instance()->delete($currentBackup);
}

// ###############################################################
// #    ВЫГРУЗКА ДОПОЛНИТЕЛЬНЫХ ДИРЕКТОРИЙ
// ###############################################################
$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.START_ARCHIVE_ADDITIONAL'));

foreach ($additionalDirs as $additional)
{
    $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ADDITIONAL_DIRECTORY') . ': ' . $additional['path']);
    if ($additional['exists'] === 'N')
    {
        $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ERROR.ADDITIONAL_DIRECTORY_NOT_EXISTS'));
        continue;
    }

    $tarName =  $additional['name'] . '.tar.gz';
    $tarDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/' . $tarName;

    $archive = new CArchiver($tarDir, true);
    $archive->Add('"' . $additional['path'] . '"', $additional['name'], $additional['path']);

    $arErrors = $archive->GetErrors();
    if(count($arErrors) > 0)
    {
        $errors = print_r($arErrors, true);
        break;
    }

    $ftp->connect();
    $send = $ftp->send(BackupController::Instance()->path . '/' . $tarName, $tarName);
    $ftp->close();

    if ($send === false)
    {
        $logger->Log(Loc::getMessage('BACKUP_OUTLOAD.ERROR.TRANSFER_ADDITIONAL_ERROR'));
    }

    unlink( $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/' . $tarName);
}

$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.END_ARCHIVE_ADDITIONAL'));

$logger->Log(Loc::getMessage('BACKUP_OUTLOAD.SUCCESS_OUTLOAD'));
?>
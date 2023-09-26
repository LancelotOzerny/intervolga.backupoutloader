<?php
require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

set_time_limit(0);

if (\Bitrix\Main\Loader::includeModule('intervolga.backupoutloader') === false)
{
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.MODULE_INCLUDE'));
}

$logger = new \Intervolga\BackupOutloader\Log\Logger();
$outloader = new \Intervolga\BackupOutloader\Controller\OutloadController();

$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.DATA_CHECK'));

if ($outloader->checkData() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.DATA_CHECK'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.DATA_CHECK'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.DATA_CHECK_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.DATA_PREPARE'));
if ($outloader->prepare() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.DATA_PREPARE'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.DATA_PREPARE'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.DATA_PREPARE_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.BACKUP_CREATE'));
if ($outloader->create() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.BACKUP_CREATE'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.BACKUP_CREATE'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.BACKUP_CREATE_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.BACKUP_OUTLOAD'));
if ($outloader->outload() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.BACKUP_OUTLOAD'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.BACKUP_OUTLOAD'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.BACKUP_OUTLOAD_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.LOCAL_CLEAN'));
$outloader->clean();
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.LOCAL_CLEAN_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.ADDITIONAL_PREPARE'));
if ($outloader->prepareAdditional() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.ADDITIONAL_PREPARE'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.ADDITIONAL_PREPARE'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.ADDITIONAL_PREPARE_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.ADDITIONAL_OUTLOAD'));
if ($outloader->sendAdditional() === false)
{
    $logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.ADDITIONAL_OUTLOAD'));
    die(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.ERROR.ADDITIONAL_OUTLOAD'));
}
$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.ADDITIONAL_OUTLOAD_SUCCESSFUL'));



$logger->Log(Loc::getMessage('INTERVOLGA_BACKUP_OUTLOADER.MESSAGE.OUTLOAD_SUCCESSFUL'));
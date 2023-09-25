<?php
require $_SERVER["DOCUMENT_ROOT"] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

set_time_limit(0);

if (\Bitrix\Main\Loader::includeModule('intervolga.backupoutloader') === false)
{
    die(Loc::getMessage('BACKUP_OUTLOAD.ERROR.MODULE_INCLUDE'));
}

$logger = new \Intervolga\BackupOutloader\Log\Logger();
$outloader = new \Intervolga\BackupOutloader\Controller\OutloadController();

$logger->Log('Начало выгрузки. Проверка данных.');

if ($outloader->checkData() === false)
{
    $logger->Log('Проверка данных провалена. Повторите попытку с новыми данными!');
    die('Проверка данных провалена. Повторите попытку с новыми данными!');
}
$logger->Log('Проверка данных прошла успешно.');



$logger->Log('Подготовка данных к выгрузке.');
if ($outloader->prepare() === false)
{
    $logger->Log('Подготовка данных к выгрузке провалена. Повторите попытку позже.');
    die('Подготовка данных к выгрузке провалена. Повторите попытку позже.');
}
$logger->Log('Подготовка данных к выгрузке прошла успешно.');



$logger->Log('Создание резервной копии');
if ($outloader->create() === false)
{
    $logger->Log('Создание резервной копии провалено. Попывторите попытку позже или обратитесь к программистам за помощью!');
    die('Создание резервной копии провалено. Попывторите попытку позже или обратитесь к программистам за помощью!');
}
$logger->Log('Создание резервной копии прошло успешно');



$logger->Log('Отправка резервной копии по FTP');
if ($outloader->outload() === false)
{
    $logger->Log('Отправка резервной копии по FTP провалена. Попывторите попытку позже или обратитесь к программистам за помощью!');
    die('Отправка резервной копии по FTP провалена. Попывторите попытку позже или обратитесь к программистам за помощью!');
}
$logger->Log('Отправка резервной копии по FTP прошла успешно');

$logger->Log('Выгрузка прошла успешно.');
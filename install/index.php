<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Class intervolga_outloader extends CModule
{
    var $MODULE_ID = "intervolga.outloader";
    var $MODULE_GROUP_RIGHTS = "Y";

    public function __construct()
    {
        $arModuleVersion = [];
        include_once(__DIR__. '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion))
        {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('INTERVOLGA_BACKUPOUTLOADER.MODULE.NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('INTERVOLGA_BACKUPOUTLOADER.MODULE.DESCRIPTION');
    }

    public function DoInstall()
    {
        $this->InstallFiles();
        \Bitrix\Main\ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        $cronFolder = $_SERVER['DOCUMENT_ROOT'] . '/cron';
        $installCronFolder = dirname(__FILE__) . '/cron';

        if (is_dir($cronFolder) === false)
        {
            mkdir($cronFolder);
        }

        $files = scandir($installCronFolder);
        foreach ($files as $file)
        {
            copy("$installCronFolder/$file", "$cronFolder/$file");
        }
    }

    public function UnInstallFiles()
    {
        $cronFolder = $_SERVER['DOCUMENT_ROOT'] . '/cron';
        if (is_dir($cronFolder))
        {
            $files = scandir(dirname(__FILE__) . '/cron');
            foreach ($files as $file)
            {
                unlink($cronFolder . '/' . $file);
            }
        }
    }
}
?>
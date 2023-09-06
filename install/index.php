<?php
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Class lancy_backupoutloader extends CModule
{
    var $MODULE_ID = "lancy.backupoutloader";
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

        $this->MODULE_NAME = Loc::getMessage('LANCY_BACKUPOUTLOADER.MODULE.NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('LANCY_BACKUPOUTLOADER.MODULE.DESCRIPTION');
    }

    public function DoInstall()
    {
        RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall()
    {
        UnRegisterModule($this->MODULE_ID);
    }
}
?>
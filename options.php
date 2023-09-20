<?php B_PROLOG_INCLUDED === true || die();

// ###############################################################
// #    PREPARE
// ###############################################################
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();
$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

Loader::includeModule($module_id);

global $APPLICATION, $USER;

// ###############################################################
// #    OPTIONS
// ###############################################################
$options = [
    'connection' => [
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TITLE.CONNECTION_SETTINGS'),
        [
            'connection_host',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.HOSTNAME'),
            '',
            ['text']
        ],
        [
            'connection_port',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.PORT'),
            '21',
            ['text']
        ],
        [
            'connection_login',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.LOGIN'),
            '',
            ['text']
        ],
        [
            'connection_password',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.PASSWORD'),
            '',
            ['password']
        ],
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TITLE.ADDITIONAL_SETTINGS'),
        [
            'connection_passive_mode',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.PASSIVE_MODE'),
            'N',
            ['checkbox']
        ]
    ],

    'outload' => [
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TITLE.OUTLOAD_SETTINGS'),
        [
            'outload_count',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.BACKUP_OUTLOAD_COUNT'),
            '0',
            ['text']
        ],
        [
            'outload_path',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.BACKUP_OUTLOAD_PATH'),
            '',
            ['text']
        ],
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TITLE.BACKUPS_SETTINGS'),
        [
            'outload_remove_current',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.REMOVE_CURRENT'),
            '',
            [
                'selectbox',
                [
                    'none' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.REMOVE_ARRAY.NOT'),
                    'yes' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.REMOVE_ARRAY.YES'),
                ]
            ]
        ],
        [
            'outload_remove_all',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.REMOVE_OTHER'),
            '',
            [
                'selectbox',
                [
                    'none' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.REMOVE_ARRAY.NOT'),
                    'after' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.REMOVE_ARRAY.AFTER'),
                    'before' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.REMOVE_ARRAY.BEFORE'),
                ]
            ]
        ],
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.ADDITIONAL'),
        [
            'outload_additional',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.ADDITIONAL_ARCHIVES'),
            '',
            ['textarea'],
        ]
    ],

    'debug' => [
        Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TITLE.DEBUG_SETTINGS'),
        [
            'debug_mode',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.DEBUG_MODE'),
            'N',
            ['checkbox']
        ],
        [
            'debug_path',
            Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.DEBUG_COUNT'),
            '/log/backup_outload_log',
            ['text']
        ],
        //[
        //    'debug_limit',
        //    Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.OPTION.DEBUG_LIMIT'),
        //    '0',
        //    ['text']
        //],

    ],
];

$tabs = [
    [
        'DIV' => 'connection',
        'TAB' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.NAME.CONNECTION'),
        'TITLE' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.TITLE.CONNECTION'),
    ],
    [
        'DIV' => 'outload',
        'TAB' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.NAME.OUTLOAD'),
        'TITLE' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.TITLE.OUTLOAD'),
    ],
    [
        'DIV' => 'debug',
        'TAB' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.NAME.DEBUG'),
        'TITLE' => Loc::getMessage('INTERVOLGA.BACKUPOUTLOADER.TAB.TITLE.DEBUG'),
    ],
];

// ###############################################################
// #    SAVE NEW OPTION VALUES
// ###############################################################
if ($USER->IsAdmin()) {
    if (check_bitrix_sessid() && strlen($_POST['save'])>0) {
        foreach ($options as $option) {
            __AdmSettingsSaveOptions($module_id, $option);
        }
        LocalRedirect($APPLICATION->GetCurPageParam());
    }
}

// ###############################################################
// #    SHOW OPTIONS FORM
// ###############################################################
$logUntilTime = Option::get('intervolga.common', 'log_until_time');
$tabControl = new CAdminTabControl("tabControl", $tabs);
$tabControl->Begin();
?>
<form method='POST'>
    <?=bitrix_sessid_post()?>

    <?php $tabControl->BeginNextTab(); ?>
    <?php __AdmSettingsDrawList($module_id, $options['connection']); ?>

    <?php $tabControl->BeginNextTab(); ?>
    <?php __AdmSettingsDrawList($module_id, $options['outload']); ?>

    <?php $tabControl->BeginNextTab(); ?>
    <?php __AdmSettingsDrawList($module_id, $options['debug']); ?>

    <?php
    $tabControl->Buttons([
        'btnApply' => true,
        'btnCancel' => true,
        'btnSaveAndAdd' => false
    ]);
    ?>

    <?=bitrix_sessid_post();?>
    <?php $tabControl->End(); ?>
</form>
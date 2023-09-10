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
        Loc::getMessage('LANCY.BACKUPOUTLOADER.TITLE.CONNECTION_SETTINGS'),
        [
            'connection_host',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.HOSTNAME'),
            '',
            ['text']
        ],
        [
            'connection_port',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.PORT'),
            '21',
            ['text']
        ],
        [
            'connection_login',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.LOGIN'),
            '',
            ['text']
        ],
        [
            'connection_password',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.PASSWORD'),
            '',
            ['password']
        ],
        Loc::getMessage('LANCY.BACKUPOUTLOADER.TITLE.ADDITIONAL_SETTINGS'),
        [
            'connection_passive_mode',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.PASSIVE_MODE'),
            'N',
            ['checkbox']
        ]
    ],

    'outload' => [
        Loc::getMessage('LANCY.BACKUPOUTLOADER.TITLE.OUTLOAD_SETTINGS'),
        [
            'outload_count',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.BACKUP_OUTLOAD_COUNT'),
            '0',
            ['text']
        ],
        [
            'outload_path',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.BACKUP_OUTLOAD_PATH'),
            '',
            ['text']
        ],
        Loc::getMessage('LANCY.BACKUPOUTLOADER.TITLE.BACKUPS_SETTINGS'),
        [
            'outload_remove_current',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.REMOVE_CURRENT'),
            '',
            [
                'selectbox',
                [
                    'none' => Loc::getMessage('LANCY.BACKUPOUTLOADER.REMOVE_ARRAY.NOT'),
                    'yes' => Loc::getMessage('LANCY.BACKUPOUTLOADER.REMOVE_ARRAY.YES'),
                ]
            ]
        ],
        [
            'outload_remove_all',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.REMOVE_OTHER'),
            '',
            [
                'selectbox',
                [
                    'none' => Loc::getMessage('LANCY.BACKUPOUTLOADER.REMOVE_ARRAY.NOT'),
                    'after' => Loc::getMessage('LANCY.BACKUPOUTLOADER.REMOVE_ARRAY.AFTER'),
                    'before' => Loc::getMessage('LANCY.BACKUPOUTLOADER.REMOVE_ARRAY.BEFORE'),
                ]
            ]
        ],
    ],

    'debug' => [
        Loc::getMessage('LANCY.BACKUPOUTLOADER.TITLE.DEBUG_SETTINGS'),
        [
            'debug_mode',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.DEBUG_MODE'),
            'N',
            ['checkbox']
        ],
        [
            'debug_path',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.DEBUG_COUNT'),
            '/log/backup_outload_log',
            ['text']
        ],
        [
            'debug_limit',
            Loc::getMessage('LANCY.BACKUPOUTLOADER.OPTION.DEBUG_LIMIT'),
            '0',
            ['text']
        ],

    ],
];

$tabs = [
    [
        'DIV' => 'connection',
        'TAB' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.NAME.CONNECTION'),
        'TITLE' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.TITLE.CONNECTION'),
    ],
    [
        'DIV' => 'outload',
        'TAB' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.NAME.OUTLOAD'),
        'TITLE' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.TITLE.OUTLOAD'),
    ],
    [
        'DIV' => 'debug',
        'TAB' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.NAME.DEBUG'),
        'TITLE' => Loc::getMessage('LANCY.BACKUPOUTLOADER.TAB.TITLE.DEBUG'),
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
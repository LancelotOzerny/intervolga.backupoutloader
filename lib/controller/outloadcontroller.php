<?php
namespace Intervolga\BackupOutloader\Controller;

use \Intervolga\BackupOutloader\Backup\BackupController;
use \Intervolga\BackupOutloader\Connection\FtpConnection;
use COption;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/tar_gz.php';

if (\Bitrix\Main\Loader::includeModule('intervolga.backupoutloader') === false)
{
    die();
}

class OutloadController
{
    private array $options;
    private array $backupListBefore;
    private array $additionalDirs;
    private string $currentBackup;
    private FtpConnection | null $ftp = null;

    public function __construct()
    {
        $this->options = [
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
    }
    public function checkData() : bool
    {
        foreach (['connection_host', 'connection_port', 'connection_login', 'connection_password'] as $option)
        {
            if (isset($this->options[$option]) === false || $this->options[$option] === '')
            {
                return false;
            }
        }

        $this->ftp = new FtpConnection([
            'HOST' => $this->options['connection_host'],
            'PORT' => $this->options['connection_port'],
            'LOGIN' => $this->options['connection_login'],
            'PASSWORD' => $this->options['connection_password'],
            'PASSIVE' => $this->options['connection_passive_mode'],
        ]);

        return $this->ftp->checkTryConnection();
    }
    public function prepare() : bool
    {
        if ($this->ftp->connect() === false)
        {
            return false;
        }

        $this->folder = 'intervolga_backup_outload__' . date('Y-m-d');

        if ($this->options['outload_path'] !== '')
        {
            $this->folder = $this->options['outload_path'] . $this->folder;
        }

        if ($this->options['outload_remove_all'] === 'before')
        {
            BackupController::Instance()->deleteAll();
        }

        $this->backupListBefore = BackupController::Instance()->getList();

        $delete = [];
        $backupDirs = [];
        $currentItems = $this->ftp->getContent();
        foreach ($currentItems as $item)
        {
            if ($this->ftp->isDir($item))
            {
                $backupDirs[] = $item;
            }
        }

        if (isset($this->options['outload_count']) && intval($this->options['outload_count']) > 0)
        {
            $toDeleteCount = (1 + count($backupDirs) - intval($this->options['outload_count']));
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
            $this->ftp->delete($delFile);
        }

        if($this->ftp->exist($this->folder))
        {
            $this->ftp->delete($this->folder);
        }
        $this->ftp->createDir($this->folder);
        $this->ftp->go($this->folder);

        $this->ftp->close();

        return true;
    }
    public function create() : bool
    {
        if ( BackupController::Instance()->create() !== true)
        {
            return false;
        }
        $backupsListAfter = BackupController::Instance()->getList();
        $this->currentBackup = '';

        foreach ($backupsListAfter as $backup)
        {
            if (in_array($backup, $this->backupListBefore) === false)
            {
                $this->currentBackup = $backup;
                break;
            }
        }

        return true;
    }
    public function outload() : bool
    {
        if ($this->ftp->connect() === false)
        {
            return false;
        }

        $this->ftp->go($this->folder);

        $backupParts = BackupController::Instance()->getAllParts($this->currentBackup);
        foreach ($backupParts as $part)
        {
            $send = $this->ftp->send(BackupController::Instance()->path . '/' . $part, $part);

            if ($send === false)
            {
                if ($this->ftp->isConnected() === false)
                {
                    return false;
                }
                return false;
            }
        }

        $this->ftp->close();

        return true;
    }
    public function clean() : void
    {
        if ($this->options['outload_remove_all'] === 'after')
        {
            $backupsAfter = BackupController::Instance()->getList();
            foreach ($backupsAfter as $backup)
            {
                if (in_array($backup, $this->backupListBefore))
                {
                    BackupController::Instance()->delete($backup);
                    break;
                }
            }
        }
        else if ($this->options['outload_remove_current'] === 'yes')
        {
            BackupController::Instance()->delete($this->currentBackup);
        }
    }
    public function prepareAdditional() : void
    {
        if (trim($this->options['outload_additional']) === '')
        {
            $this->additionalDirs = [];
            return;
        }

        $additionalDirs = explode("\n", $this->options['outload_additional']);

        foreach ($additionalDirs as $key => $additionalDir)
        {
            $itemArr = explode(' ', $additionalDir);
            $multiple = in_array('multiple', $itemArr);
            $name = "intervolga_additional" . str_replace('/', '_', $itemArr[0]);

            $this->additionalDirs[$key] = [
                'name' => trim($name),
                'path' => $_SERVER['DOCUMENT_ROOT'] . trim($itemArr[0]),
                'multiple' => $multiple ? 'Y' : 'N',
                'exists' => is_dir($_SERVER['DOCUMENT_ROOT']  . trim($itemArr[0])) ? 'Y' : 'N',
            ];
        }
    }
    public function sendAllAdditional() : bool
    {
        foreach ($this->additionalDirs as $additional)
        {
            if ($additional['exists'] === 'N')
            {
                continue;
            }

            if ($additional['multiple'] === 'N')
            {
                $info = [
                    'name' => $additional['name'],
                    'path' => $additional['path'],
                ];

                print_r($info);
                //$this->sendAdditional($info);
            }

            if ($additional['multiple'] === 'Y')
            {
                $items = scandir($additional['path']);

                foreach ($items as $item)
                {
                    if ($item === '.' || $item === '..')
                    {
                        continue;
                    }

                    $info = [
                        'name' => $item,
                        'path' => $additional['path'] . '/' . $item,
                        'parent_container' => $additional['name'],
                    ];

                    $this->sendAdditional($info);
                }
            }
        }

        return true;
    }
    public function sendAdditional(array $additional)
    {
        $tarName =  $additional['name'] . '.tar.gz';
        $tarDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/' . $tarName;

        $archive = new \CArchiver($tarDir, true);
        $archive->Add('"' . $additional['path'] . '"', $additional['name'], $additional['path']);

        $arErrors = $archive->GetErrors();
        if(count($arErrors) > 0)
        {
            return false;
        }

        $this->ftp->connect();

        if (isset($additional['parent_container']))
        {
            $this->ftp->createDir($this->folder . "/" . $additional["parent_container"] . "/");
            $this->ftp->go($this->folder . "/" . $additional["parent_container"] . "/");
        }

        $this->ftp->send($tarDir, $this->folder . "/" . $additional["parent_container"] . "/" . $tarName);
        $this->ftp->close();

        unlink( $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup/' . $tarName);
    }
}
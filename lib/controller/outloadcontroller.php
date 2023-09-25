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
    private string $currentBackup;
    private $ftp = null;

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
}
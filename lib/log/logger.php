<?php

namespace Intervolga\BackupOutloader\Log;

class Logger
{
    private bool $debugMode;
    private string $debugPath;
    private string $title;
    private int $debugLimit;

    public function __construct()
    {
        $this->debugMode =  \COption::GetOptionString('intervolga.backupoutloader', 'debug_mode');
        $this->debugLimit = intval(\COption::GetOptionString('intervolga.backupoutloader', 'debug_limit'));
        $this->title = '__intervolga_backup_outload_' . date('Y-m-d___H:i:s') . '__.csv';

        $this->setLogFolder();
        $this->Log("Data;Message;");
    }

    private function setLogFolder()
    {
        $startPath = \COption::GetOptionString('intervolga.backupoutloader', 'debug_path');
        $startPath = trim($startPath, '/');
        $path = $_SERVER['DOCUMENT_ROOT'];

        if (is_dir($path . '/' . $startPath))
        {
            $this->debugPath = $path . '/' . $startPath;
            return;
        }

        $arr = explode('/', $startPath);

        foreach ($arr as $element)
        {
            $path .= '/' . $element;

            if (is_dir($path) === false)
            {
                mkdir($path);
            }
        }

        $this->debugPath = $path;
    }

    public function Log(string $message)
    {
        if ($this->debugMode === false)
        {
            return;
        }

        $temp = fopen($this->debugPath . '/' . $this->title, 'a+');
        fwrite($temp,  '"' . date("H:i:s") . '";' . '"' . $message . '";' . PHP_EOL);
        fclose($temp);
    }
}
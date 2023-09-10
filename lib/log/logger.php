<?php

namespace Lancy\BackupOutloader\Log;

class Logger
{
    private bool $debugMode;
    private string $debugPath;
    private int $debugLimit;

    public function __construct()
    {
        $this->debugMode =  \COption::GetOptionString('lancy.backupoutloader', 'debug_mode');
        $this->debugLimit = intval(\COption::GetOptionString('lancy.backupoutloader', 'debug_limit'));

        $this->setLogFolder();
    }

    private function setLogFolder()
    {
        $startPath = \COption::GetOptionString('lancy.backupoutloader', 'debug_path');
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
        $title = '__backup_outload_' . date('Y-m-d') . '__.log';

        if ($this->debugMode)
        {
            echo $this->debugPath . PHP_EOL . PHP_EOL . PHP_EOL;
            $temp = fopen($this->debugPath . '/' . $title, 'a+');
            fwrite($temp, date('H:i:s') . PHP_EOL . $message . PHP_EOL . PHP_EOL);
            fclose($temp);
        }
    }
}
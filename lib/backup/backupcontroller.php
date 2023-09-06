<?php
namespace Lancy\BackupOutloader\Backup;

class BackupController
{
    private static self | null $instance = null;

    private function __construct()
    {
        $this->path = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/backup';
    }
    private function __clone() {}

    public function create() : bool | string
    {
         $command = 'php -f ' . $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/tools/backup.php';

         try {
           exec($command);
        }
        catch (Exception $e)
        {
            return $e->getMessage();
        }
        return true;
    }

    public function delete(string $name) : bool
    {
        if ($this->checkExist($name) === false) return false;

        $items = scandir($this->path);
        foreach ($items as $item)
        {
            if (is_dir("$this->path/$item"))
            {
                continue;
            }

            if (str_contains($item, $name))
            {
                unlink("$this->path/$item");
            }
        }

        return false;
    }

    public function checkExist(string $name) : bool
    {
        return in_array($name, $this->getList());
    }

    public function getList() : array
    {
        $result = [];
        $items = scandir($this->path);

        foreach ($items as $item)
        {
            if ($this->isAdditional($item) || is_dir("$this->path/$item"))
            {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }


    private function isAdditional($name) : bool
    {
        $index = $name === 'index.php';
        $part = ctype_digit(pathinfo($name, PATHINFO_EXTENSION));
        return ($index || $part);
    }

    public static function Instance() : null | self
    {
        if (self::$instance instanceof self === false)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
?>
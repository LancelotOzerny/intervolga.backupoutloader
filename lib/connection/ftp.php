<?php
namespace Lancy\BackupOutloader\Connection;

class Ftp
{
    private static self | null $instance = null;

    private function __construct() {}
    private function __clone() {}

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
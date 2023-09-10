<?php
namespace Lancy\BackupOutloader\Connection;

class FtpConnection
{
    private $connection = null;

    public function __construct($host, $port = 21)
    {
        $this->connection = ftp_connect($host, $port);
    }

    public function isDir($name)
    {
        $current = $this->getDir();
        if ($this->go($name) && $this->go($current))
        {
            return true;
        }

        return false;
    }

    public function login(string $login, string $password, bool $passive = true)
    {
        if ($this->connection === null)
        {
            return false;
        }
        $login = ftp_login($this->connection, $login, $password);
        $passive = ftp_pasv($this->connection, $passive);
        return $login && $passive;
    }

    public function getContent($remote_path = '.')
    {
        if ($this->connection === null)
        {
            return false;
        }

        return ftp_nlist($this->connection, $remote_path);
    }

    public function delete($name)
    {
        if ($this->isDir($name))
        {
            return $this->deleteFolder($name);
        }

        return $this->deleteFile($name);
    }

    private function deleteFile($file)
    {
        if ($this->connection == null)
        {
            return false;
        }

        ftp_delete($this->connection, $file);
    }

    private function deleteFolder($folder)
    {
        if ($this->connection == null)
        {
            return false;
        }

        $startPath = $this->getDir();
        $this->go($folder);
        $files = $this->getContent();

        foreach ($files as $file)
        {
            $this->delete($file);
        }

        $this->go($startPath);
        return ftp_rmdir($this->connection, $folder);
    }


    public function getDir() : bool | string
    {
        if ($this->connection === null)
        {
            return false;
        }

        return ftp_pwd($this->connection);
    }

    public function go($remote_path)
    {
        if ($this->connection === null)
        {
            return false;
        }
        return ftp_chdir($this->connection, $remote_path);
    }

    public function exist($name)
    {
        if ($this->connection == null)
        {
            return false;
        }

        $files = $this->getContent();

        foreach ($files as $file)
        {
            if ($file == $name)
            {
                return true;
            }
        }

        return false;
    }

    public function createDir($path)
    {
        if ($this->connection === null)
        {
            return false;
        }

        $start_path = $this->getDir();
        $folders = explode('/', $path);
        foreach ($folders as $folder)
        {
            if ($folder === '') continue;

            if ($this->exist($folder) === false)
            {
                ftp_mkdir($this->connection, $folder);
            }

            $this->go($folder);
        }

        $this->go($start_path);

        return true;
    }

    public function __destruct()
    {
        if ($this->connection === null)
        {
            return false;
        }

        ftp_close($this->connection);
    }

    public function send(string $localfile, string $remotefile)
    {
        if ($this->connection === null)
        {
            return false;
        }

        $retry = ftp_nb_put($this->connection, $remotefile, $localfile);

        while ($retry == FTP_MOREDATA)
        {
            $retry = ftp_nb_continue($this->connection);
        }

        if ($retry != FTP_FINISHED)
        {
            return false;
        }

        return $retry;
    }

}
?>
<?php
namespace Intervolga\BackupOutloader\Connection;

class FtpConnection
{
    private $connection;
    private string $hostname;
    private int $port;
    private string $login;
    private string $password;
    private bool $passive;

    public function isDir($name)
    {
        $current = $this->getDir();
        if ($this->go($name) && $this->go($current))
        {
            return true;
        }

        return false;
    }

    public function setParams(array $params = [])
    {
        $this->hostname =   isset($params['HOST'])      ?   $params['HOST'] : '';
        $this->port     =   isset($params['PORT'])      ?   intval($params['PORT']) : '';
        $this->login    =   isset($params['LOGIN'])     ?   $params['LOGIN'] : '';
        $this->password =   isset($params['PASSWORD'])  ?   $params['PASSWORD'] : '';
        $this->passive  =   isset($params['PASSIVE'])   ?   $params['PASSIVE'] === 'Y' : false;
    }

    public function connect()
    {
        $this->connection = ftp_connect($this->hostname, $this->port);
        return $this->connection !== false && $this->login();
    }

    private function login()
    {
        if ($this->connection === null)
        {
            return false;
        }

        $login = ftp_login($this->connection, $this->login, $this->password);
        $passive = ftp_pasv($this->connection, $this->passive);

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

    public function close()
    {
        if ($this->connection == null)
        {
            return true;
        }

        return ftp_close($this->connection);
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

    public function send(string $localFile, string $remoteFile) : bool
    {
        if ($this->connection === null)
        {
            return false;
        }

        $result = ftp_put($this->connection, $remoteFile, $localFile);

        return $result;
    }

    public function isConnected() : bool
    {
        return $this->connection === null;
    }
}
?>
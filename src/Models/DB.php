<?php

namespace App\Models;

use \PDO;

class DB
{
    private $host = '10.0.30.147';
    private $user = 'postgres';
    private $pass = 'Ar!$t0teles.2k24*.*';
    private $dbname = 'siservi_catering_local';

    public function connect()
    {
        $conn_str = "pgsql:host=$this->host;dbname=$this->dbname";
        $conn = new PDO($conn_str, $this->user, $this->pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $conn;
    }
}
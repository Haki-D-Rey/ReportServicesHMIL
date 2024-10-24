<?php

namespace App\Models;

use \PDO;

class DB
{

    // private $host = '10.0.30.147';
    // private $user = 'postgres';
    // private $pass = 'Ar!$t0teles.2k24*.*';
    // private $dbname = 'siservi_catering_local';

    // private $host = 'localhost';
    // private $user = 'postgres';
    // private $pass = '&ecurity23';
    // private $dbname = 'siservi_catering_local';


    public $connections = [];

    public function __construct($databases)
    {
        foreach ($databases as $name => $config) {
            $this->connections[$name] = $this->connect(
                $config['driver'],
                $config['host'],
                $config['port'] ?? "",
                $config['user'],
                $config['pass'],
                $config['dbname']
            );
        }
    }

    private function connect($driver, $host, $port, $user, $pass, $dbname)
    {
        $opciones = array();
        if ($driver === 'pgsql') {
            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
        } elseif ($driver === 'mysql') {
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
        } elseif ($driver === 'sqlsrv') {
            // Para SQL Server con sqlsrv
            $dsn = "sqlsrv:Server=$host,$port;Database=$dbname";
        } elseif ($driver === 'dblib') {
            // Para SQL Server con dblib
            $dsn = "dblib:host=$host:$port;dbname=$dbname;charset=UTF-8;";
        } else {
            throw new \InvalidArgumentException("Driver '$driver' no es soportado.");
        }

        $conn = new PDO($dsn, $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }

    public function getConnection($name)
    {
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        } else {
            throw new \Exception("La conexión '$name' no existe.");
        }
    }
}

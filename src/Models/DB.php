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
                $config['user'],
                $config['pass'],
                $config['dbname']
            );
        }
    }

    private function connect($driver, $host, $user, $pass, $dbname)
    {
        try {
            // Determinar el DSN basado en el driver
            switch ($driver) {
                case 'pgsql':
                    $dsn = "pgsql:host=$host;dbname=$dbname";
                    break;
                case 'mysql':
                    $dsn = "mysql:host=$host;dbname=$dbname";
                    break;
                case 'sqlsrv':
                    $dsn = "dblib:host=$host;dbname=$dbname;charset=UTF-8;";
                    break;
                default:
                    throw new \InvalidArgumentException("Driver '$driver' no es soportado.");
            }

            // Intentar crear la conexión
            $conn = new PDO($dsn, $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            return $conn;
        } catch (\PDOException $e) {
            // Manejo específico de errores de PDO
            throw new \RuntimeException("Error al conectar a la base de datos: " . $e->getMessage(), (int)$e->getCode(), $e);
        } catch (\InvalidArgumentException $e) {
            // Manejo de errores para argumentos inválidos
            throw $e; // Re-throw the exception to be handled elsewhere
        } catch (\Exception $e) {
            // Manejo de errores generales
            throw new \RuntimeException("Ocurrió un error inesperado: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
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

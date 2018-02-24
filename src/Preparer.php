<?php

namespace Mrcrmn\Mysql;

use \PDO;
use Mrcrmn\Mysql\Connectors\DatabaseConnection;

/**
* The Proxy class to the PDO instance.
*
* @package mrcrmn/mysql
* @author Marco Reimann <marcoreimann@outlook.de>
*/
class Preparer
{
    /**
     * The PDO Database Connection.
     *
     * @var \Mrcrmn\Mysql\Connectors\DatabaseConnection
     */
    public $connection;

    /**
     * Connects to the Database.
     *
     * @param  string  $host
     * @param  string  $username
     * @param  string  $password
     * @param  int $port
     * @param  string  $database
     *
     * @return bool
     */
    public function __construct($host = "127.0.0.1", $username = "root", $password = "", $port = 3306, $database = "")
    {
        $this->connection = new DatabaseConnection($host, $username, $password, $port, $database);
    }

    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * Proxy for the Pdo.
     *
     * @param  string $method
     * @param  mixed $args
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return $this->connection->$method(...$args);
    }

}

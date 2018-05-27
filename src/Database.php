<?php

namespace Infinity;

use PDO;

class Database
{
    /** @var PDO  */
    private $connection;

    public function __construct()
    {
        $this->connection = new PDO("mysql:dbname=infinity;host=localhost", "infinity", "infinity");
    }

    public function createTableIfNeeded()
    {
        $sql = file_get_contents('schema/event.sql');
        $this->connection->exec($sql);
    }
}
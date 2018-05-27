<?php

namespace Infinity;

use Exception;
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

    /**
     * Insert the given data in a single multi-insert statement, using bind variables.
     *
     * TODO: For larger data sets, either support for streaming single row inserts or batching of multi-row inserts.
     * TODO: Confirm that every row has the right number of columns.
     *
     * @param string $table
     * @param array $parsedData
     * @throws Exception
     */
    public function insertData(string $table, array $parsedData)
    {
        $headers = $parsedData['headers'];
        $rows = $parsedData['data'];

        // Build the bind variable placeholders:
        // Single row of data. eg. "(?,?,?,?,?)"
        $singleRowPlaceholder = "(" . implode(",", array_fill(0, count($headers), "?")) . ")";
        // All rows. eg. "(?,?,?,?,?),(?,?,?,?,?),(?,?,?,?,?),..."
        $allPlaceHolders = implode(',', array_fill(0, count($rows), $singleRowPlaceholder));

        $sql = sprintf(
            "insert into %s (%s) values %s",
            $table,
            implode(",", $headers ),
            $allPlaceHolders
        );

        $statement = $this->connection->prepare($sql);
        $statement->execute(array_merge(... $rows));
    }
}

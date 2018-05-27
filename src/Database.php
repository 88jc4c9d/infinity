<?php

namespace Infinity;

use Exception;
use PDO;

class Database
{
    /** @var PDO  */
    private $connection;

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
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
     *
     * @param array $parsedData
     */
    public function insertData(array $parsedData)
    {
        $headers = $parsedData['headers'];
        $rows = $parsedData['data'];

        // Build the bind variable placeholders:
        // Single row of data. eg. "(?,?,?,?,?)"
        $singleRowPlaceholder = "(" . implode(",", array_fill(0, count($headers), "?")) . ")";
        // All rows. eg. "(?,?,?,?,?),(?,?,?,?,?),(?,?,?,?,?),..."
        $allPlaceHolders = implode(',', array_fill(0, count($rows), $singleRowPlaceholder));

        $sql = sprintf(
            "insert into event (%s) values %s",
            implode(",", $headers ),
            $allPlaceHolders
        );

        $statement = $this->connection->prepare($sql);
        try {
            $statement->execute(array_merge(... $rows));
        } catch(Exception $e) {
            $this->logger->log("Error inserting data: {$e->getMessage()}");
            // Should the file be marked as processed, or left to be tried again?
        }
    }
}

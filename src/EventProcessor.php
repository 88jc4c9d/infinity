<?php

namespace Infinity;

use Exception;

class EventProcessor
{
    /** @var string */
    private $inputDirectory;

    /** @var string */
    private $outputDirectory;

    /** @var Logger */
    private $logger;

    /** @var CsvParser */
    private $csvParser;

    /** @var Database */
    private $database;

    public function __construct(string $inputDirectory, string $outputDirectory)
    {
        $this->inputDirectory = $inputDirectory;
        $this->outputDirectory = $outputDirectory;
        $this->logger = new Logger();
        $this->csvParser = new CsvParser($this->logger);
        $this->database = new Database();
    }

    public function run()
    {
        // Use a lock to prevent multiple instances from running
        $lock = fopen($this->inputDirectory, "rw");
        if(!flock($lock, LOCK_EX | LOCK_NB)) {
            exit(-1);
        }

        $files = glob($this->inputDirectory . "/*.csv");
        if (! $files) {
            $this->logger->log("No files to process.");
            exit(1);
        }

        if(!is_dir($this->outputDirectory)) {
            mkdir($this->outputDirectory);
        }

        $this->database->createTableIfNeeded();

        foreach($files as $file) {
            try {
                $this->processFile($file);
            } catch (EventProcessException $e) {
                $this->logger->log("Skipping file '$file': {$e->getMessage()}");
                continue;
            }

            // Move the file to the processed directory
            $processedFile = str_replace($this->inputDirectory, $this->outputDirectory, $file);
            if(!rename($file, $processedFile)) {
                $this->logger->log("Unable to move $file to $processedFile");
            }
        }

        fclose($lock);
    }

    /**
     * @param string $file
     * @throws EventProcessException
     */
    private function processFile(string $file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        try {
            $parsedData = $this->csvParser->parseData($lines);
        } catch (CsvParseException $e) {
            throw new EventProcessException("Invalid CSV file: {$e->getMessage()}", null, $e);
        }

        try {
            $this->database->insertData("event", $parsedData);
        } catch (Exception $e) {
            throw new EventProcessException("Error inserting data: {$e->getMessage()}", null, $e);
        }

        $this->logger->log(sprintf(
            "Processed file %s, total lines %d, skipped lines %d",
            $file,
            $parsedData['numProcessedLines'],
            $parsedData['numSkippedLines']
        ));
    }
}

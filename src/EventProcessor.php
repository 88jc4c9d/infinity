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

    /**
     * Main entry point for this class.
     */
    public function run()
    {
        // Use a lock to prevent multiple instances from running
        $lock = fopen($this->inputDirectory, "rw");
        if(!flock($lock, LOCK_EX | LOCK_NB)) {
            exit(-1);
        }

        // Grab the list of uploaded files to work on
        $files = glob($this->inputDirectory . "/*.csv");
        if (! $files) {
            $this->logger->log("No files to process.");
            exit(1);
        }

        $this->init();

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
     * Parse a CSV file and insert its contents into
     * @param string $file
     * @throws EventProcessException
     */
    private function processFile(string $file)
    {
        $parsedData = $this->parseFile($file);

        $this->storeParsedData($parsedData);

        $this->logger->log(sprintf(
            "Processed file %s, total lines %d, skipped lines %d",
            $file,
            $parsedData['numProcessedLines'],
            $parsedData['numSkippedLines']
        ));
    }

    /**
     * Parse a CSV file and extract off valid data.
     * @param string $file
     * @return array
     * @throws EventProcessException
     */
    private function parseFile(string $file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        try {
            $parsedData = $this->csvParser->parseData($lines);
        } catch (CsvParseException $e) {
            throw new EventProcessException("Invalid CSV file: {$e->getMessage()}", null, $e);
        }

        return $parsedData;
    }

    /**
     * @param array $parsedData
     * @throws EventProcessException
     */
    private function storeParsedData(array $parsedData)
    {
        try {
            $this->database->insertData("event", $parsedData);
        } catch (Exception $e) {
            throw new EventProcessException("Error inserting data: {$e->getMessage()}", null, $e);
        }
    }

    /**
     * Various bits of setup.
     * Called all the time but in practise, this should be one time setup.
     */
    private function init()
    {
        if(!is_dir($this->outputDirectory)) {
            mkdir($this->outputDirectory);
        }

        $this->database->createTableIfNeeded();
    }
}

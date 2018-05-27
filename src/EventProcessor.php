<?php

namespace Infinity;

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
        $this->database = new Database($this->logger);
    }

    public function run()
    {
        $files = glob($this->inputDirectory . "/*.csv");
        if (! $files) {
            $this->logger->log("No files to process.");
            exit(1);
        }

        $this->database->createTableIfNeeded();

        foreach($files as $file) {
           $this->processFile($file);
        }
    }

    private function processFile(string $file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        try {
            $parsedData = $this->csvParser->parseData($lines);
        } catch (CsvParseException $e) {
            $this->logger->log("Skipping invalid CSV file $file: {$e->getMessage()}");
            return;
        }

        $this->database->insertData($parsedData);

        $this->logger->log(sprintf(
            "Processed file %s, total lines %d, skipped lines %d",
            $file,
            $parsedData['numProcessedLines'],
            $parsedData['numSkippedLines']
        ));
    }
}

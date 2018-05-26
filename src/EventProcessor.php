<?php

namespace Infinity;

class EventProcessor
{
    /**
     * @var string
     */
    private $inputDirectory;

    /**
     * @var string
     */
    private $outputDirectory;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(string $inputDirectory, string $outputDirectory)
    {
        $this->inputDirectory = $inputDirectory;
        $this->outputDirectory = $outputDirectory;
        $this->logger = new Logger();
    }

    public function run()
    {
        $files = glob($this->inputDirectory . "/*.csv");
        if (! $files) {
            $this->logger->log("No files to process.");
            exit(1);
        }

        foreach($files as $file) {
           $this->processFile($file);
        }
    }

    private function processFile(string $file)
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach($lines as $line) {
            $this->logger->log($line);
        }

        // TODO: Parse CSV

        // TODO: Insert into database
    }
}
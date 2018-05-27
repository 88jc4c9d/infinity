<?php

namespace Infinity;

use DateTime;

class CsvParser
{
    const EXPECTED_HEADERS = [
        'callRef',
        'eventAction',
        'eventCurrencyCode',
        'eventDatetime',
        'eventValue'
    ];

    const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var Logger */
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $lines
     * @return array [
     *      'headers' => ['x', 'y', ...],
     *      'data' => [
     *          [ ... ],
     *          ...
     *      ]
     *  ]
     * @throws CsvParseException
     */
    public function parseData(array $lines)
    {
        if (count($lines) <= 1) {
            throw new CsvParseException("Not enough lines in file");
        }

        $parsedData = [];

        $headers = $this->parseHeaderRow($lines[0]);
        $parsedData['headers'] = $headers;

        array_shift($lines);

        $numSkippedLines = 0;
        foreach ($lines as $dataRow) {
            try {
                $parsedData['data'][] = $this->parseDataRow($headers, $dataRow);
            } catch (CsvParseException $e) {
                $numSkippedLines++;
                $this->logger->log(sprintf(
                    "Skipping invalid row. Reason: %s Row: %s",
                    $e->getMessage(),
                    $dataRow
                ));
            }
        }

        if(count($lines) === $numSkippedLines) {
            throw new CsvParseException("No valid lines in file");
        }

        $parsedData['numProcessedLines'] = count($lines);
        $parsedData['numSkippedLines'] = $numSkippedLines;

        return $parsedData;
    }

    /**
     * @param string $headerRow
     * @return array
     * @throws CsvParseException
     */
    public function parseHeaderRow(string $headerRow)
    {
        $headers = array_map('trim', explode(",", $headerRow));

        if (count($headers) !== count(self::EXPECTED_HEADERS)) {
            throw new CsvParseException("Not enough column headers: " . $headerRow);
        }

        $sortedHeaders = $headers;
        sort($sortedHeaders);
        if($sortedHeaders !== self::EXPECTED_HEADERS) {
            throw new CsvParseException("Incorrect column headers: " . $headerRow);
        }

        return $headers;
    }

    /**
     * @param array $headers
     * @param string $dataRow
     * @return array
     * @throws CsvParseException
     */
    public function parseDataRow(array $headers, string $dataRow)
    {
        $values = array_map('trim', explode(",", $dataRow));

        if (count($values) !== count($headers)) {
            throw new CsvParseException("Incorrect number of values.");
        }

        $data = array_combine($headers, $values);

        // Validate the event time field
        $date = DateTime::createFromFormat(self::DATE_FORMAT, $data['eventDatetime']);
        if (!$date || $date->format(self::DATE_FORMAT) !== $data['eventDatetime']) {
            throw new CsvParseException("Invalid date format.");
        }

        // Validate the event action field
        if (!$data['eventAction']) {
            throw new CsvParseException("Missing event action.");
        }

        // Validate the call ref field
        if (!$data['callRef'] || !is_integer(filter_var($data['callRef'], FILTER_VALIDATE_INT))) {
            throw new CsvParseException("Missing or invalid call ref.");
        }

        // Validate the value field if it is present
        if ($data['eventValue']) {
            if (!is_float(filter_var($data['eventValue'], FILTER_VALIDATE_FLOAT))){
                throw new CsvParseException("Invalid value.");
            }

            // If value is there, then currency must also be there
            if (!$data['eventCurrencyCode']) {
                throw new CsvParseException("Missing currency code.");
            }
        }

        // Validate the currency code if it is there
        if ($data['eventCurrencyCode']) {
            if(strlen($data['eventCurrencyCode']) !== 3) {
                throw new CsvParseException("Invalid currency code.");
            }
        }

        return $values;
    }
}
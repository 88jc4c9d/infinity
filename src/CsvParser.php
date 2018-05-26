<?php

namespace Infinity;

class CsvParser
{
    const EXPECTED_HEADERS = [
        'callRef',
        'eventAction',
        'eventCurrencyCode',
        'eventDatetime',
        'eventValue'
    ];
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

        $parsedData['headers'] = $this->parseHeaderRow($lines[0]);

        array_shift($lines);

        $parsedData['data'] = $this->parseDataRows($lines);

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

    private function parseDataRows(array $dataRows)
    {
        // TODO
        return [[]];
    }
}
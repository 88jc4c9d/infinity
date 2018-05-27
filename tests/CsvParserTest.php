<?php

use Infinity\CsvParser;
use Infinity\Logger;
use PHPUnit\Framework\TestCase;

final class CsvParserTest extends TestCase
{
    /** @var CsvParser */
    private $csvParser;

    public function setUp()
    {
        parent::setUp();
        $this->csvParser = new CsvParser(new Logger);
    }

    /**
     * @param string $rawHeaderLine
     * @param array $expectedHeaders
     * @dataProvider goodHeadersData
     * @throws \Infinity\CsvParseException
     */
    public function testGoodHeaders(string $rawHeaderLine, array $expectedHeaders)
    {
        $parsedHeaders = $this->csvParser->parseHeaderRow($rawHeaderLine);

        $this->assertEquals($expectedHeaders, $parsedHeaders);
    }

    /**
     * @return array
     */
    public function goodHeadersData()
    {
        return [
            // [ raw header line, expected parsed headers ]

            // Correct headers in suggested order
            [
                "eventDatetime,eventAction,callRef,eventValue,eventCurrencyCode",
                ["eventDatetime","eventAction","callRef","eventValue","eventCurrencyCode"]
            ],
            // Correct headers in a different order
            [
                "callRef,eventDatetime,eventCurrencyCode,eventAction,eventValue",
                ["callRef","eventDatetime","eventCurrencyCode","eventAction","eventValue"]
            ],
            // Some whitespace between headers
            [
                "eventDatetime, eventAction,  callRef,  eventValue  ,eventCurrencyCode",
                ["eventDatetime","eventAction","callRef","eventValue","eventCurrencyCode"]
            ],
        ];
    }

    /**
     * @param string $rawHeaderLine
     * @dataProvider badHeadersData
     * @expectedException \Infinity\CsvParseException
     * @throws \Infinity\CsvParseException
     */
    public function testBadHeaders(string $rawHeaderLine)
    {
        $this->csvParser->parseHeaderRow($rawHeaderLine);
    }

    /**
     * @return array
     */
    public function badHeadersData()
    {
        return [
            // Not enough headers
            ["eventDatetime,eventAction,callRef,eventValue"],
            // Duplicate headers
            ["eventDatetime,eventAction,callRef,callRef,eventValue,eventCurrencyCode"],
            // Unknown headers
            ["eventDatetime,eventAction,callRef,eventValue,FOO"]
        ];
    }

    /**
     * @param string $rowData
     * @param array $expected
     * @dataProvider parseGoodRowData
     */
    public function testParseGoodRow(string $rowData, array $expected)
    {
        $headers = ["eventDatetime","eventAction","callRef","eventValue","eventCurrencyCode"];

        $parsedRow = $this->csvParser->parseDataRow($headers, $rowData);

        $this->assertEquals($expected, $parsedRow);
    }

    /**
     * @return array
     */
    public function parseGoodRowData()
    {
        return [
            // [ row data, parsed data ]

            // All columns populated
            [
                "2018-01-15 10:14:56,foo,1,1.00,GBP",
                ["2018-01-15 10:14:56","foo",1,1.00,"GBP"]
            ],

            // Value and currency omitted
            [
                "2018-01-15 10:14:56,foo,1,,",
                ["2018-01-15 10:14:56","foo",1,null,null]
            ],

            // Value omitted but currency included
            [
                "2018-01-15 10:14:56,foo,1,,GBP",
                ["2018-01-15 10:14:56","foo",1,null,"GBP"]
            ],
        ];
    }

    /**
     * @param string $dataRow
     * @dataProvider parseBadRowData
     * @expectedException \Infinity\CsvParseException
     */
    public function testParseBadRow(string $dataRow)
    {
        $headers = ["eventDatetime","eventAction","callRef","eventValue","eventCurrencyCode"];

        $this->csvParser->parseDataRow($headers, $dataRow);
    }

    /**
     * @return array
     */
    public function parseBadRowData()
    {
        return [
            // Not enough columns
            ["2018-01-15 10:14:56,foo,1,1.00"],
            // Too many columns
            ["2018-01-15 10:14:56,foo,1,1.00,GBP,BAR"],
            // Invalid date format
            ["2018/01/15 10:14:56,foo,1,1.00,GBP"],
            // Missing required field
            ["2018-01-15 10:14:56,,1,1.00,GBP"],
            // Invalid integer value
            ["2018-01-15 10:14:56,foo,bar,1.00,GBP"],
            // Invalid decimal value
            ["2018-01-15 10:14:56,foo,1,bar,GBP"],
            // Invalid ISO country code field
            ["2018-01-15 10:14:56,foo,1,1.00,X"],
        ];
    }
}

<?php

use Infinity\CsvParser;
use PHPUnit\Framework\TestCase;

final class CsvParserTest extends TestCase
{
    /** @var CsvParser */
    private $csvParser;

    public function setUp()
    {
        parent::setUp();
        $this->csvParser = new CsvParser;
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
}
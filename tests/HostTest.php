<?php

namespace GlpiPlugin\Centreon\tests;

use PHPUnit\Framework\TestCase;
use GlpiPlugin\Centreon\Host;

class HostTest extends TestCase
{
    public function testGetComputerList()
    {
        $host = new Host();
        $computerList = $host->getComputerList();
        $this->assertIsArray($computerList);
        $this->assertNotEmpty($computerList);
    }

    public function testOneHost()
    {
        $id = 88;
        $host = new Host();
        $newHost = $host->oneHost($id);
        $this->assertIsArray($newHost);
        $this->assertNotEmpty($newHost);
    }

    public function testDiffDateInSeconds()
    {
        $host = new Host();

        $startDate = '2023-05-24 15:30:00';
        $endDate = '2023-05-24 15:31:00';
        $expectedResult = 60;
        $result = $host->diffDateInSeconds($startDate, $endDate);
        $this->assertEquals($expectedResult, $result);
    }

    public function testConvertToSeconds()
    {
        $optionMinute = 2;
        $optionHour = 3;
        $duration = 1;
        $expectedForMinute = 60;
        $expectedForHour = 3600;

        $host = new Host();
        $resultForMinute = $host->convertToSeconds($optionMinute, $duration);
        $this->assertEquals($expectedForMinute, $resultForMinute);

        $resultForHour = $host->convertToSeconds($optionHour, $duration);
        $this->assertEquals($expectedForHour, $resultForHour);
    }
}

<?php

/**
 * -------------------------------------------------------------------------
 * Centreon plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Centreon.
 *
 * Centreon is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * Centreon is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Centreon. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @copyright Copyright (C) 2022-2023 by Centreon plugin team.
 * @license   GPLv3 https://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://github.com/pluginsGLPI/centreon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Centreon\tests;

use GlpiPlugin\Centreon\ApiClient;
use PHPUnit\Framework\TestCase;
use GlpiPlugin\Centreon\Host;
use Computer;

class HostTest extends TestCase
{
    public function testGetComputerList()
    {
        $computer = new Computer();
        $computer->add([
            'entities_id'   => 0,
            'name'  => "computer1",
        ]);

        $host = new Host();
        $computerList = $host->getComputerList();
        $this->assertIsArray($computerList);
        $this->assertNotEmpty($computerList);
    }

    public function testOneHost()
    {
        $id = 88;

        $api = $this->createStub(ApiClient::class);
        $api
            ->method('connectionRequest')
            ->willReturn([
                'security' => [
                    'token' => 'mocked token',
                ],
                'contact' => [
                    'id' => 123,
                    'alias' => 'mocked alias'
                ]
            ]);
        $api
            ->method('getOneHost')
            ->with($id)
            ->willReturn([
                'alias'         => 'mocked alias',
                'last_check'    => 'mocked last check',
                'next_check'    => 'mocked next check',
                'check_period'  => 'mocked check period'
            ]);
        $api
            ->method('getOneHostResources')
            ->with($id)
            ->willReturn([
                'status' => [
                    'name'     => 'mocked status name',
                ],
                'name'   => 'mocked name',
                'fqdn'   => 'mocked fqdn',
                'in_downtime'   => true,
                'downtimes'      => []
            ]);
        $api
            ->method('getServicesListForOneHost')
            ->with($id)
            ->willReturn([
                'result' => []
            ]);
        $api
            ->method('listDowntimes')
            ->with($id)
            ->willReturn([]);

        // $host_mock = $this->getMockBuilder(Host::class)
        //     ->setConstructorArgs([$api])
        //     ->getMock();

        $new_host = new Host($api);
        $result = $new_host->oneHost($id);

        $this->assertIsArray($result);
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

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
 * the Free Software Foundation; either version 2 of the License, or
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
 * @copyright Copyright (C) 2013-2022 by Centreon plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/centreon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Centreon\tests;

use PHPUnit\Framework\TestCase;
use GlpiPlugin\Centreon\ApiClient;
use GuzzleHttp\Client;

use function PHPUnit\Framework\equalTo;

class ApiClientTest extends TestCase
{
    public function testConnectionRequest()
    {
        $apiClientMock = $this->getMockBuilder(ApiClient::class)->getMock();
        $apiClientMock
            ->expects($this->once())
            ->method('clientRequest')
            ->will($this->returnValue(true));

        $apiClientMock
            ->expects($this->once())
            ->method('connectionRequest')
            ->with([
                $this->equalTo('login'),
                $this->equalTo([
                    'json' => [
                        'security' => [
                            'credentials' => [
                                'login' => 'mock_username',
                                'password' => 'mock_password',
                            ]
                        ]
                    ]
                ]),
                $this->equalTo('POST')
            ])
            ->willReturn([
                'security' => [
                    'token' => 'mock_token'
                ],
                'contact' => [
                    'id' => 123
                ]
            ]);

        $apiClient = new ApiClient($apiClientMock);

        $result = $apiClient->connectionRequest();

        $this->assertEquals([
            'security' => [
                'token' => 'mock_token'
            ],
            'contact' => [
                'id' => 123
            ]
        ], $result);

        // $guzzleMock    = $this->getMockBuilder(Client::class)
        //                     ->setConstructorArgs(array('base_uri' =>  "path/to/api", 'verify' => false))
        //                     ->getMock();


        // $apiClientMock
        //     ->expects($this->once())
        //     ->method('connectionRequest')
        //     ->with(
        //         $this->equalTo('login'),
        //         $this->equalTo([
        //             'json' => [
        //                 'security' => [
        //                     'credentials' => [
        //                         'login' => 'mock_username',
        //                         'password' => 'mock_password',
        //                     ]
        //                 ]
        //             ]
        //         ]),
        //         $this->equalTo('POST')
        //     )
        //     ->willReturn([
        //         'security' => [
        //             'token' => 'mock_token'
        //         ],
        //         'contact' => [
        //             'id' => 123
        //         ]
        //     ]);

        // $guzzleMock    = $this->getMockBuilder(Client::class)
        //                     ->setConstructorArgs(array('base_uri' =>  "path/to/api", 'verify' => false))
        //                     ->getMock();

        // $api = new ApiClient($apiClientMock);

        // $result = $api->clientRequest();

        // $this->assertEquals([
        //     'security' => [
        //         'token' => 'mock_token'
        //     ],
        //     'contact' => [
        //         'id' => 123
        //     ]
        // ], $result);
    }
}

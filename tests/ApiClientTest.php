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

use PHPUnit\Framework\TestCase;
use GlpiPlugin\Centreon\ApiClient;

class ApiClientTest extends TestCase
{
    public $expectedParams = ['json' => [
        'security' => [
            'credentials' => [
                'login'    => 'mock_username',
                'password' => 'mock_password',
            ],
        ],
    ],
    ];
    public $returndata = [
        'security' => [
            'token' => 'auth-token',
        ],
        'contact' => [
            'id' => 123,
        ],
    ];

    public function testClientRequest()
    {
        $apiClientMock = $this->getMockBuilder(ApiClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $apiClientMock
            ->expects($this->once())
            ->method('clientRequest')
            ->with(
                $this->equalTo('login'),
                $this->callback(function ($params) {
                    $this->assertEquals($this->expectedParams, $params);

                    return true;
                }),
                $this->equalTo('POST'),
            )
            ->willReturn($this->returndata);

        $result = $apiClientMock->clientRequest('login', $this->expectedParams, 'POST');

        $this->assertEquals($this->returndata, $result);
    }
}

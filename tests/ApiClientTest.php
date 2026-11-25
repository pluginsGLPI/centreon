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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    protected function setUp(): void
    {
        parent::setUp();
        ApiClient::resetCache();
    }

    protected function tearDown(): void
    {
        ApiClient::resetCache();
        parent::tearDown();
    }

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

    /**
     * Build an ApiClient whose configuration is set and whose HTTP layer (clientRequest()) is mocked.
     */
    private function getApiClientWithMockedRequest(): ApiClient&MockObject
    {
        $api = $this->getMockBuilder(ApiClient::class)
            ->onlyMethods(['centreonConfig', 'clientRequest'])
            ->getMock();
        $api->api_config = [
            'centreon-url'      => 'https://centreon.example.com/api/latest/',
            'centreon-username' => 'mock_username',
        ];
        $api->method('centreonConfig')->willReturn(true);

        return $api;
    }

    public function testConnectionRequestReusesLoginExceptWithCustomParams()
    {
        $api = $this->getApiClientWithMockedRequest();
        $api
            ->expects($this->exactly(2))
            ->method('clientRequest')
            ->with($this->equalTo('login'), $this->anything(), $this->equalTo('POST'))
            ->willReturn($this->returndata);

        // First call logs in
        $this->assertEquals($this->returndata, $api->connectionRequest());

        // Second call reuses the login of the request
        $this->assertEquals($this->returndata, $api->connectionRequest());
        $this->assertEquals('auth-token', $api->auth_token);
        $this->assertEquals(123, $api->user_id);

        // Diagnostic (custom params) always logs in
        $this->assertEquals($this->returndata, $api->connectionRequest(['throw' => true]));
    }

    public function testConnectionRequestCachesLoginFailure()
    {
        $api = $this->getApiClientWithMockedRequest();
        $api
            ->expects($this->once())
            ->method('clientRequest')
            ->willReturn(['error' => 'Connection timed out']);

        $this->assertEquals(['error' => 'Connection timed out'], $api->connectionRequest());
        $this->assertNull($api->auth_token);

        // Centreon is not called again in the same request
        $this->assertEquals(['error' => 'Connection timed out'], $api->connectionRequest());
        $this->assertNull($api->auth_token);
        $this->assertNull($api->user_id);
    }
}

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

namespace GlpiPlugin\Centreon;

use Exception;
use GLPIKey;
use GlpiPlugin\Centreon\Config;
use GuzzleHttp\Client;

use function Safe\json_decode;
use function Safe\json_encode;

class ApiClient
{
    public ?string $auth_token = null;

    public ?int $user_id = null;

    public array $api_config = [];

    /**
     * Load and check Centreon configuration.
     *
     * @return bool True if configuration is valid, false otherwise.
     */
    public function centreonConfig(): bool
    {
        $api_i            = new Config();
        $this->api_config = $api_i->getConfig();
        return isset($this->api_config['centreon-url']) && trim($this->api_config['centreon-url']) !== '';
    }

    /**
     * Authenticate and retrieve auth token from Centreon API.
     *
     * @param array $params Additional request parameters.
     * @return array The response array or error .
     * @throws Exception If the configuration is missing or request fails.
     */
    public function connectionRequest(array $params = []): array
    {
        if (!$this->centreonConfig()) {
            throw new Exception('Centreon configuration is not set.');
        }

        $defaults = [
            'json' => [
                'security' => [
                    'credentials' => [
                        'login'    => $this->api_config['centreon-username'] ?? '',
                        'password' => (new GLPIKey())->decrypt($this->api_config['centreon-password'] ?? ''),
                    ],
                ],
            ],
        ];
        $params = array_replace_recursive($defaults, $params);

        try {
            $data = $this->clientRequest('login', $params, 'POST');
        } catch (Exception $exception) {
            if (isset($params['throw'])) {
                throw $exception;
            }

            return ['error' => $exception->getMessage()];
        }

        $this->auth_token = $data['security']['token'];
        $this->user_id    = $data['contact']['id'];

        return $data;
    }

    /**
     * Test the connection with Centreon API.
     *
     * @return array Diagnostic result with status and message.
     */
    public function diagnostic(): array
    {
        $result = [];
        try {
            $test = $this->connectionRequest(['throw' => true]);

            if (isset($test['security']['token'])) {
                $result = [
                    'result'  => true,
                    'message' => 'You are connected to Centreon API !',
                ];
            }
        } catch (Exception $exception) {
            $result = [
                'result'  => false,
                'message' => $exception->getMessage(),
            ];
        }

        return $result;
    }

    /**
     * Generic method to perform HTTP requests to Centreon API.
     *
     * @param string $endpoint API endpoint.
     * @param array $params Request parameters.
     * @param string $method HTTP method (GET, POST, etc.).
     * @return array The response array or error.
     */
    public function clientRequest(string $endpoint = '', array $params = [], string $method = 'GET'): array
    {
        $api_client = new Client([
            'base_uri' => $this->api_config['centreon-url'] ?? '',
            'connect_timeout' => 3,
            'timeout' => 10,
        ]);
        $params['headers'] = ['Content-Type' => 'application/json'];

        if ($this->auth_token != null) {
            $params['headers']['X-AUTH-TOKEN'] = $this->auth_token;
        }

        try {
            $data = $api_client->request($method, $endpoint, $params);
        } catch (Exception $exception) {
            if (isset($params['throw'])) {
                throw $exception;
            }

            $err_msg = $exception->getMessage();

            return ['error' => $err_msg];
        }

        $data_body = $data->getBody();
        $data      = json_decode($data_body, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {

            return [];
        }

        return $data;
    }

    /**
     * Get a list of hosts.
     *
     * @param array $params Query parameters.
     */
    public function getHostsList(array $params = []): array
    {
        $defaults = [
            'query' => [
                'limit' => 1,
            ],
        ];
        $params = array_replace_recursive($defaults, $params);

        return $this->clientRequest('monitoring/hosts', $params);
    }

    /**
     * Get details of a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function getOneHost(int $host_id, array $params = []): array
    {

        return $this->clientRequest('monitoring/hosts/' . $host_id, $params);
    }

    /**
     * Get resource details for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function getOneHostResources(int $host_id, array $params = []): array
    {
        return $this->clientRequest('monitoring/resources/hosts/' . $host_id, $params);
    }

    /**
     * Get the timeline of a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function getOneHostTimeline(int $host_id, array $params = []): array
    {
        return $this->clientRequest('monitoring/hosts/' . $host_id . '/timeline', $params);
    }

    /**
     * Get a list of all services.
     *
     * @param array $params Optional parameters.
     */
    public function getServicesList(array $params = []): array
    {
        return $this->clientRequest('monitoring/services', $params);
    }

    /**
     * Get the list of services for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function getServicesListForOneHost(int $host_id, array $params = []): array
    {
        $params['query'] = ['limit' => 30];

        return $this->clientRequest('monitoring/hosts/' . $host_id . '/services', $params);
    }

    /**
     * Trigger a check for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function sendCheckToAnHost(int $host_id, array $params = []): array
    {
        $params['json']['is_forced'] = true;

        return $this->clientRequest('monitoring/hosts/' . $host_id . '/check', $params['json'], 'POST');
    }

    /**
     * Schedule a downtime for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Downtime parameters.
     */
    public function setDowntimeOnAHost(int $host_id, array $params): array
    {
        return $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params, 'POST');
    }

    /**
     * List all downtimes of a host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function listDowntimes(int $host_id, array $params = []): array
    {
        return $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params);
    }

    /**
     * Get a specific downtime details.
     *
     * @param int $downtime_id Downtime ID.
     */
    public function displayDowntime(int $downtime_id): array
    {
        return $this->clientRequest('monitoring/downtimes/' . $downtime_id);
    }

    /**
     * Get all service downtimes for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     */
    public function servicesDowntimesByHost(int $host_id, array $params = []): array
    {
        $defaultParams = [
            'query' => [
                'search' => json_encode([
                    'host.id' => [
                        '$eq' => $host_id,
                    ],
                ]),
            ],
        ];

        $queryParams = array_merge($defaultParams, $params);

        return $this->clientRequest('monitoring/services/downtimes', $queryParams);
    }

    /**
     * Cancel a specific downtime.
     *
     * @param int $downtime_id Downtime ID.
     * @param array $params Optional parameters.
     */
    public function cancelDowntime(int $downtime_id, array $params = []): array
    {
        return $this->clientRequest('monitoring/downtimes/' . $downtime_id, $params, 'DELETE');
    }

    /**
     * Send an acknowledgement for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $request Request payload.
     */
    public function acknowledgement(int $host_id, array $request = []): array
    {
        return $this->clientRequest('monitoring/hosts/' . $host_id . '/acknowledgements', $request, 'POST');
    }
}

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

use GuzzleHttp\Client;
use GLPIKey;
use GlpiPlugin\Centreon\Config;

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

        if (!isset($this->api_config['centreon-url']) || strlen(trim($this->api_config['centreon-url'])) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Authenticate and retrieve auth token from Centreon API.
     *
     * @param array $params Additional request parameters.
     * @return array The response array or error .
     * @throws \Exception If the configuration is missing or request fails.
     */
    public function connectionRequest(array $params = []): array
    {
        if (!$this->centreonConfig()) {
            throw new \Exception('Centreon configuration is not set.');
        }

        $defaults = [
            'json' => [
                'security' => [
                    'credentials' => [
                        'login'    => $this->api_config['centreon-username'],
                        'password' => (new GLPIKey())->decrypt($this->api_config['centreon-password']),
                    ],
                ],
            ],
        ];
        $params = array_replace_recursive($defaults, $params);

        try {
            $data = $this->clientRequest('login', $params, 'POST');
        } catch (\Exception $e) {
            if (isset($params['throw'])) {
                throw $e;
            }

            return ['error' => $e->getMessage()];
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
        } catch (\Exception $e) {
            $result = [
                'result'  => false,
                'message' => $e->getMessage(),
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
            'base_uri' => $this->api_config['centreon-url'],
            'verify' => false,
            'connect_timeout' => 3,
            'timeout' => 10,
        ]);
        $params['headers'] = ['Content-Type' => 'application/json'];

        if ($this->auth_token != null) {
            $params['headers']['X-AUTH-TOKEN'] = $this->auth_token;
        }

        try {
            $data = $api_client->request($method, $endpoint, $params);
        } catch (\Exception $e) {
            if (isset($params['throw'])) {
                throw $e;
            }
            $err_msg = $e->getMessage();

            return ['error' => $err_msg];
        }
        $data_body = $data->getBody();
        $data      = json_decode($data_body, true);

        return $data;
    }

    /**
     * Get a list of hosts.
     *
     * @param array $params Query parameters.
     * @return array
     */
    public function getHostsList(array $params = []): array
    {
        $defaults = [
            'query' => [
                'limit' => 1,
            ],
        ];
        $params = array_replace_recursive($defaults, $params);
        $data   = $this->clientRequest('monitoring/hosts', $params);

        return $data;
    }

    /**
     * Get details of a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function getOneHost(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id, $params);

        return $data;
    }

    /**
     * Get resource details for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function getOneHostResources(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/resources/hosts/' . $host_id, $params);

        return $data;
    }

    /**
     * Get the timeline of a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function getOneHostTimeline(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/timeline', $params);

        return $data;
    }

    /**
     * Get a list of all services.
     *
     * @param array $params Optional parameters.
     * @return array
     */
    public function getServicesList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/services', $params);

        return $data;
    }

    /**
     * Get the list of services for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function getServicesListForOneHost(int $host_id, array $params = []): array
    {
        $params['query'] = ['limit' => 30];
        $data            = $this->clientRequest('monitoring/hosts/' . $host_id . '/services', $params);

        return $data;
    }

    /**
     * Trigger a check for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function sendCheckToAnHost(int $host_id, array $params = []): array
    {
        $params['json']['is_forced'] = true;
        $data                        = $this->clientRequest('monitoring/hosts/' . $host_id . '/check', $params['json'], 'POST');

        return $data;
    }

    /**
     * Schedule a downtime for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Downtime parameters.
     * @return array
     */
    public function setDowntimeOnAHost(int $host_id, array $params): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params, 'POST');

        return $data;
    }

    /**
     * List all downtimes of a host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function listDowntimes(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params);

        return $data;
    }

    /**
     * Get a specific downtime details.
     *
     * @param int $downtime_id Downtime ID.
     * @return array
     */
    public function displayDowntime(int $downtime_id): array
    {
        $data = $this->clientRequest('monitoring/downtimes/' . $downtime_id);

        return $data;
    }

    /**
     * Get all service downtimes for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $params Optional parameters.
     * @return array
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

        $data = $this->clientRequest('monitoring/services/downtimes', $queryParams);

        return $data;
    }

    /**
     * Cancel a specific downtime.
     *
     * @param int $downtime_id Downtime ID.
     * @param array $params Optional parameters.
     * @return array
     */
    public function cancelDowntime(int $downtime_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/downtimes/' . $downtime_id, $params, 'DELETE');

        return $data;
    }


    /**
     * Send an acknowledgement for a specific host.
     *
     * @param int $host_id Host ID.
     * @param array $request Request payload.
     * @return array
     */
    public function acknowledgement(int $host_id, array $request = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/acknowledgements', $request, 'POST');

        return $data;
    }
}

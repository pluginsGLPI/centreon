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
use GlpiPlugin\Centreon\Config;

class ApiClient
{
    public $auth_token = null;
    public $user_id    = null;
    public $api_config = [];

    public function centreonConfig()
    {
        $api_i            = new Config();
        $this->api_config = $api_i->getConfig();
    }

    public function connectionRequest(array $params = [])
    {
        self::centreonConfig();

        $defaults = [
            'json' => [
                'security' => [
                    'credentials' => [
                        'login'    => $this->api_config['centreon-username'],
                        'password' => $this->api_config['centreon-password'],
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

            return $e->getMessage();
        }
        $this->auth_token = $data['security']['token'];
        $this->user_id    = $data['contact']['id'];

        return $data;
    }

    public function diagnostic()
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

    public function clientRequest(string $endpoint = '', array $params = [], string $method = 'GET')
    {
        $api_client        = new Client(['base_uri' => $this->api_config['centreon-url'], 'verify' => false]);
        $params['headers'] = ['Content-Type' => 'application/json'];

        if ($this->auth_token != null) {
            $params['headers'] = ['Content-Type' => 'application/json', 'X-AUTH-TOKEN' => $this->auth_token];
        }

        try {
            $data = $api_client->request($method, $endpoint, $params);
        } catch (\Exception $e) {
            if (isset($params['throw'])) {
                throw $e;
            }
            $err_msg = $e->getMessage();

            return $err_msg;
        }
        $data_body = $data->getBody();
        $data      = json_decode($data_body, true);

        return $data;
    }

    public function getHostsList(array $params = [])
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

    public function getOneHost(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id, $params);

        return $data;
    }

    public function getOneHostResources(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/resources/hosts/' . $host_id, $params);

        return $data;
    }

    public function getOneHostTimeline(int $host_id, array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/timeline', $params);

        return $data;
    }

    public function getServicesList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/services', $params);

        return $data;
    }

    public function getServicesListForOneHost(int $host_id, array $params = [])
    {
        $params['query'] = ['limit' => 30];
        $data            = $this->clientRequest('monitoring/hosts/' . $host_id . '/services', $params);

        return $data;
    }

    public function sendCheckToAnHost(int $host_id, array $params = [])
    {
        $params['json']['is_forced'] = true;
        $data                        = $this->clientRequest('monitoring/hosts/' . $host_id . '/check', $params['json'], 'POST');

        return $data;
    }

    public function setDowntimeOnAHost(int $host_id, array $params)
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params, 'POST');

        return $data;
    }

    public function listDowntimes(int $host_id, array $params = [])
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . '/downtimes', $params);

        return $data;
    }

    public function displayDowntime(int $downtime_id): array
    {
        $data = $this->clientRequest('monitoring/downtimes/' . $downtime_id);

        return $data;
    }

    public function servicesDowntimesByHost(int $host_id, array $params = [])
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

    public function cancelDowntime(int $downtime_id, array $params = [])
    {
        $data = $this->clientRequest('monitoring/downtimes/' . $downtime_id, $params, 'DELETE');

        return $data;
    }

    public function acknowledgement(int $host_id, array $request = [])
    {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id . 'acknowledgements', $request, 'POST');

        return $data;
    }
}

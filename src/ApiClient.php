<?php

namespace GlpiPlugin\Centreon;

use GuzzleHttp\Client;
use Toolbox;

class ApiClient
{
    public $auth_token = null;

    public function connectionRequest(array $params = [])
    {
        $params = [
            'json' => [
                'security'  => [
                    'credentials' => [
                        'login' => API_USER,
                        'password' => API_PASSWORD,
                    ]
                ]
            ]
        ];

        try {
            $data = $this->clientRequest('login', $params, 'POST');
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
            $this->auth_token   = $data["security"]["token"];

            return $data;
    }

    public function clientRequest(string $endpoint = '', array $params = [], string $method = 'GET')
    {
        $api_client        = new Client(['base_uri' =>  CENTREON_URL, 'verify' => false]);
        $params['headers'] = ['Content-Type' => "application/json"];

        if ($this->auth_token != null) {
            $params['headers'] = ['Content-Type' => "application/json", 'X-AUTH-TOKEN' => $this->auth_token];
        }

        try {
            $data   = $api_client->request($method, $endpoint, $params);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        $data_code = $data->getStatusCode();
        Toolbox::logDebug($data_code);
        $data_body = $data->getBody();
        $data      = json_decode($data_body, true);
        return $data;
    }

    public function getHostsList(array $params = []): array
    {
        $params['query'] = ['limit' => 100];
        $data = $this->clientRequest('monitoring/hosts', $params);
        return $data;
    }

    public function getOneHost(int $host_id, array $params = []): array {
        $data = $this->clientRequest('monitoring/hosts/' . $host_id, $params);
        return $data;
    }

    public function getServicesList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/services', $params);
        return $data;
    }

    public function getServicesListForOneHost(int $host_id, array $params = []): array
    {
        $data   = $this->clientRequest('monitoring/hosts/' . $host_id . '/services', $params);
        Toolbox::logDebug($host_id);
        return $data;
    }
}

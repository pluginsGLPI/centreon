<?php

namespace GlpiPlugin\Centreon;

use GuzzleHttp\Client;
use Toolbox;
use GlpiPlugin\Centreon\Config;
use Session;

class ApiClient
{
    public $auth_token = null;
    public $api_config = [];

    public function centreonConfig()
    {
        $api_i = new Config();
        $this->api_config = $api_i->getConfig();
    }

    public function connectionRequest(array $params = [])
    {
        self::centreonConfig();

        $defaults = [
            'json' => [
                'security'  => [
                    'credentials' => [
                        'login' => $this->api_config["centreon-username"],
                        'password' => $this->api_config["centreon-password"],
                    ]
                ]
            ]
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
        $this->auth_token   = $data["security"]["token"];

        return $data;
    }

    public function diagnostic()
    {
        try {
            $test = $this->connectionRequest(['throw' => true]);

            if (isset($test["security"]["token"])) {
                $result = [
                    'result' => true,
                    'message' => "You are connected to Centreon API !"
                ];
            }
        } catch (\Exception $e) {
            $result = [
                'result'   => false,
                'message'  => $e->getMessage()
            ];
        }
        return $result;
    }


    public function clientRequest(string $endpoint = '', array $params = [], string $method = 'GET')
    {
        $api_client        = new Client(['base_uri' =>  $this->api_config["centreon-url"], 'verify' => false]);
        $params['headers'] = ['Content-Type' => "application/json"];

        if ($this->auth_token != null) {
            $params['headers'] = ['Content-Type' => "application/json", 'X-AUTH-TOKEN' => $this->auth_token];
        }

        try {
            $data   = $api_client->request($method, $endpoint, $params);
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

    public function getHostsList(array $params = []): array
    {
        $params['query'] = ['limit' => 100];
        $data = $this->clientRequest('monitoring/hosts', $params);
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

    public function getServicesList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/services', $params);
        return $data;
    }

    public function getServicesListForOneHost(int $host_id, array $params = []): array
    {
        $params['query'] = ['limit' => 30];
        $data   = $this->clientRequest('monitoring/hosts/' . $host_id . '/services', $params);
        Toolbox::logDebug($host_id);
        return $data;
    }
}

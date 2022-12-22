<?php

namespace GlpiPlugin\Centreon;

use Glpi\Application\ErrorHandler;
use GuzzleHttp\Client;

class ApiClient
{
    public $auth_token = null;

    //Connection to CENTREON API
    /*public function connectionRequest()
    {
        $api_client = new Client(['base_uri' =>  CENTREON_URL]);

        try {
            $response    =  $api_client->request('POST', 'login', [
                'headers' => [
                    'Content-Type' => "application/json",
                ],
                'json' => [
                    'security'  => [
                        'credentials' => [
                            'login' => API_USER,
                            'password' => API_PASSWORD,
                        ]
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            ErrorHandler::getInstance()->handleException($e, true);
            return false;
            echo "Wrong request";
        }

        $response_code      = $response->getStatusCode();
        $response_body      = $response->getBody();
        $response_data      = json_decode($response_body, true);
        $this->auth_token   = $response_data["security"]["token"];

        return $response_data;
        echo $this->auth_token;
        echo "</br>";
        echo "Connexion OK";
    }*/

    public function connexionRequest(array $params = [])
    {
        $params = [
            'headers' => [
                'Content-Type' => "application/json",
            ],
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
            $json = $this->clientRequest('login', $params, 'POST');
        } catch (\Exception $e) {
            ErrorHandler::getInstance()->handleException($e, true);
            return false;
            echo "Wrong request";
        }
            $json_body          = $json->getBody();
            $data               = json_decode($json_body, true);
            $this->auth_token   = $data["security"]["token"];

            return $data;
            echo "Connexion ok";
            echo $this->auth_token;
    }

    public function clientRequest(string $endpoint = '', array $params = [], string $method = 'GET')
    {
        $api_client        = new Client(['base_uri' =>  CENTREON_URL]);

        if ($this->auth_token != null) {
            $params['headers'] = ['Content-Type' => "application/json", 'X-AUTH-TOKEN' => $this->auth_token];
        }

        try {
            $response   = $api_client->request($method, $endpoint, $params);
        } catch (\Exception $e) {
            ErrorHandler::getInstance()->handleException($e, true);
            return false;
        }

        $data_body = $response->getBody();
        $data      = json_decode($data_body, true);
        return $data;
    }

    public function getHostsList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/hosts', $params);
        return $data;
    }

    public function getServicesList(array $params = []): array
    {
        $data = $this->clientRequest('monitoring/services', $params);
        return $data;
    }

    public function testEcho()
    {
        echo "It works";
    }
    /*protected function getServicesListForOneHost($method = 'GET', $endpoint = 'monitoring/services/'.$host_id.'services', $params = [])
    {
        $response   = $this->clientRequest($method, $endpoint, $params);
        return $response;
    }*/
}

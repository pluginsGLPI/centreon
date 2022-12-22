<?php

namespace GlpiPlugin\Centreon;

require 'vendor/autoload.php';
require 'environnement.php';

use Glpi\Application\ErrorHandler;
use GuzzleHttp\Client;

class ApiClient
{
    private $auth_token = '';

    //Connection to CENTREON API
    public function ConnectionRequest()
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
        }

        $response_code      = $response->getStatusCode();
        $response_body      = $response->getBody();
        $response_data      = json_decode($response_body, true);
        $this->auth_token   = $response_data["security"]["token"];

        return $response_data;
    }

    protected function clientRequest($method, $endpoint = '', $params = [])
    {
        $api_client        = new Client(['base_uri' =>  CENTREON_URL]);
        $params['headers'] = ['Content-Type' => "application/json", 'X-AUTH-TOKEN' => $this->auth_token];

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

    protected function getHostsList($method = 'GET', $endpoint = 'monitoring/hosts', $params = [])
    {
        $response   = $this->clientRequest($method, $endpoint, $params);
        return $response;
    }

    protected function getServicesList($method = 'GET', $endpoint = 'monitoring/services', $params = []) 
    {
        $response   = $this->clientRequest($method, $endpoint, $params);
        return $response;
    }

    /*protected function getServicesListForOneHost($method = 'GET', $endpoint = 'monitoring/services/'.$host_id.'services', $params = [])
    {
        $response   = $this->clientRequest($method, $endpoint, $params);
        return $response;
    }*/
}

<?php

namespace GlpiPlugin\Centreon;

require 'vendor/autoload.php';
require 'environnement.php';

use Glpi\Application\ErrorHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiClient
{

//Connexion to CENTREON API
    public function CentronApiConnexion()
    {
        $api_client = new Client([
            'base_uri'  => CENTREON_URL,
            'verify'    => false,
        ]);
    
    try {

        $response = $api_client->request('POST', 'login', [
            'headers'   => [
                'Content-Type'  =>  "application/json",
            ],
            'json'      => [
                'security'  => [
                    'credentials'   => [
                        'login'     =>  API_USER,
                        'password'  =>  API_PASSWORD,
                    ]
                ]
            ]
        ]);

        $response_body  = $response->getBody();
        $response_exp   = json_decode($response_body, true);
        $auth_token     = $response_exp["security"]["token"];
        $response_status= $response->getStatusCode();

        \Session::addMessageAfterRedirect(
            __("Vous êtes connecté à l'API Centreon !")
        );        
    
        } 
        catch (\Exception $e) 
        {
            return $response_status;
            return $response_exp["message"];
            \Session::addMessageAfterRedirect(
                __("Une erreur s'est produite")
            );
        } 



        
    }

}

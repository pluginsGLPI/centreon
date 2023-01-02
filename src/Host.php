<?php

namespace GlpiPlugin\Centreon;

use CommonDBTM;
use GuzzleHttp\Client;
use GlpiPlugin\Centreon\ApiClient;

class Host extends CommonDBTM
{
    public function getComputerList()
    {
        $computer       =   new \Computer();
        $computer_list  =   $computer->find(['is_deleted' =>  0]);
        echo "Computer list OK ";
        //print_r($computer_list);
        return $computer_list;
    }

    public function hostList()
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if($res["security"]["token"] != NULL) {
            echo "Connexion OK ";
            $list = $api->getHostsList();
            if($list != NULL) {
                echo "List OK ";
                return $list;
            } else {
                echo "No list";
            }
        } else {
            echo "failed : no token";
        }
    }
}

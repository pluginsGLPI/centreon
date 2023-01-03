<?php

namespace GlpiPlugin\Centreon;

use CommonDBTM;
use GuzzleHttp\Client;
use GlpiPlugin\Centreon\ApiClient;

class Host extends CommonDBTM
{
    public $glpi_items   = [];
    public $centreon_items  = [];

    public function getComputerList()
    {
        $computer       =   new \Computer();
        $computer_list  =   $computer->find(['is_deleted' =>  0]);

        if ($computer_list != NULL) {
            foreach ($computer_list as $computeritem) {
                $array_computer[] = [
                    'cpt_id'    =>   $computeritem["id"],
                    'cpt_name'  =>  $computeritem["name"],
                ];
            }
        } else {
            echo "No List";
        }
        //echo "<pre>";
        //print_r($array_computer);
        //echo "</pre>";
        $this->glpi_items = $array_computer;
        //return $array_computer;
    }

    public function hostList()
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if ($res["security"]["token"] != NULL) {
            echo "Connexion OK ";
            $list = $api->getHostsList();
            if ($list != NULL) {
                echo "List OK ";
                foreach ($list["result"] as $item_centreon) {
                    $items_centreon[]   =   [
                        'centreon_id'   =>  $item_centreon["id"],
                        'centreon_name' =>  $item_centreon["name"],
                    ];
                }
                //echo "<pre>";
                //print_r($items_centreon);
                //echo "</pre>";
                $this->centreon_items = $items_centreon;
                //return $list;
            } else {
                echo "No list";
            }
        } else {
            echo "failed : no token";
        }
    }

    public function matchItems()
    {
        foreach ($this->glpi_items as $o1) {
            foreach ($this->centreon_items as $o2) {
                if ($o1["cpt_name"] == $o2["centreon_name"]) {
                    $this->add([
                        'items_id'      => $o1["cpt_id"],
                        'centreon_id'   => $o2["centreon_id"],
                        'itemtype'      => 'Computer'
                    ]);
                    echo "item ajouté";
                }
            }
        }
    }
}

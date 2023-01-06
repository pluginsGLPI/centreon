<?php

namespace GlpiPlugin\Centreon;

use CommonDBTM;
use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use GuzzleHttp\Client;
use GlpiPlugin\Centreon\ApiClient;

class Host extends CommonDBTM
{
    public $glpi_items   = [];
    public $centreon_items  = [];
    public $one_host = [];

    static function getTypeName($nb = 0)
    {
        return _n('Host', 'Hosts', $nb);
    }

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
                //print_r($list);
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
                        'itemtype'      => 'Computer',
                        'centreon_type' => 'Host'
                    ]);
                    echo "item ajouté";
                }
            }
        }
    }

    public function oneHost($id) {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if($res["security"]["token"] != NULL) {
            $gethost = $api->getOneHost($id);
            if($gethost != NULL) {
                $i_host = [];
                $i_host = [
                    'status'            =>  $gethost["output"],
                    'address'           =>  $gethost["address_ip"],
                    'alias'             =>  $gethost["alias"],
                    'timezone'          =>  $gethost["timezone"],
                    'last_check'        =>  $gethost["last_check"],
                    'next_check'        =>  $gethost["next_check"],
                    'check_period'      =>  $gethost["check_period"],
                ];
                $i_host["services"] = $gethost["services"];
                $i_host["nb_services"] = count($i_host["services"]);
                $this->one_host = $i_host;
                //echo "<pre>";
                //print_r($this->one_host);
                //echo "</pre>";
            } else {
                echo "Failed to find host";
            }
        } else {
                echo "Failed wrong token";
        }
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM) {
            $nb = countElementsInTable(
                self::getTable(),
                [
                    'items_id'  => $item->getID(),
                    'itemtype'  => $item->getType()
                ]
            );
            return self::createTabEntry(self::getTypeName($nb), $nb);
        }
        return '';
    }

    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM) {
            return self::showForItem($item, $withtemplate);
        }
        return true;
    }

    static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $self = new self();

        $self->getFromDBByCrit([
            'itemtype' => $item->getType(),
            'items_id' => $item->getID(),
         ]);
        
        
        $host_id = $self->fields['centreon_id'];
        $self->oneHost($host_id);
        
        
        TemplateRenderer::getInstance()->display('@centreon/host.html.twig', [
            'one_host'  =>  $self->one_host
        ]);

    }
}

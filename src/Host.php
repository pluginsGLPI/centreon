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
 * the Free Software Foundation; either version 2 of the License, or
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
 * @copyright Copyright (C) 2013-2022 by Centreon plugin team.
 * @license   GPLv2 https://www.gnu.org/licenses/gpl-2.0.html
 * @link      https://github.com/pluginsGLPI/centreon
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Centreon;

use Computer;
use CommonDBTM;
use CommonGLPI;
use DateTime;
use GlpiPlugin\Centreon\ApiClient;
use Glpi\Application\View\TemplateRenderer;

class Host extends CommonDBTM
{
    public $glpi_items      = [];
    public $centreon_items  = [];
    public $one_host        = [];
    public $uid             = '';
    public $username        = '';

    public static function getTypeName($nb = 0)
    {
        return _n('Centreon', 'Centreon', $nb);
    }

    public function getComputerList()
    {
        $computer       =   new \Computer();
        $computer_list  =   $computer->find(['is_deleted' =>  0]);

        if ($computer_list != null) {
            foreach ($computer_list as $computeritem) {
                $array_computer[] = [
                    'cpt_id'    =>   $computeritem["id"],
                    'cpt_name'  =>   $computeritem["name"],
                ];
            }
        } else {
            echo __("The list is empty", "centreon");
        }
        $this->glpi_items = $array_computer;
    }

    public function hostList()
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if ($res["security"]["token"] != null) {
            $list = $api->getHostsList();
            if ($list != null) {
                foreach ($list["result"] as $item_centreon) {
                    $items_centreon[]   =   [
                        'centreon_id'   =>  $item_centreon["id"],
                        'centreon_name' =>  $item_centreon["name"],
                    ];
                }
                $this->centreon_items = $items_centreon;
            }
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
                }
            }
        }
    }

    public function oneHost($id)
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if ($res["security"]["token"] != null) {
            $this->username = $res['contact']['alias'];
            $this->uid      = $res['contact']['id'];
            $gethost        = $api->getOneHost($id);
            $gethost_r      = $api->getOneHostResources($id);
            $getservices    = $api->getServicesListForOneHost($id);
            $getdowntimes   = $api->listDowntimes($id);
            if ($gethost != null) {
                $i_host = [];
                $i_host = [
                    'status'            =>  $gethost_r["status"]["name"],
                    'name'              =>  $gethost_r["name"],
                    'alias'             =>  $gethost["alias"],
                    'fqdn'              =>  $gethost_r['fqdn'],
                    'last_check'        =>  $gethost["last_check"],
                    'next_check'        =>  $gethost["next_check"],
                    'check_period'      =>  $gethost["check_period"],
                    'in_downtime'       =>  $gethost_r['in_downtime']
                ];
                if ($gethost_r['in_downtime'] == true) {
                    $i_host['downtimes'] = $gethost_r['downtimes'];
                }
                $i_host["services"]     = $getservices["result"];
                $i_host["nb_services"]  = count($i_host["services"]);
                $this->one_host = $i_host;
                return $i_host;
            }
        }
    }

    public function hostTimeline(int $id, string $period)
    {
        $api     = new ApiClient();
        $session = $api->connectionRequest();
        $timeline = [];
        if (isset($session["security"]["token"])) {
            $gettimeline   = $api->getOneHostTimeline($id);
            $timeline_r      = $gettimeline["result"];
            foreach ($timeline_r as $event) {
                if ($event['type'] == "downtime") {
                    $event['status']['name'] = __('unset', 'centreon');
                    $event['tries']          = __('unset', 'centreon');
                }
                $timeline[] = [
                    'id'        =>  $event['id'],
                    'date'      =>  $this->transformDate($event['date']),
                    'content'   =>  $event['content'],
                    'status'    =>  $event['status']['name'],
                    'tries'     =>  $event['tries']
                ];
            }

            $period_string = "";
            switch ($period) {
                case "day":
                    $period_string = "-1 day";
                    break;
                case "week":
                    $period_string = "-7 days";
                    break;
                case "month":
                    $period_string = "-1 month";
                    break;
            }
            $date_end = date('Y-m-d', strtotime(date('Y-m-d') . $period_string));
            $filtered_timeline = [];
            foreach ($timeline as $event => $info) {
                $setdate = $this->transformDateForCompare($info['date']);
                if ($setdate >= $date_end) {
                    $filtered_timeline[$event] = $info;
                }
            }
            TemplateRenderer::getInstance()->display('@centreon/timeline.html.twig', [
                'timeline'  =>  $filtered_timeline,
            ]);
        }
    }
    public function transformDate($date)
    {
        $timestamp = strtotime($date);
        $newdate   = date('l,F d,Y G:i:s', $timestamp);
        return $newdate;
    }

    public function transformDateForCompare($date)
    {
        $timestamp = strtotime($date);
        $newdate   = date('Y-m-d', $timestamp);
        return $newdate;
    }

    public function sendCheck(int $id)
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res["security"]["token"])) {
            try {
                $res         = $api->sendCheckToAnHost($id);
                $sentcheckok = __('Check sent', 'centreon');
                return $sentcheckok;
            } catch (\Exception $e) {
                $error_msg = $e->getMessage();
                return $error_msg;
            }
        }
    }
    public function setDowntime(int $id, array $params)
    {

        $params['author_id']        = filter_var($params['author_id'], FILTER_VALIDATE_INT);
        $params['is_fixed']         = filter_var($params['is_fixed'], FILTER_VALIDATE_BOOLEAN);
        $params['with_services']    = filter_var($params['with_services'], FILTER_VALIDATE_BOOLEAN);
        $params['start_time']       = $this->convertDateToIso8601($params['start_time']);
        $params['end_time']         = $this->convertDateToIso8601($params['end_time']);

        if ($params['is_fixed'] == true) {
            $params['duration']         = $this->diffDateInSeconds($params['end_time'], $params['start_time']);
        }
        if ($params['is_fixed'] == false) {
            $option                     = $params['time_select'];
            $params['duration']         = $this->convertToSeconds($option, $params['duration']);
            $params['duration']         = filter_var($params['duration'], FILTER_VALIDATE_INT);
        }
        unset($params['time_select']);
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res["security"]["token"])) {
            try {
                $res = $api->setDowntimeOnAHost($id, ['json' => $params]);
                return $res;
            } catch (\Exception $e) {
                $error_msg = $e->getMessage();
                return $error_msg;
            }
        }
    }

    public function convertDateToIso8601($date)
    {
        $new_date = new \DateTime($date);
        $iso_date = $new_date->format(DATE_ATOM);
        return $iso_date;
    }

    public function diffDateInSeconds($date1, $date2)
    {
        $ts1    = strtotime($date1);
        $ts2    = strtotime($date2);
        $diff   = abs($ts2 - $ts1);
        return $diff;
    }

    public function convertToSeconds($option, $duration)
    {
        if ($option == 2) {
            $new_duration = $duration * 60;
        } elseif ($option == 3) {
            $new_duration = $duration * 60 * 60;
        } else {
            $new_duration = $duration;
        }
        return $new_duration;
    }

    public function cancelActualDownTime(int $downtime_id)
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res['security']['token'])) {
            try {
                $result = $api->cancelDowntime($downtime_id);
                return $result;
            } catch (\Exception $e) {
                $error_msg = $e->getMessage();
                return $error_msg;
            }
        }
    }

    public function acknowledgement(int $host_id, array $request = [])
    {
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res['security']['token'])) {
            try {
                $result[] = $api->acknowledgement($host_id, $request);
                return $result;
            } catch (\Exception $e) {
                $error_msg = $e->getMessage();
                return $error_msg;
            }
        }
    }

    public function searchItemMatch(int $id)
    {
        $item           = new Computer();
        $computer       = $item->getFromDB($id);
        $computer_name  = $item->fields['name'];

        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res["security"]["token"])) {
            $params = [
                'query' => [
                    'search' => json_encode([
                        'host.name' => [
                            '$eq' => $computer_name
                        ]
                    ])
                ]
            ];
            $match = $api->getHostsList($params);
        }

        if (isset($match["result"]["0"]["name"]) && $match["result"]["0"]["name"] == $computer_name) {
            $centreon_id = $match["result"]["0"]["id"];
            $new_id = $this->add([
                'itemtype'      => "Computer",
                'items_id'      =>  $id,
                'centreon_id'   =>  $centreon_id,
                'centreon_type' =>  "host"
            ]);
            $this->getFromDB($new_id);
            return true;
        } else {
            return false;
        }
    }

    public function searchForItem($id)
    {
        if ($this->getFromDBByCrit(['items_id' => $id])) {
            return true;
        } else {
            return false;
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

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof CommonDBTM) {
            return self::showForItem($item, $withtemplate);
        }
        return true;
    }

    public static function showForItem(CommonDBTM $item, $withtemplate = 0)
    {
        $self       = new self();
        $item_id    = $item->getID();
        if ($self->searchForItem($item_id) == true || $self->searchItemMatch($item_id) == true) {
            $host_id = $self->fields['centreon_id'];
            $self->oneHost($host_id);
            TemplateRenderer::getInstance()->display('@centreon/host.html.twig', [
                'one_host'      =>  $self->one_host,
                'hostid'        =>  $host_id,
                'uid'           =>  $self->uid,
                'username'      =>  $self->username
            ]);
        } else {
            TemplateRenderer::getInstance()->display('@centreon/nohost.html.twig');
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'id':
                if (intval($values["centreon_id"]) > 0) {
                    $self   = new self();
                    $res    = $self->oneHost($values["centreon_id"]);
                    return $res["status"] ?? '';
                }
                break;
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}

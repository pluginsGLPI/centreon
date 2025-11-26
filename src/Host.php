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

use CommonDBTM;
use CommonGLPI;
use Computer;
use DateTimeZone;
use Exception;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Centreon\ApiClient;
use Safe\DateTime;

use function Safe\json_encode;
use function Safe\strtotime;

class Host extends CommonDBTM
{
    private $api_client;
    public $glpi_items     = [];
    public $centreon_items = [];
    public $one_host       = [];
    public $uid            = '';
    public $username       = '';

    public function __construct(?ApiClient $api_client = null)
    {
        $this->api_client = $api_client ?? new ApiClient();
    }

    public static function getTypeName($nb = 0)
    {
        return _sn('Centreon', 'Centreon', $nb);
    }

    /**
     * Get the list of computers from GLPI
     *
     * @return array
     */
    public function getComputerList(): array
    {
        $computer      = new Computer();
        $computer_list = $computer->find(['is_deleted' => 0]);

        $array_computer = [];
        if ($computer_list != null) {
            foreach ($computer_list as $computeritem) {
                $array_computer[] = [
                    'cpt_id'   => $computeritem['id'],
                    'cpt_name' => $computeritem['name'],
                ];
            }
        } else {
            echo __s('The list is empty', 'centreon');
        }
        $this->glpi_items = $array_computer;

        return $array_computer;
    }
    /**
     * Get the list of hosts from Centreon
     *
     * @return void
     */
    public function hostList(): void
    {
        $res = $this->api_client->connectionRequest();
        if ($res['security']['token'] != null) {
            $list = $this->api_client->getHostsList();
            if ($list != null) {
                $items_centreon = [];
                foreach ($list['result'] as $item_centreon) {
                    $items_centreon[] = [
                        'centreon_id'   => $item_centreon['id'],
                        'centreon_name' => $item_centreon['name'],
                    ];
                }
                $this->centreon_items = $items_centreon;
            }
        }
    }

    /**
     * Match Centreon hosts with GLPI computers based on their names.
     *
     * @return void
     */
    public function matchItems(): void
    {
        foreach ($this->glpi_items as $o1) {
            foreach ($this->centreon_items as $o2) {
                if ($o1['cpt_name'] == $o2['centreon_name']) {
                    $this->add([
                        'items_id'      => $o1['cpt_id'],
                        'centreon_id'   => $o2['centreon_id'],
                        'itemtype'      => 'Computer',
                        'centreon_type' => 'Host',
                    ]);
                }
            }
        }
    }

    /**
     * Get detailed information about a Centreon host.
     *
     * @param int $id
     * @return array
     */
    public function oneHost($id): array
    {
        $res = $this->api_client->connectionRequest();
        if ($res['security']['token'] != null) {
            $this->username = $res['contact']['alias'];
            $this->uid      = $res['contact']['id'];
            $gethost        = $this->api_client->getOneHost($id);
            $gethost_r      = $this->api_client->getOneHostResources($id);
            $getservices    = $this->api_client->getServicesListForOneHost($id);
            $getdowntimes   = $this->api_client->listDowntimes($id);
            if ($gethost != null) {
                $i_host = [];
                $i_host = [
                    'status'       => $gethost_r['status']['name'],
                    'name'         => $gethost_r['name'],
                    'alias'        => $gethost['alias'],
                    'fqdn'         => $gethost_r['fqdn'],
                    'last_check'   => $gethost['last_check'],
                    'next_check'   => $gethost['next_check'],
                    'check_period' => $gethost['check_period'],
                    'in_downtime'  => $gethost_r['in_downtime'],
                ];
                if ($gethost_r['in_downtime'] == true) {
                    $i_host['downtimes'] = $gethost_r['downtimes'];
                }
                $i_host['services']    = $getservices['result'];
                $i_host['nb_services'] = count($i_host['services']);
                $this->one_host        = $i_host;

                return $i_host;
            }
        }

        return [];
    }

    /**
     * Display the timeline of events for a given host.
     *
     * @param int $id
     * @param string $period 'day', 'week', or 'month'
     * @return string
     */
    public function hostTimeline(int $id, string $period): string
    {
        $api      = new ApiClient();
        $session  = $api->connectionRequest();
        $timeline = [];
        if (isset($session['security']['token'])) {
            $gettimeline = $api->getOneHostTimeline($id);
            $timeline_r  = $gettimeline['result'];
            foreach ($timeline_r as $event) {
                if ($event['type'] == 'downtime') {
                    $event['status']['name'] = __s('unset', 'centreon');
                    $event['tries']          = __s('unset', 'centreon');
                }
                $timeline[] = [
                    'id'      => $event['id'],
                    'date'    => $this->transformDate($event['date']),
                    'content' => $event['content'],
                    'status'  => $event['status']['name'],
                    'tries'   => $event['tries'],
                ];
            }

            $period_string = '';
            switch ($period) {
                case 'day':
                    $period_string = '-1 day';
                    break;
                case 'week':
                    $period_string = '-7 days';
                    break;
                case 'month':
                    $period_string = '-1 month';
                    break;
            }
            $date_end          = date('Y-m-d', strtotime(date('Y-m-d') . $period_string));
            $filtered_timeline = [];
            foreach ($timeline as $event => $info) {
                $setdate = $this->transformDateForCompare($info['date']);
                if ($setdate >= $date_end) {
                    $filtered_timeline[$event] = $info;
                }
            }
            TemplateRenderer::getInstance()->display('@centreon/timeline.html.twig', [
                'timeline' => $filtered_timeline,
            ]);
        }
        return __s('Error: unable to display timeline', 'centreon');
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

    /**
     * Send a check command to a host.
     *
     * @param int $id
     * @return string
     */
    public function sendCheck(int $id): string
    {
        $res = $this->api_client->connectionRequest();
        if (isset($res['security']['token'])) {
            try {
                $res         = $this->api_client->sendCheckToAnHost($id);
                $message = __s('Check sent', 'centreon');

                return $message;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        return __s('Error: unable to send check (unauthenticated)', 'centreon');
    }

    /**
     * Schedule a downtime on a host.
     *
     * @param int $id
     * @param array $params
     * @return array
     */
    public function setDowntime(int $id, array $params): array
    {
        $params['is_fixed']      = filter_var($params['is_fixed'], FILTER_VALIDATE_BOOLEAN);
        $params['with_services'] = filter_var($params['with_services'], FILTER_VALIDATE_BOOLEAN);
        $params['start_time']    = $this->convertDateToIso8601($params['start_time']);
        $params['end_time']      = $this->convertDateToIso8601($params['end_time']);

        if ($params['is_fixed'] == true) {
            $params['duration'] = $this->diffDateInSeconds($params['end_time'], $params['start_time']);
        }
        if ($params['is_fixed'] == false) {
            $option             = $params['time_select'];
            $params['duration'] = $this->convertToSeconds($option, $params['duration']);
            $params['duration'] = filter_var($params['duration'], FILTER_VALIDATE_INT);
        }
        unset($params['time_select']);
        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res['security']['token'])) {
            try {
                $res = $api->setDowntimeOnAHost($id, ['json' => $params]);

                return $res;
            } catch (Exception $e) {
                $error_msg = $e->getMessage();

                return ['error' => $e->getMessage()];
            }
        }
        return [
            'error' => __s('Error: unauthenticated or unable to set downtime', 'centreon'),
        ];
    }

    public function convertDateToIso8601($date)
    {
        $timezone = new DateTimeZone($_SESSION['glpi_tz'] ?? date_default_timezone_get());
        $new_date = new DateTime($date, $timezone);
        $iso_date = $new_date->format(DATE_ATOM);

        return $iso_date;
    }

    public function diffDateInSeconds($date1, $date2)
    {
        $ts1  = strtotime($date1);
        $ts2  = strtotime($date2);
        $diff = abs($ts2 - $ts1);

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

    /**
     * Cancel the current host downtime and its related service downtimes.
     *
     * @param int $downtime_id
     * @return array
     */
    public function cancelActualDownTime(int $downtime_id): array
    {
        $api   = new ApiClient();
        $res   = $api->connectionRequest();
        $error = [];

        if (isset($res['security']['token'])) {
            try {
                $actualDowntime = $api->displayDowntime($downtime_id);
                $host_id        = $actualDowntime['host_id'];
                $start_time     = $actualDowntime['start_time'];
                $end_time       = $actualDowntime['end_time'];

                $servicesDowntimes = $api->servicesDowntimesByHost($host_id);
                foreach ($servicesDowntimes['result'] as $serviceDowntime) {
                    if (isset($serviceDowntime['start_time']) && isset($serviceDowntime['end_time'])) {
                        if ($serviceDowntime['start_time'] == $start_time && $serviceDowntime['end_time'] == $end_time) {
                            $s_downtime_id = $serviceDowntime['id'];
                            $api->cancelDowntime($s_downtime_id);
                        }
                    } else {
                        $error[] = [
                            'service_id' => $serviceDowntime['id'],
                            'message'    => 'No downtime found for this service',
                        ];
                    }
                }
                $api->cancelDowntime($downtime_id);
            } catch (Exception $e) {
                $error[] = [
                    'message' => $e->getMessage(),
                ];
            }
        } else {
            $error[] = [
                'message' => 'Error',
            ];
        }

        return $error;
    }

    /**
     * Acknowledge a Centreon host alert.
     *
     * @param int $host_id
     * @param array $request
     * @return array|string
     */
    public function acknowledgement(int $host_id, array $request = [])
    {
        $res = $this->api_client->connectionRequest();
        if (isset($res['security']['token'])) {
            try {

                $request = $this->sanitizeAcknowledgementPayload($request);

                $result[] = $this->api_client->acknowledgement($host_id, ['json' => $request]);

                return $result;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }
        return __s('Error: unauthenticated or unable to acknowledge', 'centreon');
    }

    /**
     * Sanitize the acknowledgement request payload.
     *
     * This ensures the expected types are correctly set before sending to Centreon API.
     *
     * @param array $request
     * @return array
     */
    private function sanitizeAcknowledgementPayload(array $request): array
    {
        $boolean_fields = [
            'is_notify_contacts',
            'is_sticky',
            'is_persistent_comment',
            'with_services',
        ];

        foreach ($boolean_fields as $field) {
            if (isset($request[$field])) {

                $request[$field] = filter_var($request[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $request;
    }

    /**
     * Search for a matching host in Centreon based on the local Computer name.
     *
     * @param int $id The ID of the Computer item to search for.
     * @return bool True if a match was found and added; false otherwise.
     */
    public function searchItemMatch(int $id): bool
    {
        $item          = new Computer();
        $item->getFromDB($id);
        $computer_name = $item->fields['name'];

        $api = new ApiClient();
        $res = $api->connectionRequest();
        if (isset($res['security']['token'])) {
            $params = [
                'query' => [
                    'search' => json_encode([
                        'host.name' => [
                            '$lk' => '%' . $computer_name . '%',
                        ],
                    ]),
                ],
            ];
            $match = $api->getHostsList($params);

            //compare results case-insensitively
            foreach ($match['result'] as $host) {
                if (strcasecmp($host['name'], $computer_name) === 0) {
                    $centreon_id = $host['id'];
                    $new_id = $this->add([
                        'itemtype'      => 'Computer',
                        'items_id'      => $id,
                        'centreon_id'   => $centreon_id,
                        'centreon_type' => 'host',
                    ]);
                    $this->getFromDB($new_id);
                    return true;
                }
            }

            return false;

        } else {

            return false;
        }
    }

    /**
     * Check if an item with the given ID exists in the database.
     *
     * Performs a search in the database using the provided item ID
     * and returns true if a matching entry is found.
     *
     * @param int $id The ID of the item to search for.
     *
     * @return bool True if the item exists; false otherwise.
     */
    public function searchForItem($id): bool
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
                    'items_id' => $item->getID(),
                    'itemtype' => $item->getType(),
                ],
            );

            return self::createTabEntry(self::getTypeName(), 0, $item::getType(), Config::getIcon());
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
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        $self    = new self();
        $item_id = $item->getID();
        if ($self->searchForItem($item_id) == true || $self->searchItemMatch($item_id) == true) {
            $host_id = $self->fields['centreon_id'];
            $self->oneHost($host_id);
            TemplateRenderer::getInstance()->display('@centreon/host.html.twig', [
                'one_host' => $self->one_host,
                'hostid'   => $host_id,
                'uid'      => $self->uid,
                'username' => $self->username,
                'logo'     => $CFG_GLPI['root_doc'] . '/plugins/centreon/files/logo-centreon.png',
            ]);
        } else {
            TemplateRenderer::getInstance()->display('@centreon/nohost.html.twig');
        }
    }

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        switch ($field) {
            case 'id':
                if (intval($values['centreon_id']) > 0) {
                    $self = new self();
                    $res  = $self->oneHost($values['centreon_id']);

                    return $res['status'] ?? '';
                }
                break;
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}

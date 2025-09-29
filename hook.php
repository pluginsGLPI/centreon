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

use GlpiPlugin\Centreon\Host;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_centreon_install($version)
{
    /** @var DBmysql $DB */
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();

    $migration = new Migration(PLUGIN_CENTREON_VERSION);

    $table = Host::getTable();
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
                  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `itemtype`      VARCHAR(100) NOT NULL,
                  `items_id`      INT UNSIGNED NOT NULL DEFAULT '0',
                  `centreon_id`   INT UNSIGNED NOT NULL,
                  `centreon_type` VARCHAR(100) DEFAULT 'host',
                  PRIMARY KEY  (`id`),
                  KEY `items_id` (`items_id`)
                 ) ENGINE=InnoDB
                 DEFAULT CHARSET={$default_charset}
                 COLLATE={$default_collation}";
        $DB->doQuery($query);
    } else {
        $migration->changeField($table, 'items_id', 'items_id', "int unsigned NOT NULL DEFAULT '0'");
        $migration->changeField($table, 'centreon_id', 'centreon_id', "int unsigned NOT NULL");
    }
    $centreon_password = Config::getConfigurationValue('plugin:centreon', 'centreon-password');
    /**Migration to 1.0.1 */
    if ($centreon_password !== null) {
        /** Check if pwd is already encrypted, if not, it returns empty string */
        /** It's not necessary to encrypt again, because setConfigurationValues() check
         * if the value is in secured_configs and if yes, and encrypt it
         */
        $decrypted_pwd = @(new GLPIKey())->decrypt($centreon_password);
        if ($decrypted_pwd == '') {
            Config::setConfigurationValues('plugin:centreon', [
                'centreon-password' => $centreon_password,
            ]);
        }
    }
    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_centreon_uninstall()
{
    /** @var DBmysql $DB */
    global $DB;

    $tables = [Host::getTable(), ];

    foreach ($tables as $table) {
        $migration = new Migration(PLUGIN_CENTREON_VERSION);
        $migration->displayMessage("Uninstalling $table");
        $migration->dropTable($table);
        $DB->error();
    }

    $config = new Config();
    $config->deleteByCriteria(['context' => 'plugin:centreon']);

    return true;
}

function plugin_centreon_getAddSearchOptionsNew($itemtype)
{
    $sopt = [];

    if ($itemtype == 'Computer') {
        $sopt[] = [
            'id'               => 2023,
            'table'            => Host::getTable(),
            'field'            => 'id',
            'name'             => __s('Centreon Host Status', 'centreon'),
            'additionalfields' => ['centreon_id'],
            'datatype'         => 'specific',
            'nosearch'         => true,
            'nosort'           => true,
            'massiveaction'    => false,
            'joinparams'       => [
                'jointype' => 'itemtype_item',
            ],
        ];
    }

    return $sopt;
}

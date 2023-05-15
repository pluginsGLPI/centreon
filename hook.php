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

use GlpiPlugin\Centreon\Host;

/**
 * Plugin install process
 *
 * @return boolean
 */
function plugin_centreon_install()
{
    global $DB;

    $default_charset   = DBConnection::getDefaultCharset();
    $default_collation = DBConnection::getDefaultCollation();

    $migration = new \Migration(PLUGIN_CENTREON_VERSION);

    $table = GlpiPlugin\Centreon\Host::getTable();
    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
                  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `itemtype`      VARCHAR(100) NOT NULL,
                  `items_id`      INT(10) UNSIGNED NOT NULL DEFAULT '0',
                  `centreon_id`   INT(10) NOT NULL,
                  `centreon_type` VARCHAR(100) DEFAULT 'host',
                  PRIMARY KEY  (`id`),
                  KEY `items_id` (`items_id`)
                 ) ENGINE=InnoDB
                 DEFAULT CHARSET={$default_charset}
                 COLLATE={$default_collation}";
        $DB->queryOrDie($query, $DB->error());
    }
    Toolbox::logDebug($table);
    $migration->executeMigration();

    return true;
}

/**
 * Plugin uninstall process
 *
 * @return boolean
 */
function plugin_centreon_uninstall()
{
    global $DB;

    $tables = [GlpiPlugin\Centreon\Host::getTable(),];

    foreach ($tables as $table) {
            $migration = new Migration(PLUGIN_CENTREON_VERSION);
            $migration->displayMessage("Uninstalling $table");
            $migration->dropTable($table);
            $DB->error();
    }

    return true;

    $config = new \Config();
    $config->deleteByCriteria(['context' => 'plugin:centreon']);
}

function plugin_centreon_getAddSearchOptionsNew($itemtype)
{
    $sopt = [];

    if ($itemtype == 'Computer') {
        $sopt[] = [
            'id'        => 2023,
            'table'     => GlpiPlugin\Centreon\Host::getTable(),
            'field'     => 'id',
            'name'      => __('Centreon Host Status', 'centreon'),
            'additionalfields'  => ['centreon_id'],
            'datatype'  => 'specific',
            'nosearch'  => true,
            'nosort'    => true,
            'massiveaction' => false,
            'joinparams' => [
                'jointype' => 'itemtype_item'
            ]
        ];
    }
    return $sopt;
}

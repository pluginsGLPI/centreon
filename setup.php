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

define('PLUGIN_CENTREON_VERSION', '1.0.2');

// Minimal GLPI version, inclusive
define('PLUGIN_CENTREON_MIN_GLPI_VERSION', '10.0.0');
// Maximum GLPI version, exclusive
define('PLUGIN_CENTREON_MAX_GLPI_VERSION', '10.0.99');
// Define the plugin directory
define('CENTREON_DIR_PATH', __DIR__);

use Glpi\Plugin\Hooks;

/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_centreon()
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['centreon'] = true;

    $PLUGIN_HOOKS['config_page']['centreon'] = '../../front/config.form.php';

    $PLUGIN_HOOKS[Hooks::SECURED_CONFIGS]['centreon'] = ['centreon-password'];

    Plugin::registerClass(GlpiPlugin\Centreon\Host::class, [
        'addtabon' => ['Computer'],
    ]);

    Plugin::registerClass(GlpiPlugin\Centreon\Config::class, [
        'addtabon' => ['Config'],
    ]);
}


/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_centreon()
{
    return [
        'name'         => 'centreon',
        'version'      => PLUGIN_CENTREON_VERSION,
        'author'       => '<a href="http://www.teclib.com">Teclib\'</a>',
        'license'      => 'GPLv3',
        'homepage'     => '',
        'requirements' => [
            'glpi' => [
                'min' => PLUGIN_CENTREON_MIN_GLPI_VERSION,
                'max' => PLUGIN_CENTREON_MAX_GLPI_VERSION,
            ],
        ],
    ];
}

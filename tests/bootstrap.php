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

$current_plugin_folder = basename(dirname(__DIR__));

// GLPI core test bootstrap: boots the TESTING kernel, exposes the
// `Glpi\Tests\*` test case classes and every core class.
require __DIR__ . '/../../../tests/bootstrap.php';

// Plugin runtime dependencies (Guzzle is provided by core, kept for parity).
require dirname(__DIR__) . '/vendor/autoload.php';

if (!Plugin::isPluginActive($current_plugin_folder)) {
    throw new RuntimeException(
        sprintf(
            'Plugin %s is not active in the test database.'
            . ' Run `make test-setup` (plugin:install/enable --env=testing) first.',
            $current_plugin_folder,
        ),
    );
}

// hook.php only declares the install/uninstall routines; GLPI loads it lazily
// at (un)install time, so pull it in here for the lifecycle test cases.
require_once dirname(__DIR__) . '/hook.php';

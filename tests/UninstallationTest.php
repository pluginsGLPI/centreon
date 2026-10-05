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

use Glpi\Tests\GLPITestCase;
use GlpiPlugin\Centreon\Host;

use function Safe\ob_end_clean;
use function Safe\ob_start;

/**
 * plugin_centreon_uninstall() must remove every artefact the install created.
 *
 * The routine is run for real against the shared test database, then
 * plugin_centreon_install() is replayed in a finally block so the schema is
 * restored for the rest of the suite.
 */
class UninstallationTest extends GLPITestCase
{
    private const CONTEXT = 'plugin:centreon';

    public function tearDown(): void
    {
        // Safety net: never leave the shared test schema half-uninstalled.
        /** @var DBmysql $DB */
        global $DB;
        $DB->clearSchemaCache();
        if (!$DB->tableExists(Host::getTable())) {
            $this->runSilently(static fn() => plugin_centreon_install(PLUGIN_CENTREON_VERSION));
        }

        parent::tearDown();
    }

    public function testUninstallDropsTableAndConfigThenReinstallRestoresIt(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        Config::setConfigurationValues(self::CONTEXT, ['centreon-url' => 'https://centreon.example.com']);

        try {
            $this->runSilently(static fn() => plugin_centreon_uninstall());

            $this->assertFalse(
                $DB->tableExists(Host::getTable()),
                'the host table should have been dropped',
            );
            $this->assertSame([], Config::getConfigurationValues(self::CONTEXT));
        } finally {
            $restored = $this->runSilently(static fn(): bool => plugin_centreon_install(PLUGIN_CENTREON_VERSION));
            $this->assertTrue($restored, 'plugin_centreon_install() failed to restore the schema');
        }

        $this->assertTrue($DB->tableExists(Host::getTable()));
    }

    private function runSilently(callable $fn): mixed
    {
        /** @var DBmysql $DB */
        global $DB;

        $ob_level = ob_get_level();
        ob_start();
        try {
            return $fn();
        } finally {
            while (ob_get_level() > $ob_level) {
                ob_end_clean();
            }

            // (un)install routines run DDL directly, bypassing the schema cache.
            $DB->clearSchemaCache();
        }
    }
}

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
 * Post-install state assertions + idempotence of plugin_centreon_install().
 *
 * The plugin is installed and active in the test database (tests/bootstrap.php);
 * these tests describe what that install produced.
 */
class InstallationTest extends GLPITestCase
{
    private const CONTEXT = 'plugin:centreon';

    private const EXPECTED_COLUMNS = [
        'id',
        'itemtype',
        'items_id',
        'centreon_id',
        'centreon_type',
    ];

    public function testTargetsGlpi12(): void
    {
        $this->assertSame('12.0.0', PLUGIN_CENTREON_MIN_GLPI_VERSION);
        $this->assertSame('12.0.99', PLUGIN_CENTREON_MAX_GLPI_VERSION);
    }

    public function testHostTableExistsWithItsColumns(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $table = Host::getTable();
        $this->assertTrue($DB->tableExists($table), "missing table {$table}");

        foreach (self::EXPECTED_COLUMNS as $column) {
            $this->assertTrue($DB->fieldExists($table, $column), "missing column {$table}.{$column}");
        }
    }

    public function testSecuredConfigIsDeclared(): void
    {
        /** @var array $PLUGIN_HOOKS */
        global $PLUGIN_HOOKS;

        $this->assertContains(
            'centreon-password',
            $PLUGIN_HOOKS['secured_configs']['centreon'] ?? [],
        );
    }

    public function testInstallScriptIsIdempotent(): void
    {
        /** @var DBmysql $DB */
        global $DB;

        $config_before = Config::getConfigurationValues(self::CONTEXT);

        $ok = $this->runSilently(static fn(): bool => plugin_centreon_install(PLUGIN_CENTREON_VERSION));

        $this->assertTrue($ok);
        $this->assertTrue($DB->tableExists(Host::getTable()));
        foreach (self::EXPECTED_COLUMNS as $column) {
            $this->assertTrue($DB->fieldExists(Host::getTable(), $column));
        }

        $this->assertSame($config_before, Config::getConfigurationValues(self::CONTEXT));
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

            // plugin_centreon_install() runs DDL directly; refresh the cache so
            // later tableExists()/fieldExists() calls see the real schema.
            $DB->clearSchemaCache();
        }
    }
}

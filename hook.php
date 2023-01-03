<?php

/**
 * -------------------------------------------------------------------------
 * centreon plugin for GLPI
 * Copyright (C) 2022 by the centreon Development Team.
 * -------------------------------------------------------------------------
 *
 * MIT License
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * --------------------------------------------------------------------------
 */

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
        if($DB->tableExists($table)) {
            $DB->queryOrDie(
                "DROP TABLE `$table`",
                $DB->error()
            );
        }
    }

    return true;
}

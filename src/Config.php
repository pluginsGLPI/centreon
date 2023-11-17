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

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;
use GlpiPlugin\Centreon\ApiClient;
use Toolbox;
use Config as Glpi_Config;

class Config extends Glpi_Config
{
    public static function getTypeName($nb = 0)
    {
        return __('Centreon settings', 'Centreon');
    }

    public static function getConfig()
    {
        return \Config::getConfigurationValues('plugin:centreon');
    }


    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case \Config::class:
                return self::createTabEntry(self::getTypeName());
        }
        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        switch ($item->getType()) {
            case \Config::class:
                return self::showForConfig($item, $withtemplate);
        }

        return true;
    }

    public static function showForConfig(\Config $config, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canView()) {
            return false;
        }

        $current_config = self::getConfig();
        $canedit        = Session::haveRight(self::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@centreon/config.html.twig', [
            'item'           => $config,
            'current_config' => $current_config,
            'can_edit'       => $canedit
        ]);

        $conf_ok = true;

        foreach ($current_config as $v) {
            if (strlen($v) == 0) {
                $conf_ok = false;
            }
        }
        Toolbox::logDebug($conf_ok);
        if ($conf_ok == true) {
            $api  = new ApiClient();
            $diag = $api->diagnostic();

            TemplateRenderer::getInstance()->display('@centreon/diagnostic.html.twig', [
                'diag' => $diag
            ]);
        } else {
            TemplateRenderer::getInstance()->display('@centreon/checkField.html.twig');
        }
    }
}

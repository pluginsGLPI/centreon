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
use Config as Glpi_Config;
use Glpi\Application\View\TemplateRenderer;
use GlpiPlugin\Centreon\ApiClient;
use Session;

class Config extends Glpi_Config
{
    public static function getTypeName($nb = 0)
    {
        return __s('Centreon settings', 'Centreon');
    }

    public static function getConfig()
    {
        return Glpi_config::getConfigurationValues('plugin:centreon');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        switch ($item->getType()) {
            case Glpi_Config::class:
                return self::createTabEntry(self::getTypeName(), 0, $item::getType(), self::getIcon());
        }

        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        if ($item instanceof Glpi_Config) {
            return self::showForConfig($item, $withtemplate);
        }

        return true;
    }

    public static function showForConfig(Glpi_Config $config, $withtemplate = 0)
    {
        /** @var array $CFG_GLPI */
        global $CFG_GLPI;

        if (!self::canUpdate()) {
            return false;
        }

        $current_config = self::getConfig();
        $canedit        = Session::haveRight(self::$rightname, UPDATE);

        TemplateRenderer::getInstance()->display('@centreon/config.html.twig', [
            'item'           => $config,
            'current_config' => $current_config,
            'can_edit'       => $canedit,
        ]);

        $conf_ok = true;

        foreach ($current_config as $v) {
            if (strlen($v) == 0) {
                $conf_ok = false;
            }
        }
        if ($conf_ok == true) {
            $api  = new ApiClient();
            $diag = $api->diagnostic();

            TemplateRenderer::getInstance()->display('@centreon/diagnostic.html.twig', [
                'diag' => $diag,
            ]);
        } else {
            TemplateRenderer::getInstance()->display('@centreon/checkField.html.twig');
        }
    }

    public static function prepareConfigUpdate(CommonDBTM $item)
    {
        if (
            isset($item->input['centreon-password'])
            && ($item->input['centreon-password'] == '')
        ) {
            unset($item->input['centreon-password']);
        }
    }

    public static function getIcon()
    {
        return "ti ti-square-rounded-letter-c";
    }
}

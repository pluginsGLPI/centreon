<?php

namespace GlpiPlugin\Centreon;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;
use GlpiPlugin\Centreon\ApiClient;
use Toolbox;

class Config extends \Config
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

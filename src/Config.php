<?php

namespace GlpiPlugin\Centreon;

use CommonGLPI;
use Glpi\Application\View\TemplateRenderer;
use Session;

class Config extends \Config 
{
    static function getTypeName($nb = 0)
    {
        return __('Centreon settings', 'Centreon');
    }

    static function getConfig()
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

    static function displayTabContentForItem(
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

    static function showForConfig(\Config $config, $withtemplate = 0) 
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
    }
}

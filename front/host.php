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

include('../../../inc/includes.php');

Html::header(
    Host::getTypeName(),
    $_SERVER['PHP_SELF'],
    "plugins",
    Host::class,
    "Host"
);
\Search::show(Host::class);
\Html::footer();

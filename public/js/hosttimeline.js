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

function centreonInitTimeline(root) {
    const hostId = Number(root.dataset.centreonTimelineHostId);

    $('#t-day').click(function () {
        $('#p-test').load(CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/hostTimeline.php?period=day&hostid=" + hostId);
    });
    $('#t-week').click(function () {
        $('#p-test').load(CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/hostTimeline.php?period=week&hostid=" + hostId);
    });
    $('#t-month').click(function () {
        $('#p-test').load(CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/hostTimeline.php?period=month&hostid=" + hostId);
    });
}

$(function () {
    document.querySelectorAll('[data-centreon-timeline-host-id]:not([data-centreon-timeline-ready])').forEach(function (root) {
        root.dataset.centreonTimelineReady = '';
        centreonInitTimeline(root);
    });
});

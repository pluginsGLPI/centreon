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

function centreonWaitForDowntime(hostId, interval = 2000, timeout = 15000) {
    const start = Date.now();

    const intervalId = setInterval(function () {
        $.ajax({
            url: CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/checkDowntime.php",
            method: 'GET',
            data: { host_id: hostId },
            dataType: 'json',
            success: function (data) {
                if (data.in_downtime === true || data === 'true') {
                    clearInterval(intervalId);
                    window.location.reload();
                } else if (data?.error) {
                    console.error('API error :', data.error);
                    clearInterval(intervalId);
                } else {
                    console.log('Downtime undetected, waiting...');
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX error :', status, error);
                clearInterval(intervalId);
            }
        });

        if (Date.now() - start > timeout) {
            clearInterval(intervalId);
            console.warn('Timeout reached : downtime undetected');
        }
    }, interval);
}

function centreonInitHost(root) {
    const hostId = Number(root.dataset.centreonHostId);
    const inDowntime = root.dataset.centreonInDowntime;
    const lastDowntimeId = root.dataset.centreonLastDowntimeId;

    $('#check').click(function () {
        $('.toggle').toggleClass('d-none');
        $.post(CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/sendCheck.php?hostid=" + hostId, function (response) {
            reloadTab('');
            $('#check').addClass('btn-lime');
            $('.toggle').toggleClass('d-none');
        })
    });
    $('#downtime').click(function () {
        $('#duration_select').hide();
        $('#dialog').modal('show');
    });
    $('#is_fixed').click(function () {
        $('#duration_select').toggle();
    });
    $('#submit').click(function () {
        var textarea = $.trim($('#comment').val());
        if (textarea != "") {
            $('#comment').val(textarea);
        }

        var params = $('#setdowntime').serializeArray();
        var flatparams = {};
        $.each(params, function () {
            if (this.name.includes('time')) {
                var date = new Date(this.value);
                this.value = date.toISOString();
            }
            flatparams[this.name] = this.value;
        });
        $.ajax({
            "type": 'POST',
            "url": CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/setDowntime.php",
            "data": {
                "hostid": hostId,
                "params": flatparams
            },
            "success": function (result) {
                $('#dialog').modal('hide');
                centreonWaitForDowntime(hostId);
            }
        });
    });
    $('#ack').click(function () {
        $('#ack_modal').modal('show');
    });
    $('#ack_ok').click(function () {
        var params = $('#ack_form').serializeArray();
        var flatparams = {};
        $.each(params, function () {
            flatparams[this.name] = this.value;
        });
        console.log(params);
        $.ajax({
            "type": 'POST',
            "url": CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/ack.php",
            "data": {
                "hostid": hostId,
                "params": flatparams
            },
            "success": function (data) {
                reloadTab('');
            }
        });
    });

    $('#cancel_d').click(function () {
        console.log(inDowntime);
        $.ajax({
            "type": 'POST',
            "url": CFG_GLPI['root_doc'] + "/plugins/centreon/ajax/cancelDowntime.php",
            "data": {
                "downtimeid": lastDowntimeId,
            },
            "success": function () {
                setTimeout(function () {
                    location.reload();
                }, 2000);
            }
        });
    });
    $(document).on('submit', '#setdowntime', function (event) {
        event.preventDefault();
    });
}

$(function () {
    document.querySelectorAll('[data-centreon-host-id]:not([data-centreon-host-ready])').forEach(function (root) {
        root.dataset.centreonHostReady = '';
        centreonInitHost(root);
    });
});

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library functions for courseremainder mails
 *
 * @package    local_courseremainder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_login();

global $DB;

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);

$records = $DB->get_records(
    'local_courseremainder_mails_log',
    [
        'userid' => $userid,
        'courseid' => $courseid,
    ],
    'timesent DESC'
);
if (!$records) {
    echo '<p>No reminder history found.</p>';
    exit;
}
echo '<table class="reminder-detail-table">';
echo '<thead>
<tr>
    <th>Reminder Type</th>
    <th>Days</th>
    <th>Status</th>
    <th>Sent Time</th>
</tr>
</thead><tbody>';

foreach ($records as $r) {
    if ($r->status) {
        $status = '<span class="reminder-status-success">Sent</span>';
    } else {
        $status = '<span class="reminder-status-failed">Failed</span>';
    }
    echo '<tr>';
    echo '<td>'.s($r->expairy_notification_type).'</td>';
    echo '<td>'.$r->day.'</td>';
    echo '<td>'.$status.'</td>';
    echo '<td>'.userdate($r->timesent).'</td>';
    echo '</tr>';
}

echo '</tbody></table>';


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
 * Reminder reports page.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/coursereminder_mails:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/coursereminder_mails/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_coursereminder_mails'));
$PAGE->set_heading(get_string('pluginname', 'local_coursereminder_mails'));
$PAGE->requires->js_call_amd('local_coursereminder_mails/reminder_history', 'init');

echo $OUTPUT->header();

$form = new \local_coursereminder_mails\form\report_filter();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/coursereminder_mails/index.php'));
}

// Read filters.
$filters = [
    'courseid' => 0,
    'userid'   => 0,
    'status'   => '',
    'fromdate' => 0,
    'todate'   => 0,
];

if ($data = $form->get_data()) {
    $filters['courseid'] = (int)$data->courseid;
    $filters['userid']   = (int)$data->userid;
    $filters['status']   = ($data->status === '') ? '' : (int)$data->status;
    $filters['fromdate'] = !empty($data->fromdate) ? $data->fromdate : 0;
    $filters['todate']   = !empty($data->todate) ? $data->todate : 0;
}

$form->set_data($filters);
$form->display();

$table = new \local_coursereminder_mails\table\reminder_table('coursereminderreport');

$params = array_filter([
    'courseid' => $filters['courseid'],
    'userid'   => $filters['userid'],
    'status'   => ($filters['status'] === '') ? null : $filters['status'],
    'fromdate' => $filters['fromdate'],
    'todate'   => $filters['todate'],
], function ($v) {
    return $v !== 0 && $v !== null && $v !== '';
});

$table->define_baseurl(new moodle_url('/local/coursereminder_mails/index.php', $params));
$table->setup_table($filters);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/coursereminder_mails/delete.php'),
]);
echo html_writer::empty_tag('input', [
    'type'  => 'hidden',
    'name'  => 'sesskey',
    'value' => sesskey(),
]);

$table->out(10, true);

echo html_writer::start_div('mt-3');
echo html_writer::empty_tag('input', [
    'type'  => 'submit',
    'class' => 'btn btn-danger',
    'name'  => 'delete',
    'value' => get_string('delete'),
]);
echo html_writer::end_div();
echo html_writer::end_tag('form');

// Modal container for reminder details.
echo html_writer::start_div('modal fade', ['id' => 'course-modal']);
echo html_writer::start_div('modal-dialog modal-lg');
echo html_writer::start_div('modal-content');
echo html_writer::start_div('modal-header');
echo html_writer::tag('h5', '', ['id' => 'course-modal-label']);
echo html_writer::tag(
    'button',
    html_writer::tag('span', '&times;', ['aria-hidden' => 'true']),
    [
        'type'         => 'button',
        'class'        => 'close reminder-modal-close',
        'data-dismiss' => 'modal',
        'aria-label'   => get_string('close', 'core'),
    ]
);
echo html_writer::end_div();
echo html_writer::tag('div', '', ['class' => 'modal-body', 'id' => 'course-modal-body']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

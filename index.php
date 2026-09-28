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

require_once($CFG->dirroot . '/local/courseremainder_mails/classes/form/report_filter.php');
require_once($CFG->dirroot . '/local/courseremainder_mails/classes/table/reminder_table.php');
require_login();
$context = context_system::instance();

if (!is_siteadmin()) {
    throw new required_capability_exception(
        $context,
        'moodle/site:config',
        'nopermissions',
        ''
    );
}
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/courseremainder_mails/index.php'));
$PAGE->set_title(get_string('pluginname', 'local_courseremainder_mails'));
$PAGE->requires->js(new moodle_url('/local/courseremainder_mails/js/modelpopup.js'));
$PAGE->requires->css(new moodle_url('/local/courseremainder_mails/styles.css'));
$PAGE->set_heading(get_string('pluginname', 'local_courseremainder_mails'));
echo $OUTPUT->header();
$form = new \local_courseremainder_mails\form\report_filter();
/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/courseremainder_mails/index.php'));
}
/*
|--------------------------------------------------------------------------
| Read filters
|--------------------------------------------------------------------------
*/
$filters = [];
// Course.
$filters['courseid'] = optional_param_array('courseid', 0, PARAM_INT);
// User.
$filters['userid'] = optional_param_array('userid', 0, PARAM_INT);
// Status.
$status = optional_param('status', '', PARAM_RAW);
if ($status === '') {
    $filters['status'] = '';
} else {
    $filters['status'] = (int)$status;
}
// Dates.
$filters['fromdate'] = optional_param_array('fromdate', 0, PARAM_INT);
$filters['todate'] = optional_param_array('todate', 0, PARAM_INT);
/*
|--------------------------------------------------------------------------
| Form submitted
|--------------------------------------------------------------------------
*/
if ($data = $form->get_data()) {
    $filters['courseid'] = (int)$data->courseid;
    $filters['userid']   = (int)$data->userid;
    if ($data->status === '') {
        $filters['status'] = '';
    } else {
        $filters['status'] = (int)$data->status;
    }
    $filters['fromdate'] = !empty($data->fromdate) ? $data->fromdate : 0;
    $filters['todate']   = !empty($data->todate) ? $data->todate : 0;
}
/*
|--------------------------------------------------------------------------
| Preserve values in form
|--------------------------------------------------------------------------
*/
$form->set_data($filters);
$form->display();
/*
|--------------------------------------------------------------------------
| Table
|--------------------------------------------------------------------------
*/
$table = new \local_courseremainder_mails\table\reminder_table(
    'courseremainderreport'
);
/*
|--------------------------------------------------------------------------
| Base URL
|--------------------------------------------------------------------------
*/
$params = [];
if (!empty($filters['courseid'])) {
    $params['courseid'] = $filters['courseid'];
}
if (!empty($filters['userid'])) {
    $params['userid'] = $filters['userid'];
}
if ($filters['status'] !== '') {
    $params['status'] = $filters['status'];
}
if (!empty($filters['fromdate'])) {
    $params['fromdate'] = $filters['fromdate'];
}
if (!empty($filters['todate'])) {
    $params['todate'] = $filters['todate'];
}
$table->define_baseurl(
    new moodle_url(
        '/local/courseremainder_mails/index.php',
        $params
    )
);
/*
|--------------------------------------------------------------------------
| SQL
|--------------------------------------------------------------------------
*/
$table->setup_table($filters);
/*
|--------------------------------------------------------------------------
| Delete form
|--------------------------------------------------------------------------
*/
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => new moodle_url('/local/courseremainder_mails/delete.php'),
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'sesskey',
    'value' => sesskey(),
]);
$table->out(10, true);
echo html_writer::start_div('mt-3');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'class' => 'btn btn-danger',
    'name' => 'delete',
    'value' => get_string('delete'),
]);
echo '
<div class="modal fade" id="course-modal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

  <div class="modal-header">
    <h5 id="course-modal-label">Reminder Details</h5>

    <button
        type="button"
        class="close reminder-modal-close"
        data-dismiss="modal"
        aria-label="Close"
    >
        <span aria-hidden="true">&times;</span>
    </button>
</div>

            <div class="modal-body" id="course-modal-body"></div>

        </div>
    </div>
</div>';
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo $OUTPUT->footer();




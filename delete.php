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
 * Delete reminder logs (soft delete).
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/coursereminder_mails:manage', $context);
require_sesskey();

$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_url(new moodle_url('/local/coursereminder_mails/delete.php'));
$PAGE->set_title(get_string('pluginname', 'local_coursereminder_mails'));
$PAGE->set_heading(get_string('pluginname', 'local_coursereminder_mails'));

$ids = optional_param_array('selectedids', [], PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

if (empty($ids)) {
    redirect(
        new moodle_url('/local/coursereminder_mails/index.php'),
        get_string('selectatleastonerecord', 'local_coursereminder_mails'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

echo $OUTPUT->header();

if (!$confirm) {
    $count = count($ids);
    echo $OUTPUT->heading(get_string('deletehistorytitle', 'local_coursereminder_mails'));
    echo $OUTPUT->notification(
        get_string('confirmdeletehistory', 'local_coursereminder_mails', $count),
        \core\output\notification::NOTIFY_WARNING
    );

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => new moodle_url('/local/coursereminder_mails/delete.php'),
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => 1]);
    foreach ($ids as $id) {
        echo html_writer::empty_tag('input', [
            'type'  => 'hidden',
            'name'  => 'selectedids[]',
            'value' => $id,
        ]);
    }
    echo html_writer::start_div('mt-3');
    echo html_writer::empty_tag('input', [
        'type'  => 'submit',
        'class' => 'btn btn-danger',
        'value' => get_string('confirmdelete', 'local_coursereminder_mails'),
    ]);
    echo ' ';
    echo html_writer::link(
        new moodle_url('/local/coursereminder_mails/index.php'),
        get_string('cancel'),
        ['class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

global $DB, $USER;

$transaction = $DB->start_delegated_transaction();
try {
    foreach ($ids as $id) {
        if ($record = $DB->get_record('local_coursereminder_mails_log', ['id' => $id])) {
            $record->deleted = 1;
            $record->modifierid = $USER->id;
            $record->timemodified = time();
            $DB->update_record('local_coursereminder_mails_log', $record);
        }
    }
    $transaction->allow_commit();
} catch (Exception $e) {
    $transaction->rollback($e);
}

redirect(
    new moodle_url('/local/coursereminder_mails/index.php'),
    get_string('deletedhistory', 'local_coursereminder_mails'),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);

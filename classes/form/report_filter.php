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

namespace local_courseremainder_mails\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for filtering course reminder reports.
 *
 * @package local_courseremainder_mails
 * @copyright 2026 Your Organization
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter extends \moodleform {

    /**
     * Defines the report filter form.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;

        // Course.
        $courses = [
            0 => get_string('select_course', 'local_courseremainder_mails'),
        ];

        $courserecords = $DB->get_records(
            'course',
            ['visible' => 1],
            'fullname ASC',
            'id, fullname'
        );

        foreach ($courserecords as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            $courses[$course->id] = format_string($course->fullname);
        }

        $mform->addElement(
            'select',
            'courseid',
            get_string('course'),
            $courses
        );

        $mform->setDefault('courseid', 0);

        // User.
        $users = [
            0 => get_string('select_user', 'local_courseremainder_mails'),
        ];

        $studentroleid = $DB->get_field(
            'role',
            'id',
            ['shortname' => 'student']
        );

        if ($studentroleid) {
            $userrecords = $DB->get_records_sql(
                "SELECT DISTINCT
                        u.id,
                        u.firstname,
                        u.lastname,
                        u.email
                   FROM {user} u
                   JOIN {user_enrolments} ue ON ue.userid = u.id
                   JOIN {enrol} e ON e.id = ue.enrolid
                   JOIN {role_assignments} ra ON ra.userid = u.id
                  WHERE u.deleted = 0
                    AND u.suspended = 0
                    AND ue.status = 0
                    AND ra.roleid = :roleid
                    AND ra.contextid = (
                        SELECT ctx.id
                          FROM {context} ctx
                         WHERE ctx.contextlevel = :contextlevel
                           AND ctx.instanceid = e.courseid
                    )
               ORDER BY u.firstname, u.lastname",
                [
                    'roleid' => $studentroleid,
                    'contextlevel' => CONTEXT_COURSE,
                ]
            );

            foreach ($userrecords as $user) {
                $users[$user->id] = fullname($user) . ' (' . $user->email . ')';
            }
        }

        $mform->addElement('select', 'userid', get_string('user'), $users);
        $mform->setDefault('userid', 0);

        // Status.
        $status = [
            '' => get_string('select_status', 'local_courseremainder_mails'),
            1 => 'Success',
            0 => 'Failed',
        ];

        $mform->addElement(
            'select',
            'status',
            get_string('status', 'local_courseremainder_mails'),
            $status
        );

        $mform->setDefault('status', '');

        // From date (optional).
        $mform->addElement(
            'date_selector',
            'fromdate',
            get_string('from', 'local_courseremainder_mails'),
            ['optional' => true]
        );

        // Checkbox is unchecked by default.
        $mform->setDefault('fromdate', 0);

        // To date (optional).
        $mform->addElement(
            'date_selector',
            'todate',
            get_string('to', 'local_courseremainder_mails'),
            ['optional' => true]
        );

        // Checkbox is unchecked by default.
        $mform->setDefault('todate', 0);

        // Buttons.
        $this->add_action_buttons(
            false,
            get_string('applyfilter', 'local_courseremainder_mails')
        );

        $mform->addElement(
            'cancel',
            'reset',
            get_string('clearfilter', 'local_courseremainder_mails'),
            ['class' => 'crmbtn']
        );
    }
}


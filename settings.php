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
 * Plugin settings.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_coursereminder_mails',
        get_string('pluginname', 'local_coursereminder_mails')
    );

    $ADMIN->add('localplugins', $settings);

    // Enable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_coursereminder_mails/enabled',
        get_string('enabled', 'local_coursereminder_mails'),
        get_string('enabled_desc', 'local_coursereminder_mails'),
        1
    ));

    // Reminder days.
    $settings->add(new admin_setting_configtext(
        'local_coursereminder_mails/firstreminderdays',
        get_string('firstreminderdays', 'local_coursereminder_mails'),
        '',
        30,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_coursereminder_mails/secondreminderdays',
        get_string('secondreminderdays', 'local_coursereminder_mails'),
        '',
        15,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_coursereminder_mails/customreminderdays',
        get_string('customreminderdays', 'local_coursereminder_mails'),
        '',
        7,
        PARAM_INT
    ));

    // Reminder date source.
    $settings->add(new admin_setting_configselect(
        'local_coursereminder_mails/reminderdatebasedon',
        get_string('reminderdatebasedon', 'local_coursereminder_mails'),
        get_string('reminderdatebasedon_desc', 'local_coursereminder_mails'),
        'courseenddate',
        [
            'courseenddate'    => get_string('courseenddate', 'local_coursereminder_mails'),
            'enrolmentenddate' => get_string('enrolmentenddate', 'local_coursereminder_mails'),
        ]
    ));

    // Email subject.
    $settings->add(new admin_setting_configtext(
        'local_coursereminder_mails/emailsubject',
        get_string('emailsubject', 'local_coursereminder_mails'),
        '',
        get_string('defaultemailsubject', 'local_coursereminder_mails'),
        PARAM_TEXT
    ));

    // Email body.
    $settings->add(new admin_setting_configtextarea(
        'local_coursereminder_mails/emailbody',
        get_string('emailbody', 'local_coursereminder_mails'),
        '',
        get_string('defaultemailbody', 'local_coursereminder_mails')
    ));

    // Course/category selection.
    global $DB;

    // Only load categories that contain at least one course.
    $categories = $DB->get_records_sql(
        "SELECT DISTINCT cc.id, cc.name
           FROM {course_categories} cc
           JOIN {course} c ON c.category = cc.id
          WHERE c.id <> :siteid
       ORDER BY cc.sortorder ASC",
        ['siteid' => SITEID]
    );

    $courseoptions = [];
    foreach ($categories as $category) {
        $courses = $DB->get_records(
            'course',
            ['category' => $category->id],
            'fullname ASC',
            'id, fullname'
        );

        $categoryoptions = [];
        $categoryoptions['category_' . $category->id] = get_string(
            'allcoursesincategory',
            'local_coursereminder_mails',
            $category->name
        );

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }
            $categoryoptions['course_' . $course->id] = format_string($course->fullname);
        }

        if (count($categoryoptions) > 1) {
            $courseoptions[$category->name] = $categoryoptions;
        }
    }

    $settings->add(new admin_setting_configmultiselect(
        'local_coursereminder_mails/course_selection',
        get_string('courseselection', 'local_coursereminder_mails'),
        get_string('courseselection_desc', 'local_coursereminder_mails'),
        [],
        $courseoptions
    ));

    // Reports link.
    $settings->add(new admin_setting_heading(
        'local_coursereminder_mails/reports',
        get_string('reminderreports', 'local_coursereminder_mails'),
        html_writer::div(
            html_writer::link(
                new moodle_url('/local/coursereminder_mails/index.php'),
                get_string('viewreminderreports', 'local_coursereminder_mails'),
                ['class' => 'btn btn-primary']
            ),
            'mt-2'
        )
    ));
}

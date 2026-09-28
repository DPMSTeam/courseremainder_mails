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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage(
        'local_courseremainder_mails',
        get_string('pluginname', 'local_courseremainder_mails')
    );

    $ADMIN->add('localplugins', $settings);

    // Enable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_courseremainder_mails/enabled',
        get_string('enabled', 'local_courseremainder_mails'),
        '',
        1
    ));

    // First reminder.
    $settings->add(new admin_setting_configtext(
        'local_courseremainder_mails/firstremainderdays',
        get_string('firstremainderdays', 'local_courseremainder_mails'),
        '',
        30,
        PARAM_INT
    ));

    // Second reminder.
    $settings->add(new admin_setting_configtext(
        'local_courseremainder_mails/secondremainderdays',
        get_string('secondremainderdays', 'local_courseremainder_mails'),
        '',
        15,
        PARAM_INT
    ));

    // Custom reminder.
    $settings->add(new admin_setting_configtext(
        'local_courseremainder_mails/customremainderdays',
        get_string('customremainderdays', 'local_courseremainder_mails'),
        '',
        7,
        PARAM_INT
    ));

    // Reminder date based on.
    $settings->add(new admin_setting_configselect(
        'local_courseremainder_mails/reminderdatebasedon',
        get_string(
            'reminderdatebasedon',
            'local_courseremainder_mails'
        ),
        get_string(
            'reminderdatebasedon_desc',
            'local_courseremainder_mails'
        ),
        'courseenddate',
        [
            'courseenddate' => get_string(
                'courseenddate',
                'local_courseremainder_mails'
            ),
            'enrolmentenddate' => get_string(
                'enrolmentenddate',
                'local_courseremainder_mails'
            ),
        ]
    ));

    // Email subject.
    $settings->add(new admin_setting_configtext(
        'local_courseremainder_mails/emailsubject',
        get_string('emailsubject', 'local_courseremainder_mails'),
        '',
        'Course Expiry Reminder',
        PARAM_TEXT
    ));

    // Email body.
    $settings->add(new admin_setting_configtextarea(
        'local_courseremainder_mails/emailbody',
        get_string('emailbody', 'local_courseremainder_mails'),
        '',
        "Hello {fullname},\n\n"
        . "Your course {course} will end in {days} days.\n\n"
        . "Please complete your remaining activities before the course end date.\n\n"
        . "Thank you,\n\n"
        . "Regards,\n"
        . "Administrator"
    ));

    // Course and category selection.
    // One setting controls both categories and individual courses.
    global $DB;

    // Get all categories.
    $categories = $DB->get_records(
        'course_categories',
        null,
        'sortorder ASC',
        'id,name'
    );

    // Get all courses.
    $courses = $DB->get_records(
        'course',
        null,
        'fullname ASC',
        'id,fullname,category'
    );

    // Group courses by category.
    $coursesbycategory = [];

    foreach ($courses as $course) {
        // Do not include the site course.
        if ($course->id == SITEID) {
            continue;
        }

        if (!isset($coursesbycategory[$course->category])) {
            $coursesbycategory[$course->category] = [];
        }

        $coursesbycategory[$course->category][$course->id] = $course->fullname;
    }

    // Build grouped options.
    // Category_10 means the entire category.
    // Course_25 means an individual course.
    $courseoptions = [];

    foreach ($categories as $category) {
        if (empty($coursesbycategory[$category->id])) {
            continue;
        }

        $categoryoptions = [];

        // Category itself.
        // Selecting this includes all courses directly inside this category.
        $categoryoptions['category_' . $category->id] =
            'All courses in ' . $category->name;

        // Individual courses.
        foreach ($coursesbycategory[$category->id] as $courseid => $fullname) {
            $categoryoptions['course_' . $courseid] = $fullname;
        }

        // Create optgroup.
        $courseoptions[$category->name] = $categoryoptions;
    }

    // Single setting for both categories and courses.
    $settings->add(new admin_setting_configmultiselect(
        'local_courseremainder_mails/course_selection',
        'Courses / Categories for Reminders',
        'Select an entire category to include all courses in that category, '
            . 'or select individual courses. Only selected courses will be '
            . 'eligible for expiry reminder emails.',
        [],
        $courseoptions
    ));

    // View reminder reports.
    $settings->add(new admin_setting_heading(
        'local_courseremainder_mails/reports',
        'Reminder Reports',
        html_writer::div(
            html_writer::link(
                new moodle_url(
                    '/local/courseremainder_mails/index.php'
                ),
                'View Reminder Reports',
                [
                    'class' => 'btn btn-primary',
                ]
            ),
            'mt-2'
        )
    ));
}


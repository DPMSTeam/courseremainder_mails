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

namespace local_courseremainder_mails\task;
/**
 * Scheduled task for sending course reminder emails.
 */
class send_reminder extends \core\task\scheduled_task {

    /**
     * Get task name.
     */
    public function get_name() {

        return get_string(
            'pluginname',
            'local_courseremainder_mails'
        );
    }

    /**
     * Execute scheduled task.
     */
    public function execute() {

        global $DB;

        /*
        |--------------------------------------------------------------------------
        | Check whether plugin is enabled.
        |--------------------------------------------------------------------------
        */

        if (!get_config(
            'local_courseremainder_mails',
            'enabled'
        )) {

            mtrace('Course reminder disabled.');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Get student role.
        |--------------------------------------------------------------------------
        */

        $studentroleid = $DB->get_field(
            'role',
            'id',
            [
                'shortname' => 'student',
            ]
        );

        if (!$studentroleid) {

            mtrace('Student role not found.');

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Support user.
        |--------------------------------------------------------------------------
        */

        $supportuser = \core_user::get_support_user();

        /*
        |--------------------------------------------------------------------------
        | Reminder days.
        |--------------------------------------------------------------------------
        */

        $reminders = [

            'first' => (int)get_config(
                'local_courseremainder_mails',
                'firstremainderdays'
            ),

            'second' => (int)get_config(
                'local_courseremainder_mails',
                'secondremainderdays'
            ),

            'custom' => (int)get_config(
                'local_courseremainder_mails',
                'customremainderdays'
            ),

        ];

        /*
        |--------------------------------------------------------------------------
        | Reminder Date Based On
        |--------------------------------------------------------------------------
        |
        | courseenddate:
        |     Uses course.enddate.
        |
        | enrolmentenddate:
        |     Uses user_enrolments.timeend.
        |
        | Default:
        |     courseenddate.
        |
        |--------------------------------------------------------------------------
        */

        $reminderdatebasedon = get_config(
            'local_courseremainder_mails',
            'reminderdatebasedon',
        );

        if (empty($reminderdatebasedon)) {

            $reminderdatebasedon = 'courseenddate';
        }

        if (!in_array(
            $reminderdatebasedon,
            [
                'courseenddate',
                'enrolmentenddate',
            ],
            true
        )) {

            $reminderdatebasedon = 'courseenddate';
        }

        if ($reminderdatebasedon === 'courseenddate') {

            mtrace(
                'Reminder date source: Course End Date'
            );

        } else {

            mtrace(
                'Reminder date source: User Enrolment End Date'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get Selected Categories / Courses
        |--------------------------------------------------------------------------
        |
        | category_XX = entire category
        | course_XX   = individual course
        |
        |--------------------------------------------------------------------------
        */

        $selection = get_config(
            'local_courseremainder_mails',
            'course_selection',
        );

        $selection = !empty($selection)
            ? explode(',', $selection)
            : [];

        /*
        |--------------------------------------------------------------------------
        | Final list of eligible courses.
        |--------------------------------------------------------------------------
        */

        $eligiblecourseids = [];

        foreach ($selection as $item) {

            $item = trim($item);

            if (empty($item)) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Entire category selected.
            |--------------------------------------------------------------------------
            */

            if (strpos($item, 'category_') === 0) {

                $categoryid = (int)str_replace(
                    'category_',
                    '',
                    $item,
                );

                $categorycourses = $DB->get_records(
                    'course',
                    [
                        'category' => $categoryid,
                    ],
                    '',
                    'id',
                );

                foreach ($categorycourses as $categorycourse) {

                    if ($categorycourse->id == SITEID) {
                        continue;
                    }

                    $eligiblecourseids[] =
                        (int)$categorycourse->id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Individual course selected.
            |--------------------------------------------------------------------------
            */

            if (strpos($item, 'course_') === 0) {

                $courseid = (int)str_replace(
                    'course_',
                    '',
                    $item
                );

                if ($courseid != SITEID) {

                    $eligiblecourseids[] =
                        $courseid;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Remove duplicate course IDs.
        |--------------------------------------------------------------------------
        */

        $eligiblecourseids = array_values(
            array_unique($eligiblecourseids)
        );

        /*
        |--------------------------------------------------------------------------
        | Nothing selected = no emails.
        |--------------------------------------------------------------------------
        */

        if (empty($eligiblecourseids)) {

            mtrace(
                'No courses/categories selected for reminders.'
            );

            mtrace(
                'No reminder emails will be sent.'
            );

            return;
        }

        mtrace(
            'Eligible course IDs: '
            . implode(', ', $eligiblecourseids)
        );

        /*
        |--------------------------------------------------------------------------
        | Email configuration.
        |--------------------------------------------------------------------------
        */

        $emailbody = get_config(
            'local_courseremainder_mails',
            'emailbody'
        );

        $emailsubject = get_config(
            'local_courseremainder_mails',
            'emailsubject'
        );

        /*
        |--------------------------------------------------------------------------
        | Today's range for duplicate check.
        |--------------------------------------------------------------------------
        */

        $todaystart = strtotime(
            date('Y-m-d 00:00:00')
        );

        $todayend = strtotime(
            date('Y-m-d 23:59:59')
        );

        /*
        |--------------------------------------------------------------------------
        | Start transaction.
        |--------------------------------------------------------------------------
        */

        $transaction = $DB->start_delegated_transaction();

        try {

            $processeddays = [];

            /*
            |--------------------------------------------------------------------------
            | Process every reminder type.
            |--------------------------------------------------------------------------
            */

            foreach ($reminders as $type => $days) {

                if ($days <= 0) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Prevent duplicate reminder when two settings
                | have the same number of days.
                |--------------------------------------------------------------------------
                */

                if (isset($processeddays[$days])) {

                    mtrace(
                        "Skipping duplicate reminder for "
                        . "{$days} days ({$type})"
                    );

                    continue;
                }

                $processeddays[$days] = true;

                mtrace(
                    "=== Checking {$type} reminder "
                    . "({$days} days before expiry) ==="
                );

                /*
                |--------------------------------------------------------------------------
                | Calculate target date.
                |--------------------------------------------------------------------------
                */

                $targetdate = strtotime(
                    "+{$days} days"
                );

                $startofday = strtotime(
                    date(
                        'Y-m-d 00:00:00',
                        $targetdate
                    )
                );

                $endofday = strtotime(
                    date(
                        'Y-m-d 23:59:59',
                        $targetdate
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | Build eligible course SQL.
                |--------------------------------------------------------------------------
                */

                list($courseinsql, $courseparams) =
                    $DB->get_in_or_equal(
                        $eligiblecourseids,
                        SQL_PARAMS_NAMED,
                        'eligiblecourse'
                    );

                /*
                |--------------------------------------------------------------------------
                | COURSE END DATE
                |--------------------------------------------------------------------------
                |
                | When admin selected:
                |
                | Course End Date
                |
                | We first find courses whose course.enddate
                | matches the reminder date.
                |--------------------------------------------------------------------------
                */

                if ($reminderdatebasedon === 'courseenddate') {

                    $conditions = "
                        visible = 1

                        AND id <> :siteid

                        AND id $courseinsql

                        AND enddate >= :startday

                        AND enddate <= :endday
                    ";

                    $params = array_merge(

                        [
                            'siteid' => SITEID,

                            'startday' => $startofday,

                            'endday' => $endofday,
                        ],

                        $courseparams,
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Get eligible courses.
                    |--------------------------------------------------------------------------
                    */

                    $courses = $DB->get_records_select(
                        'course',
                        $conditions,
                        $params,
                    );

                    mtrace(
                        'Eligible courses found: '
                        . count($courses)
                    );

                    if (empty($courses)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Process courses.
                    |--------------------------------------------------------------------------
                    */

                    foreach ($courses as $course) {

                        mtrace(
                            "Processing Course: "
                            . $course->fullname
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Get enrolled students.
                        |--------------------------------------------------------------------------
                        */

                        $users = $DB->get_records_sql(

                            "SELECT DISTINCT
                                u.*,
                                ue.enrolid

                             FROM {user} u

                             JOIN {user_enrolments} ue
                               ON ue.userid = u.id

                             JOIN {enrol} e
                               ON e.id = ue.enrolid

                             JOIN {context} ctx
                               ON ctx.instanceid = e.courseid

                              AND ctx.contextlevel = :contextlevel

                             JOIN {role_assignments} ra
                               ON ra.userid = u.id

                              AND ra.contextid = ctx.id

                             WHERE e.courseid = :courseid

                               AND ra.roleid = :roleid

                               AND ue.status = 0

                               AND u.deleted = 0

                               AND u.suspended = 0",

                            [

                                'contextlevel' =>
                                    CONTEXT_COURSE,

                                'courseid' =>
                                    $course->id,

                                'roleid' =>
                                    $studentroleid,

                            ]
                        );

                        mtrace(
                            "  Students found: "
                            . count($users)
                        );

                        if (empty($users)) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Process students.
                        |--------------------------------------------------------------------------
                        */

                        foreach ($users as $user) {

                            $this->process_reminder_user(
                                $user,
                                $course,
                                $days,
                                $type,
                                $emailsubject,
                                $emailbody,
                                $supportuser,
                                $todaystart,
                                $todayend,
                            );
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | USER ENROLMENT END DATE
                |--------------------------------------------------------------------------
                |
                | When admin selected:
                |
                | User Enrolment End Date
                |
                | We find individual user enrolments whose
                | user_enrolments.timeend matches the reminder date.
                |
                | This means different users can have different
                | expiry dates for the same course.
                |--------------------------------------------------------------------------
                */

                if ($reminderdatebasedon === 'enrolmentenddate') {

                    $enrolments = $DB->get_records_sql(

                        "SELECT DISTINCT

                            ue.id AS userenrolmentid,

                            ue.userid,

                            ue.enrolid,

                            ue.timeend,

                            e.courseid

                         FROM {user_enrolments} ue

                         JOIN {enrol} e
                           ON e.id = ue.enrolid

                         JOIN {course} c
                           ON c.id = e.courseid

                         JOIN {user} u
                           ON u.id = ue.userid

                         JOIN {context} ctx
                           ON ctx.instanceid = e.courseid

                          AND ctx.contextlevel = :contextlevel

                         JOIN {role_assignments} ra
                           ON ra.userid = u.id

                          AND ra.contextid = ctx.id

                         WHERE ue.status = 0

                           AND ue.timeend > 0

                           AND ue.timeend >= :startday

                           AND ue.timeend <= :endday

                           AND c.visible = 1

                           AND c.id <> :siteid

                           AND c.id $courseinsql

                           AND u.deleted = 0

                           AND u.suspended = 0

                           AND ra.roleid = :roleid",

                        array_merge(

                            [

                                'contextlevel' =>
                                    CONTEXT_COURSE,

                                'startday' =>
                                    $startofday,

                                'endday' =>
                                    $endofday,

                                'siteid' =>
                                    SITEID,

                                'roleid' =>
                                    $studentroleid,

                            ],

                            $courseparams
                        )
                    );

                    mtrace(
                        'User enrolments found: '
                        . count($enrolments)
                    );

                    if (empty($enrolments)) {
                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Process individual enrolments.
                    |--------------------------------------------------------------------------
                    */

                    foreach ($enrolments as $enrolment) {

                        /*
                        |--------------------------------------------------------------------------
                        | Get course.
                        |--------------------------------------------------------------------------
                        */

                        $course = $DB->get_record(
                            'course',
                            [
                                'id' =>
                                    $enrolment->courseid,

                                'visible' => 1,
                            ],
                            '*',
                            MUST_EXIST
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Get user.
                        |--------------------------------------------------------------------------
                        */

                        $user = $DB->get_record(
                            'user',
                            [
                                'id' =>
                                    $enrolment->userid,

                                'deleted' => 0,
                            ],
                            '*',
                            MUST_EXIST
                        );

                        mtrace(
                            "Processing Course: "
                            . $course->fullname
                            . " | User: "
                            . $user->email
                        );

                        /*
                        |--------------------------------------------------------------------------
                        | Process reminder.
                        |--------------------------------------------------------------------------
                        */

                        $this->process_reminder_user(
                            $user,
                            $course,
                            $days,
                            $type,
                            $emailsubject,
                            $emailbody,
                            $supportuser,
                            $todaystart,
                            $todayend,
                            $enrolment->enrolid,
                        );
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Commit.
            |--------------------------------------------------------------------------
            */

            $transaction->allow_commit();

            mtrace(
                "=================================================="
            );

            mtrace(
                "Course Reminder task completed successfully."
            );

            mtrace(
                "=================================================="
            );

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | Rollback.
            |--------------------------------------------------------------------------
            */

            $transaction->rollback($e);

            mtrace(
                "=================================================="
            );

            mtrace(
                "Course Reminder task FAILED!"
            );

            mtrace(
                $e->getMessage()
            );

            mtrace(
                $e->getTraceAsString()
            );

            mtrace(
                "=================================================="
            );

            throw $e;
        }
    }

    /**
     * Process and send reminder to a user.
     *
     * @param object $user
     * @param object $course
     * @param int $days
     * @param string $type
     * @param string $emailsubject
     * @param string $emailbody
     * @param object $supportuser
     * @param int $todaystart
     * @param int $todayend
     * @param int|null $enrolid
     */
    private function process_reminder_user(
        $user,
        $course,
        $days,
        $type,
        $emailsubject,
        $emailbody,
        $supportuser,
        $todaystart,
        $todayend,
        $enrolid = null,
    ) {

        global $DB;

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate reminder on the same day.
        |--------------------------------------------------------------------------
        */

        $alreadysent = $DB->record_exists_sql(

            "SELECT 1

               FROM {local_courseremainder_log}

              WHERE userid = :userid

                AND courseid = :courseid

                AND status = 1

                AND timesent BETWEEN :start AND :end",

            [

                'userid' =>
                    $user->id,

                'courseid' =>
                    $course->id,

                'start' =>
                    $todaystart,

                'end' =>
                    $todayend,
            ]
        );

        if ($alreadysent) {

            mtrace(
                "  Skipping "
                . "{$user->email}"
                . " - already received reminder today"
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Check course completion.
        |--------------------------------------------------------------------------
        */

        $progress =
            \core_completion\progress::get_course_progress_percentage(
                $course,
                $user->id,
            );

        if (
            $progress !== null
            && $progress >= 100
        ) {

            mtrace(
                "  Skipping "
                . "{$user->email}"
                . " - course completed"
            );

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Prepare email.
        |--------------------------------------------------------------------------
        */

        $subject = $emailsubject;

        $message = str_replace(

            [
                '{fullname}',
                '{course}',
                '{days}',
            ],

            [
                fullname($user),
                $course->fullname,
                $days,
            ],

            $emailbody
        );

        /*
        |--------------------------------------------------------------------------
        | Send email.
        |--------------------------------------------------------------------------
        */

        $sent = email_to_user(

            $user,

            $supportuser,

            $subject,

            $message,

            text_to_html($message)
        );

        /*
        |--------------------------------------------------------------------------
        | Log email.
        |--------------------------------------------------------------------------
        */

        $now = time();

        /*
        | If enrolid wasn't supplied, try to get
        | the user's active enrolment for this course.
        */

        if (empty($enrolid)) {

            $enrolid = $DB->get_field_sql(

                "SELECT ue.enrolid

                   FROM {user_enrolments} ue

                   JOIN {enrol} e
                     ON e.id = ue.enrolid

                  WHERE ue.userid = :userid

                    AND e.courseid = :courseid

                    AND ue.status = 0

                  ORDER BY ue.id ASC",

                [

                    'userid' =>
                        $user->id,

                    'courseid' =>
                        $course->id,
                ]
            );
        }

        $log = (object)[

            'userid' =>
                $user->id,

            'email' =>
                $user->email,

            'courseid' =>
                $course->id,

            'enrol_id' =>
                $enrolid,

            'status' =>
                $sent ? 1 : 0,

            'day' =>
                $days,

            'expairy_notification_type' =>
                $type,

            'deleted' =>
                0,

            'modifierid' =>
                $supportuser->id,

            'timesent' =>
                $now,

            'timecreated' =>
                $now,

            'timemodified' =>
                $now,
        ];

        $DB->insert_record(
            'local_courseremainder_log',
            $log,
        );

        mtrace(

            "  "
            . ($sent
                ? "Sent"
                : "Failed")

            . " to: "

            . $user->email

            . " ({$type} - "
            . "{$days} days)"

        );
    }
}


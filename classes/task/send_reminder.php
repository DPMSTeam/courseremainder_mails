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
 * Scheduled task for sending course reminder emails.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursereminder_mails\task;

/**
 * Send reminder task.
 */
class send_reminder extends \core\task\scheduled_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_coursereminder_mails');
    }

    /**
     * Execute scheduled task.
     */
    public function execute() {
        global $DB;

        if (!get_config('local_coursereminder_mails', 'enabled')) {
            mtrace(get_string('taskdisabled', 'local_coursereminder_mails'));
            return;
        }

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        if (!$studentroleid) {
            mtrace(get_string('tasknorole', 'local_coursereminder_mails'));
            return;
        }

        $supportuser = \core_user::get_support_user();

        $reminders = [
            'first'  => (int)get_config('local_coursereminder_mails', 'firstreminderdays'),
            'second' => (int)get_config('local_coursereminder_mails', 'secondreminderdays'),
            'custom' => (int)get_config('local_coursereminder_mails', 'customreminderdays'),
        ];

        $reminderdatebasedon = get_config('local_coursereminder_mails', 'reminderdatebasedon');
        if (empty($reminderdatebasedon) ||
            !in_array($reminderdatebasedon, ['courseenddate', 'enrolmentenddate'], true)) {
            $reminderdatebasedon = 'courseenddate';
        }

        // Resolve eligible courses.
        $selection = get_config('local_coursereminder_mails', 'course_selection');
        $selection = !empty($selection) ? explode(',', $selection) : [];
        $eligiblecourseids = [];

        foreach ($selection as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            if (strpos($item, 'category_') === 0) {
                $categoryid = (int)str_replace('category_', '', $item);
                $categorycourses = $DB->get_records('course', ['category' => $categoryid], '', 'id');
                foreach ($categorycourses as $cc) {
                    if ($cc->id != SITEID) {
                        $eligiblecourseids[] = (int)$cc->id;
                    }
                }
            }
            if (strpos($item, 'course_') === 0) {
                $courseid = (int)str_replace('course_', '', $item);
                if ($courseid != SITEID) {
                    $eligiblecourseids[] = $courseid;
                }
            }
        }
        $eligiblecourseids = array_values(array_unique($eligiblecourseids));

        if (empty($eligiblecourseids)) {
            mtrace(get_string('tasknocourseselected', 'local_coursereminder_mails'));
            return;
        }

        $emailbody = get_config('local_coursereminder_mails', 'emailbody');
        $emailsubject = get_config('local_coursereminder_mails', 'emailsubject');

        $timezone = \core_date::get_user_timezone();
        $todaystart = usergetmidnight(time(), $timezone);
        $todayend = $todaystart + DAYSECS - 1;

        $processeddays = [];

        foreach ($reminders as $type => $days) {
            if ($days <= 0) {
                continue;
            }
            if (isset($processeddays[$days])) {
                continue;
            }
            $processeddays[$days] = true;

            $targetdate = strtotime("+{$days} days");
            $startofday = strtotime(date('Y-m-d 00:00', $targetdate));
            $endofday = strtotime(date('Y-m-d 23:59', $targetdate));

            list($courseinsql, $courseparams) = $DB->get_in_or_equal(
                $eligiblecourseids,
                SQL_PARAMS_NAMED,
                'eligiblecourse'
            );

            if ($reminderdatebasedon === 'courseenddate') {
                $conditions = "visible = 1
                               AND id <> :siteid
                               AND id $courseinsql
                               AND enddate >= :startday
                               AND enddate <= :endday";
                $params = array_merge([
                    'siteid' => SITEID,
                    'startday' => $startofday,
                    'endday' => $endofday,
                ], $courseparams);

                $courses = $DB->get_records_select('course', $conditions, $params);
                if (empty($courses)) {
                    continue;
                }

                foreach ($courses as $course) {
                    $users = $DB->get_records_sql(
                        "SELECT DISTINCT u.*, ue.enrolid
                           FROM {user} u
                           JOIN {user_enrolments} ue ON ue.userid = u.id
                           JOIN {enrol} e ON e.id = ue.enrolid
                           JOIN {context} ctx ON ctx.instanceid = e.courseid
                                             AND ctx.contextlevel = :contextlevel
                           JOIN {role_assignments} ra ON ra.userid = u.id
                                                     AND ra.contextid = ctx.id
                          WHERE e.courseid = :courseid
                            AND ra.roleid = :roleid
                            AND ue.status = 0
                            AND u.deleted = 0
                            AND u.suspended = 0",
                        [
                            'contextlevel' => CONTEXT_COURSE,
                            'courseid' => $course->id,
                            'roleid' => $studentroleid,
                        ]
                    );

                    foreach ($users as $user) {
                        $this->process_reminder_user(
                            $user, $course, $days, $type,
                            $emailsubject, $emailbody, $supportuser,
                            $todaystart, $todayend
                        );
                    }
                }
            }

            if ($reminderdatebasedon === 'enrolmentenddate') {
                $enrolments = $DB->get_records_sql(
                    "SELECT DISTINCT ue.id AS userenrolmentid,
                            ue.userid, ue.enrolid, ue.timeend, e.courseid
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                       JOIN {course} c ON c.id = e.courseid
                       JOIN {user} u ON u.id = ue.userid
                       JOIN {context} ctx ON ctx.instanceid = e.courseid
                                         AND ctx.contextlevel = :contextlevel
                       JOIN {role_assignments} ra ON ra.userid = u.id
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
                    array_merge([
                        'contextlevel' => CONTEXT_COURSE,
                        'startday' => $startofday,
                        'endday' => $endofday,
                        'siteid' => SITEID,
                        'roleid' => $studentroleid,
                    ], $courseparams)
                );

                foreach ($enrolments as $enrolment) {
                    $course = $DB->get_record('course', ['id' => $enrolment->courseid, 'visible' => 1], '*', MUST_EXIST);
                    $user = $DB->get_record('user', ['id' => $enrolment->userid, 'deleted' => 0], '*', MUST_EXIST);

                    $this->process_reminder_user(
                        $user, $course, $days, $type,
                        $emailsubject, $emailbody, $supportuser,
                        $todaystart, $todayend, $enrolment->enrolid
                    );
                }
            }
        }

        mtrace(get_string('taskcompleted', 'local_coursereminder_mails'));
    }

    /**
     * Send a reminder for one user and log it.
     */
    private function process_reminder_user(
        $user, $course, $days, $type,
        $emailsubject, $emailbody, $supportuser,
        $todaystart, $todayend, $enrolid = null
    ) {
        global $DB;

        $alreadysent = $DB->record_exists_sql(
            "SELECT 1
            FROM {local_coursereminder_mails_log}
            WHERE userid = :userid
                AND courseid = :courseid
                AND day = :days
                AND expairy_notification_type = :type
                AND status = 1
                AND deleted = 0
                AND timesent BETWEEN :start AND :end",
            [
                'userid' => $user->id,
                'courseid' => $course->id,
                'days' => $days,
                'type' => $type,
                'start' => $todaystart,
                'end' => $todayend,
            ]
        );

        if ($alreadysent) {
            mtrace(get_string('taskskipalready', 'local_coursereminder_mails', ['email' => $user->email]));
            return;
        }

        $progress = \core_completion\progress::get_course_progress_percentage($course, $user->id);
        if ($progress !== null && $progress >= 100) {
            mtrace(get_string('taskskipcompleted', 'local_coursereminder_mails', ['email' => $user->email]));
            return;
        }

        $subject = $emailsubject;
        $message = str_replace(
            ['{fullname}', '{course}', '{days}'],
            [fullname($user), $course->fullname, $days],
            $emailbody
        );

        // Send email OUTSIDE any transaction.
        $sent = email_to_user($user, $supportuser, $subject, $message, text_to_html($message));

        if (empty($enrolid)) {
            $enrolid = $DB->get_field_sql(
                "SELECT ue.enrolid
                   FROM {user_enrolments} ue
                   JOIN {enrol} e ON e.id = ue.enrolid
                  WHERE ue.userid = :userid
                    AND e.courseid = :courseid
                    AND ue.status = 0
               ORDER BY ue.id ASC",
                ['userid' => $user->id, 'courseid' => $course->id]
            );
        }

        $now = time();
        $log = (object)[
            'userid' => $user->id,
            'email' => $user->email,
            'courseid' => $course->id,
            'enrol_id' => $enrolid ?: 0,
            'status' => $sent ? 1 : 0,
            'day' => $days,
            'expairy_notification_type' => $type,
            'deleted' => 0,
            'modifierid' => $supportuser->id,
            'timesent' => $now,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $DB->insert_record('local_coursereminder_mails_log', $log);

        $a = (object)['email' => $user->email, 'type' => $type, 'days' => $days];
        mtrace($sent
            ? get_string('tasksent', 'local_coursereminder_mails', $a)
            : get_string('taskfailed', 'local_coursereminder_mails', $a)
        );
    }
}

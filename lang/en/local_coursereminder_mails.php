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
 * Language strings for local_coursereminder_mails.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allcoursesincategory'] = 'All courses in {$a}';
$string['applyfilter'] = 'Apply filter';
$string['cancel'] = 'Cancel';
$string['clearfilter'] = 'Clear filter';
$string['confirmdelete'] = 'Confirm delete';
$string['confirmdeletehistory'] = 'Are you sure you want to delete {$a} selected reminder log(s)?';
$string['count'] = 'Sent notifications';
$string['courseenddate'] = 'Course end date';
$string['coursereminder_mails:manage'] = 'Manage course reminder history';
$string['coursereminder_mails:view'] = 'View course reminder history';
$string['courseselection'] = 'Courses / categories for reminders';
$string['courseselection_desc'] = 'Select an entire category to include all courses in that category, or select individual courses. Only selected courses will be eligible for expiry reminder emails.';
$string['customreminderdays'] = 'Custom reminder days';
$string['days'] = 'Send reminder after (days)';
$string['defaultemailbody'] = "Hello {fullname},\n\nYour course {course} will end in {days} days.\n\nPlease complete your remaining activities before the course end date.\n\nThank you,\n\nRegards,\nAdministrator";
$string['defaultemailsubject'] = 'Course expiry reminder';
$string['deletedhistory'] = 'Selected reminder logs have been deleted.';
$string['deletehistorytitle'] = 'Delete reminder logs';
$string['emailbody'] = 'Email body';
$string['emailsubject'] = 'Email subject';
$string['enabled'] = 'Enable reminder';
$string['enabled_desc'] = 'Enable or disable the course reminder email task.';
$string['enrolmentenddate'] = 'User enrolment end date';
$string['failed'] = 'Failed';
$string['firstreminderdays'] = 'First reminder days';
$string['from'] = 'From date';
$string['loadremindererror'] = 'Unable to load reminder details.';
$string['noreminderhistory'] = 'No reminder history found.';
$string['pluginname'] = 'Course Reminder Mails';
$string['privacy:metadata:log'] = 'The plugin stores course reminder delivery history.';
$string['privacy:metadata:log:courseid'] = 'The course the reminder relates to.';
$string['privacy:metadata:log:day'] = 'The reminder interval in days.';
$string['privacy:metadata:log:email'] = 'The recipient email address.';
$string['privacy:metadata:log:enrolid'] = 'The enrolment record associated with the reminder.';
$string['privacy:metadata:log:status'] = 'Whether delivery succeeded.';
$string['privacy:metadata:log:timecreated'] = 'The time the log record was created.';
$string['privacy:metadata:log:timesent'] = 'The time the reminder was processed.';
$string['privacy:metadata:log:type'] = 'The reminder type.';
$string['privacy:metadata:log:userid'] = 'The user the reminder relates to.';
$string['privacy:path'] = 'Reminder history';
$string['reminderdatebasedon'] = 'Reminder date based on';
$string['reminderdatebasedon_desc'] = 'Choose whether reminder emails should be calculated from the course end date or the user enrolment end date.';
$string['reminderdays'] = 'Days';
$string['reminderhistory'] = 'Reminder history';
$string['reminderreports'] = 'Reminder reports';
$string['reminderstatus'] = 'Status';
$string['remindertype'] = 'Reminder type';
$string['reset'] = 'Reset';
$string['search'] = 'Search';
$string['secondreminderdays'] = 'Second reminder days';
$string['select_course'] = 'Select course';
$string['select_status'] = 'Select status';
$string['select_user'] = 'Select user';
$string['sent'] = 'Sent';
$string['senttime'] = 'Sent time';
$string['status'] = 'Status';
$string['success'] = 'Sent';
$string['taskcompleted'] = 'Course reminder task completed successfully.';
$string['taskdatesource'] = 'Reminder date source: {$a}';
$string['taskdisabled'] = 'Course reminder is disabled.';
$string['taskeligiblecourses'] = 'Eligible course IDs: {$a}';
$string['taskfailed'] = 'Failed to send to {$a->email} ({$a->type} - {$a->days} days)';
$string['tasknocourseselected'] = 'No courses or categories selected. No reminder emails will be sent.';
$string['tasknorole'] = 'Student role not found.';
$string['tasksent'] = 'Sent to {$a->email} ({$a->type} - {$a->days} days)';
$string['taskskipalready'] = 'Skipping {$a->email} - already received reminder today.';
$string['taskskipcompleted'] = 'Skipping {$a->email} - course completed.';
$string['to'] = 'To date';
$string['viewreminderreports'] = 'View reminder reports';

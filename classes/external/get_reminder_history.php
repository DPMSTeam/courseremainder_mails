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
 * External function for retrieving reminder history.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursereminder_mails\external;


use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_multiple_structure;
use core_external\external_single_structure;

/**
 * Retrieve reminder history for one user and course.
 */
class get_reminder_history extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid'   => new external_value(PARAM_INT, 'User ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Fetch reminder history after checking permissions.
     *
     * @param int $userid
     * @param int $courseid
     * @return array
     */
    public static function execute($userid, $courseid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid'   => $userid,
            'courseid' => $courseid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/coursereminder_mails:view', $context);

        $records = $DB->get_records(
            'local_coursereminder_mails_log',
            [
                'userid'   => $params['userid'],
                'courseid' => $params['courseid'],
                'deleted'  => 0,
            ],
            'timesent DESC',
            'id, expairy_notification_type, day, status, timesent'
        );

        $result = [];
        foreach ($records as $record) {
            $result[] = [
                'type'    => $record->expairy_notification_type,
                'day'     => (int)$record->day,
                'status'  => (int)$record->status,
                'timesent' => userdate($record->timesent),
            ];
        }
        return $result;
    }

    /**
     * Return structure.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'type'     => new external_value(PARAM_TEXT, 'Reminder type'),
                'day'      => new external_value(PARAM_INT, 'Reminder days'),
                'status'   => new external_value(PARAM_INT, 'Delivery status'),
                'timesent' => new external_value(PARAM_TEXT, 'Sent time'),
            ])
        );
    }
}

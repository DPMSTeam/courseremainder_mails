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
 * Privacy API implementation.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursereminder_mails\privacy;


use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\metadata\provider as metadata_provider;

/**
 * Describes and manages personal data stored by this plugin.
 */
class provider implements metadata_provider, plugin_provider {

    /**
     * Describe personal data stored by the plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_coursereminder_mails_log',
            [
                'userid' => 'privacy:metadata:log:userid',
                'email' => 'privacy:metadata:log:email',
                'courseid' => 'privacy:metadata:log:courseid',
                'enrol_id' => 'privacy:metadata:log:enrolid',
                'day' => 'privacy:metadata:log:day',
                'expairy_notification_type' => 'privacy:metadata:log:type',
                'status' => 'privacy:metadata:log:status',
                'timesent' => 'privacy:metadata:log:timesent',
                'timecreated' => 'privacy:metadata:log:timecreated',
            ],
            'privacy:metadata:log'
        );
        return $collection;
    }

    /**
     * Find contexts containing a user's data.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $contextlist = new contextlist();

        if ($DB->record_exists('local_coursereminder_mails_log', ['userid' => $userid])) {
            $contextlist->add_context(\context_system::instance());
        }
        return $contextlist;
    }

    /**
     * Export a user's plugin data.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $records = $DB->get_records(
            'local_coursereminder_mails_log',
            ['userid' => $userid],
            'timesent ASC'
        );

        if (!$records) {
            return;
        }

        $export = [];
        foreach ($records as $record) {
            $export[] = (object)[
                'email' => $record->email,
                'courseid' => $record->courseid,
                'day' => $record->day,
                'type' => $record->expairy_notification_type,
                'status' => $record->status,
                'timesent' => transform::datetime($record->timesent),
                'timecreated' => transform::datetime($record->timecreated),
            ];
        }

        writer::with_context(\context_system::instance())->export_data(
            [get_string('privacy:path', 'local_coursereminder_mails')],
            (object)['records' => $export]
        );
    }

    /**
     * Delete all plugin data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('local_coursereminder_mails_log');
        }
    }

    /**
     * Delete data for a user in approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel === CONTEXT_SYSTEM) {
                $DB->delete_records(
                    'local_coursereminder_mails_log',
                    ['userid' => $contextlist->get_user()->id]
                );
            }
        }
    }
}

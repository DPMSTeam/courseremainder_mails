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

namespace local_courseremainder_mails\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');
use html_writer;
/**
 * Table for displaying course reminder users.
 */
class reminder_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'select',
            'fullname',
            'email',
            'course',
            'count',
        ]);

        $this->define_headers([
            html_writer::checkbox('selectall', '', false, '', ['id' => 'selectall']),
            get_string('user'),
            get_string('email'),
            get_string('course'),
            get_string('count', 'local_courseremainder_mails'),
        ]);

        $this->collapsible(false);
        $this->pageable(true);
        $this->pagesize(10, true);
        $this->no_sorting('select');
        $this->no_sorting('count');

        // Optional: Set default sorting (e.g., by user name).
        $this->sortable(true, 'fullname', SORT_ASC);

        // Optional: Add CSS class.
        $this->set_attribute('class', 'generaltable courseremainder-table');
    }

    /**
     * Select all checkbox header.
     */
    private function get_select_all_checkbox() {

        return \html_writer::checkbox(
            'selectall',
            '',
            false,
            '',
            [
                'id' => 'selectall',
                'onclick' => 'M.util.select_all("selectall", "selectedids[]");',
            ]
        );
    }
    /**
     * Checkbox column.
     */
    public function col_select($row) {

        return html_writer::checkbox(
            'selectedids[]',
            $row->id,
            false,
            '',
            [
                'class' => 'selectitem',
            ]
        );
    }
    /**
     * User fullname.
     */
    public function col_fullname($row) {
        return fullname($row);
    }

    /**
     * Email.
     */
    public function col_email($row) {
        return s($row->email);
    }
    /**
     * Display the reminder count.
     *
     * @param object $row Table row data.
     * @return string Reminder count.
     */
    public function col_count($row) {
        global $DB;

        $count = $DB->count_records(
            'local_courseremainder_mails_log',
            [
                'userid' => $row->userid,
                'courseid' => $row->courseid,
            ]
        );

        return html_writer::tag(
            'a',
            $count,
            [
                'href' => 'javascript:void(0)',
                'class' => 'view-reminder-details',
                'data-userid' => $row->userid,
                'data-courseid' => $row->courseid,
            ]
        );
    }
    /**
     * Course.
     */
    public function col_course($row) {
        return format_string($row->course);
    }

    /**
     * Status.
     */
    public function col_status($row) {

        if ((int)$row->status === 1) {
            return html_writer::span('Success', 'badge bg-success');
        }

        return html_writer::span('Failed', 'badge bg-danger');
    }

    /**
     * Sent date.
     */
    public function col_timesent($row) {
        return userdate($row->timesent);
    }

    /**
     * Configure SQL query.
     *
     * @param array $filters
     */
    public function setup_table(array $filters = []) {

        $fields = "
            l.id,
            l.userid,
            l.email,
            l.courseid,
            l.status,
            l.timesent,
            u.firstname,
            u.lastname,
            c.fullname AS course
        ";

        $from = "
            {local_courseremainder_mails_log} l
            INNER JOIN (
                SELECT userid, courseid, MAX(timesent) AS latestsent
                FROM {local_courseremainder_mails_log}
                WHERE deleted = 0
                GROUP BY userid, courseid
            ) latest
                ON latest.userid = l.userid
               AND latest.courseid = l.courseid
               AND latest.latestsent = l.timesent

            INNER JOIN {user} u
                ON u.id = l.userid

            INNER JOIN {course} c
                ON c.id = l.courseid
        ";

        $where = "l.deleted = 0";
        $params = [];

        // Course.
        if (!empty($filters['courseid'])) {
            $where .= " AND l.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        // User.
        if (!empty($filters['userid'])) {
            $where .= " AND l.userid = :userid";
            $params['userid'] = $filters['userid'];
        }

        // Status.
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where .= " AND l.status = :status";
            $params['status'] = (int)$filters['status'];
        }

        // From date.
        if (!empty($filters['fromdate'])) {
            $where .= " AND l.timesent >= :fromdate";
            $params['fromdate'] = $filters['fromdate'];
        }

        // To date.
        if (!empty($filters['todate'])) {
            $where .= " AND l.timesent <= :todate";
            $params['todate'] = $filters['todate'] + 86399;
        }
        $this->set_sql(
            $fields,
            $from,
            $where,
            $params
        );
    }
}


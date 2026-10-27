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
 * Reminder report table.
 *
 * @package    local_coursereminder_mails
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_coursereminder_mails\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

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
            \html_writer::checkbox('selectall', '', false, '', ['id' => 'selectall']),
            get_string('user'),
            get_string('email'),
            get_string('course'),
            get_string('count', 'local_coursereminder_mails'),
        ]);

        $this->collapsible(false);
        $this->pageable(true);
        $this->pagesize(10, true);
        $this->no_sorting('select');
        $this->no_sorting('count');
        $this->sortable(true, 'fullname', SORT_ASC);
        $this->set_attribute('class', 'generaltable coursereminder-table');
    }

    /**
     * Checkbox column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_select($row) {
        return \html_writer::checkbox(
            'selectedids[]',
            $row->logid,
            false,
            '',
            ['class' => 'selectitem']
        );
    }

    /**
     * Fullname column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_fullname($row) {
        return fullname($row);
    }

    /**
     * Email column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_email($row) {
        return s($row->email);
    }

    /**
     * Reminder count column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_count($row) {
        global $DB;

        $count = $DB->count_records(
            'local_coursereminder_mails_log',
            [
                'userid'   => $row->userid,
                'courseid' => $row->courseid,
                'deleted'  => 0,
            ]
        );

        return \html_writer::tag(
            'a',
            $count,
            [
                'href'          => '#',
                'class'         => 'view-reminder-details',
                'data-userid'   => $row->userid,
                'data-courseid' => $row->courseid,
            ]
        );
    }

    /**
     * Course column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_course($row) {
        return format_string($row->course);
    }

    /**
     * Configure SQL query.
     *
     * @param array $filters
     */
    public function setup_table(array $filters = []) {
        global $DB;

        // Use all name fields for fullname().
        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false);

        $fields = "
            l.id AS logid,
            l.userid,
            l.email,
            l.courseid,
            l.status,
            l.timesent,
            {$userfields->selects},
            c.fullname AS course
        ";

        $from = "
            {local_coursereminder_mails_log} l
            INNER JOIN (
                SELECT userid, courseid, MAX(timesent) AS latestsent
                  FROM {local_coursereminder_mails_log}
                 WHERE deleted = 0
              GROUP BY userid, courseid
            ) latest
                ON latest.userid = l.userid
               AND latest.courseid = l.courseid
               AND latest.latestsent = l.timesent
            INNER JOIN {user} u ON u.id = l.userid
            INNER JOIN {course} c ON c.id = l.courseid
        ";

        $where = "l.deleted = 0";
        $params = $userfields->params;

        if (!empty($filters['courseid'])) {
            $where .= " AND l.courseid = :courseid";
            $params['courseid'] = $filters['courseid'];
        }

        if (!empty($filters['userid'])) {
            $where .= " AND l.userid = :userid";
            $params['userid'] = $filters['userid'];
        }

        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where .= " AND l.status = :status";
            $params['status'] = (int)$filters['status'];
        }

        if (!empty($filters['fromdate'])) {
            $where .= " AND l.timesent >= :fromdate";
            $params['fromdate'] = $filters['fromdate'];
        }

        if (!empty($filters['todate'])) {
            $where .= " AND l.timesent <= :todate";
            $params['todate'] = $filters['todate'] + 86399;
        }

        $this->set_sql($fields, $from, $where, $params);
    }
}

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
 * Reminder history modal.
 *
 * @module     local_coursereminder_mails/reminder_history
 * @copyright  2026 IEX
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Str from 'core/str';
import Notification from 'core/notification';
import Modal from 'core/modal';

/**
 * Initialise event handlers.
 */
export const init = () => {
    document.addEventListener('click', async(event) => {
        const trigger = event.target.closest('.view-reminder-details');
        if (!trigger) {
            return;
        }
        event.preventDefault();

        const userid   = Number(trigger.dataset.userid);
        const courseid = Number(trigger.dataset.courseid);

try {
    const records = await Ajax.call([{
        methodname: 'local_coursereminder_mails_get_reminder_history',
        args: {userid, courseid},
    }])[0];

    const context = {
        hasrecords: records.length > 0,
        records: records.map((record) => ({
            type:     record.type,
            day:      record.day,
            timesent: record.timesent,
            sent:     Number(record.status) === 1,
        })),
    };

            const {html, js} = await Templates.renderForPromise(
                'local_coursereminder_mails/reminder_history',
                context
            );
            Templates.runTemplateJS(js);

            const title = await Str.get_string('reminderhistory', 'local_coursereminder_mails');
            const modal = await Modal.create({title, body: html});
            modal.show();
        } catch (error) {
            Notification.exception(error);
        }
    });

    document.addEventListener('change', (event) => {
        if (event.target.matches('#selectall')) {
            document.querySelectorAll('.selectitem').forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
        }
    });
};

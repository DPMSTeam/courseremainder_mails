# Course Reminder Mails

Moodle local plugin component: `local_coursereminder_mails`.

The plugin sends course-expiry reminder emails to enrolled students and stores delivery history for administrators.

## Requirements

- Moodle 4.3 or later.
- Working Moodle cron.
- A user with `local/coursereminder_mails:view` to access the report.

## Installation

1. Place the plugin in `moodle/local/coursereminder_mails/`.
2. Visit **Site administration → Notifications** to complete installation.
3. Configure the plugin under **Site administration → Plugins → Local plugins → Course Reminder Mails**.
4. Ensure cron is running.

## Configuration

- **Enabled** — enable/disable reminders.
- **First / Second / Custom reminder days** — intervals before the target date.
- **Reminder date based on** — `course end date` or `user enrolment end date`.
- **Email subject / body** — placeholders: `{fullname}`, `{course}`, `{days}`.
- **Courses / Categories for reminders** — select entire categories or individual courses.

## Scheduled task

The task `local_coursereminder_mails\task\send_reminder` runs daily at 09:00 server time. You can run it manually:

```bash
php admin/cli/scheduled_task.php --execute='\local_coursereminder_mails\task\send_reminder'
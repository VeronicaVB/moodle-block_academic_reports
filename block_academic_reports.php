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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Continuous reporting block
 *
 * @package    block_academic_reports
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/academic_reports/lib.php');

class block_academic_reports extends block_base
{

    public function init() {
        $this->title = get_string('pluginname', 'block_academic_reports');
    }

    public function get_content() {
        global  $OUTPUT, $PAGE, $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $config = get_config('block_academic_reports');

        //Check DB settings are available
        if (
            empty($config->dbtype) ||
            empty($config->dbhost) ||
            empty($config->dbuser) ||
            empty($config->dbpass) ||
            empty($config->dbname) ||
            empty($config->dbspstudentreportdocs)  ||
            empty($config->dbspsretrievestdreport) ||
            empty($config->dbspsretrievestdreports)

        ) {
            $notification = new \core\output\notification(
                get_string('nodbsettings', 'block_academic_reports'),
                \core\output\notification::NOTIFY_ERROR
            );
            $notification->set_show_closebutton(false);
            return $OUTPUT->render($notification);
        }


        $this->content = new stdClass;
        $this->content->footer = '';

        if (academic_reports\can_view_on_profile()) {

            $data = '';
            $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);

            // Students are allowed to see their reports.
            if ($profileuser->username == $USER->username && !is_siteadmin($USER)) {
                $data = academic_reports\get_template_context($profileuser->username, $profileuser->username);
            }
            // Allow admins to see the reports to test.
            if ($profileuser->username != $USER->username && is_siteadmin($USER)) {
                $data = academic_reports\get_template_context($profileuser->username, $profileuser->username);
            }


            $mentor = academic_reports\get_mentor($profileuser);

            // Mentors are allowed to see their mentees reports.
            if (!empty($mentor)){

                $data = academic_reports\get_template_context($profileuser->username, $USER->username);
            }

            empty($data) ? $this->content->text = '' : $this->content->text = $OUTPUT->render_from_template('block_academic_reports/main', $data);
        }


        return $this->content;
    }

    public function instance_allow_multiple()
    {
        return false;
    }

    public function instance_allow_config()
    {
        return false;
    }

    public function has_config()
    {
        return true;
    }

    public function hide_header()
    {
        return true;
    }


}

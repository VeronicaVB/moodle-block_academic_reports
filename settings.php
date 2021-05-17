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
 * Attendance report block
 *
 * @package    block_academic_reports
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();


if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_heading(
        'block_academic_reports',
        '',
        get_string('pluginname_desc', 'block_academic_reports')
    ));

    $options = array('', "mysqli", "oci", "pdo", "pgsql", "sqlite3", "sqlsrv");
    $options = array_combine($options, $options);

    $settings->add(new admin_setting_configselect(
        'block_academic_reports/dbtype',
        get_string('dbtype', 'block_academic_reports'),
        get_string('dbtype_desc', 'block_academic_reports'),
        '',
        $options
    ));

    $settings->add(new admin_setting_configtext('block_academic_reports/dbhost', get_string('dbhost', 'block_academic_reports'), get_string('dbhost_desc', 'block_academic_reports'), 'localhost'));

    $settings->add(new admin_setting_configtext('block_academic_reports/dbuser', get_string('dbuser', 'block_academic_reports'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('block_academic_reports/dbpass', get_string('dbpass', 'block_academic_reports'), '', ''));

    $settings->add(new admin_setting_configtext('block_academic_reports/dbname', get_string('dbname', 'block_academic_reports'), '', ''));

    $settings->add(new admin_setting_configtext('block_academic_reports/dbspstudentreportdocs', get_string('dbspstudentreportdocs', 'block_academic_reports'), get_string('dbspstudentreportdocs_desc', 'block_academic_reports'), ''));
    
    $settings->add(new admin_setting_configtext('block_academic_reports/dbspsretrievestdreport', get_string('dbspsretrievestdreport', 'block_academic_reports'), get_string('dbspsretrievestdreport_desc', 'block_academic_reports'), ''));

    $settings->add(new admin_setting_configtext('block_academic_reports/profileurl', get_string('profileurl', 'block_academic_reports'), get_string('profileurl_desc', 'block_attendance_report'), ''));

}

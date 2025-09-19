<?php
// This file is part of giportfolio module for Moodle - http://moodle.org/
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
 * academic_reports block upgrade code
 *
 * @package    mod_giportfolio
 * @copyright  2012 Synergy Learning / Manolescu Dorel based on book module
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


function xmldb_block_academic_reports_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

     if ($oldversion < 2025091901) {

        // Define table block_academic_reports_log to be created.
        $table = new xmldb_table('block_academic_reports_log');

        // Adding fields to table block_academic_reports_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('studentusername', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tdocumentsseq', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sequences', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('access_granted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('reason', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ip_address', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_field('user_agent', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table block_academic_reports_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for block_academic_reports_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Academic_reports savepoint reached.
        upgrade_block_savepoint(true, 2025091901, 'academic_reports');
    }


    return true;
}
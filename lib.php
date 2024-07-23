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
 *  Academic report block
 *
 * @package    block_academic_reports
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace academic_reports;


// Parent view of own child's activity functionality
function can_view_on_profile() {
    global $DB, $USER, $PAGE;

    $config = get_config('block_academic_reports');
  
    if ($PAGE->url->get_path() ==  $config->profileurl) {
        // Admin is allowed.
        $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);

        if (is_siteadmin($USER) && $profileuser->username != $USER->username) {
            return true;
        }

        // Students are allowed to see block in their own profiles.
        if ($profileuser->username == $USER->username && !is_siteadmin($USER)) {
            return true;
        }

        // Parents are allowed to view block in their mentee profiles.

        if (!empty(get_mentor($profileuser))) {
            return true;
        }
    }

    return false;
}

function get_mentor($profileuser) {
    global $DB, $USER;

    // Parents are allowed to view block in their mentee profiles.
    $mentorrole = $DB->get_record('role', array('shortname' => 'parent'));
    $mentor = null;

    if ($mentorrole) {

        $sql = "SELECT ra.*, r.name, r.shortname
            FROM {role_assignments} ra
            INNER JOIN {role} r ON ra.roleid = r.id
            INNER JOIN {user} u ON ra.userid = u.id
            WHERE ra.userid = ?
            AND ra.roleid = ?
            AND ra.contextid IN (SELECT c.id
                FROM {context} c
                WHERE c.contextlevel = ?
                AND c.instanceid = ?)";
        $params = array(
            $USER->id, //Where current user
            $mentorrole->id, // is a mentor
            CONTEXT_USER,
            $profileuser->id, // of the prfile user
        );

        $mentor = $DB->get_records_sql($sql, $params);
    }

    return $mentor;
}

/**
 * Returns the context for the template
 * @return string
 */

function get_template_context($studentusername, $mentorusername) {

    $reports = get_student_reports($studentusername, $mentorusername);
    $data = null;

    foreach ($reports as $report) {
        $repo = new \stdClass();
        $repo->description = $report->description;
        $repo->documentcreateddate = (new  \DateTime($report->documentcreateddate))->format("d/m/Y");;
        $repo->tdocumentsseq = $report->tdocumentsseq;
        $data['reports'][] = $repo;
    }

    return $data;
}



/**
 * Call to the SP 
 */
function get_student_reports($studentusername, $mentorusername) {
    
    $docreports = [];
    
    try {

        $config = get_config('block_academic_reports');

        // Last parameter (external = true) means we are not connecting to a Moodle database.
        $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);

        // Connect to external DB.
        $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

        $sql = 'EXEC ' . $config->dbspstudentreportdocs . ':studentid, :userid';
        $params = array(
            'studentid' => $studentusername,
            'userid' => $mentorusername
        );

        $docreports = $externalDB->get_records_sql($sql, $params);
      
    } catch (\Exception $ex) {
    }

    return $docreports;
}

/**
 * Returns the report clicked on the view
 */

function get_student_report_file($tdocumentsseq) {
    
    $config = get_config('block_academic_reports');
    // Last parameter (external = true) means we are not connecting to a Moodle database.
    $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
    // Connect to external DB.
    $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

    $sql = 'EXEC ' . $config->dbspsretrievestdreport . ' :tdocumentsseq';
    $params = array('tdocumentsseq' => intval($tdocumentsseq));
   

    $documents = $externalDB->get_records_sql($sql, $params);
    $document = reset($documents);

    return $document->document;
}

/**
 *  Returns all the reports the student has
 */
function get_student_reports_files($tDocumentsSequences) {

    $config = get_config('block_academic_reports');
    // Last parameter (external = true) means we are not connecting to a Moodle database.
    $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
    // Connect to external DB.
    $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

    $sql = 'EXEC ' . $config->dbspsretrievestdreports . ' :sequences';
   
    $params = array('sequences' => strval($tDocumentsSequences));

    $documents = $externalDB->get_records_sql($sql, $params);
    $documentsaux = [];

    foreach($documents as $i => $document) {
        $document->document = json_encode(base64_encode($document->document), JSON_UNESCAPED_UNICODE);
        $documentsaux[$i] =  $document;
    }


    return $documentsaux;
}

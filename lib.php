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

    if ($PAGE->url->get_path() == $config->profileurl) {
        $profileuser = $DB->get_record('user', ['id' => $PAGE->url->get_param('id')]);
        profile_load_custom_fields($profileuser);
        $campusrole = $USER->profile['CampusRoles'];

        // Only show block in seniors profiles
        if ( $profileuser->profile['CampusRoles'] == 'Senior School:Students') {

            // Admin is allowed.
            if (is_siteadmin($USER) && $profileuser->username != $USER->username) {
                return true;
            }
            // Staff not allowed
            if (preg_match('/\b(Staff|staff)\b/', $campusrole) == 1 && $profileuser->username != $USER->username) {
                return false;
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
        $repo->studentid = $studentusername;
        $data['reports'][] = $repo;
    }

    $data['studentid'] = $studentusername;

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
 * Validates if the current user can access reports for a specific student
 * @param object $currentuser The current user
 * @param object $studentuser The student whose reports are being accessed
 * @return bool True if access is allowed, false otherwise
 */
function can_user_access_student_reports($currentuser, $studentuser) {
    // Admin can access all reports
    if (is_siteadmin($currentuser)) {
        return true;
    }

    // Student can only access their own reports
    if ($currentuser->id == $studentuser->id) {
        return true;
    }

    // Mentors can access their mentees' reports
    $mentor = get_mentor($studentuser);
    if (!empty($mentor)) {
        return true;
    }

    // All other users denied
    return false;
}

/**
 * Returns the report clicked on the view
 */

function get_student_report_file($tdocumentsseq, $std) {
    global $USER, $DB;

    // Validate that the student username exists
    $studentuser = $DB->get_record('user', ['username' => $std]);
    if (!$studentuser) {
        log_access_attempt($USER->id, $std, $tdocumentsseq, null, false, 'Invalid student username');
        throw new \moodle_exception('invaliduserid', 'error');
    }

    // Validate that current user can access this student's reports
    if (!can_user_access_student_reports($USER, $studentuser)) {
        log_access_attempt($USER->id, $std, $tdocumentsseq, null, false, 'Access denied - insufficient permissions');
        error_log('Academic Reports: Unauthorized access attempt - User: ' . $USER->username .
                 ' tried to access document: ' . $tdocumentsseq . ' for student ID: ' . $std);
        throw new \moodle_exception('nopermissions', 'error');
    }

    // Log successful access
    log_access_attempt($USER->id, $std, $tdocumentsseq, null, true, 'Access granted');

    $config = get_config('block_academic_reports');
    // Last parameter (external = true) means we are not connecting to a Moodle database.
    $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
    // Connect to external DB.
    $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

    $sql = 'EXEC ' . $config->dbspsretrievestdreport . ' :tdocumentsseq, :std';
    $params = array('tdocumentsseq' => intval($tdocumentsseq), 'std' => intval($std));


    $documents = $externalDB->get_records_sql($sql, $params);
    $document = reset($documents);

    return $document->document;
}

/**
 * Log access attempts to the academic reports
 * @param int $userid User ID attempting access
 * @param string $studentusername Username of the student being accessed
 * @param int|null $tdocumentsseq Document sequence if single document
 * @param string|null $sequences Multiple document sequences if applicable
 * @param bool $access_granted Whether access was granted or denied
 * @param string|null $reason Reason for access denial or grant
 */
function log_access_attempt($userid, $studentusername, $tdocumentsseq = null, $sequences = null, $access_granted = false, $reason = null) {
    global $DB;

    $record = new \stdClass();
    $record->userid = $userid;
    $record->studentusername = $studentusername;
    $record->tdocumentsseq = $tdocumentsseq;
    $record->sequences = $sequences;
    $record->access_granted = $access_granted ? 1 : 0;
    $record->reason = $reason;
    $record->ip_address = getremoteaddr();
    $record->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $record->timecreated = time();

    try {
        $DB->insert_record('block_academic_reports_log', $record);
    } catch (\Exception $e) {
        error_log('Failed to log access attempt: ' . $e->getMessage());
    }
}

/**
 *  Returns all the reports the student has
 */
function get_student_reports_files($tDocumentsSequences, $std) {
    global $USER, $DB;

    // Validate that the student ID exists
    $studentuser = $DB->get_record('user', ['username' => $std]); // id
    if (!$studentuser) {
        log_access_attempt($USER->id, $std, null, $tDocumentsSequences, false, 'Invalid student username');
        throw new \moodle_exception('invaliduserid', 'error');
    }

    // Validate that current user can access this student's reports
    if (!can_user_access_student_reports($USER, $studentuser)) {
        log_access_attempt($USER->id, $std, null, $tDocumentsSequences, false, 'Access denied - insufficient permissions');

        throw new \moodle_exception('nopermissions', 'error');
    }

    // Log successful access
    log_access_attempt($USER->id, $std, null, $tDocumentsSequences, true, 'Access granted');

    $config = get_config('block_academic_reports');
    // Last parameter (external = true) means we are not connecting to a Moodle database.
    $externalDB = \moodle_database::get_driver_instance($config->dbtype, 'native', true);
    // Connect to external DB.
    $externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

    $sql = 'EXEC ' . $config->dbspsretrievestdreports . ' :sequences, :std';

    $params = array('sequences' => strval($tDocumentsSequences), 'std' => intval($std));

    $documents = $externalDB->get_records_sql($sql, $params);
    $documentsaux = [];

    foreach($documents as $i => $document) {
        $document->document = json_encode(base64_encode($document->document), JSON_UNESCAPED_UNICODE);
        $documentsaux[$i] =  $document;
    }


    return $documentsaux;
}

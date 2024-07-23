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
 *  Web service to get reports the student has
 *
 * @package   academic_reports
 * @category
 * @copyright 2024 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_academic_reports\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/blocks/academic_reports/lib.php');

/**
 * Trait implementing the external function block_academic_reports
 */
trait get_student_reports {


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */

    public static  function get_student_reports_parameters()    {
        return new external_function_parameters(
            array(
                'sequences' => new external_value(PARAM_RAW, 'file ids to get'),               
            )
        );
    }

    /**
     * Return context.
     */
    public static function get_student_reports($sequences) {
        global $USER, $PAGE;
        
        $context = \context_user::instance($USER->id);
       
        self::validate_context($context);
        //Parameters validation
        self::validate_parameters(self::get_student_reports_parameters(), array('sequences' => $sequences));
        
       
        $blobs = \academic_reports\get_student_reports_files($sequences);
      
        return array(
            'blobs' => json_encode($blobs) //json_encode(base64_encode($blobs), JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Describes the structure of the function return value.
     * @return external_single_structures
     */
    public static function get_student_reports_returns()
    {
        return new external_single_structure(array(
            'blobs' =>  new external_value(PARAM_RAW, 'A JSON object with all the blob that represents each report the student'),
        ));
    }
}

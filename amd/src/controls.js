
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
 * @package   block_academic_reports
 * @copyright 2021 Veronica Bermegui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log', 'core/pubsub',
    'block_academic_reports/jszip',
    'block_academic_reports/FileSaver'],
    function ($, Ajax, Log, PubSub, JSZip, FileSaver) {
        'use strict';
        // FileSaver is loaded and attached to the window object. It needs to be added in the arguments list but
        // has to be accessed from the windows object
        function init() {
            var control = new Controls();
            control.main();

        }

        /**
        * Controls a single block_academic_reports block instance contents.
        *
        * @constructor
        */
        function Controls() {
            Log.debug('block_academic_reports: initializing controls');
            let self = this;

        }

        /**
         * Run the controller.
         *
         */
        Controls.prototype.main = function () {

            let self = this;
            self.setupEvents();

            // Subscribe to nav drawer event and resize when it completes.
            PubSub.subscribe('nav-drawer-toggle-end', function (el) {
                Log.debug('resizing block_academic_reports');

                if ($("#nav-drawer").hasClass("closed")) {
                    $('table.table.table-striped.table-wrapper-scroll-y.my-custom-scrollbar').css('height', '13rem ');
                    $('section.block.block_academic_reports.card.mb-3').css('height', '20rem');
                } else {
                    $('table.table.table-striped.table-wrapper-scroll-y.my-custom-scrollbar').css('height', '18rem ');
                    $('section.block.block_academic_reports.card.mb-3').css('height', '24rem');
                }
            });

        };

        Controls.prototype.setupEvents = function () {
            let self = this;

            $('.view-report').on('click', function (event) {
                const tdocumentsseq = ($(event.target).data()).tdss;
                const std = ($(event.target).data()).std;
                self.displayReportService(tdocumentsseq, std);
            });

            // Link to download reports
            $('.ar-download-all').on('click', function (event) {
                // Get the sequence ids
                var sequences = [];
                var std = ($(event.target).data()).std;

                document.querySelectorAll('[data-tdss]').forEach(el => {
                    sequences.push(el.getAttribute('data-tdss'));
                });

                // Add animation to let the user know that something is happening
                (document.getElementsByClassName('ar-downloading')[0]).removeAttribute('hidden')

                sequences = sequences.join(',')
                sequences = `[${sequences}]`
                self.getAllReportService(sequences, std);
                event.preventDefault();

            });


        };

        Controls.prototype.displayReportService = function (tdocumentsseq, std) {
            let self = this;
            Ajax.call([{
                methodname: 'block_academic_reports_get_student_report',
                args: {
                    tdocumentsseq: tdocumentsseq,
                    std:std
                },

                done: function (response) {
                    const base64Data = JSON.parse(response.blob);
                    self.displayReport(base64Data);
                },

                fail: function (reason) {
                    Log.error('block_academic_report_get_student_report: Unable to get blob.');
                    Log.debug(reason);
                }
            }]);


        };

        Controls.prototype.getAllReportService = function (sequences, std) {
            let self = this;

            Ajax.call([{
                methodname: 'block_academic_reports_get_student_reports',
                args: {
                    sequences: sequences,
                    std:std
                },

                done: function (response) {
                    const r = JSON.parse(response.blobs);
                    // Convert the object into an array
                    const filesData = Object.values(r);
                    self.generateZipToDownload(filesData);

                },

                fail: function (reason) {
                    Log.error('block_academic_report_get_student_report: Unable to get all reports blobs.');

                }
            }]);


        };

        Controls.prototype.displayReport = async function (base64Data) {
            const base64Response = await fetch(`data:application/pdf;base64,${base64Data}`);
            const blob = await base64Response.blob();
            var blobURL = URL.createObjectURL(blob);
            window.open(blobURL);
        }

        // Create and download a zip file with the students reports
        Controls.prototype.generateZipToDownload = async function (filesFromDB) {

            const zip = new JSZip();

            for (const file of filesFromDB) {

                const document = await fetch(`data:application/pdf;base64,${JSON.parse(file.document)}`);
                const doc = await document.blob();
                zip.file(`${file.description}.pdf`, doc); // adds the image file to the zip file

            }

            // Generates the zip file and open the save as window (in Chrome), downloads directly on Edge
            await zip.generateAsync({
                type: "blob",
                streamFiles: true
            }).then(function (content) {
                window.saveAs(content, "cgsStudentReports.zip");
            });

            // Remove animation animation to let the user know that something is happening
            (document.getElementsByClassName('ar-downloading')[0]).setAttribute('hidden', true)

        }



        return { init: init }
    });

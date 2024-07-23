
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

define(['jquery', 'core/ajax', 'core/log', 'core/pubsub', 'block_academic_reports/jszip'],
    function ($, Ajax, Log, PubSub, JSZip) {
        'use strict';

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
                self.displayReportService(tdocumentsseq);
            });

            // Link to download reports
            $('.ar-download-all').on('click', function (event) {
                // Get the sequence ids
                var sequences = [];

                document.querySelectorAll('[data-tdss]').forEach(el => {
                    sequences.push(el.getAttribute('data-tdss'));
                });

                // Add animation to let the user know that something is happening
                (document.getElementsByClassName('ac-reports-working')[0]).removeAttribute('hidden')

                sequences = sequences.join(',')
                sequences = `[${sequences}]`
                self.getAllReportService(sequences)
            });


        };

        Controls.prototype.displayReportService = function (tdocumentsseq) {
            let self = this;
            Ajax.call([{
                methodname: 'block_academic_reports_get_student_report',
                args: {
                    tdocumentsseq: tdocumentsseq
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

        Controls.prototype.getAllReportService = function (sequences) {
            let self = this;

            Ajax.call([{
                methodname: 'block_academic_reports_get_student_reports',
                args: {
                    sequences: sequences
                },

                done: function (response) {
                    const r = JSON.parse(response.blobs);
                    // Convert the object into an array
                    const filesData = Object.values(r);

                    console.log(filesData)
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

            const zipData = await zip.generateAsync({
                type: "blob",
                streamFiles: true
            })

            window.URL.createObjectURL(zipData)

            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(zipData);
            link.download = `reports.zip`
            link.click();

            // Remove animation animation to let the user know that something is happening
            (document.getElementsByClassName('ac-reports-working')[0]).setAttribute('hidden', true)

        }



        return { init: init }
    });

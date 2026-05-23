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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'report_examstats', language 'en'
 *
 * @package   report_examstats
 * @copyright 2026 Khayam <kymulhaq@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Exam Performance Report';
$string['privacy:metadata'] = 'The Exam Performance Report displays aggregated quiz grade data and does not store personal information directly.';
$string['selectcourse'] = '-- Select Course --';
$string['course'] = 'Course:';
$string['theoryexam'] = 'Theory Exam:';
$string['ospeexam'] = 'OSPE Exam:';
$string['nonedeselect'] = '-- None / Deselect --';
$string['applyfilters'] = 'Apply Filters';
$string['examtarget'] = 'Exam Target:';
$string['passed'] = 'Passed';
$string['failed'] = 'Failed';
$string['students'] = 'Students';
$string['attendance'] = 'Attendance';
$string['absent'] = 'Absent';
$string['theorycutoff'] = 'Theory Cutoff';
$string['ospecutoff'] = 'OSPE Cutoff';
$string['passingcutoff'] = 'Passing Cutoff';
$string['activemode'] = 'Active Mode';
$string['singletheory'] = 'Single Theory';
$string['singleospe'] = 'Single OSPE';
$string['failurebreakdown'] = 'Failure Breakdown Diagnostics';
$string['pointoffailure'] = 'Point of Failure';
$string['studentcount'] = 'Student Count';
$string['failedtheoryonly'] = 'Failed Theory Only';
$string['failedospeonly'] = 'Failed OSPE Only';
$string['failedboth'] = 'Failed Both';
$string['passedtheorybutmissedospe'] = 'Passed Theory but missed OSPE cutoff';
$string['passedospebutmissedtheory'] = 'Passed OSPE but missed Theory cutoff';
$string['missedbothcutoffs'] = 'Missed cutoffs on both exams';
$string['topfiveperformers'] = 'Top 5 Best Performing Students';
$string['rank'] = 'Rank #';
$string['combinedtotal'] = 'Combined Total';
$string['points'] = 'Points';
$string['nodatadefined'] = 'Please select an exam configuration and click "Apply Filters" to load the matrix dashboard.';
$string['printreport'] = 'Print Report';
$string['downloadcsv'] = 'Download Failed Students (CSV)';
$string['presentinboth'] = 'present in both';
$string['didnotattempt'] = 'did not attempt';
$string['presentoutof'] = 'present out of';
$string['outof'] = 'out of';

// CSV Column Headers
$string['csvfirstname'] = 'First Name';
$string['csvlastname'] = 'Last Name';
$string['csvemail'] = 'Email';
$string['csvtheoryscore'] = 'Theory Score';
$string['csvospescore'] = 'OSPE Score';
$string['csvfailurereason'] = 'Failure Diagnostic Reason';
$string['csvscoreachieved'] = 'Score Achieved';
$string['csvpassingcutoff'] = 'Passing Cutoff';
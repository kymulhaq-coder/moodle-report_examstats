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
$string['examstats:view'] = 'View the Exam Performance Report';
$string['privacy:metadata'] = 'The Exam Performance Report displays aggregated quiz grade data and does not store personal information directly.';
$string['privacy:metadata:core_grades'] = 'The Exam Performance Report reads grade data from the Moodle core grades subsystem (grade_grades and grade_items tables) in order to display exam performance analytics. No personal data is stored by this plugin independently.';
$string['selectcourse'] = '-- Select Course --';
$string['course'] = 'Course:';
$string['theoryexam'] = 'Theory Exam:';
$string['skillexam'] = 'Skill Exam:';
$string['nonedeselect'] = '-- None / Deselect --';
$string['applyfilters'] = 'Apply Filters';
$string['examtarget'] = 'Exam Target:';
$string['passed'] = 'Passed';
$string['failed'] = 'Failed';
$string['students'] = 'Students';
$string['attendance'] = 'Attendance';
$string['absent'] = 'Absent';
$string['theorycutoff'] = 'Theory Exam Cutoff';
$string['skillcutoff'] = 'Skill Exam Cutoff';
$string['passingcutoff'] = 'Passing Cutoff';
$string['activemode'] = 'Active Mode';
$string['singletheory'] = 'Single Theory Exam';
$string['singleskill'] = 'Single Skill Exam';
$string['failurebreakdown'] = 'Failure Breakdown Diagnostics';
$string['pointoffailure'] = 'Point of Failure';
$string['studentcount'] = 'Student Count';
$string['failedtheoryonly'] = 'Failed Theory Exam Only';
$string['failedskillonly'] = 'Failed Skill Exam Only';
$string['failedboth'] = 'Failed Both';
$string['passedtheorybutmissedskill'] = 'Passed Theory Exam but missed Skill Exam cutoff';
$string['passedskillbutmissedtheory'] = 'Passed Skill Exam but missed Theory cutoff';
$string['missedbothcutoffs'] = 'Missed cutoffs on both exams';
$string['topfiveperformers'] = 'Top 5 Best Performing Students';
$string['rank'] = 'Rank #';
$string['combinedtotal'] = 'Combined Total';
$string['points'] = 'Points';
$string['nodatadefined'] = 'Please select an exam configuration and click "Apply Filters" to load the matrix dashboard.';
$string['printreport'] = 'Print Report';
$string['downloadcsv'] = 'Download (CSV)';
$string['presentinboth'] = 'present in both';
$string['didnotattempt'] = 'did not attempt';
$string['presentoutof'] = 'present out of';
$string['outof'] = 'out of';
$string['calcbasis'] = 'Calculation Basis';
$string['calcbasisappeared'] = 'Appeared Cohort';
$string['calcbasisregistered'] = 'Total Cohort';
$string['basis'] = 'Basis:';

// CSV Column Headers.
$string['csvfirstname'] = 'First Name';
$string['csvlastname'] = 'Last Name';
$string['csvemail'] = 'Email';
$string['csvtheoryscore'] = 'Theory Score';
$string['csvskillscore'] = 'Skill Score';
$string['csvfailurereason'] = 'Failure Diagnostic Reason';
$string['csvscoreachieved'] = 'Score Achieved';
$string['csvpassingcutoff'] = 'Passing Cutoff';

// Admin settings (Theory/Skill exam quiz name filter patterns).
$string['pluginsettings'] = 'Exam Stats Settings';
$string['theory_pattern'] = 'Theory quiz name pattern';
$string['theory_pattern_desc'] = 'Quizzes whose name contains this text will appear in the Theory dropdown.';
$string['theory_pattern_default'] = 'Theory';
$string['skill_pattern'] = 'Skill exam quiz name patterns';
$string['skill_pattern_desc'] = 'Comma-separated list of terms (e.g. "Skill, OSCE, OSPE, Practical"). A quiz will appear in the Skill Exam dropdown if its name contains any one of these terms.';
$string['skill_pattern_default'] = 'Skill, OSCE, OSPE, Practical';

// Performance band thresholds and labels.
$string['bandsettingsheading'] = 'Performance Band Thresholds & Labels';
$string['bandsettingsheading_desc'] = 'Configure the percentage cutoffs and descriptive labels used in the Performance Band Distribution table. Each band applies to students scoring at or above its minimum percentage, up to (but not including) the next band above it.';
$string['band_a_min'] = 'Band A minimum %';
$string['band_a_min_desc'] = 'Students scoring at or above this percentage are placed in Band A.';
$string['band_b_min'] = 'Band B minimum %';
$string['band_b_min_desc'] = 'Students scoring at or above this percentage (and below the Band A cutoff) are placed in Band B.';
$string['band_c_min'] = 'Band C minimum %';
$string['band_c_min_desc'] = 'Students scoring at or above this percentage (and below the Band B cutoff) are placed in Band C. Anything below this cutoff falls into Band D.';
$string['band_a_label'] = 'Band A label';
$string['band_a_label_desc'] = 'Descriptor shown in the Performance Band Distribution table for Band A.';
$string['band_b_label'] = 'Band B label';
$string['band_b_label_desc'] = 'Descriptor shown in the Performance Band Distribution table for Band B.';
$string['band_c_label'] = 'Band C label';
$string['band_c_label_desc'] = 'Descriptor shown in the Performance Band Distribution table for Band C.';
$string['band_d_label'] = 'Band D label';
$string['band_d_label_desc'] = 'Descriptor shown in the Performance Band Distribution table for Band D (e.g. "Fail", "Poor Performance", "Needs Improvement").';

// CSV dropdown menu.
$string['byperformanceband'] = 'By Performance Band';
$string['highachievers'] = 'High Achievers';
$string['satisfactory'] = 'Satisfactory';
$string['borderline'] = 'Borderline';
$string['failedstudents'] = 'Failed Students';
$string['completeresult'] = 'Complete Result (All Students)';

// Performance Band Distribution table.
$string['band'] = 'Band';
$string['descriptor'] = 'Descriptor';
$string['scorerange'] = 'Score Range';
$string['pctgraded'] = '% of Graded';
$string['performancebanddistribution'] = 'Performance Band Distribution';
$string['highachieverdesc'] = 'High Achiever';
$string['satisfactorydesc'] = 'Satisfactory';
$string['borderlinedesc'] = 'Borderline';
$string['faildesc'] = 'Fail';

// CSV export column headers and status values.
$string['csv_firstname'] = 'First Name';
$string['csv_lastname'] = 'Last Name';
$string['csv_email'] = 'Email';
$string['csv_remarks'] = 'Remarks';
$string['csv_percentofmax'] = '% of Max';
$string['csv_descriptor'] = 'Descriptor';
$string['csv_status'] = 'Status';
$string['csv_theoryscore'] = 'Theory Score';
$string['csv_skillscore'] = 'Skill Score';
$string['csv_grandtotal'] = 'Grand Total';
$string['csv_outof'] = 'out of';
$string['statuspass'] = 'Pass';
$string['statusfail'] = 'Fail';
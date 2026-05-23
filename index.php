<?php
// This file is part of Moodle - https://moodle.org/
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
 * Main report page for Exam Performance Report.
 *
 * Displays pass/fail statistics, attendance, KPI cards, failure breakdown
 * diagnostics, and a top-performers leaderboard for Theory, OSPE, or
 * combined Theory+OSPE quiz assessments.
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Security check: Ensure this file is running inside Moodle.
require_once(__DIR__ . '/../../config.php');

// Require admin access to view this page.
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Get URL parameters.
$courseid      = optional_param('courseid',      0,  PARAM_INT);
$quizid_theory = optional_param('quizid_theory', 0,  PARAM_INT);
$quizid_ospe   = optional_param('quizid_ospe',   0,  PARAM_INT);
$export        = optional_param('export',        '', PARAM_ALPHA);

// Determine active exam mode.
$has_theory       = ($quizid_theory > 0);
$has_ospe         = ($quizid_ospe   > 0);
$is_combined      = ($has_theory && $has_ospe);
$any_exam_selected = ($has_theory || $has_ospe);

// -------------------------------------------------------------------------
// CSV Export Engine (Supports Single and Combined configurations)
// -------------------------------------------------------------------------
if ($export !== '' && $courseid > 0 && $any_exam_selected) {
    $coursecontext = context_course::instance($courseid);
    $student_users = get_enrolled_users($coursecontext, 'mod/quiz:attempt', 0,
        'u.id, u.firstname, u.lastname, u.email');
    $student_ids   = array_keys($student_users);

    if (!empty($student_ids)) {
        // Fetch grade items.
        $theory_item = $has_theory
            ? $DB->get_record('grade_items', array('iteminstance' => $quizid_theory, 'itemmodule' => 'quiz'))
            : null;
        $ospe_item = $has_ospe
            ? $DB->get_record('grade_items', array('iteminstance' => $quizid_ospe, 'itemmodule' => 'quiz'))
            : null;

        // Max marks and pass thresholds.
        $t_max  = $theory_item ? floatval($theory_item->grademax) : 0;
        $o_max  = $ospe_item   ? floatval($ospe_item->grademax)   : 0;
        $t_pass = ($theory_item && floatval($theory_item->gradepass) > 0)
            ? floatval($theory_item->gradepass) : $t_max * 0.50;
        $o_pass = ($ospe_item && floatval($ospe_item->gradepass) > 0)
            ? floatval($ospe_item->gradepass) : $o_max * 0.50;

        // Build score map.
        $t_grades = array(); $o_grades = array();
        $gsql = "SELECT userid, finalgrade FROM {grade_grades} WHERE itemid = ?";
        if ($theory_item) {
            $t_grades = $DB->get_records_sql_menu($gsql, array($theory_item->id));
        }
        if ($ospe_item) {
            $o_grades = $DB->get_records_sql_menu($gsql, array($ospe_item->id));
        }

        // Classify every student into a band with full data.
        $band_rows = array();
        foreach ($student_users as $su) {
            $uid = $su->id;
            $ts  = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0)
                ? floatval($t_grades[$uid]) : 0;
            $os  = (isset($o_grades[$uid]) && floatval($o_grades[$uid]) > 0)
                ? floatval($o_grades[$uid]) : 0;

            if ($is_combined) {
                if ($ts <= 0 && $os <= 0) { continue; }
                $grand_total = $ts + $os;
                $maxval      = $t_max + $o_max;
                // Per-component remarks.
                $t_remark = ($ts >= $t_pass) ? 'Pass' : 'Fail';
                $o_remark = ($os >= $o_pass) ? 'Pass' : 'Fail';
                // Overall status: Fail if EITHER component is failed.
                $status = ($t_remark === 'Pass' && $o_remark === 'Pass') ? 'Pass' : 'Fail';
            } else {
                $active_score = $has_theory ? $ts : $os;
                if ($active_score <= 0) { continue; }
                $grand_total  = $active_score;
                $maxval       = $has_theory ? $t_max : $o_max;
                $pass_line    = $has_theory ? $t_pass : $o_pass;
                $t_remark     = '';
                $o_remark     = '';
                $status       = ($active_score >= $pass_line) ? 'Pass' : 'Fail';
            }

            $pct = ($maxval > 0) ? ($grand_total / $maxval) * 100 : 0;

            if ($pct >= 80) {
                $band = 'A'; $descriptor = 'High Achiever';
            } else if ($pct >= 60) {
                $band = 'B'; $descriptor = 'Satisfactory';
            } else if ($pct >= 50) {
                $band = 'C'; $descriptor = 'Borderline';
            } else {
                $band = 'D'; $descriptor = 'Fail';
            }

            $band_rows[$uid] = array(
                'firstname'   => $su->firstname,
                'lastname'    => $su->lastname,
                'email'       => $su->email,
                'ts'          => $ts,
                't_remark'    => $t_remark,
                'os'          => $os,
                'o_remark'    => $o_remark,
                'grand_total' => $grand_total,
                'pct'         => round($pct, 1),
                'descriptor'  => $descriptor,
                'status'      => $status,
                'band'        => $band,
            );
        }

        // Band filter map.
        $export_map = array(
            'failedcsv'     => 'D',
            'highachievers' => 'A',
            'satisfactory'  => 'B',
            'borderline'    => 'C',
        );

        $filename_map = array(
            'failedcsv'      => 'Failed_Students',
            'highachievers'  => 'High_Achievers',
            'satisfactory'   => 'Satisfactory_Students',
            'borderline'     => 'Borderline_Students',
            'completeresult' => 'Complete_Result',
        );

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'
            . ($filename_map[$export] ?? 'Result') . '.csv"');
        $out = fopen('php://output', 'w');

        // Dynamic column headers based on actual max marks.
        if ($is_combined) {
            fputcsv($out, array(
                'First Name',
                'Last Name',
                'Email',
                'Theory Score (out of ' . $t_max . ')',
                'Remarks',
                'OSPE Score (out of ' . $o_max . ')',
                'Remarks',
                'Grand Total (out of ' . ($t_max + $o_max) . ')',
                '% of Max',
                'Descriptor',
                'Status',
            ));
        } else {
            $single_max   = $has_theory ? $t_max : $o_max;
            $single_label = $has_theory ? 'Theory Score' : 'OSPE Score';
            fputcsv($out, array(
                'First Name',
                'Last Name',
                'Email',
                $single_label . ' (out of ' . $single_max . ')',
                'Remarks',
                '% of Max',
                'Descriptor',
            ));
        }

        foreach ($band_rows as $row) {
            // Filter by band for specific downloads; completeresult exports all.
            if ($export !== 'completeresult' && isset($export_map[$export])) {
                if ($row['band'] !== $export_map[$export]) {
                    continue;
                }
            }

            if ($is_combined) {
                fputcsv($out, array(
                    $row['firstname'],
                    $row['lastname'],
                    $row['email'],
                    $row['ts'],
                    $row['t_remark'],
                    $row['os'],
                    $row['o_remark'],
                    $row['grand_total'],
                    $row['pct'] . '%',
                    $row['descriptor'],
                    $row['status'],
                ));
            } else {
                $single_score = $has_theory ? $row['ts'] : $row['os'];
                fputcsv($out, array(
                    $row['firstname'],
                    $row['lastname'],
                    $row['email'],
                    $single_score,
                    $row['status'],
                    $row['pct'] . '%',
                    $row['descriptor'],
                ));
            }
        }
        fclose($out);
        exit;
    }
}

// Initialise Moodle page.
$url = new moodle_url('/report/examstats/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_examstats'));
$PAGE->set_heading(get_string('pluginname', 'report_examstats'));

echo $OUTPUT->header();
echo '<div class="report-examstats">';

// CSS Layout Rules & Clean Print Enhancements
echo '<style>
    .report-examstats .re-label { color: #343a40 !important; font-weight: bold; margin-right: 8px; }
    .report-examstats .re-leaderboard-img { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; box-shadow: 0 2px 4px rgba(0,0,0,0.15); border: 2px solid #fff; }
    .report-examstats .re-leaderboard-card { background: #fff; min-height: 170px; border-radius: 8px; position: relative; transition: transform 0.2s; }
    .report-examstats .re-leaderboard-card:hover { transform: translateY(-3px); }
    .report-examstats .table, .report-examstats .card, .report-examstats .progress, .report-examstats .re-leaderboard-card { page-break-inside: avoid; break-inside: avoid; }
    @media print {
        body { background: #fff !important; }
        body * { visibility: hidden; }
        #re-analytics-dashboard, #re-analytics-dashboard * { visibility: visible; }
        #re-analytics-dashboard { position: absolute; left: 0; top: 0; width: 100%; display: block !important; border: none !important; box-shadow: none !important; background: transparent !important; padding: 0 !important; }
        .re-no-print { display: none !important; }
    }
</style>';

// Fetch baseline Course structure
$courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname');

// DISPLAY: Filter Card
echo '<div class="card mb-4 bg-light re-no-print">';
echo '  <div class="card-body py-3">';
echo '    <form method="get" action="index.php" class="form-inline m-0">';
echo '      <div class="form-group mr-4 mb-2">';
echo '        <label class="re-label">Course:</label>';
echo '        <select name="courseid" class="form-control" onchange="this.form.submit()">';
echo '          <option value="0">-- Select Course --</option>';
foreach ($courses as $c) {
    $sel = ($c->id == $courseid) ? 'selected' : '';
    echo '<option value="' . $c->id . '" ' . $sel . '>' . s($c->fullname) . '</option>';
}
echo '        </select>';
echo '      </div>';

if ($courseid > 0) {
    // Theory dropdown: only quizzes whose name contains "Theory" (case-insensitive)
    $theory_quizzes = $DB->get_records_select(
        'quiz',
        'course = ? AND ' . $DB->sql_like('name', '?', false),
        array($courseid, '%Theory%'),
        'name ASC',
        'id, name'
    );

    // OSPE dropdown: only quizzes whose name contains "OSPE" or "OSCE" (case-insensitive)
    $ospe_quizzes = $DB->get_records_select(
        'quiz',
        'course = ? AND (' . $DB->sql_like('name', '?', false) . ' OR ' . $DB->sql_like('name', '?', false) . ')',
        array($courseid, '%OSPE%', '%OSCE%'),
        'name ASC',
        'id, name'
    );

    echo '      <div class="form-group mr-4 mb-2">';
    echo '        <label class="re-label">Theory Exam:</label>';
    echo '        <select name="quizid_theory" class="form-control">';
    echo '          <option value="0">-- None / Deselect --</option>';
    if ($theory_quizzes) {
        foreach ($theory_quizzes as $q) {
            $sel = ($q->id == $quizid_theory) ? 'selected' : '';
            echo '<option value="' . $q->id . '" ' . $sel . '>' . s($q->name) . '</option>';
        }
    }
    echo '        </select>';
    echo '      </div>';

    echo '      <div class="form-group mr-4 mb-2">';
    echo '        <label class="re-label">OSPE Exam:</label>';
    echo '        <select name="quizid_ospe" class="form-control">';
    echo '          <option value="0">-- None / Deselect --</option>';
    if ($ospe_quizzes) {
        foreach ($ospe_quizzes as $q) {
            $sel = ($q->id == $quizid_ospe) ? 'selected' : '';
            echo '<option value="' . $q->id . '" ' . $sel . '>' . s($q->name) . '</option>';
        }
    }
    echo '        </select>';
    echo '      </div>';
    
    echo '      <button type="submit" class="btn btn-primary mb-2"><i class="fa fa-filter mr-1"></i> Apply Filters</button>';
}
echo '    </form>';
echo '  </div>';
echo '</div>';

// Process Active Statistics Engine
if ($courseid > 0 && $any_exam_selected) {
    $course_record = $DB->get_record('course', array('id' => $courseid));
    
    // Resolve dynamic active labels for the professional header
    $active_exam_names = array();
    $theory_item = $has_theory ? $DB->get_record('grade_items', array('iteminstance' => $quizid_theory, 'itemmodule' => 'quiz')) : null;
    $ospe_item = $has_ospe ? $DB->get_record('grade_items', array('iteminstance' => $quizid_ospe, 'itemmodule' => 'quiz')) : null;
    
    if ($theory_item) { $active_exam_names[] = $DB->get_field('quiz', 'name', array('id' => $quizid_theory)); }
    if ($ospe_item) { $active_exam_names[] = $DB->get_field('quiz', 'name', array('id' => $quizid_ospe)); }
    $printed_exam_titles = implode(' & ', $active_exam_names);

    // Filter strictly for users with student role capabilities to completely exclude teachers/admins
    $enrolled_users = get_enrolled_users(context_course::instance($courseid), 'mod/quiz:attempt', 0, 'u.id, u.firstname, u.lastname, u.picture, u.imagealt, u.email');
    $total_enrolled = count($enrolled_users);
    if ($total_enrolled <= 0) { $total_enrolled = 1; }
    
    // Arrays to construct metrics mapping
    $t_grades = array(); $o_grades = array();
    $sql = "SELECT userid, finalgrade FROM {grade_grades} WHERE itemid = ? AND finalgrade IS NOT NULL";
    
    if ($has_theory && $theory_item) { $t_grades = $DB->get_records_sql_menu($sql, array($theory_item->id)); }
    if ($has_ospe && $ospe_item) { $o_grades = $DB->get_records_sql_menu($sql, array($ospe_item->id)); }
    
    // Execute Calculations Strategy
    $passed_count = 0; $failed_count = 0;
    $p_theory_count = 0; $p_ospe_count = 0; $p_both_count = 0;
    
    $leaderboard_stack = array();
    $bands = array('A' => array(), 'B' => array(), 'C' => array(), 'D' => array());
    
    foreach ($enrolled_users as $user) {
        $uid = $user->id;
        $ts = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0) ? floatval($t_grades[$uid]) : 0;
        $os = (isset($o_grades[$uid]) && floatval($o_grades[$uid]) > 0) ? floatval($o_grades[$uid]) : 0;
        
        $has_active_t = ($ts > 0);
        $has_active_o = ($os > 0);
        
        if ($has_active_t) { $p_theory_count++; }
        if ($has_active_o) { $p_ospe_count++; }
        if ($has_active_t && $has_active_o) { $p_both_count++; }

        // Evaluate pass statistics bounds based on target mode selected.
        if ($is_combined) {
            if ($has_active_t || $has_active_o) {
                $t_max  = floatval($theory_item->grademax);
                $t_pass = floatval($theory_item->gradepass) > 0 ? floatval($theory_item->gradepass) : $t_max * 0.50;
                $o_max  = floatval($ospe_item->grademax);
                $o_pass = floatval($ospe_item->gradepass) > 0 ? floatval($ospe_item->gradepass) : $o_max * 0.50;

                if ($ts >= $t_pass && $os >= $o_pass) {
                    $passed_count++;
                } else {
                    $failed_count++;
                }
                $combined_score = $ts + $os;
                $leaderboard_stack[$uid] = $combined_score;

                // Grade band: percentage of combined max.
                $combined_max = $t_max + $o_max;
                $pct = ($combined_max > 0) ? ($combined_score / $combined_max) * 100 : 0;
                if ($pct >= 80) {
                    $bands['A'][] = $uid;
                } else if ($pct >= 60) {
                    $bands['B'][] = $uid;
                } else if ($pct >= 50) {
                    $bands['C'][] = $uid;
                } else {
                    $bands['D'][] = $uid;
                }
            }
        } else {
            $active_score = $has_theory ? $ts : $os;
            $active_item  = $has_theory ? $theory_item : $ospe_item;

            if ($active_score > 0) {
                $pass_line = floatval($active_item->gradepass) > 0
                    ? floatval($active_item->gradepass)
                    : floatval($active_item->grademax) * 0.50;
                if ($active_score >= $pass_line) {
                    $passed_count++;
                } else {
                    $failed_count++;
                }
                $leaderboard_stack[$uid] = $active_score;

                // Grade band: percentage of single exam max.
                $pct = (floatval($active_item->grademax) > 0)
                    ? ($active_score / floatval($active_item->grademax)) * 100 : 0;
                if ($pct >= 80) {
                    $bands['A'][] = $uid;
                } else if ($pct >= 60) {
                    $bands['B'][] = $uid;
                } else if ($pct >= 50) {
                    $bands['C'][] = $uid;
                } else {
                    $bands['D'][] = $uid;
                }
            }
        }
    }
    
    // Resolve dynamic presentation metrics percentages
    $evaluated_pool_total = $passed_count + $failed_count;
    if ($evaluated_pool_total <= 0) { $evaluated_pool_total = 1; }
    $pass_percent = round(($passed_count / $evaluated_pool_total) * 100, 1);
    $fail_percent = round(($failed_count / $evaluated_pool_total) * 100, 1);
    
    // Action Controls Row
    echo '<div class="d-flex justify-content-end mb-3 re-no-print">';
    echo '  <button onclick="window.print()" class="btn btn-outline-info btn-sm mr-2">'
        . '<i class="fa fa-print mr-1"></i> Print Report</button>';

    // Build base URL params for all export links.
    $base_export_params = array(
        'courseid'      => $courseid,
        'quizid_theory' => $quizid_theory,
        'quizid_ospe'   => $quizid_ospe,
    );

    $url_failed       = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'failedcsv')));
    $url_highachievers = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'highachievers')));
    $url_satisfactory = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'satisfactory')));
    $url_borderline   = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'borderline')));
    $url_complete     = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'completeresult')));

    echo '  <style>
    .epr-dropdown { position:relative; display:inline-block; }
    .epr-dropdown-menu {
        display:none; position:absolute; right:0; top:100%; z-index:9999;
        background:#fff; border:1px solid rgba(0,0,0,.15); border-radius:6px;
        box-shadow:0 6px 20px rgba(0,0,0,0.15); min-width:240px; margin-top:4px;
    }
    .epr-dropdown-menu.show { display:block; }
    .epr-dropdown-menu a {
        display:block; padding:9px 16px; color:#343a40; text-decoration:none;
        font-size:.88rem; border-bottom:1px solid #f1f1f1;
    }
    .epr-dropdown-menu a:last-child { border-bottom:none; }
    .epr-dropdown-menu a:hover { background:#f8f9fa; color:#000; }
    .epr-dropdown-header {
        padding:8px 16px 4px; font-size:.72rem; font-weight:700;
        text-transform:uppercase; letter-spacing:.05em; color:#6c757d;
    }
    .epr-dropdown-divider { border-top:2px solid #e9ecef; margin:4px 0; }
    </style>';

    echo '  <div class="epr-dropdown re-no-print">
      <button type="button" onclick="
        var m=document.getElementById(\'epr-csv-menu\');
        m.classList.toggle(\'show\');
        document.addEventListener(\'click\', function handler(e){
            if(!e.target.closest(\'.epr-dropdown\')){
                m.classList.remove(\'show\');
                document.removeEventListener(\'click\', handler);
            }
        });
      " class="btn btn-danger btn-sm">
        <i class="fa fa-download mr-1"></i> Download CSV &nbsp;&#9660;
      </button>
      <div class="epr-dropdown-menu" id="epr-csv-menu">
        <div class="epr-dropdown-header">By Performance Band</div>
        <a href="' . $url_highachievers->out(false) . '">
          <i class="fa fa-star text-warning mr-1"></i> High Achievers (&ge; 80%)</a>
        <a href="' . $url_satisfactory->out(false) . '">
          <i class="fa fa-thumbs-up text-success mr-1"></i> Satisfactory (60&ndash;79%)</a>
        <a href="' . $url_borderline->out(false) . '">
          <i class="fa fa-exclamation-circle text-warning mr-1"></i> Borderline (50&ndash;59%)</a>
        <a href="' . $url_failed->out(false) . '">
          <i class="fa fa-times-circle text-danger mr-1"></i> Failed Students (&lt; 50%)</a>
        <div class="epr-dropdown-divider"></div>
        <a href="' . $url_complete->out(false) . '">
          <i class="fa fa-table mr-1"></i> Complete Result (All Students)</a>
      </div>
    </div>';
    echo '</div>';
    
    echo '<div id="re-analytics-dashboard" class="card border-0 shadow-sm">';
    echo '  <div class="card-body p-4">';
    
    // Professional Header Block: Always rendered contextually at the top of the analytics viewport
    echo '    <div class="mb-4 text-left border-bottom pb-3">';
    echo '      <h3 class="font-weight-bold text-dark mb-2">Exam Performance Report</h3>';
    echo '      <div class="text-secondary font-weight-bold" style="font-size: 1.05rem; line-height: 1.6;">Course: <span class="text-dark font-weight-normal">' . s($course_record->fullname) . '</span></div>';
    echo '      <div class="text-secondary font-weight-bold" style="font-size: 1.05rem; line-height: 1.6;">Exam Target: <span class="text-dark font-weight-normal">' . s($printed_exam_titles) . '</span></div>';
    echo '    </div>';
    
    // Attendance Calculation Blocks
    // KPI Cards Row
    // Shared inner-card style: fixed min-height + flexbox so all cards are the same height
    $kpi_inner = 'display:flex;flex-direction:column;justify-content:center;align-items:center;'
               . 'border-radius:12px;padding:22px 14px;text-align:center;color:#fff;min-height:130px;'
               . 'box-shadow:0 4px 15px rgba(0,0,0,0.18);';
    $kpi_label = 'font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;opacity:.9;margin-bottom:6px;font-weight:600;';
    $kpi_val   = 'font-size:1.75rem;font-weight:700;line-height:1.1;margin:0;';
    $kpi_sub   = 'font-size:.78rem;opacity:.9;margin-top:6px;';
    $kpi_sub2  = 'font-size:.75rem;opacity:.95;margin-top:5px;font-weight:500;';

    if ($is_combined) {
        $attend_pct   = round(($p_both_count / $total_enrolled) * 100, 1);
        $absent_count = $total_enrolled - $p_both_count;
        $t_cutoff_val = floatval($theory_item->gradepass) > 0 ? floatval($theory_item->gradepass) : floatval($theory_item->grademax)*0.50;
        $o_cutoff_val = floatval($ospe_item->gradepass)   > 0 ? floatval($ospe_item->gradepass)   : floatval($ospe_item->grademax)*0.50;

        // Card 1 — Attendance (blue)
        $attendance_card = '<div class="col-6 col-md-3 mb-3">
          <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#1a6dff,#00c6ff);">
            <div style="' . $kpi_label . '">Attendance</div>
            <div style="' . $kpi_val . '">' . $attend_pct . '%</div>
            <div style="' . $kpi_sub . '">' . $p_both_count . ' / ' . $total_enrolled . ' present in both</div>
            <div style="' . $kpi_sub2 . '">P in Theory: <strong>' . $p_theory_count . '</strong> &nbsp;|&nbsp; P in OSPE: <strong>' . $p_ospe_count . '</strong></div>
          </div>
        </div>';

        // Card 2 — Absent (slate)
        $absent_card = '<div class="col-6 col-md-3 mb-3">
          <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#485563,#29323c);">
            <div style="' . $kpi_label . '">Absent</div>
            <div style="' . $kpi_val . '">' . $absent_count . '</div>
            <div style="' . $kpi_sub . '">did not attempt</div>
            <div style="' . $kpi_sub2 . '">&nbsp;</div>
          </div>
        </div>';

        // Cards 3 & 4 — Theory cutoff (purple→pink) | OSPE cutoff (green)
        $cutoff_cards =
          '<div class="col-6 col-md-3 mb-3">
            <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#7b2ff7,#e83e8c);">
              <div style="' . $kpi_label . '">Theory Cutoff</div>
              <div style="' . $kpi_val . '">' . $t_cutoff_val . '</div>
              <div style="' . $kpi_sub . '">out of ' . floatval($theory_item->grademax) . '</div>
              <div style="' . $kpi_sub2 . '">&nbsp;</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#11998e,#38ef7d);color:#fff;">
              <div style="' . $kpi_label . '">OSPE Cutoff</div>
              <div style="' . $kpi_val . 'color:#fff;">' . $o_cutoff_val . '</div>
              <div style="' . $kpi_sub . '">out of ' . floatval($ospe_item->grademax) . '</div>
              <div style="' . $kpi_sub2 . '">&nbsp;</div>
            </div>
          </div>';

    } else {
        $single_present = $has_theory ? $p_theory_count : $p_ospe_count;
        $attend_pct     = round(($single_present / $total_enrolled) * 100, 1);
        $absent_count   = $total_enrolled - $single_present;
        $act_item       = $has_theory ? $theory_item : $ospe_item;
        $act_cutoff     = floatval($act_item->gradepass) > 0 ? floatval($act_item->gradepass) : floatval($act_item->grademax)*0.50;

        // Card 1 — Attendance (blue)
        $attendance_card = '<div class="col-6 col-md-3 mb-3">
          <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#1a6dff,#00c6ff);">
            <div style="' . $kpi_label . '">Attendance</div>
            <div style="' . $kpi_val . '">' . $attend_pct . '%</div>
            <div style="' . $kpi_sub . '">' . $single_present . ' present out of ' . $total_enrolled . '</div>
            <div style="' . $kpi_sub2 . '">&nbsp;</div>
          </div>
        </div>';

        // Card 2 — Absent (slate)
        $absent_card = '<div class="col-6 col-md-3 mb-3">
          <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#485563,#29323c);">
            <div style="' . $kpi_label . '">Absent</div>
            <div style="' . $kpi_val . '">' . $absent_count . '</div>
            <div style="' . $kpi_sub . '">did not attempt</div>
            <div style="' . $kpi_sub2 . '">&nbsp;</div>
          </div>
        </div>';

        // Cards 3 & 4 — Cutoff (purple→pink) | Active Mode (green)
        $cutoff_cards =
          '<div class="col-6 col-md-3 mb-3">
            <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#7b2ff7,#e83e8c);">
              <div style="' . $kpi_label . '">Passing Cutoff</div>
              <div style="' . $kpi_val . '">' . $act_cutoff . '</div>
              <div style="' . $kpi_sub . '">out of ' . floatval($act_item->grademax) . '</div>
              <div style="' . $kpi_sub2 . '">&nbsp;</div>
            </div>
          </div>
          <div class="col-6 col-md-3 mb-3">
            <div style="' . $kpi_inner . 'background:linear-gradient(135deg,#11998e,#38ef7d);">
              <div style="' . $kpi_label . '">Active Mode</div>
              <div style="' . $kpi_val . '">' . ($has_theory ? 'Single Theory' : 'Single OSPE') . '</div>
              <div style="' . $kpi_sub . '">&nbsp;</div>
              <div style="' . $kpi_sub2 . '">&nbsp;</div>
            </div>
          </div>';
    }

    echo '    <div class="row mb-4">';
    echo $attendance_card;
    echo $absent_card;
    echo $cutoff_cards;
    echo '    </div>'; // end KPI row
    echo '    <hr class="my-4">';
    
    // Metrics Progress Bars
    echo '    <div class="mb-4">';
    echo '      <div class="d-flex justify-content-between mb-1"><span><i class="fa fa-check-circle text-success mr-1"></i> <strong>Passed (' . $passed_count . ' Students)</strong></span><span class="text-success font-weight-bold">' . $pass_percent . '%</span></div>';
    echo '      <div class="progress" style="height: 24px; border-radius: 4px;">';
    echo '        <div class="progress-bar bg-success" style="width: ' . $pass_percent . '%"></div>';
    echo '      </div>';
    echo '    </div>';
    
    echo '    <div class="mb-4">';
    echo '      <div class="d-flex justify-content-between mb-1"><span><i class="fa fa-times-circle text-danger mr-1"></i> <strong>Failed (' . $failed_count . ' Students)</strong></span><span class="text-danger font-weight-bold">' . $fail_percent . '%</span></div>';
    echo '      <div class="progress" style="height: 24px; border-radius: 4px;">';
    echo '        <div class="progress-bar bg-danger" style="width: ' . $fail_percent . '%"></div>';
    echo '      </div>';
    echo '    </div>';

    // Grade Band Distribution Table.
    $total_graded = count($bands['A']) + count($bands['B']) + count($bands['C']) + count($bands['D']);
    $total_graded = ($total_graded > 0) ? $total_graded : 1;
    echo '    <hr class="my-4">';
    echo '    <h5 class="mb-3 font-weight-bold text-secondary text-left">'
        . '<i class="fa fa-bar-chart mr-2"></i>Performance Band Distribution</h5>';
    echo '    <div class="table-responsive mb-2">';
    echo '      <table class="table table-bordered m-0 text-center">';
    echo '        <thead class="thead-dark">';
    echo '          <tr><th>Band</th><th>Descriptor</th><th>Score Range</th>'
        . '<th>Students</th><th>% of Graded</th></tr>';
    echo '        </thead>';
    echo '        <tbody>';
    echo '          <tr style="background:#d4edda;">'
        . '<td><strong>A</strong></td>'
        . '<td><strong>High Achiever</strong></td>'
        . '<td>≥ 80%</td>'
        . '<td class="font-weight-bold">' . count($bands['A']) . '</td>'
        . '<td>' . round(count($bands['A']) / $total_graded * 100, 1) . '%</td></tr>';
    echo '          <tr style="background:#d1ecf1;">'
        . '<td><strong>B</strong></td>'
        . '<td><strong>Satisfactory</strong></td>'
        . '<td>60 – 79%</td>'
        . '<td class="font-weight-bold">' . count($bands['B']) . '</td>'
        . '<td>' . round(count($bands['B']) / $total_graded * 100, 1) . '%</td></tr>';
    echo '          <tr style="background:#fff3cd;">'
        . '<td><strong>C</strong></td>'
        . '<td><strong>Borderline</strong></td>'
        . '<td>50 – 59%</td>'
        . '<td class="font-weight-bold">' . count($bands['C']) . '</td>'
        . '<td>' . round(count($bands['C']) / $total_graded * 100, 1) . '%</td></tr>';
    echo '          <tr style="background:#f8d7da;">'
        . '<td><strong>D</strong></td>'
        . '<td><strong>Fail</strong></td>'
        . '<td>&lt; 50%</td>'
        . '<td class="font-weight-bold">' . count($bands['D']) . '</td>'
        . '<td>' . round(count($bands['D']) / $total_graded * 100, 1) . '%</td></tr>';
    echo '        </tbody>';
    echo '      </table>';
    echo '    </div>';
    
    // Failure Diagnostics Component (Combined Exams Only)
    if ($is_combined) {
        $fail_theory_only = 0; $fail_ospe_only = 0; $fail_both = 0;
        $t_pass = floatval($theory_item->gradepass) > 0 ? floatval($theory_item->gradepass) : floatval($theory_item->grademax) * 0.50;
        $o_pass = floatval($ospe_item->gradepass) > 0 ? floatval($ospe_item->gradepass) : floatval($ospe_item->grademax) * 0.50;
        
        foreach ($enrolled_users as $user) {
            $uid = $user->id;
            $ts = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0) ? floatval($t_grades[$uid]) : 0;
            $os = (isset($o_grades[$uid]) && floatval($o_grades[$uid]) > 0) ? floatval($o_grades[$uid]) : 0;
            
            if ($ts > 0 || $os > 0) {
                $pass_t = ($ts >= $t_pass);
                $pass_o = ($os >= $o_pass);
                if (!$pass_t && $pass_o) {
                    $fail_theory_only++;
                } else if ($pass_t && !$pass_o) {
                    $fail_ospe_only++;
                } else if (!$pass_t && !$pass_o) {
                    $fail_both++;
                }
            }
        }
        echo '    <div class="mt-4">';
        echo '      <h5 class="mb-3 font-weight-bold text-secondary text-left"><i class="fa fa-stethoscope mr-2"></i>Failure Breakdown Diagnostics</h5>';
        echo '      <div class="table-responsive">';
        echo '        <table class="table table-bordered table-striped m-0 text-left">';
        echo '          <thead class="thead-dark"><tr><th>Point of Failure</th><th class="text-center">Student Count</th></tr></thead>';
        echo '          <tbody>';
        echo '            <tr><td><strong>Failed Theory Only</strong> <span class="text-muted small">(Passed OSPE but missed Theory cutoff)</span></td><td class="text-center h5 text-danger font-weight-bold">' . $fail_theory_only . '</td></tr>';
        echo '            <tr><td><strong>Failed OSPE Only</strong> <span class="text-muted small">(Passed Theory but missed OSPE cutoff)</span></td><td class="text-center h5 text-danger font-weight-bold">' . $fail_ospe_only . '</td></tr>';
        echo '            <tr><td><strong>Failed Both</strong> <span class="text-muted small">(Missed cutoffs on both exams)</span></td><td class="text-center h5 text-danger font-weight-bold">' . $fail_both . '</td></tr>';
        echo '          </tbody>';
        echo '        </table>';
        echo '      </div>';
        echo '    </div>';
    }
    
    // Top 5 High-Performers Leaderboard
    arsort($leaderboard_stack);
    $top_five = array_slice($leaderboard_stack, 0, 5, true);
    
    echo '    <div class="mt-5">';
    echo '      <h5 class="mb-3 font-weight-bold text-dark text-left"><i class="fa fa-star text-warning mr-2"></i>Top 5 Best Performing Students</h5>';
    echo '      <div class="d-flex flex-wrap justify-content-center">';
    
    $rank = 1;
    foreach ($top_five as $top_uid => $achieved_score) {
        if (isset($enrolled_users[$top_uid])) {
            $student_user = $enrolled_users[$top_uid];
            $user_pic = $OUTPUT->user_picture($student_user, array('size' => 50, 'link' => false, 'class' => 're-leaderboard-img mb-2'));
            
            echo '  <div class="mb-3 px-2 text-center" style="flex: 1; min-width: 175px; max-width: 210px;">';
            echo '    <div class="card border shadow-sm p-3 leaderboard-card">';
            echo '      <div class="badge badge-warning text-dark font-weight-bold mb-2 position-absolute" style="top:10px; left:10px;">Rank #' . $rank . '</div>';
            echo '      <div class="mt-2">' . $user_pic . '</div>';
            echo '      <div class="font-weight-bold text-dark text-truncate mt-1">' . s($student_user->firstname) . ' ' . s($student_user->lastname) . '</div>';
            echo '      <div class="h5 font-weight-bold text-success mt-1 mb-0">' . $achieved_score . '</div>';
            echo '      <small class="text-muted">' . ($is_combined ? 'Combined Total' : 'Points') . '</small>';
            echo '    </div>';
            echo '  </div>';
            $rank++;
        }
    }
    echo '      </div>';
    echo '    </div>';
    
    echo '  </div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info re-no-print">Please select an exam configuration and click "Apply Filters" to load the matrix dashboard.</div>';
}

echo '</div>'; // End .report-examstats wrapper.
echo $OUTPUT->footer();
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
 * diagnostics, and a top-performers leaderboard for Theory, Skill Exam, or
 * combined Theory+Skill Exam quiz assessments.
 *
 * Rendering is handled via Mustache templates under templates/.
 * JavaScript interactions are handled via amd/src/dashboard.js.
 * CSS lives in styles.css (auto-loaded by Moodle).
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Security check: Ensure this file is running inside Moodle.
require_once(__DIR__ . '/../../config.php');

require_login();

// -------------------------------------------------------------------------
// Input parameters
// -------------------------------------------------------------------------
$courseid      = optional_param('courseid',      0,  PARAM_INT);
$quizid_theory = optional_param('quizid_theory', 0,  PARAM_INT);
$quizid_skill   = optional_param('quizid_skill',   0,  PARAM_INT);
$calc_basis    = optional_param('calc_basis',    'appeared', PARAM_ALPHA); // Appeared vs Registered (KMU rule).
$export        = optional_param('export',        '', PARAM_ALPHA);

// -------------------------------------------------------------------------
// Guard against stale quiz IDs left over from a previously-selected course.
//
// The course dropdown auto-submits on change, which can carry forward the
// Theory/Skill exam selections from whichever course was previously shown.
// Since quiz ids are unique site-wide, a stale id would otherwise still
// resolve to a real (but wrong-course) grade item further down. Confirm
// each selected quiz actually belongs to $courseid before trusting it.
// -------------------------------------------------------------------------
if ($courseid > 0) {
    if ($quizid_theory > 0 && !$DB->record_exists('quiz', array('id' => $quizid_theory, 'course' => $courseid))) {
        $quizid_theory = 0;
    }
    if ($quizid_skill > 0 && !$DB->record_exists('quiz', array('id' => $quizid_skill, 'course' => $courseid))) {
        $quizid_skill = 0;
    }
}

// Determine active exam mode.
$has_theory        = ($quizid_theory > 0);
$has_skill          = ($quizid_skill   > 0);
$is_combined       = ($has_theory && $has_skill);
$any_exam_selected = ($has_theory || $has_skill);

// -------------------------------------------------------------------------
// Capability check.
//
// Managers (and admins) hold report/examstats:view at system level and can
// browse the report for any course. Teachers normally hold the capability
// only at course level, so once a course is selected we re-check there.
// This also keeps a teacher of Course A from viewing Course B's data by
// editing the courseid in the URL.
// -------------------------------------------------------------------------
$systemcontext = context_system::instance();
$hassystemview = has_capability('report/examstats:view', $systemcontext);

if ($courseid > 0) {
    $course = get_course($courseid);
    $context = context_course::instance($courseid);
    require_login($course);
    require_capability('report/examstats:view', $context);
} else {
    $context = $systemcontext;
    if (!$hassystemview) {
        // No sitewide access: the user must hold the capability in at least
        // one course to be allowed onto the course-selector screen.
        $ownedcourses = get_user_capability_course('report/examstats:view', null, false, 'id');
        if (empty($ownedcourses)) {
            require_capability('report/examstats:view', $systemcontext);
        }
    }
}

// -------------------------------------------------------------------------
// Configurable performance band thresholds and labels (with sane defaults
// if the admin hasn't saved the settings page yet). Loaded here, before
// the CSV Export Engine, so both the CSV export and the on-screen
// dashboard use the exact same thresholds/labels and can never disagree.
// -------------------------------------------------------------------------
$band_a_min = get_config('report_examstats', 'band_a_min');
$band_a_min = ($band_a_min !== false && $band_a_min !== '') ? floatval($band_a_min) : 80;
$band_b_min = get_config('report_examstats', 'band_b_min');
$band_b_min = ($band_b_min !== false && $band_b_min !== '') ? floatval($band_b_min) : 60;
$band_c_min = get_config('report_examstats', 'band_c_min');
$band_c_min = ($band_c_min !== false && $band_c_min !== '') ? floatval($band_c_min) : 50;

$band_a_label = get_config('report_examstats', 'band_a_label');
$band_a_label = ($band_a_label !== false && $band_a_label !== '') ? $band_a_label : get_string('highachieverdesc', 'report_examstats');
$band_b_label = get_config('report_examstats', 'band_b_label');
$band_b_label = ($band_b_label !== false && $band_b_label !== '') ? $band_b_label : get_string('satisfactorydesc', 'report_examstats');
$band_c_label = get_config('report_examstats', 'band_c_label');
$band_c_label = ($band_c_label !== false && $band_c_label !== '') ? $band_c_label : get_string('borderlinedesc', 'report_examstats');
$band_d_label = get_config('report_examstats', 'band_d_label');
$band_d_label = ($band_d_label !== false && $band_d_label !== '') ? $band_d_label : get_string('faildesc', 'report_examstats');

// Localized Pass/Fail status text, shared by the CSV export and dashboard.
$str_pass = get_string('statuspass', 'report_examstats');
$str_fail = get_string('statusfail', 'report_examstats');

// -------------------------------------------------------------------------
// CSV Export Engine (Supports Single and Combined configurations)
// Runs before any page output and exits early.
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
        $skill_item = $has_skill
            ? $DB->get_record('grade_items', array('iteminstance' => $quizid_skill, 'itemmodule' => 'quiz'))
            : null;

        // Max marks and pass thresholds.
        $t_max  = $theory_item ? floatval($theory_item->grademax) : 0;
        $skill_max  = $skill_item   ? floatval($skill_item->grademax)   : 0;
        $t_pass = ($theory_item && floatval($theory_item->gradepass) > 0)
            ? floatval($theory_item->gradepass) : $t_max * 0.50;
        $skill_pass = ($skill_item && floatval($skill_item->gradepass) > 0)
            ? floatval($skill_item->gradepass) : $skill_max * 0.50;

        // Build score map.
        $t_grades = array();
        $skill_grades = array();
        $gsql = "SELECT userid, finalgrade FROM {grade_grades} WHERE itemid = ?";
        if ($theory_item) {
            $t_grades = $DB->get_records_sql_menu($gsql, array($theory_item->id));
        }
        if ($skill_item) {
            $skill_grades = $DB->get_records_sql_menu($gsql, array($skill_item->id));
        }

        // Classify every student into a band with full data.
        $band_rows = array();
        foreach ($student_users as $su) {
            $uid = $su->id;
            $ts  = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0)
                ? floatval($t_grades[$uid]) : 0;
            $skillscore  = (isset($skill_grades[$uid]) && floatval($skill_grades[$uid]) > 0)
                ? floatval($skill_grades[$uid]) : 0;

            // Eligibility rule shared with the on-screen dashboard: "registered"
            // (KMU rule) includes every enrolled student, scoring absentees as
            // zero; "appeared" (standard) only includes students who attempted
            // at least one of the selected exam(s).
            if ($calc_basis === 'registered') {
                $csv_evaluate = true;
            } else if ($is_combined) {
                $csv_evaluate = ($ts > 0 || $skillscore > 0);
            } else {
                $csv_evaluate = ($has_theory ? ($ts > 0) : ($skillscore > 0));
            }
            if (!$csv_evaluate) {
                continue;
            }

            if ($is_combined) {
                $grand_total = $ts + $skillscore;
                $maxval      = $t_max + $skill_max;
                $t_remark    = ($ts >= $t_pass) ? $str_pass : $str_fail;
                $skill_remark    = ($skillscore >= $skill_pass) ? $str_pass : $str_fail;
                $status      = ($t_remark === $str_pass && $skill_remark === $str_pass) ? $str_pass : $str_fail;
            } else {
                $active_score = $has_theory ? $ts : $skillscore;
                $grand_total = $active_score;
                $maxval      = $has_theory ? $t_max : $skill_max;
                $pass_line   = $has_theory ? $t_pass : $skill_pass;
                $t_remark    = '';
                $skill_remark    = '';
                $status      = ($active_score >= $pass_line) ? $str_pass : $str_fail;
            }

            $pct = ($maxval > 0) ? ($grand_total / $maxval) * 100 : 0;

            if ($pct >= $band_a_min) {
                $band = 'A'; $descriptor = $band_a_label;
            } else if ($pct >= $band_b_min) {
                $band = 'B'; $descriptor = $band_b_label;
            } else if ($pct >= $band_c_min) {
                $band = 'C'; $descriptor = $band_c_label;
            } else {
                $band = 'D'; $descriptor = $band_d_label;
            }

            $band_rows[$uid] = array(
                'firstname'   => $su->firstname,
                'lastname'    => $su->lastname,
                'email'       => $su->email,
                'ts'          => $ts,
                't_remark'    => $t_remark,
                'skillscore'          => $skillscore,
                'skill_remark'    => $skill_remark,
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

        // Filenames now follow the admin's configured band labels (settings
        // page) instead of hardcoded English names, so a renamed band (e.g.
        // "Fail" -> "Poor Performance") is reflected in the downloaded file
        // too, not just the on-screen table.
        $filename_map = array(
            'failedcsv'      => preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', trim($band_d_label))),
            'highachievers'  => preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', trim($band_a_label))),
            'satisfactory'   => preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', trim($band_b_label))),
            'borderline'     => preg_replace('/[^A-Za-z0-9_\-]/', '', str_replace(' ', '_', trim($band_c_label))),
            'completeresult' => 'Complete_Result',
        );
        foreach ($filename_map as $key => $value) {
            if ($value === '') {
                $filename_map[$key] = 'Result';
            }
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'
            . ($filename_map[$export] ?? 'Result') . '.csv"');
        $out = fopen('php://output', 'w');

        // Dynamic column headers based on actual max marks.
        if ($is_combined) {
            fputcsv($out, array(
                get_string('csv_firstname', 'report_examstats'),
                get_string('csv_lastname', 'report_examstats'),
                get_string('csv_email', 'report_examstats'),
                get_string('csv_theoryscore', 'report_examstats') . ' (' . get_string('csv_outof', 'report_examstats') . ' ' . $t_max . ')',
                get_string('csv_remarks', 'report_examstats'),
                get_string('csv_skillscore', 'report_examstats') . ' (' . get_string('csv_outof', 'report_examstats') . ' ' . $skill_max . ')',
                get_string('csv_remarks', 'report_examstats'),
                get_string('csv_grandtotal', 'report_examstats') . ' (' . get_string('csv_outof', 'report_examstats') . ' ' . ($t_max + $skill_max) . ')',
                get_string('csv_percentofmax', 'report_examstats'),
                get_string('csv_descriptor', 'report_examstats'),
                get_string('csv_status', 'report_examstats'),
            ));
        } else {
            $single_max   = $has_theory ? $t_max : $skill_max;
            $single_label = $has_theory
                ? get_string('csv_theoryscore', 'report_examstats')
                : get_string('csv_skillscore', 'report_examstats');
            fputcsv($out, array(
                get_string('csv_firstname', 'report_examstats'),
                get_string('csv_lastname', 'report_examstats'),
                get_string('csv_email', 'report_examstats'),
                $single_label . ' (' . get_string('csv_outof', 'report_examstats') . ' ' . $single_max . ')',
                get_string('csv_remarks', 'report_examstats'),
                get_string('csv_percentofmax', 'report_examstats'),
                get_string('csv_descriptor', 'report_examstats'),
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
                    $row['skillscore'],
                    $row['skill_remark'],
                    $row['grand_total'],
                    $row['pct'] . '%',
                    $row['descriptor'],
                    $row['status'],
                ));
            } else {
                $single_score = $has_theory ? $row['ts'] : $row['skillscore'];
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

// -------------------------------------------------------------------------
// Initialise Moodle page
// -------------------------------------------------------------------------
$url = new moodle_url('/report/examstats/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'report_examstats'));
$PAGE->set_heading(get_string('pluginname', 'report_examstats'));

// Explicit, version-safe scoping hook for styles.css — lets us namespace
// plugin CSS (especially the print rules) to only this page instead of
// leaking global selectors like bare html/body across all of Moodle.
$PAGE->add_body_class('report-examstats');

// Register AMD module — replaces all inline onclick handlers.
$PAGE->requires->js_call_amd('report_examstats/dashboard', 'init');

echo $OUTPUT->header();
echo '<div class="report-examstats">';

// -------------------------------------------------------------------------
// Filter form template data
// -------------------------------------------------------------------------
if ($hassystemview) {
    // Managers/admins with sitewide access can browse every course.
    $courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname');
} else {
    // Teachers only see courses where they hold report/examstats:view.
    $courses = get_user_capability_course('report/examstats:view', null, false, 'id, fullname', 'fullname ASC');
    $courses = $courses ? $courses : array();
}

$courses_data = array();
foreach ($courses as $c) {
    $courses_data[] = array(
        'id'       => $c->id,
        'fullname' => $c->fullname,
        'selected' => ($c->id == $courseid),
    );
}

$theory_quizzes_data = array();
$skill_quizzes_data   = array();

if ($courseid > 0) {
    // Read admin-configurable quiz name filter patterns (fall back to defaults if not set).
    $theory_raw = get_config('report_examstats', 'theory_pattern');
    $theory_pat = '%' . ($theory_raw !== false && $theory_raw !== '' ? $theory_raw : 'Theory') . '%';

    // Skill exam patterns: comma-separated list, e.g. "Skill, OSCE, OSPE,
    // Practical" — a quiz matches if its name contains any one of these terms.
    $skill_raw = get_config('report_examstats', 'skill_pattern');
    $skill_raw = ($skill_raw !== false && $skill_raw !== '') ? $skill_raw : 'Skill';
    $skill_terms = array_filter(array_map('trim', explode(',', $skill_raw)), function($term) {
        return $term !== '';
    });
    if (empty($skill_terms)) {
        $skill_terms = array('Skill');
    }

    $theory_quizzes = $DB->get_records_select(
        'quiz',
        'course = ? AND ' . $DB->sql_like('name', '?', false),
        array($courseid, $theory_pat),
        'name ASC',
        'id, name'
    );

    $skill_like_clauses = array();
    $skill_like_params   = array($courseid);
    foreach ($skill_terms as $term) {
        $skill_like_clauses[] = $DB->sql_like('name', '?', false);
        $skill_like_params[]  = '%' . $term . '%';
    }

    $skill_quizzes = $DB->get_records_select(
        'quiz',
        'course = ? AND (' . implode(' OR ', $skill_like_clauses) . ')',
        $skill_like_params,
        'name ASC',
        'id, name'
    );

    foreach ($theory_quizzes as $q) {
        $theory_quizzes_data[] = array(
            'id'       => $q->id,
            'name'     => $q->name,
            'selected' => ($q->id == $quizid_theory),
        );
    }

    foreach ($skill_quizzes as $q) {
        $skill_quizzes_data[] = array(
            'id'       => $q->id,
            'name'     => $q->name,
            'selected' => ($q->id == $quizid_skill),
        );
    }
}

$calcbasis_options = array(
    array(
        'value'    => 'appeared',
        'label'    => get_string('calcbasisappeared', 'report_examstats'),
        'selected' => ($calc_basis === 'appeared'),
    ),
    array(
        'value'    => 'registered',
        'label'    => get_string('calcbasisregistered', 'report_examstats'),
        'selected' => ($calc_basis === 'registered'),
    ),
);

echo $OUTPUT->render_from_template('report_examstats/filterform', array(
    'courseurl'      => (new moodle_url('/report/examstats/index.php'))->out(false),
    'courses'        => $courses_data,
    'courseselected' => ($courseid > 0),
    'theoryquizzes'  => $theory_quizzes_data,
    'skillquizzes'   => $skill_quizzes_data,
    'calcbasisoptions'   => $calcbasis_options,
    'strcourse'          => get_string('course',        'report_examstats'),
    'strselectcourse'    => get_string('selectcourse',  'report_examstats'),
    'strtheoryexam'      => get_string('theoryexam',    'report_examstats'),
    'strskillexam'       => get_string('skillexam',     'report_examstats'),
    'strcalcbasis'       => get_string('calcbasis',     'report_examstats'),
    'strnonedeselect'    => get_string('nonedeselect',  'report_examstats'),
    'strapplyfilters'    => get_string('applyfilters',  'report_examstats'),
));

// -------------------------------------------------------------------------
// Statistics Engine + Dashboard rendering
// -------------------------------------------------------------------------
if ($courseid > 0 && $any_exam_selected) {

    $course_record = $DB->get_record('course', array('id' => $courseid));

    // Resolve exam names for the dashboard header.
    $active_exam_names = array();
    $theory_item = $has_theory
        ? $DB->get_record('grade_items', array('iteminstance' => $quizid_theory, 'itemmodule' => 'quiz'))
        : null;
    $skill_item = $has_skill
        ? $DB->get_record('grade_items', array('iteminstance' => $quizid_skill, 'itemmodule' => 'quiz'))
        : null;

    if ($theory_item) {
        $active_exam_names[] = $DB->get_field('quiz', 'name', array('id' => $quizid_theory));
    }
    if ($skill_item) {
        $active_exam_names[] = $DB->get_field('quiz', 'name', array('id' => $quizid_skill));
    }
    $printed_exam_titles = implode(' & ', $active_exam_names);

    // Fetch enrolled students only (excludes teachers/admins).
    //
    // Includes the full field set $OUTPUT->user_picture() requires (used
    // later for the leaderboard avatars) — omitting these caused a
    // "Missing 'firstnamephonetic' property" debugging() notice to be
    // logged on every single page load.
    $enrolled_users = get_enrolled_users(
        context_course::instance($courseid),
        'mod/quiz:attempt', 0,
        'u.id, u.picture, u.firstname, u.lastname, u.firstnamephonetic, ' .
        'u.lastnamephonetic, u.middlename, u.alternatename, u.imagealt, u.email'
    );
    $total_enrolled = count($enrolled_users);
    if ($total_enrolled <= 0) {
        $total_enrolled = 1;
    }

    // Build grade maps.
    $t_grades = array();
    $skill_grades = array();
    $sql = "SELECT userid, finalgrade FROM {grade_grades} WHERE itemid = ? AND finalgrade IS NOT NULL";
    if ($has_theory && $theory_item) {
        $t_grades = $DB->get_records_sql_menu($sql, array($theory_item->id));
    }
    if ($has_skill && $skill_item) {
        $skill_grades = $DB->get_records_sql_menu($sql, array($skill_item->id));
    }

    // Configurable performance band thresholds/labels were already loaded
    // near the top of the file (shared with the CSV Export Engine above).

    // Run pass/fail and band calculations.
    $passed_count   = 0;
    $failed_count   = 0;
    $p_theory_count = 0;
    $p_skill_count   = 0;
    $p_both_count   = 0;

    $leaderboard_stack = array();
    $bands = array('A' => array(), 'B' => array(), 'C' => array(), 'D' => array());

    foreach ($enrolled_users as $user) {
        $uid = $user->id;
        $ts  = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0)
            ? floatval($t_grades[$uid]) : 0;
        $skillscore  = (isset($skill_grades[$uid]) && floatval($skill_grades[$uid]) > 0)
            ? floatval($skill_grades[$uid]) : 0;

        $has_active_t = ($ts > 0);
        $has_active_skill = ($skillscore > 0);

        if ($has_active_t) { $p_theory_count++; }
        if ($has_active_skill) { $p_skill_count++; }
        if ($has_active_t && $has_active_skill) { $p_both_count++; }

        // Determine if this student should be included in the pass/fail math.
        // "registered" (KMU rule) includes every enrolled student, scoring
        // absentees as zero; "appeared" (standard) only evaluates students
        // who actually attempted the selected exam(s).
        if ($calc_basis === 'registered') {
            $evaluate_student = true;
        } else if ($is_combined) {
            $evaluate_student = ($has_active_t || $has_active_skill);
        } else {
            $evaluate_student = ($has_theory ? $has_active_t : $has_active_skill);
        }

        if ($is_combined) {
            if ($evaluate_student) {
                $t_max  = floatval($theory_item->grademax);
                $t_pass = floatval($theory_item->gradepass) > 0
                    ? floatval($theory_item->gradepass) : $t_max * 0.50;
                $skill_max  = floatval($skill_item->grademax);
                $skill_pass = floatval($skill_item->gradepass) > 0
                    ? floatval($skill_item->gradepass) : $skill_max * 0.50;

                if ($ts >= $t_pass && $skillscore >= $skill_pass) {
                    $passed_count++;
                } else {
                    $failed_count++;
                }
                $combined_score = $ts + $skillscore;
                $leaderboard_stack[$uid] = $combined_score;

                $combined_max = $t_max + $skill_max;
                $pct = ($combined_max > 0) ? ($combined_score / $combined_max) * 100 : 0;
                if ($pct >= $band_a_min) {
                    $bands['A'][] = $uid;
                } else if ($pct >= $band_b_min) {
                    $bands['B'][] = $uid;
                } else if ($pct >= $band_c_min) {
                    $bands['C'][] = $uid;
                } else {
                    $bands['D'][] = $uid;
                }
            }
        } else {
            $active_score = $has_theory ? $ts : $skillscore;
            $active_item  = $has_theory ? $theory_item : $skill_item;

            if ($evaluate_student) {
                $pass_line = floatval($active_item->gradepass) > 0
                    ? floatval($active_item->gradepass)
                    : floatval($active_item->grademax) * 0.50;

                if ($active_score >= $pass_line) {
                    $passed_count++;
                } else {
                    $failed_count++;
                }
                $leaderboard_stack[$uid] = $active_score;

                $pct = (floatval($active_item->grademax) > 0)
                    ? ($active_score / floatval($active_item->grademax)) * 100 : 0;
                if ($pct >= $band_a_min) {
                    $bands['A'][] = $uid;
                } else if ($pct >= $band_b_min) {
                    $bands['B'][] = $uid;
                } else if ($pct >= $band_c_min) {
                    $bands['C'][] = $uid;
                } else {
                    $bands['D'][] = $uid;
                }
            }
        }
    }

    $evaluated_pool_total = $passed_count + $failed_count;
    if ($evaluated_pool_total <= 0) {
        $evaluated_pool_total = 1;
    }
    $pass_percent = round(($passed_count / $evaluated_pool_total) * 100, 1);
    $fail_percent = round(($failed_count / $evaluated_pool_total) * 100, 1);

    // -------------------------------------------------------------------------
    // Action controls row: Print + CSV dropdown
    // Uses data-action attributes picked up by amd/src/dashboard.js.
    // -------------------------------------------------------------------------
    $base_export_params = array(
        'courseid'      => $courseid,
        'quizid_theory' => $quizid_theory,
        'quizid_skill'   => $quizid_skill,
        'calc_basis'    => $calc_basis,
    );

    $url_failed        = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'failedcsv')));
    $url_highachievers = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'highachievers')));
    $url_satisfactory  = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'satisfactory')));
    $url_borderline    = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'borderline')));
    $url_complete      = new moodle_url('/report/examstats/index.php',
        array_merge($base_export_params, array('export' => 'completeresult')));

    echo $OUTPUT->render_from_template('report_examstats/toolbar', array(
        'urlhighachievers'    => $url_highachievers->out(false),
        'urlsatisfactory'     => $url_satisfactory->out(false),
        'urlborderline'       => $url_borderline->out(false),
        'urlfailed'           => $url_failed->out(false),
        'urlcomplete'         => $url_complete->out(false),
        'bandamin'            => $band_a_min,
        'bandbmin'            => $band_b_min,
        'bandbmax'            => $band_a_min - 1,
        'bandcmin'            => $band_c_min,
        'bandcmax'            => $band_b_min - 1,
        'strprintreport'      => get_string('printreport',       'report_examstats'),
        'strdownloadcsv'      => get_string('downloadcsv',       'report_examstats'),
        'strbyperformanceband' => get_string('byperformanceband', 'report_examstats'),
        'strhighachievers'    => $band_a_label,
        'strsatisfactory'     => $band_b_label,
        'strborderline'       => $band_c_label,
        'strfailedstudents'   => $band_d_label,
        'strcompleteresult'   => get_string('completeresult',    'report_examstats'),
    ));

    // -------------------------------------------------------------------------
    // Analytics dashboard card — wraps all template output below
    // -------------------------------------------------------------------------
    echo '<div id="re-analytics-dashboard" class="card border-0 shadow-sm">';
    echo '  <div class="card-body p-4">';

    $display_mode_text = ($calc_basis === 'registered')
        ? get_string('calcbasisregistered', 'report_examstats')
        : get_string('calcbasisappeared', 'report_examstats');

    echo $OUTPUT->render_from_template('report_examstats/reportheader', array(
        'strpluginname'  => get_string('pluginname',  'report_examstats'),
        'strcourse'      => get_string('course',      'report_examstats'),
        'coursefullname' => $course_record->fullname,
        'strexamtarget'  => get_string('examtarget',  'report_examstats'),
        'examtitles'     => $printed_exam_titles,
        'strbasis'       => get_string('basis',       'report_examstats'),
        'basistext'      => $display_mode_text,
    ));

    // -------------------------------------------------------------------------
    // KPI Cards template data
    // -------------------------------------------------------------------------
    if ($is_combined) {
        $attend_pct   = round(($p_both_count / $total_enrolled) * 100, 1);
        $absent_count = $total_enrolled - $p_both_count;
        $t_cutoff_val = floatval($theory_item->gradepass) > 0
            ? floatval($theory_item->gradepass) : floatval($theory_item->grademax) * 0.50;
        $skill_cutoff_val = floatval($skill_item->gradepass) > 0
            ? floatval($skill_item->gradepass) : floatval($skill_item->grademax) * 0.50;

        $kpi_data = array(
            'iscombined'      => true,
            'attendpct'       => $attend_pct,
            'presentcount'    => $p_both_count,
            'totalenrolled'   => $total_enrolled,
            'presentlabel'    => get_string('presentinboth', 'report_examstats'),
            'ptheorycount'    => $p_theory_count,
            'pskillcount'     => $p_skill_count,
            'absentcount'     => $absent_count,
            'theorycutoffval' => $t_cutoff_val,
            'theorymax'       => floatval($theory_item->grademax),
            'skillcutoffval'  => $skill_cutoff_val,
            'skillmax'        => floatval($skill_item->grademax),
        );
    } else {
        $single_present = $has_theory ? $p_theory_count : $p_skill_count;
        $attend_pct     = round(($single_present / $total_enrolled) * 100, 1);
        $absent_count   = $total_enrolled - $single_present;
        $act_item       = $has_theory ? $theory_item : $skill_item;
        $act_cutoff     = floatval($act_item->gradepass) > 0
            ? floatval($act_item->gradepass) : floatval($act_item->grademax) * 0.50;

        $kpi_data = array(
            'iscombined'      => false,
            'attendpct'       => $attend_pct,
            'presentcount'    => $single_present,
            'totalenrolled'   => $total_enrolled,
            'presentlabel'    => get_string('presentoutof', 'report_examstats'),
            'absentcount'     => $absent_count,
            'activecutoffval' => $act_cutoff,
            'activemax'       => floatval($act_item->grademax),
            'activemodelabel' => $has_theory
                ? get_string('singletheory', 'report_examstats')
                : get_string('singleskill',  'report_examstats'),
        );
    }

    // Shared KPI string keys.
    $kpi_data['strattendance']   = get_string('attendance',    'report_examstats');
    $kpi_data['strabsent']       = get_string('absent',        'report_examstats');
    $kpi_data['strdidnotattempt'] = get_string('didnotattempt', 'report_examstats');
    $kpi_data['strtheorycutoff'] = get_string('theorycutoff',  'report_examstats');
    $kpi_data['strskillcutoff']  = get_string('skillcutoff',   'report_examstats');
    $kpi_data['strpassingcutoff'] = get_string('passingcutoff', 'report_examstats');
    $kpi_data['stractivemode']   = get_string('activemode',    'report_examstats');
    $kpi_data['stroutof']        = get_string('outof',         'report_examstats');

    echo $OUTPUT->render_from_template('report_examstats/kpicards', $kpi_data);

    // -------------------------------------------------------------------------
    // Band Distribution template data
    // -------------------------------------------------------------------------
    $total_graded = count($bands['A']) + count($bands['B']) + count($bands['C']) + count($bands['D']);
    $total_graded = ($total_graded > 0) ? $total_graded : 1;

    $band_data = array(
        'strpassed'      => get_string('passed',      'report_examstats'),
        'strfailed'      => get_string('failed',      'report_examstats'),
        'strstudents'    => get_string('students',    'report_examstats'),
        'passedcount'    => $passed_count,
        'failedcount'    => $failed_count,
        'passpercent'    => $pass_percent,
        'failpercent'    => $fail_percent,
        'strband'                      => get_string('band',                     'report_examstats'),
        'strdescriptor'                => get_string('descriptor',               'report_examstats'),
        'strscorerange'                => get_string('scorerange',               'report_examstats'),
        'strpctgraded'                 => get_string('pctgraded',                'report_examstats'),
        'strperformancebanddistribution' => get_string('performancebanddistribution', 'report_examstats'),
        'bands' => array(
            array(
                'letter'     => 'A',
                'rowstyle'   => 'background:#d4edda;',
                'descriptor' => $band_a_label,
                'scorerange' => '&ge; ' . $band_a_min . '%',
                'count'      => count($bands['A']),
                'percent'    => round(count($bands['A']) / $total_graded * 100, 1),
            ),
            array(
                'letter'     => 'B',
                'rowstyle'   => 'background:#d1ecf1;',
                'descriptor' => $band_b_label,
                'scorerange' => $band_b_min . '% &ndash; &lt; ' . $band_a_min . '%',
                'count'      => count($bands['B']),
                'percent'    => round(count($bands['B']) / $total_graded * 100, 1),
            ),
            array(
                'letter'     => 'C',
                'rowstyle'   => 'background:#fff3cd;',
                'descriptor' => $band_c_label,
                'scorerange' => $band_c_min . '% &ndash; &lt; ' . $band_b_min . '%',
                'count'      => count($bands['C']),
                'percent'    => round(count($bands['C']) / $total_graded * 100, 1),
            ),
            array(
                'letter'     => 'D',
                'rowstyle'   => 'background:#f8d7da;',
                'descriptor' => $band_d_label,
                'scorerange' => '&lt; ' . $band_c_min . '%',
                'count'      => count($bands['D']),
                'percent'    => round(count($bands['D']) / $total_graded * 100, 1),
            ),
        ),
    );

    echo $OUTPUT->render_from_template('report_examstats/banddistribution', $band_data);

    // -------------------------------------------------------------------------
    // Failure Breakdown template data (combined mode only)
    // -------------------------------------------------------------------------
    if ($is_combined) {
        $fail_theory_only = 0;
        $fail_skill_only   = 0;
        $fail_both        = 0;

        $t_pass = floatval($theory_item->gradepass) > 0
            ? floatval($theory_item->gradepass) : floatval($theory_item->grademax) * 0.50;
        $skill_pass = floatval($skill_item->gradepass) > 0
            ? floatval($skill_item->gradepass) : floatval($skill_item->grademax) * 0.50;

        foreach ($enrolled_users as $user) {
            $uid = $user->id;
            $ts  = (isset($t_grades[$uid]) && floatval($t_grades[$uid]) > 0)
                ? floatval($t_grades[$uid]) : 0;
            $skillscore  = (isset($skill_grades[$uid]) && floatval($skill_grades[$uid]) > 0)
                ? floatval($skill_grades[$uid]) : 0;

            // Same eligibility rule as the main pass/fail loop: "registered"
            // (KMU rule) includes every enrolled student, scoring absentees
            // as zero on both exams; "appeared" (standard) only considers
            // students who attempted at least one of the two exams.
            $breakdown_evaluate = ($calc_basis === 'registered')
                ? true
                : ($ts > 0 || $skillscore > 0);

            if ($breakdown_evaluate) {
                $pass_t = ($ts >= $t_pass);
                $pass_o = ($skillscore >= $skill_pass);
                if (!$pass_t && $pass_o) {
                    $fail_theory_only++;
                } else if ($pass_t && !$pass_o) {
                    $fail_skill_only++;
                } else if (!$pass_t && !$pass_o) {
                    $fail_both++;
                }
            }
        }

        echo $OUTPUT->render_from_template('report_examstats/failurebreakdown', array(
            'strfailurebreakdown'         => get_string('failurebreakdown',         'report_examstats'),
            'strpointoffailure'           => get_string('pointoffailure',           'report_examstats'),
            'strstudentcount'             => get_string('studentcount',             'report_examstats'),
            'strfailedtheoryonly'         => get_string('failedtheoryonly',         'report_examstats'),
            'strfailedskillonly'          => get_string('failedskillonly',          'report_examstats'),
            'strfailedboth'               => get_string('failedboth',               'report_examstats'),
            'strpassedskillbutmissedtheory' => get_string('passedskillbutmissedtheory', 'report_examstats'),
            'strpassedtheorybutmissedskill' => get_string('passedtheorybutmissedskill', 'report_examstats'),
            'strmissedbothcutoffs'        => get_string('missedbothcutoffs',        'report_examstats'),
            'failtheoryonly'              => $fail_theory_only,
            'failskillonly'               => $fail_skill_only,
            'failboth'                    => $fail_both,
        ));
    }

    // -------------------------------------------------------------------------
    // Leaderboard template data
    // -------------------------------------------------------------------------
    arsort($leaderboard_stack);
    $top_five  = array_slice($leaderboard_stack, 0, 5, true);
    $performers = array();
    $rank = 1;

    foreach ($top_five as $top_uid => $achieved_score) {
        if (isset($enrolled_users[$top_uid])) {
            $student_user = $enrolled_users[$top_uid];
            $performers[] = array(
                'rank'           => $rank,
                'userpictureurl' => $OUTPUT->user_picture(
                    $student_user,
                    array('size' => 50, 'link' => false, 'class' => 're-leaderboard-img mb-2')
                ),
                'fullname' => s($student_user->firstname) . ' ' . s($student_user->lastname),
                'score'    => $achieved_score,
            );
            $rank++;
        }
    }

    echo $OUTPUT->render_from_template('report_examstats/leaderboard', array(
        'strtopfiveperformers' => get_string('topfiveperformers', 'report_examstats'),
        'strscoresuffix'       => $is_combined
            ? get_string('combinedtotal', 'report_examstats')
            : get_string('points',        'report_examstats'),
        'performers' => $performers,
    ));

    echo '  </div>'; // .card-body
    echo '</div>';   // #re-analytics-dashboard

} else {
    echo $OUTPUT->render_from_template('report_examstats/nodata', array(
        'strnodatadefined' => get_string('nodatadefined', 'report_examstats'),
    ));
}

echo '</div>'; // .report-examstats
echo $OUTPUT->footer();
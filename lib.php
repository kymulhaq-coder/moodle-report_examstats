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
 * Library functions for report_examstats.
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds the Exam Performance Report link to a course's "Reports" list
 * (Course > More > Reports), the same place core reports like the Logs
 * or Participation report appear.
 *
 * Moodle calls this automatically for every installed report plugin while
 * building course navigation — no custom menu entry is required.
 *
 * @param navigation_node $navigation The "Reports" navigation node for the course.
 * @param stdClass $course The course object.
 * @param context_course $context The course context.
 */
function report_examstats_extend_navigation_course($navigation, $course, $context) {
    if (!has_capability('report/examstats:view', $context)) {
        return;
    }

    $url = new moodle_url('/report/examstats/index.php', array('courseid' => $course->id));

    $navigation->add(
        get_string('pluginname', 'report_examstats'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'report_examstats',
        new pix_icon('i/report', '')
    );
}

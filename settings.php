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
 * Administration settings for report_examstats
 *
 * @package   report_examstats
 * @copyright 2026 Khayam <kymulhaq@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('reports', new admin_externalpage(
        'report_examstats',
        get_string('pluginname', 'report_examstats'),
        new moodle_url('/report/examstats/index.php'),
        'report/examstats:view'
    ));

    // Settings page for report_examstats.
    $settings = new admin_settingpage(
        'report_examstats_settings',
        get_string('pluginsettings', 'report_examstats')
    );

    // Theory quiz name filter pattern.
    $settings->add(new admin_setting_configtext(
        'report_examstats/theory_pattern',
        get_string('theory_pattern', 'report_examstats'),
        get_string('theory_pattern_desc', 'report_examstats'),
        get_string('theory_pattern_default', 'report_examstats'),
        PARAM_TEXT
    ));

    // Skill exam quiz name filter patterns. Accepts a comma-separated list
    // (e.g. "Skill, OSCE, OSPE, Practical") — a quiz matches if its name
    // contains any one of the listed terms.
    $settings->add(new admin_setting_configtext(
        'report_examstats/skill_pattern',
        get_string('skill_pattern', 'report_examstats'),
        get_string('skill_pattern_desc', 'report_examstats'),
        get_string('skill_pattern_default', 'report_examstats'),
        PARAM_TEXT
    ));

    // -------------------------------------------------------------------
    // Performance Band Distribution: configurable thresholds and labels.
    // -------------------------------------------------------------------
    $settings->add(new admin_setting_heading(
        'report_examstats/bandsettingsheading',
        get_string('bandsettingsheading', 'report_examstats'),
        get_string('bandsettingsheading_desc', 'report_examstats')
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_a_min',
        get_string('band_a_min', 'report_examstats'),
        get_string('band_a_min_desc', 'report_examstats'),
        '80',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_b_min',
        get_string('band_b_min', 'report_examstats'),
        get_string('band_b_min_desc', 'report_examstats'),
        '60',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_c_min',
        get_string('band_c_min', 'report_examstats'),
        get_string('band_c_min_desc', 'report_examstats'),
        '50',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_a_label',
        get_string('band_a_label', 'report_examstats'),
        get_string('band_a_label_desc', 'report_examstats'),
        get_string('highachieverdesc', 'report_examstats'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_b_label',
        get_string('band_b_label', 'report_examstats'),
        get_string('band_b_label_desc', 'report_examstats'),
        get_string('satisfactorydesc', 'report_examstats'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_c_label',
        get_string('band_c_label', 'report_examstats'),
        get_string('band_c_label_desc', 'report_examstats'),
        get_string('borderlinedesc', 'report_examstats'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'report_examstats/band_d_label',
        get_string('band_d_label', 'report_examstats'),
        get_string('band_d_label_desc', 'report_examstats'),
        get_string('faildesc', 'report_examstats'),
        PARAM_TEXT
    ));

    $ADMIN->add('reports', $settings);
}
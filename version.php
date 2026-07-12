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
 * Plugin version and other meta-data are defined here.
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'report_examstats';
$plugin->release = '1.6.4';
$plugin->version = 2026071108; // Properly fixed KPI card height mismatch (previous fix in 1.6.3 only addressed the text-wrap symptom, not the root cause). .re-kpi-inner now has height:100% so it fills its parent column's stretched height, which Bootstrap's row already equalizes to the tallest sibling — all 4 cards now always match regardless of how much content each one holds.
$plugin->requires = 2025041404;
$plugin->maturity = MATURITY_STABLE;
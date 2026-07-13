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
$plugin->release = '1.6.6';
$plugin->version = 2026071302; // CI fix 2/4 (Mustache Lint): fixed leaderboard.mustache's example placeholder to include an alt attribute (real production output already had this via user_picture(), was just a doc example gap); added the missing strperformancebanddistribution key to banddistribution.mustache's example context, which was causing the linter's test-render to see an empty heading.
$plugin->requires = 2025041404;
$plugin->maturity = MATURITY_STABLE;
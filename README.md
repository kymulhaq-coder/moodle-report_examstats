# Exam Performance Report #

A Moodle admin report plugin for medical colleges that provides detailed
pass/fail analytics for Theory and OSPE/OSCE quiz assessments, with
performance band distribution, failure diagnostics, and CSV exports.

## Features ##

- **Flexible exam selection** — analyse a single Theory exam, a single
  OSPE/OSCE exam, or both combined in one report
- **Smart quiz filtering** — Theory dropdown shows only quizzes named
  with "Theory"; OSPE dropdown shows only "OSPE" or "OSCE" quizzes
- **KPI summary cards** — Attendance %, Absent count, passing cutoffs
  displayed as colour-coded cards
- **Pass/Fail progress bars** — instant visual overview of results
- **Performance Band Distribution table** — students classified into
  four bands based on percentage of maximum marks:
  - A — High Achiever (≥ 80%)
  - B — Satisfactory (60–79%)
  - C — Borderline (50–59%)
  - D — Fail (< 50%)
- **Failure Breakdown Diagnostics** — in combined mode, shows how many
  students failed Theory only, OSPE only, or both components
- **Top 5 Leaderboard** — best performing students with profile pictures
- **CSV Export Dropdown** — download results by band (High Achievers,
  Satisfactory, Borderline, Failed) or as a Complete Result; columns
  include per-component scores, remarks, grand total, % of max,
  descriptor, and overall pass/fail status
- **Print-ready layout** — clean print stylesheet hides UI controls

## Requirements ##

- Moodle 4.5 or later (tested on Moodle 5.0)
- Admin capability: `moodle/site:config`
- Quizzes must have passing grade configured in the Moodle gradebook
- Quiz names must contain the word "Theory" or "OSPE"/"OSCE" to appear
  in the respective filter dropdowns

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to
   _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted
   to add extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.
4. Go to _Site administration > Reports > Exam Performance Report_.

## Installing manually ##

The plugin can also be installed by putting the contents of this
directory into:

    {your/moodle/dirroot}/report/examstats

Afterwards, log in to your Moodle site as an admin and go to
_Site administration > Notifications_ to complete the installation.

Alternatively, run:

    $ php admin/cli/upgrade.php

## Usage ##

1. Go to **Site administration > Reports > Exam Performance Report**
2. Select a **Course** from the dropdown — the page refreshes
   automatically
3. Select a **Theory Exam** and/or **OSPE Exam** from the filtered
   dropdowns
4. Click **Apply Filters** to load the performance dashboard
5. Use the **Download CSV** dropdown to export results by band or as a
   complete result sheet

## License ##

2026 Khayam <kymulhaq@gmail.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.

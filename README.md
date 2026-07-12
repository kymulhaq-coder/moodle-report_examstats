# Exam Performance Report #

A Moodle report plugin that provides detailed pass/fail analytics for
Theory and Skill Exam quiz assessments, with performance band
distribution, failure diagnostics, and CSV exports.

## Features ##

- **Flexible exam selection** — analyse a single Theory exam, a single
  Skill Exam, or both combined in one report
- **Configurable quiz filtering** — the Theory and Skill Exam dropdowns
  only list quizzes whose name matches admin-configurable patterns (see
  [Admin Settings](#admin-settings) below). The Skill Exam pattern
  accepts a comma-separated list (e.g. `Skill, OSCE, OSPE, Practical`)
  — a quiz matches if its name contains any one of the listed terms, so
  the report adapts to whatever naming convention your institution uses
  (Skill, Practical, OSCE, OSPE, or any custom label).
- **Calculation Basis** — choose how the pass/fail cohort is defined:
  - **Appeared Cohort (Standard)** — only students who attempted at
    least one of the selected exam(s) are included in the pass/fail
    math and band distribution.
  - **Total Cohort (KMU Rule)** — every enrolled student is included;
    anyone who didn't attempt an exam is scored zero and counted as
    failed. Useful for institutions that require absentees to be
    reflected in overall pass/fail statistics.
- **KPI summary cards** — Attendance %, Absent count, and passing
  cutoff(s) displayed as colour-coded cards, plus an Active Mode
  indicator showing whether the report is running in Single Theory,
  Single Skill, or Combined mode
- **Pass/Fail progress bars** — instant visual overview of results
- **Performance Band Distribution table** — students classified into
  four bands based on percentage of maximum marks. Both the percentage
  thresholds and the band names are fully configurable by the admin
  (see [Admin Settings](#admin-settings)); the defaults are:
  - A — High Achiever (≥ 80%)
  - B — Satisfactory (60% – < 80%)
  - C — Borderline (50% – < 60%)
  - D — Fail (< 50%)
- **Failure Breakdown Diagnostics** — in combined mode, shows how many
  students failed Theory only, Skill Exam only, or both components
- **Top 5 Leaderboard** — best performing students with profile
  pictures, ranked by combined/active score
- **CSV Export Dropdown** — download results filtered by performance
  band, or as a Complete Result sheet. The band names and downloaded
  filenames automatically follow whatever custom band labels the admin
  has configured, so the CSV always stays in sync with the on-screen
  table. Columns include per-component scores, remarks, grand total, %
  of max, descriptor, and overall pass/fail status — all fully
  localized via Moodle's string API.
- **Print-ready layout** — a dedicated print stylesheet hides UI
  controls (filters, buttons, dropdowns) and forces a clean white
  background regardless of the site theme, so the report prints/exports
  to PDF cleanly even on dark-themed Moodle sites.

## Admin Settings ##

Configurable at **Site administration > Plugins > Report > Exam
Performance Report**:

- **Theory quiz name pattern** — text a quiz name must contain to
  appear in the Theory Exam dropdown (default: `Theory`)
- **Skill exam quiz name patterns** — comma-separated list of terms; a
  quiz matches if its name contains any one of them (default:
  `Skill, OSCE, OSPE, Practical`)
- **Band A / B / C minimum %** — the percentage cutoffs for the top
  three performance bands (defaults: 80 / 60 / 50). Band D is
  automatically "anything below the Band C cutoff."
- **Band A / B / C / D labels** — the descriptor text shown in the
  Performance Band Distribution table and the CSV export dropdown
  (defaults: High Achiever / Satisfactory / Borderline / Fail).
  Rename Band D to something like "Poor Performance" or "Needs
  Improvement" if preferred — no code changes required.

## Requirements ##

- Moodle 4.5 or later (tested on Moodle 5.0)
- Capability: `report/examstats:view` (granted by default to Teacher,
  Editing Teacher, and Manager roles; admins have it automatically)
- Quizzes must have a passing grade configured in the Moodle gradebook
- Quiz names must match the configured Theory/Skill Exam name patterns
  to appear in the respective filter dropdowns (see
  [Admin Settings](#admin-settings) — both are fully configurable, not
  hardcoded)

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

1. Go to **Course > More > Reports > Exam Performance Report** (or
   **Site administration > Reports > Exam Performance Report** for
   sitewide access)
2. Select a **Course** from the dropdown — the page refreshes
   automatically
3. Select a **Theory Exam** and/or **Skill Exam** from the filtered
   dropdowns, and choose a **Calculation Basis** (Appeared or
   Registered/KMU Rule)
4. Click **Apply Filters** to load the performance dashboard
5. Use the **Print Report** button for a clean printable/PDF view, or
   the **Download CSV** dropdown to export results by band or as a
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

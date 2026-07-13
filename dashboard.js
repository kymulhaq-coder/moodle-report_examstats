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
 * Dashboard interactions for report_examstats.
 *
 * Handles the "Print Report" button and the "Download CSV" dropdown
 * toggle on the Exam Performance Report page. Replaces legacy inline
 * onclick handlers with proper AMD-based event delegation.
 *
 * @module      report_examstats/dashboard
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Force a white background and dark text on the report during print,
 * regardless of the site theme (fixes dark-mode themes printing a
 * navy/black background instead of white).
 *
 * This used to be done with `!important` CSS rules, but Moodle's coding
 * guidelines disallow `!important` in stylesheets. Inline styles set via
 * JavaScript always win over any external stylesheet rule (with or
 * without !important) without needing !important themselves here, so
 * this achieves the same guaranteed override safely from JS instead.
 *
 * Two exceptions are skipped because they rely on their own background
 * colour to convey meaning:
 *  - .re-kpi-inner (and its descendants): the KPI cards' colour gradient.
 *  - .progress-bar: the Passed/Failed bar fill (green/red).
 */
const forcePrintColours = () => {
    document.documentElement.style.setProperty('background', '#fff', 'important');
    document.body.style.setProperty('background', '#fff', 'important');

    const dashboard = document.getElementById('re-analytics-dashboard');
    if (!dashboard) {
        return;
    }
    dashboard.style.setProperty('background', '#fff', 'important');

    dashboard.querySelectorAll('*').forEach((el) => {
        if (el.closest('.re-kpi-inner') || el.classList.contains('progress-bar')) {
            return;
        }
        el.style.setProperty('background', '#fff', 'important');
        el.style.setProperty('background-image', 'none', 'important');
        el.style.setProperty('color', '#000', 'important');
        el.style.setProperty('box-shadow', 'none', 'important');
    });
};

/**
 * Undo forcePrintColours() once printing has finished, so normal
 * on-screen browsing is never affected.
 */
const clearPrintColours = () => {
    document.documentElement.style.removeProperty('background');
    document.body.style.removeProperty('background');

    const dashboard = document.getElementById('re-analytics-dashboard');
    if (!dashboard) {
        return;
    }
    dashboard.style.removeProperty('background');

    dashboard.querySelectorAll('*').forEach((el) => {
        el.style.removeProperty('background');
        el.style.removeProperty('background-image');
        el.style.removeProperty('color');
        el.style.removeProperty('box-shadow');
    });
};

/**
 * Initialise the dashboard print button, CSV dropdown, and course
 * filter auto-submit behaviour.
 */
export const init = () => {
    window.addEventListener('beforeprint', forcePrintColours);
    window.addEventListener('afterprint', clearPrintColours);

    // Print Report button.
    const printBtn = document.querySelector('[data-action="re-print-report"]');
    if (printBtn) {
        printBtn.addEventListener('click', () => {
            window.print();
        });
    }

    // Course dropdown: reset the Theory/Skill exam selections (they belong
    // to the previously-selected course and are no longer valid) then
    // auto-submit the filter form.
    const courseSelect = document.querySelector('[data-action="re-submit-on-change"]');
    if (courseSelect) {
        courseSelect.addEventListener('change', () => {
            const form = courseSelect.form;
            const theorySelect = form.querySelector('[name="quizid_theory"]');
            const skillSelect = form.querySelector('[name="quizid_skill"]');
            if (theorySelect) {
                theorySelect.value = '0';
            }
            if (skillSelect) {
                skillSelect.value = '0';
            }
            form.submit();
        });
    }

    // CSV download dropdown toggle.
    const dropdownToggle = document.querySelector('[data-action="re-toggle-csv-menu"]');
    const dropdownMenu = document.getElementById('epr-csv-menu');

    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle('show');
        });

        // Close the dropdown when clicking anywhere outside it.
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.epr-dropdown')) {
                dropdownMenu.classList.remove('show');
            }
        });
    }
};

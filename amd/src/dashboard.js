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
 * Initialise the dashboard print button, CSV dropdown, and course
 * filter auto-submit behaviour.
 */
export const init = () => {
    // Belt-and-braces fix for dark-mode themes: our stylesheet's
    // "html, body { background: #fff !important; }" print rule can still
    // lose to a more specific theme selector (e.g. "body.pagelayout-report")
    // even with !important, since specificity is the tiebreaker between two
    // !important declarations. An inline style bypasses that entirely, since
    // inline declarations win over any external stylesheet rule. Applied
    // just before printing and removed just after, so normal browsing is
    // never affected.
    window.addEventListener('beforeprint', () => {
        document.documentElement.style.setProperty('background', '#fff', 'important');
        document.body.style.setProperty('background', '#fff', 'important');
    });
    window.addEventListener('afterprint', () => {
        document.documentElement.style.removeProperty('background');
        document.body.style.removeProperty('background');
    });

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

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
 * Privacy API implementation for report_examstats.
 *
 * This plugin does not store any personal data itself.
 * It only reads and displays existing data from Moodle core
 * tables (grade_grades, grade_items, user) for reporting purposes.
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_examstats\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

/**
 * Privacy provider for report_examstats.
 *
 * This plugin reads data from Moodle core grade tables for display
 * purposes only. It does not store, export or delete personal data
 * independently — all personal data is owned and managed by Moodle core.
 *
 * @package     report_examstats
 * @copyright   2026 Khayam <kymulhaq@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Returns metadata about the data this plugin reads/uses.
     *
     * @param collection $collection The metadata collection to add to.
     * @return collection The updated collection.
     */
    public static function get_metadata(collection $collection): collection {

        // This plugin reads from Moodle core grade tables but does not
        // store any personal data in its own tables.
        $collection->add_subsystem_link(
            'core_grades',
            [],
            'privacy:metadata:core_grades'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the given user.
     *
     * This plugin does not store personal data, so returns an empty contextlist.
     *
     * @param int $userid The user ID.
     * @return contextlist An empty contextlist.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        return new contextlist();
    }

    /**
     * Get the list of users who have data within the given context.
     *
     * This plugin does not store personal data, so adds no users.
     *
     * @param userlist $userlist The userlist to add users to.
     */
    public static function get_users_in_context(userlist $userlist): void {
        // This plugin does not store personal data — nothing to add.
    }

    /**
     * Export all user data for the given approved contextlist.
     *
     * This plugin does not store personal data, so there is nothing to export.
     *
     * @param approved_contextlist $contextlist The approved contextlist.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        // This plugin does not store personal data — nothing to export.
    }

    /**
     * Delete all user data for the given context.
     *
     * This plugin does not store personal data, so there is nothing to delete.
     *
     * @param \context $context The context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        // This plugin does not store personal data — nothing to delete.
    }

    /**
     * Delete personal data for the given approved contextlist.
     *
     * This plugin does not store personal data, so there is nothing to delete.
     *
     * @param approved_contextlist $contextlist The approved contextlist.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        // This plugin does not store personal data — nothing to delete.
    }

    /**
     * Delete personal data for the given approved userlist.
     *
     * This plugin does not store personal data, so there is nothing to delete.
     *
     * @param approved_userlist $userlist The approved userlist.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        // This plugin does not store personal data — nothing to delete.
    }
}

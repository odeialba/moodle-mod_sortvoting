<?php
// This file is part of the mod_sortvoting plugin for Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_sortvoting\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Implementation of the privacy subsystem plugin provider for the sortvoting activity module.
 *
 * @package    mod_sortvoting
 * @category   privacy
 * @copyright  2023 Odei Alba <odeialba@odeialba.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider {
    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'sortvoting_answers',
            [
                'sortvotingid' => 'privacy:metadata:sortvoting_answers:sortvotingid',
                'optionid' => 'privacy:metadata:sortvoting_answers:optionid',
                'userid' => 'privacy:metadata:sortvoting_answers:userid',
                'timemodified' => 'privacy:metadata:sortvoting_answers:timemodified',
            ],
            'privacy:metadata:sortvoting_answers'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        // Fetch all sortvoting answers.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {sortvoting} sv ON sv.id = cm.instance
            INNER JOIN {sortvoting_options} svo ON svo.sortvotingid = sv.id
            INNER JOIN {sortvoting_answers} sva ON sva.optionid = svo.id AND sva.sortvotingid = sv.id
                 WHERE sva.userid = :userid";

        $params = [
            'modname'       => 'sortvoting',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all sortvoting answers.
        $sql = "SELECT sva.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {sortvoting} sv ON sv.id = cm.instance
                  JOIN {sortvoting_options} svo ON svo.sortvotingid = sv.id
                  JOIN {sortvoting_answers} sva ON sva.optionid = svo.id AND sva.sortvotingid = sv.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid'      => $context->instanceid,
            'modname'   => 'sortvoting',
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        [$contextsql, $contextparams] = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       svo.text as answer,
                       sva.timemodified
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {sortvoting} sv ON sv.id = cm.instance
            INNER JOIN {sortvoting_options} svo ON svo.sortvotingid = sv.id
            INNER JOIN {sortvoting_answers} sva ON sva.optionid = svo.id AND sva.sortvotingid = sv.id
                 WHERE c.id {$contextsql}
                       AND sva.userid = :userid
              ORDER BY cm.id";

        $params = ['modname' => 'sortvoting', 'contextlevel' => CONTEXT_MODULE, 'userid' => $user->id] + $contextparams;

        // Reference to the sortvoting activity seen in the last iteration of the loop. By comparing this with the current record,
        // and because we know the results are ordered, we know when we've moved to the answers for a new sortvoting activity and
        // therefore when we can export the complete data for the last activity.
        $lastcmid = null;

        $sortvotinganswers = $DB->get_recordset_sql($sql, $params);
        foreach ($sortvotinganswers as $sortvotinganswer) {
            // If we've moved to a new sortvoting, then write the last sortvoting data and reinit the sortvoting data array.
            if ($lastcmid != $sortvotinganswer->cmid) {
                if (!empty($sortvotingdata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_sortvoting_data_for_user($sortvotingdata, $context, $user);
                }
                $sortvotingdata = [
                    'answer' => [],
                    'timemodified' => \core_privacy\local\request\transform::datetime($sortvotinganswer->timemodified),
                ];
            }
            $sortvotingdata['answer'][] = $sortvotinganswer->answer;
            $lastcmid = $sortvotinganswer->cmid;
        }
        $sortvotinganswers->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($sortvotingdata)) {
            $context = \context_module::instance($lastcmid);
            self::export_sortvoting_data_for_user($sortvotingdata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single sortvoting activity, along with any generic data or area files.
     *
     * @param array $sortvotingdata the personal data to export for the sortvoting.
     * @param \context_module $context the context of the sortvoting.
     * @param \stdClass $user the user record
     */
    protected static function export_sortvoting_data_for_user(array $sortvotingdata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the sortvoting.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with sortvoting data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $sortvotingdata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('sortvoting', $context->instanceid)) {
            $DB->delete_records('sortvoting_answers', ['sortvotingid' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }
            $DB->delete_records('sortvoting_answers', ['sortvotingid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('sortvoting', $context->instanceid);

        if (!$cm) {
            // Only sortvoting module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        [$usersql, $userparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "sortvotingid = :sortvotingid AND userid $usersql";
        $params = ['sortvotingid' => $cm->instance] + $userparams;
        $DB->delete_records_select('sortvoting_answers', $select, $params);
    }
}

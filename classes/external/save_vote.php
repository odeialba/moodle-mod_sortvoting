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

declare(strict_types=1);

namespace mod_sortvoting\external;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/mod/sortvoting/lib.php');
require_once($CFG->libdir . '/externallib.php');

/**
 * External function save_vote for mod_sortvoting.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_vote extends external_api {

    /**
     * Returns the structure of parameters for save_vote.
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'sortvotingid' => new external_value(PARAM_INT, 'The ID of the session to signup for', VALUE_REQUIRED),
                'votes' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'position' => new external_value(PARAM_INT, 'Voted position of the option.', VALUE_REQUIRED),
                            'optionid' => new external_value(PARAM_INT, 'The ID of the option.', VALUE_REQUIRED)
                        ]
                    ), 'Votes for the positions of the options.'
                )
            ]
        );
    }

    /**
     * Submission of the vote.
     *
     * @param int $sortvotingid The ID of the session
     * @param array $votes Votes for the positions of the options.
     * @return array
     */
    public static function execute($sortvotingid, $votes) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sortvotingid' => $sortvotingid,
            'votes' => $votes
        ]);

        if (!$sortvoting = sortvoting_get_sortvoting($params['sortvotingid'])) {
            throw new moodle_exception("invalidcoursemodule", "error");
        }
        list($course, $cm) = get_course_and_cm_from_instance($sortvoting, 'sortvoting');
        $context = \context_module::instance($cm->id);
        self::validate_context($context);
        \mod_sortvoting\permission::require_can_vote($context);

        sortvoting_user_submit_response($sortvoting, $params['votes'], $course, $cm);

        return ['success' => true, 'allowupdate' => (bool) $sortvoting->allowupdate];
    }

    /**
     * Describes the return function of save_vote.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'success' => new external_value(PARAM_BOOL, 'Returns true on successful vote submision or throws an error'),
                'allowupdate' => new external_value(PARAM_BOOL, 'Returns true if vote can be updated', VALUE_REQUIRED)
            ]
        );
    }
}

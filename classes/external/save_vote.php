<?php
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace mod_sortvoting\external;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

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
     * @return bool
     */
    public static function execute($sortvotingid, $votes) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'sortvotingid' => $sortvotingid,
            'votes' => $votes
        ]);

        // Signup the user to the session

        // $session = appointment_get_session($params['sortvotingid']);
        // $cm = get_coursemodule_from_instance('appointment', $session->appointment);
        // $context = \context_module::instance($cm->id);

        // self::validate_context($context);
        // \mod_sortvoting\permission::require_can_vote($session, $context);

        // Save votes in sortvoting_answers table
        // TODO: Check if the user has already voted and update the vote.
        $answers = [];
        foreach ($params['votes'] as $vote) {
            $answers[] = [
                'userid' => $USER->id,
                'sortvotingid' => $params['sortvotingid'],
                'position' => $vote['position'],
                'optionid' => $vote['optionid']
            ];
        }
        $DB->insert_records('sortvoting_answers', $answers);

        return true;
    }

    /**
     * Describes the return function of save_vote.
     *
     * @return external_value
     */
    public static function execute_returns() {
        return new external_value(PARAM_BOOL, 'Returns true on successful vote submision or throws an error');
    }
}

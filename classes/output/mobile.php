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

namespace mod_sortvoting\output;
use context_module;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/sortvoting/lib.php');

/**
 * Class mobile
 *
 * @package    mod_sortvoting
 * @copyright  2024 Odei Alba <odeialba@odeialba.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {
    /**
     * Returns the javascript needed to initialize SortVoting in the app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array javascript
     */
    public static function mobile_init($args) {
        global $CFG;

        return [
            'templates' => [],
            'javascript' => file_get_contents($CFG->dirroot . "/mod/sortvoting/mobile/js/init.js"),
        ];
    }

    /**
     * Returns the SortVoting form view for the mobile app.
     *
     * @param mixed $args
     * @return array HTML, javascript and other data.
     */
    public static function mobile_sort_voting_view($args): array {
        global $OUTPUT, $DB, $CFG;
        $args = (object) $args;

        $cm = get_coursemodule_from_id('sortvoting', $args->cmid);
        $sortvotingid = $cm->instance;
        $userid = $args->userid;

        $sortvoting = $DB->get_record("sortvoting", ["id" => $sortvotingid]);
        $options = $DB->get_records('sortvoting_options', ['sortvotingid' => $sortvotingid], 'id ASC');
        $existingvotes = $DB->get_records_menu(
            'sortvoting_answers',
            [
                'sortvotingid' => $sortvotingid,
                'userid' => $userid,
            ],
            'id ASC',
            'optionid, position'
        );

        $allowupdate = true;
        if (!$sortvoting->allowupdate && count($existingvotes) === count($options)) {
            $allowupdate = false;
        }

        $defaultposition = (count($existingvotes) > 0) ? (count($existingvotes) + 1) : 1;
        $optionsclean = [];
        foreach ($options as $option) {
            $position = isset($existingvotes[$option->id]) ? $existingvotes[$option->id] : $defaultposition++;
            $optionsclean[] = [
                'id' => $option->id,
                'text' => $option->text,
                'position' => $position,
            ];
        }

        // Sort $optionsclean by position.
        usort($optionsclean, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        $canseeresults = \mod_sortvoting\permission::can_see_results($sortvoting, context_module::instance($cm->id));

        // Result of existing votes.
        $existingvotes = $canseeresults ? sortvoting_get_response_data($sortvoting) : [];

        $data = [
            'description' => html_to_text($sortvoting->intro),
            'allowupdate' => $allowupdate,
            'options' => $optionsclean,
            'max' => count($options),
            'canseeresults' => $canseeresults,
            'votes' => $existingvotes,
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_sortvoting/mobile_sort_voting_view', $data),
                ],
            ],
            'javascript' => file_get_contents($CFG->dirroot . "/mod/sortvoting/mobile/js/mobile_sortvoting.js"),
            'otherdata' => '',
        ];
    }
}

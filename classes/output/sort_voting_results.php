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
use renderer_base;

/**
 * Renderable for showing the sort form of a given activity
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sort_voting_results implements \templatable, \renderable {
    /**
     * @var \stdClass $sortvoting
     */
    private $sortvoting;

    /**
     * Constructor
     *
     * @param \stdClass $sortvoting
     */
    public function __construct(\stdClass $sortvoting) {
        $this->sortvoting = $sortvoting;
    }

    /**
     * Exports for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id, $this->sortvoting->course);
        $sortvoting = $DB->get_record('sortvoting', ['id' => $this->sortvoting->id], '*', MUST_EXIST);
        $groupmode = groups_get_activity_groupmode($cm);
        $onlyactive = true;

        $existingvotes = sortvoting_get_response_data($sortvoting, $cm, $groupmode, $onlyactive);

        return ['votes' => $existingvotes];
    }
}

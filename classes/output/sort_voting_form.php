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
class sort_voting_form implements \templatable, \renderable {
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
        global $DB, $USER;

        $options = $DB->get_records('sortvoting_options', ['sortvotingid' => $this->sortvoting->id], 'id ASC');
        $existingvotes = $DB->get_records_menu(
            'sortvoting_answers',
            [
                'sortvotingid' => $this->sortvoting->id,
                'userid' => $USER->id
            ],
            'id ASC',
            'optionid, position'
        );

        $allowupdate = true;
        if (!$this->sortvoting->allowupdate && count($existingvotes) === count($options)) {
            $allowupdate = false;
        }

        $defaultposition = (count($existingvotes) > 0) ? count($existingvotes) : 1;
        $optionsclean = [];
        foreach ($options as $option) {
            $position = isset($existingvotes[$option->id]) ? $existingvotes[$option->id] : $defaultposition++;
            $optionsclean[] = [
                'id' => $option->id,
                'text' => $option->text,
                'position' => $position
            ];
        }

        // Sort $optionsclean by position.
        usort($optionsclean, function($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        return [
            'sortvotingid' => $this->sortvoting->id,
            'allowupdate' => $allowupdate,
            'options' => $optionsclean,
            'max' => count($options)
        ];
    }
}

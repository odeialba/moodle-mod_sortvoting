<?php
// This file is part of Moodle - http://moodle.org/
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

        $sql = "SELECT so.id,
                    ROUND(AVG(sa.position), 2) AS avg,
                    so.text
                FROM {sortvoting_answers} sa
                    JOIN {sortvoting_options} so
                        ON sa.optionid = so.id
                WHERE so.sortvotingid = :sortvotingid
                GROUP BY so.id
                ORDER BY avg ASC";
        $votes = $DB->get_records_sql($sql, ['sortvotingid' => $this->sortvoting->id]);

        return ['votes' => array_values($votes)];
    }
}

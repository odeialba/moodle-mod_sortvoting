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

namespace mod_sortvoting;

/**
 * Class permission to perform permission checks.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class permission {

    /**
     * User can vote.
     *
     * @param \context_module $context
     * @return bool
     */
    public static function can_vote(\context_module $context): bool {
        return has_capability('mod/sortvoting:vote', $context);
    }

    /**
     * Make sure user can vote.
     *
     * @param \context_module $context
     */
    public static function require_can_vote(\context_module $context) {
        require_capability('mod/sortvoting:vote', $context);
    }
}

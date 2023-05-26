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

/**
 * SortVoting module webservice functions.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_sortvoting\external\save_vote;
defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_sortvoting_save_vote' => [
        'classname' => save_vote::class,
        'description' => 'Save users sort vote',
        'type' => 'write',
        'capabilities' => 'mod/sortvoting:choose',
        'ajax' => true,
    ],
];

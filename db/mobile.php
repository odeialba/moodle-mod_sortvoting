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

/**
 * Mobile app areas for Preference Sort Voting
 *
 * @package    mod_sortvoting
 * @copyright  2024 Odei Alba <odeialba@odeialba.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_sortvoting' => [
        "handlers" => [ // Different places where the add-on will display content.
            'coursesortvotingview' => [ // Handler unique name (can be anything).
                'displaydata' => [
                    'title' => 'pluginname',
                    'icon' => $CFG->wwwroot . '/mod/sortvoting/pix/monologo.png',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the add-on).
                'method' => 'mobile_sort_voting_view', // Main function in \mod_sortvoting\output\mobile.
                'init' => 'mobile_init',
                'styles' => [
                    'url' => $CFG->wwwroot . '/mod/sortvoting/style/mobile.css',
                    'version' => '0.1',
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_sortvoting'],
            ['instructions', 'mod_sortvoting'],
            ['votesuccess', 'mod_sortvoting'],
            ['position', 'mod_sortvoting'],
            ['option', 'mod_sortvoting'],
            ['save', 'core'],
        ],
    ],
];

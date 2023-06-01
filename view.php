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
 * Prints an instance of mod_sortvoting.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('sortvoting', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$sortvoting = $DB->get_record('sortvoting', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$params = [
    'objectid' => $sortvoting->id,
    'context' => $modulecontext
];
$event = \mod_sortvoting\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('sortvoting', $sortvoting);
$event->trigger();

// Completion update.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/sortvoting/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($sortvoting->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);
$PAGE->activityheader->set_attrs([]);

// TODO: Check if we want to include responses from inactive users.

$output = $PAGE->get_renderer('mod_sortvoting');
echo $output->header();
if (is_enrolled($modulecontext, null, 'mod/sortvoting:vote')) {
    $votingform = new \mod_sortvoting\output\sort_voting_form($sortvoting);
    echo $output->render($votingform);

    // Teacher can see results.
    if (has_capability('mod/sortvoting:readresponses', $modulecontext)) {
        $votingresults = new \mod_sortvoting\output\sort_voting_results($sortvoting);
        echo $output->render($votingresults);
    }
} else {
    echo $OUTPUT->notification(get_string('notenrolledsort', 'sortvoting'), 'notifyproblem');
}
echo $output->footer($course);

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
 * Library of interface functions and constants.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

define('SORTVOTING_EVENT_TYPE_OPEN', 'open');
define('SORTVOTING_EVENT_TYPE_CLOSE', 'close');

use mod_sortvoting\completion\custom_completion;

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
 */
function sortvoting_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_COMMUNICATION;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_sortvoting into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $sortvoting An object from the form.
 * @param mod_sortvoting_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function sortvoting_add_instance($sortvoting, $mform = null) {
    global $DB;

    $sortvoting->timecreated = time();
    $sortvoting->id = $DB->insert_record('sortvoting', $sortvoting);
    foreach ($sortvoting->option as $key => $value) {
        $value = trim($value);
        if (isset($value) && $value <> '') {
            $option = new \stdClass();
            $option->text = $value;
            $option->sortvotingid = $sortvoting->id;
            $option->timemodified = time();
            $DB->insert_record("sortvoting_options", $option);
        }
    }

    // Add calendar events if necessary.
    sortvoting_set_events($sortvoting);
    if (!empty($sortvoting->completionexpected)) {
        \core_completion\api::update_completion_date_event($sortvoting->coursemodule, 'sortvoting', $sortvoting->id,
            $sortvoting->completionexpected);
    }

    return $sortvoting->id;
}

/**
 * Updates an instance of the mod_sortvoting in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $sortvoting An object from the form in mod_form.php.
 * @param mod_sortvoting_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function sortvoting_update_instance($sortvoting, $mform = null) {
    global $DB;

    $sortvoting->timemodified = time();
    $sortvoting->id = $sortvoting->instance;

    foreach ($sortvoting->option as $key => $value) {
        $value = trim($value);
        $option = new \stdClass();
        $option->text = $value;
        $option->sortvotingid = $sortvoting->id;
        $option->timemodified = time();
        if (isset($sortvoting->optionid[$key]) && !empty($sortvoting->optionid[$key])) {
            $option->id = $sortvoting->optionid[$key];
            if (isset($value) && $value <> '') {
                $DB->update_record("sortvoting_options", $option);
            } else {
                // Delete any answers associated with this option.
                $DB->delete_records("sortvoting_answers", ["sortvotingid" => $sortvoting->id, "optionid" => $option->id]);
                // Remove the empty (unused) option.
                $DB->delete_records("sortvoting_options", ["id" => $option->id]);
            }
        } else {
            if (isset($value) && $value <> '') {
                $DB->insert_record("sortvoting_options", $option);
            }
        }
    }

    // Add calendar events if necessary.
    sortvoting_set_events($sortvoting);
    $completionexpected = (!empty($sortvoting->completionexpected)) ? $sortvoting->completionexpected : null;
    \core_completion\api::update_completion_date_event($sortvoting->coursemodule, 'sortvoting', $sortvoting->id,
        $completionexpected);

    return $DB->update_record('sortvoting', $sortvoting);

}
/**
 * Removes an instance of the mod_sortvoting from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function sortvoting_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('sortvoting', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $result = true;

    if (! $DB->delete_records('sortvoting_answers', ['sortvotingid' => $id])) {
        $result = false;
    }

    if (! $DB->delete_records('sortvoting_options', ['sortvotingid' => $id])) {
        $result = false;
    }

    if (! $DB->delete_records('sortvoting', ['id' => $id])) {
        $result = false;
    }

    // Remove old calendar events.
    if (! $DB->delete_records('event', array('modulename' => 'sortvoting', 'instance' => $id))) {
        $result = false;
    }

    return $result;
}

/**
 * Process user submitted answers for sortvoting,
 * and either updating them or saving new answers.
 *
 * @param \stdClass $sortvoting the selected sortvoting.
 * @param array $votes submitted votes.
 * @param \stdClass $course current course.
 * @param cm_info|\stdClass $cm course context.
 * @return void
 */
function sortvoting_user_submit_response($sortvoting, array $votes, $course, $cm) {
    global $DB, $USER;

    // Build answers and positions arrays for later processing.
    $positions = [];
    $answers = [];
    foreach ($votes as $vote) {
        $positions[] = $vote['position'];
        $answers[] = [
            'userid' => $USER->id,
            'sortvotingid' => $sortvoting->id,
            'position' => $vote['position'],
            'optionid' => $vote['optionid']
        ];
    }

    // Check if all elements of the positions array are unique.
    if (count($positions) !== count(array_unique($positions))) {
        throw new moodle_exception('errorduplicatedposition', 'sortvoting');
    }

    // Check if the user has already voted and update the vote.
    $existingvotes = $DB->get_records_menu(
        'sortvoting_answers',
        [
            'sortvotingid' => $sortvoting->id,
            'userid' => $USER->id
        ],
        'id ASC', 'optionid, position'
    );
    if (!empty($existingvotes)) {
        $DB->delete_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id, 'userid' => $USER->id]);
    }

    // Save votes in sortvoting_answers table.
    $DB->insert_records('sortvoting_answers', $answers);

    // Update completion state.
    sortvoting_update_completion($sortvoting, $course, $cm);
}

/**
 * Update completion state for sortvoting.
 *
 * @param \stdClass $sortvoting
 * @param \stdClass $course
 * @param cm_info|\stdClass $cm
 * @return void
 */
function sortvoting_update_completion($sortvoting, $course, $cm) {
    $completion = new \completion_info($course);
    if ($completion->is_enabled($cm) && $sortvoting->completionsubmit) {
        $completion->update_state($cm, COMPLETION_COMPLETE);
    }
}

/**
 * Add a get_coursemodule_info function in case any sortvoting type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param \stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info|false An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function sortvoting_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $customcompletionfields = custom_completion::get_defined_custom_rules();
    $fieldsarray = array_merge([
        'id',
        'name',
        'intro',
        'introformat',
    ], $customcompletionfields);
    $fields = join(',', $fieldsarray);
    if (!$sortvoting = $DB->get_record('sortvoting', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $sortvoting->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('sortvoting', $sortvoting, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        foreach ($customcompletionfields as $completiontype) {
            $result->customdata['customcompletionrules'][$completiontype] = (int) $sortvoting->$completiontype;
        }
    }

    return $result;
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param cm_info|\stdClass $cm object with fields ->completion and ->customdata['customcompletionrules']
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_sortvoting_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (empty($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }
    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionsubmit':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionsubmit', 'sortvoting');
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Gets a full sortvoting record
 *
 * @param int $sortvotingid
 * @return object|bool The sortvoting or false
 */
function sortvoting_get_sortvoting($sortvotingid) {
    global $DB;

    if ($sortvoting = $DB->get_record("sortvoting", array("id" => $sortvotingid))) {
        if ($options = $DB->get_records("sortvoting_options", array("sortvotingid" => $sortvotingid), "id")) {
            foreach ($options as $option) {
                $sortvoting->option[$option->id] = $option->text;
            }
            return $sortvoting;
        }
    }
    return false;
}

/**
 * This creates new calendar events given as timeopen and timeclose by $sortvoting.
 *
 * @param \stdClass $sortvoting
 * @return void
 */
function sortvoting_set_events($sortvoting) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    // Get CMID if not sent as part of $sortvoting.
    if (!isset($sortvoting->coursemodule)) {
        $cm = get_coursemodule_from_instance('sortvoting', $sortvoting->id, $sortvoting->course);
        $sortvoting->coursemodule = $cm->id;
    }

    // SortVoting start calendar events.
    $event = new \stdClass();
    $event->eventtype = SORTVOTING_EVENT_TYPE_OPEN;
    // The SORTVOTING_EVENT_TYPE_OPEN event should only be an action event if no close time is specified.
    $event->type = empty($sortvoting->timeclose) ? CALENDAR_EVENT_TYPE_ACTION : CALENDAR_EVENT_TYPE_STANDARD;
    if ($event->id = $DB->get_field('event', 'id',
            array('modulename' => 'sortvoting', 'instance' => $sortvoting->id, 'eventtype' => $event->eventtype))) {
        if ((!empty($sortvoting->timeopen)) && ($sortvoting->timeopen > 0)) {
            // Calendar event exists so update it.
            $event->name = get_string('calendarstart', 'sortvoting', $sortvoting->name);
            $event->description = format_module_intro('sortvoting', $sortvoting, $sortvoting->coursemodule, false);
            $event->format = FORMAT_HTML;
            $event->timestart = $sortvoting->timeopen;
            $event->timesort = $sortvoting->timeopen;
            $event->visible = instance_is_visible('sortvoting', $sortvoting);
            $event->timeduration = 0;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Calendar event is on longer needed.
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->delete();
        }
    } else {
        // Event doesn't exist so create one.
        if ((!empty($sortvoting->timeopen)) && ($sortvoting->timeopen > 0)) {
            $event->name = get_string('calendarstart', 'sortvoting', $sortvoting->name);
            $event->description = format_module_intro('sortvoting', $sortvoting, $sortvoting->coursemodule, false);
            $event->format = FORMAT_HTML;
            $event->courseid = $sortvoting->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'sortvoting';
            $event->instance = $sortvoting->id;
            $event->timestart = $sortvoting->timeopen;
            $event->timesort = $sortvoting->timeopen;
            $event->visible = instance_is_visible('sortvoting', $sortvoting);
            $event->timeduration = 0;
            calendar_event::create($event, false);
        }
    }

    // SortVoting end calendar events.
    $event = new \stdClass();
    $event->type = CALENDAR_EVENT_TYPE_ACTION;
    $event->eventtype = SORTVOTING_EVENT_TYPE_CLOSE;
    if ($event->id = $DB->get_field('event', 'id',
            array('modulename' => 'sortvoting', 'instance' => $sortvoting->id, 'eventtype' => $event->eventtype))) {
        if ((!empty($sortvoting->timeclose)) && ($sortvoting->timeclose > 0)) {
            // Calendar event exists so update it.
            $event->name = get_string('calendarend', 'sortvoting', $sortvoting->name);
            $event->description = format_module_intro('sortvoting', $sortvoting, $sortvoting->coursemodule, false);
            $event->format = FORMAT_HTML;
            $event->timestart = $sortvoting->timeclose;
            $event->timesort = $sortvoting->timeclose;
            $event->visible = instance_is_visible('sortvoting', $sortvoting);
            $event->timeduration = 0;
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event, false);
        } else {
            // Calendar event is on longer needed.
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->delete();
        }
    } else {
        // Event doesn't exist so create one.
        if ((!empty($sortvoting->timeclose)) && ($sortvoting->timeclose > 0)) {
            $event->name = get_string('calendarend', 'sortvoting', $sortvoting->name);
            $event->description = format_module_intro('sortvoting', $sortvoting, $sortvoting->coursemodule, false);
            $event->format = FORMAT_HTML;
            $event->courseid = $sortvoting->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'sortvoting';
            $event->instance = $sortvoting->id;
            $event->timestart = $sortvoting->timeclose;
            $event->timesort = $sortvoting->timeclose;
            $event->visible = instance_is_visible('sortvoting', $sortvoting);
            $event->timeduration = 0;
            calendar_event::create($event, false);
        }
    }
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $sortvoting sortvoting object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 */
function sortvoting_view($sortvoting, $course, $cm, $context) {
    // Trigger course_module_viewed event.
    $params = [
        'objectid' => $sortvoting->id,
        'context' => $context
    ];
    $event = \mod_sortvoting\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('sortvoting', $sortvoting);
    $event->trigger();

    // Completion update.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

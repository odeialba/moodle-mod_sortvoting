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

/**
 * Library of interface functions and constants.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function sortvoting_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
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
 * @param object $moduleinstance An object from the form.
 * @param mod_sortvoting_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function sortvoting_add_instance($sortvoting, $mform = null) {
    global $DB;

    $sortvoting->timecreated = time();

    //insert answers
    $sortvoting->id = $DB->insert_record('sortvoting', $sortvoting);
    // $defaultposition = 1;
    foreach ($sortvoting->option as $key => $value) {
        $value = trim($value);
        if (isset($value) && $value <> '') {
            $option = new stdClass();
            $option->text = $value;
            $option->sortvotingid = $sortvoting->id;
            // $option->defaultposition = $defaultposition++;
            $option->timemodified = time();
            $DB->insert_record("sortvoting_options", $option);
        }
    }

    // Add calendar events if necessary.
    // TODO: Check choice activity for this.

    return $sortvoting->id;
}

/**
 * Updates an instance of the mod_sortvoting in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_sortvoting_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function sortvoting_update_instance($sortvoting, $mform = null) {
    global $DB;

    $sortvoting->timemodified = time();
    $sortvoting->id = $sortvoting->instance;

    //update, delete or insert answers
    // TODO: Check this. Maybe it can be done with a simple array instead of that thing from data processing.
    // $defaultposition = 1;
    foreach ($sortvoting->option as $key => $value) {
        $value = trim($value);
        $option = new stdClass();
        $option->text = $value;
        $option->sortvotingid = $sortvoting->id;
        // $option->defaultposition = $defaultposition++;
        $option->timemodified = time();
        if (isset($sortvoting->optionid[$key]) && !empty($sortvoting->optionid[$key])){//existing sortvoting record
            $option->id = $sortvoting->optionid[$key];
            if (isset($value) && $value <> '') {
                $DB->update_record("sortvoting_options", $option);
            } else {
                // Remove the empty (unused) option.
                $DB->delete_records("sortvoting_options", ["id" => $option->id]);
                // Delete any answers associated with this option.
                // TODO: Play with answers.
                // $DB->delete_records("sortvoting_answers", array("votingid" => $sortvoting->id, "optionid" => $option->id));
            }
        } else {
            if (isset($value) && $value <> '') {
                $DB->insert_record("sortvoting_options", $option);
            }
        }
    }

    // Add calendar events if necessary.
    // TODO: Check choice activity for this.

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

    if (! $DB->delete_records('sortvoting_options', ['votingid' => $id])) {
        $result = false;
    }

    if (! $DB->delete_records('sortvoting', ['id' => $id])) {
        $result = false;
    }

    return $result;
}

/**
 * @global object
 * @param object $choice
 * @param object $user
 * @param object $coursemodule
 * @param array $allresponses
 * @return array
 */
function sortvoting_prepare_options($choice, $user, $coursemodule, $allresponses) {
    global $DB;

    $cdisplay = ['options' => []];

    $context = context_module::instance($coursemodule->id);

    foreach ($choice->option as $optionid => $text) {
        if (isset($text)) { //make sure there are no dud entries in the db with blank text values.
            $option = new stdClass;
            $option->attributes = new stdClass;
            $option->attributes->value = $optionid;
            $option->text = format_string($text);
            $option->maxanswers = $choice->maxanswers[$optionid];
            $option->displaylayout = $choice->display;

            if (isset($allresponses[$optionid])) {
                $option->countanswers = count($allresponses[$optionid]);
            } else {
                $option->countanswers = 0;
            }
            if ($DB->record_exists('choice_answers', ['choiceid' => $choice->id, 'userid' => $user->id, 'optionid' => $optionid])) {
                $option->attributes->checked = true;
            }
            if ( $choice->limitanswers && ($option->countanswers >= $option->maxanswers) && empty($option->attributes->checked)) {
                $option->attributes->disabled = true;
            }
            $cdisplay['options'][] = $option;
        }
    }

    $cdisplay['hascapability'] = is_enrolled($context, NULL, 'mod/choice:choose'); //only enrolled users are allowed to make a choice

    if ($choice->allowupdate && $DB->record_exists('choice_answers', ['choiceid'=> $choice->id, 'userid'=> $user->id])) {
        $cdisplay['allowupdate'] = true;
    }

    if ($choice->showpreview && $choice->timeopen > time()) {
        $cdisplay['previewonly'] = true;
    }

    return $cdisplay;
}

/**
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $choice
 * @param object $cm
 * @param int $groupmode
 * @param bool $onlyactive Whether to get response data for active users only.
 * @return array
 */
function sortvoting_get_response_data($choice, $cm, $groupmode, $onlyactive) {
    global $CFG, $USER, $DB;

    $context = context_module::instance($cm->id);

/// Get the current group
    if ($groupmode > 0) {
        $currentgroup = groups_get_activity_group($cm);
    } else {
        $currentgroup = 0;
    }

/// Initialise the returned array, which is a matrix:  $allresponses[responseid][userid] = responseobject
    $allresponses = [];

/// First get all the users who have access here
/// To start with we assume they are all "unanswered" then move them later
    // TODO Does not support custom user profile fields (MDL-70456).
    $userfieldsapi = \core_user\fields::for_identity($context, false)->with_userpic();
    $userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
    $allresponses[0] = get_enrolled_users($context, 'mod/choice:choose', $currentgroup,
            $userfields, null, 0, 0, $onlyactive);

/// Get all the recorded responses for this choice
    $rawresponses = $DB->get_records('choice_answers', ['choiceid' => $choice->id]);

/// Use the responses to move users into the correct column

    if ($rawresponses) {
        $answeredusers = [];
        foreach ($rawresponses as $response) {
            if (isset($allresponses[0][$response->userid])) {   // This person is enrolled and in correct group
                $allresponses[0][$response->userid]->timemodified = $response->timemodified;
                $allresponses[$response->optionid][$response->userid] = clone($allresponses[0][$response->userid]);
                $allresponses[$response->optionid][$response->userid]->answerid = $response->id;
                $answeredusers[] = $response->userid;
            }
        }
        foreach ($answeredusers as $answereduser) {
            unset($allresponses[0][$answereduser]);
        }
    }
    return $allresponses;
}
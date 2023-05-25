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

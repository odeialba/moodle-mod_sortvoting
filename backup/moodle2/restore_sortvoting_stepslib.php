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
 * Define all the restore steps that will be used by the restore_sortvoting_activity_task.
 *
 * @package     mod_sortvoting
 * @subpackage  backup-moodle2
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_sortvoting_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define structure of activity.
     *
     * @return mixed
     */
    protected function define_structure() {

        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('sortvoting', '/activity/sortvoting');
        $paths[] = new restore_path_element('sortvoting_option', '/activity/sortvoting/options/option');
        if ($userinfo) {
            $paths[] = new restore_path_element('sortvoting_answer', '/activity/sortvoting/answers/answer');
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process sortvoting.
     *
     * @param mixed $data
     * @return void
     */
    protected function process_sortvoting($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        // Insert the sortvoting record.
        $newitemid = $DB->insert_record('sortvoting', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process options.
     *
     * @param mixed $data
     * @return void
     */
    protected function process_sortvoting_option($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->sortvotingid = $this->get_new_parentid('sortvoting');

        $newitemid = $DB->insert_record('sortvoting_options', $data);
        $this->set_mapping('sortvoting_option', $oldid, $newitemid);
    }

    /**
     * Process answers.
     *
     * @param mixed $data
     * @return void
     */
    protected function process_sortvoting_answer($data) {
        global $DB;

        $data = (object)$data;

        $data->sortvotingid = $this->get_new_parentid('sortvoting');
        $data->optionid = $this->get_mappingid('sortvoting_option', $data->optionid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newitemid = $DB->insert_record('sortvoting_answers', $data);
        // No need to save this mapping as far as nothing depend on it (child paths, file areas nor links decoder).
    }

    /**
     * Add sortvoting related files, no need to match by itemname (just internally handled context).
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_sortvoting', 'intro', null);
    }
}

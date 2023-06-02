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
 * Define all the backup steps that will be used by the backup_sortvoting_activity_task.
 *
 * @package     mod_sortvoting
 * @subpackage  backup-moodle2
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_sortvoting_activity_structure_step extends backup_activity_structure_step {

    /**
     * Structure definition for sortvoting.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $sortvoting = new backup_nested_element('sortvoting', ['id'], [
            'name', 'intro', 'introformat', 'allowupdate', 'timecreated', 'timemodified', 'completionsubmit'
        ]);

        $options = new backup_nested_element('options');
        $option = new backup_nested_element('option', ['id'], [
            'text', 'timemodified']);

        $answers = new backup_nested_element('answers');
        $answer = new backup_nested_element('answer', ['id'], [
            'userid', 'optionid', 'position', 'timemodified']);

        // Build the tree.
        $sortvoting->add_child($options);
        $options->add_child($option);

        $sortvoting->add_child($answers);
        $answers->add_child($answer);

        // Define sources.
        $sortvoting->set_source_table('sortvoting', ['id' => backup::VAR_ACTIVITYID]);
        $option->set_source_table('sortvoting_options', ['sortvotingid' => backup::VAR_PARENTID], 'id ASC');

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $answer->set_source_table('sortvoting_answers', ['sortvotingid' => backup::VAR_PARENTID]);
        }

        // Define id annotations.
        $answer->annotate_ids('user', 'userid');

        // Define file annotations. This file area hasn't itemid.
        $sortvoting->annotate_files('mod_sortvoting', 'intro', null);

        // Return the root element (sortvoting), wrapped into standard activity structure.
        return $this->prepare_activity_structure($sortvoting);
    }
}

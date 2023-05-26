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
 * The main mod_sortvoting configuration form.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_sortvoting_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        // -------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('sortvotingname', 'mod_sortvoting'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'sortvotingname', 'mod_sortvoting');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // -------------------------------------------------------------------------------
        // Adding the rest of mod_sortvoting settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('header', 'optionhdr', get_string('options', 'choice'));

        $mform->addElement('selectyesno', 'allowupdate', get_string("allowupdate", "choice"));

        $repeatarray = [];
        $repeatarray[] = $mform->createElement('text', 'option', get_string('optionno', 'choice'));
        $repeatarray[] = $mform->createElement('hidden', 'optionid', 0);

        if ($this->_instance) {
            $repeatno = $DB->count_records('sortvoting_options', ['sortvotingid' => $this->_instance]);
            $repeatno += 2;
        } else {
            $repeatno = 5;
        }

        $repeateloptions['option']['helpbutton'] = ['choiceoptions', 'choice'];
        $mform->setType('option', PARAM_CLEANHTML);

        $mform->setType('optionid', PARAM_INT);

        $this->repeat_elements($repeatarray, $repeatno,
                    $repeateloptions, 'option_repeats', 'option_add_fields', 3, null, true);

        // Make the first two options required.
        if ($mform->elementExists('option[0]')) {
            $mform->addRule('option[0]', get_string('atleastoneoption', 'choice'), 'required', null, 'client');
        }
        if ($mform->elementExists('option[1]')) {
            $mform->addRule('option[1]', get_string('atleastoneoption', 'choice'), 'required', null, 'client');
        }

        // -------------------------------------------------------------------------------

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Enforce defaults here.
     *
     * @param array $defaultvalues Form defaults
     * @return void
     **/
    public function data_preprocessing(&$defaultvalues) {
        global $DB;
        if (!empty($this->_instance) &&
                ($options = $DB->get_records('sortvoting_options', ['sortvotingid' => $this->_instance], 'id ASC'))) {

            $key = 0;
            // TODO: Maybe we can just use normal array without the $key.
            foreach ($options as $option) {
                $defaultvalues['option['.$key.']'] = $option->text;
                $defaultvalues['optionid['.$key.']'] = $option->id;
                $key++;
            }
        }
    }
}

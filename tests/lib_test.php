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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/mod/sortvoting/lib.php');

/**
 * Sort Voting module library functions tests
 *
 * @category    test
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lib_test extends \externallib_advanced_testcase {

    /**
     * Tests events after sortvoting is viewed.
     *
     * @covers ::sortvoting_view
     * @return void
     */
    public function test_sortvoting_view() {
        global $CFG;

        $this->resetAfterTest();

        $this->setAdminUser();
        // Setup test data.
        $course = $this->getDataGenerator()->create_course();
        $sortvoting = $this->getDataGenerator()->create_module('sortvoting', array('course' => $course->id));
        $context = \context_module::instance($sortvoting->cmid);
        $cm = get_coursemodule_from_instance('sortvoting', $sortvoting->id);

        // Trigger and capture the event.
        $sink = $this->redirectEvents();

        sortvoting_view($sortvoting, $course, $cm, $context);

        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = array_shift($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_sortvoting\event\course_module_viewed', $event);
        $this->assertEquals($context, $event->get_context());
        $url = new \moodle_url('/mod/sortvoting/view.php', array('id' => $cm->id));
        $this->assertEquals($url, $event->get_url());
        $this->assertEventContextNotUsed($event);
        $this->assertNotEmpty($event->get_name());
    }

    /**
     * Test the callback responsible for returning the completion rule descriptions.
     * This function should work given either an instance of the module (cm_info), such as when checking the active rules,
     * or if passed a stdClass of similar structure, such as when checking the the default completion settings for a mod type.
     *
     * @covers ::mod_sortvoting_get_completion_active_rule_descriptions
     * @return void
     */
    public function test_mod_sortvoting_completion_get_active_rule_descriptions() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Two activities, both with automatic completion. One has the 'completionsubmit' rule, one doesn't.
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $sortvoting1 = $this->getDataGenerator()->create_module('sortvoting', [
            'course' => $course->id,
            'completion' => 2,
            'completionsubmit' => 1
        ]);
        $sortvoting2 = $this->getDataGenerator()->create_module('sortvoting', [
            'course' => $course->id,
            'completion' => 2,
            'completionsubmit' => 0
        ]);
        $cm1 = \cm_info::create(get_coursemodule_from_instance('sortvoting', $sortvoting1->id));
        $cm2 = \cm_info::create(get_coursemodule_from_instance('sortvoting', $sortvoting2->id));

        // Data for the stdClass input type.
        // This type of input would occur when checking the default completion rules for an activity type, where we don't have
        // any access to cm_info, rather the input is a stdClass containing completion and customdata attributes, just like cm_info.
        $moddefaults = new \stdClass();
        $moddefaults->customdata = ['customcompletionrules' => ['completionsubmit' => 1]];
        $moddefaults->completion = 2;

        $activeruledescriptions = [get_string('completionsubmit', 'sortvoting')];
        $this->assertEquals(mod_sortvoting_get_completion_active_rule_descriptions($cm1), $activeruledescriptions);
        $this->assertEquals(mod_sortvoting_get_completion_active_rule_descriptions($cm2), []);
        $this->assertEquals(mod_sortvoting_get_completion_active_rule_descriptions($moddefaults), $activeruledescriptions);
        $this->assertEquals(mod_sortvoting_get_completion_active_rule_descriptions(new \stdClass()), []);
    }
}

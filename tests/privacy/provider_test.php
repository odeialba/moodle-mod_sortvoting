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

namespace mod_sortvoting\privacy;

use core_privacy\local\metadata\collection;
use mod_sortvoting\privacy\provider;
use stdClass;

/**
 * Privacy provider tests class.
 *
 * @package    mod_sortvoting
 * @copyright  2023 Odei Alba <odeialba@odeialba.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The student object. */
    protected $student;

    /** @var stdClass The sortvoting object. */
    protected $sortvoting;

    /** @var stdClass The course object. */
    protected $course;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void {
        $this->resetAfterTest();

        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $options = ['fried rice', 'spring rolls', 'sweet and sour pork', 'satay beef', 'gyouza'];
        $params = [
            'course' => $course->id,
            'option' => $options,
            'name' => 'First Preference Voting Activity',
            'showpreview' => 0,
        ];

        $plugingenerator = $generator->get_plugin_generator('mod_sortvoting');
        // The sortvoting activity the user will answer.
        $sortvoting = $plugingenerator->create_instance($params);
        // Create another sortvoting activity.
        $plugingenerator->create_instance($params);
        $cm = get_coursemodule_from_instance('sortvoting', $sortvoting->id);

        // Create a student which will make a sortvoting.
        $student = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $course->id, $studentrole->id);

        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($student);
        sortvoting_user_submit_response($sortvoting, $votes, $course, $cm);
        $this->setAdminUser();
        $this->student = $student;
        $this->sortvoting = $sortvoting;
        $this->course = $course;
    }

    /**
     * Test for provider::get_metadata().
     * @covers ::get_metadata
     */
    public function test_get_metadata(): void {
        $collection = new collection('mod_sortvoting');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(1, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('sortvoting_answers', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('sortvotingid', $privacyfields);
        $this->assertArrayHasKey('optionid', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);

        $this->assertEquals('privacy:metadata:sortvoting_answers', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = \context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     * @covers ::export_user_data
     */
    public function test_export_for_context(): void {
        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);
        $cmcontext = \context_module::instance($cm->id);

        // Export all of the data for the context.
        $this->export_context_data_for_user($this->student->id, $cmcontext, 'mod_sortvoting');
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     * @covers ::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $sortvoting = $this->sortvoting;
        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);

        // Create another student who will answer the sortvoting activity.
        $student = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($student->id, $this->course->id, $studentrole->id);

        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($student);
        sortvoting_user_submit_response($sortvoting, $votes, $this->course, $cm);
        $this->setAdminUser();

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id]);
        $expectedanswercount = (count($sortvotingwithoptions->option) * 2);
        $this->assertEquals($expectedanswercount, $count);

        // Delete data based on context.
        $cmcontext = \context_module::instance($cm->id);
        provider::delete_data_for_all_users_in_context($cmcontext);

        // After deletion, the sortvoting answers for that sortvoting activity should have been deleted.
        $count = $DB->count_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id]);
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $sortvoting = $this->sortvoting;
        $generator = $this->getDataGenerator();
        $cm1 = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);

        // Create a second sortvoting activity.
        $options = ['Boracay', 'Camiguin', 'Bohol', 'Cebu', 'Coron'];
        $params = [
            'course' => $this->course->id,
            'option' => $options,
            'name' => 'Which do you think is the best island in the Philippines?',
            'showpreview' => 0,
        ];
        $plugingenerator = $generator->get_plugin_generator('mod_sortvoting');
        $sortvoting2 = $plugingenerator->create_instance($params);
        $plugingenerator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('sortvoting', $sortvoting2->id);

        // Make a selection for the first student for the 2nd sortvoting activity.
        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting2->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($this->student);
        sortvoting_user_submit_response($sortvoting2, $votes, $this->course, $cm2);
        $this->setAdminUser();

        // Create another student who will answer the first sortvoting activity.
        $otherstudent = $generator->create_user();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $generator->enrol_user($otherstudent->id, $this->course->id, $studentrole->id);

        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($otherstudent);
        sortvoting_user_submit_response($sortvoting, $votes, $this->course, $cm1);
        $this->setAdminUser();

        // Before deletion, we should have 2 responses.
        $count = $DB->count_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id]);
        $expectedanswercount = (count($sortvotingwithoptions->option) * 2);
        $this->assertEquals($expectedanswercount, $count);

        $context1 = \context_module::instance($cm1->id);
        $context2 = \context_module::instance($cm2->id);
        $contextlist = new \core_privacy\local\request\approved_contextlist(
            $this->student,
            'sortvoting',
            [\context_system::instance()->id, $context1->id, $context2->id]
        );
        provider::delete_data_for_user($contextlist);

        // After deletion, the sortvoting answers for the first student should have been deleted.
        $count = $DB->count_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id, 'userid' => $this->student->id]);
        $this->assertEquals(0, $count);

        // Confirm that we only have one sortvoting answer available.
        $sortvotinganswers = $DB->get_records('sortvoting_answers');
        $expectedanswercount = (count($sortvotingwithoptions->option) * 1);
        $this->assertCount($expectedanswercount, $sortvotinganswers);
        $lastresponse = reset($sortvotinganswers);
        // And that it's the other student's response.
        $this->assertEquals($otherstudent->id, $lastresponse->userid);
    }

    /**
     * Test for provider::get_users_in_context().
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context(): void {
        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);
        $cmcontext = \context_module::instance($cm->id);

        $userlist = new \core_privacy\local\request\userlist($cmcontext, 'mod_sortvoting');
        \mod_sortvoting\privacy\provider::get_users_in_context($userlist);

        $this->assertEquals(
            [$this->student->id],
            $userlist->get_userids()
        );
    }

    /**
     * Test for provider::get_users_in_context() with invalid context type.
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context_invalid_context_type(): void {
        $systemcontext = \context_system::instance();

        $userlist = new \core_privacy\local\request\userlist($systemcontext, 'mod_sortvoting');
        \mod_sortvoting\privacy\provider::get_users_in_context($userlist);

        $this->assertCount(0, $userlist->get_userids());
    }

    /**
     * Test for provider::delete_data_for_users().
     * @covers ::delete_data_for_users
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $sortvoting = $this->sortvoting;
        $generator = $this->getDataGenerator();
        $cm1 = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id);

        // Create a second sortvoting activity.
        $options = ['Boracay', 'Camiguin', 'Bohol', 'Cebu', 'Coron'];
        $params = [
            'course' => $this->course->id,
            'option' => $options,
            'name' => 'Which do you think is the best island in the Philippines?',
            'showpreview' => 0,
        ];
        $plugingenerator = $generator->get_plugin_generator('mod_sortvoting');
        $sortvoting2 = $plugingenerator->create_instance($params);
        $plugingenerator->create_instance($params);
        $cm2 = get_coursemodule_from_instance('sortvoting', $sortvoting2->id);

        // Make a selection for the first student for the 2nd sortvoting activity.
        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting2->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($this->student);
        sortvoting_user_submit_response($sortvoting2, $votes, $this->course, $cm2);
        $this->setAdminUser();

        // Create 2 other students who will answer the first sortvoting activity.
        $otherstudent = $generator->create_and_enrol($this->course, 'student');
        $anotherstudent = $generator->create_and_enrol($this->course, 'student');

        $sortvotingwithoptions = sortvoting_get_sortvoting($sortvoting->id);
        $optionids = array_keys($sortvotingwithoptions->option);

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($otherstudent);
        sortvoting_user_submit_response($sortvoting, $votes, $this->course, $cm1);
        $this->setAdminUser();

        // Create an array of votes and positions, sorting options randomly.
        $votes = [];
        shuffle($optionids);
        $i = 1;
        foreach ($optionids as $optionid) {
            $votes[] = [
                'optionid' => $optionid,
                'position' => $i++,
            ];
        }

        $this->setUser($anotherstudent);
        sortvoting_user_submit_response($sortvoting, $votes, $this->course, $cm1);
        $this->setAdminUser();

        // Before deletion, we should have 3 responses in the first sortvoting activity.
        $count = $DB->count_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id]);
        $expectedanswercount = (count($sortvotingwithoptions->option) * 3);
        $this->assertEquals($expectedanswercount, $count);

        $context1 = \context_module::instance($cm1->id);
        $approveduserlist = new \core_privacy\local\request\approved_userlist(
            $context1,
            'sortvoting',
            [$this->student->id, $otherstudent->id]
        );
        provider::delete_data_for_users($approveduserlist);

        // After deletion, the sortvoting answers of the 2 students provided above should have been deleted
        // from the first sortvoting activity. So there should only remain 1 answer which is for $anotherstudent.
        $sortvotinganswers = $DB->get_records('sortvoting_answers', ['sortvotingid' => $sortvoting->id]);
        $expectedanswercount = (count($sortvotingwithoptions->option) * 1);
        $this->assertCount($expectedanswercount, $sortvotinganswers);
        $lastresponse = reset($sortvotinganswers);
        $this->assertEquals($anotherstudent->id, $lastresponse->userid);

        // Confirm that the answer that was submitted in the other sortvoting activity is intact.
        $sortvotinganswers = $DB->get_records_select('sortvoting_answers', 'sortvotingid <> ?', [$sortvoting->id]);
        $expectedanswercount = (count($sortvotingwithoptions->option) * 1);
        $this->assertCount($expectedanswercount, $sortvotinganswers);
        $lastresponse = reset($sortvotinganswers);
        // And that it's for the sortvoting2 activity.
        $this->assertEquals($sortvoting2->id, $lastresponse->sortvotingid);
    }
}

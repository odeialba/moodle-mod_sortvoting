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

namespace mod_sortvoting\output;

use context_module;
use renderer_base;
use core_reportbuilder\system_report_factory;
use mod_sortvoting\reportbuilder\local\systemreports\sessions;

/**
 * Renderable for showing the sort form of a given activity
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sort_form_view implements \templatable, \renderable {
    /**
     * @var \stdClass $sortvoting
     */
    private $sortvoting;

    /**
     * Constructor
     *
     * @param \stdClass $sortvoting
     */
    public function __construct(\stdClass $sortvoting) {
        $this->sortvoting = $sortvoting;
    }

    /**
     * Exports for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        // Get sort voting options from database into an array.
        $options = $DB->get_records('sortvoting_options', ['sortvotingid' => $this->sortvoting->id], 'id ASC');

        return ['options' => $options];






        $cm = get_coursemodule_from_instance('sortvoting', $this->sortvoting->id, $this->sortvoting->course);
        $contextmodule = context_module::instance($cm->id);

        // Create our report instance in the course module context.
        $report = system_report_factory::create(sessions::class, $contextmodule, '', '', 0, [
            'sortvotingid' => $this->sortvoting->id,
        ]);

        $context = [
            'sessionslisttable' => $report->output(),
            'signupsessionid' => $this->signupsessionid,
        ];

        if ($this->signupsessionid) {
            $session = sortvoting_get_session($this->signupsessionid);
            $context['cansignup'] = \mod_sortvoting\permission::can_signup($session, \context_module::instance($cm->id));
        }

        if (\mod_sortvoting\permission::can_edit_sessions(\context_module::instance($cm->id))) {
            $actionmenu = new \action_menu();
            $actionmenu->set_action_label(get_string('adddots'));
            $actionmenu->set_menu_trigger(get_string('adddots'), 'btn btn-primary align-items-center mb-2');

            $link = new \action_menu_link_secondary(
                new \moodle_url('#'),
                null,
                get_string('sortvoting', 'sortvoting'),
                ['data-cmid' => $cm->id, 'data-action' => 'addsession']);
            $actionmenu->add($link);

            $link = new \action_menu_link_secondary(
                new \moodle_url('#'),
                null,
                get_string('multiplesortvotings', 'sortvoting'),
                ['data-cmid' => $cm->id, 'data-action' => 'addmultiple']);
            $actionmenu->add($link);

            $context['actions'] = $actionmenu->export_for_template($output);
        }

        return $context;
    }
}

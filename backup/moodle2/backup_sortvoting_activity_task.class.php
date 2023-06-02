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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/sortvoting/backup/moodle2/backup_sortvoting_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the sortvoting instance
 *
 * @package     mod_sortvoting
 * @category    backup
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_sortvoting_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        $this->add_step(new backup_sortvoting_activity_structure_step('sortvoting_structure', 'sortvoting.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of sortvotings.
        $search = "/(" . $base . "\/mod\/sortvoting\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SORTVOTINGINDEX*$2@$', $content);

        // Link to sortvoting view by moduleid.
        $search = "/(" . $base . "\/mod\/sortvoting\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SORTVOTINGVIEWBYID*$2@$', $content);

        return $content;
    }
}

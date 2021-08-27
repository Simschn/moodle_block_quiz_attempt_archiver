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
 * Signed Quiz Export
 *
 * @package    block_signed_quiz_export
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class block_signed_quiz_export extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_signed_quiz_export');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    function get_content() {
        global $CFG, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '<h3>FOOTER</h3>';
        $this->content->text = '<h1>'. json_encode($this->get_owning_activity()) .'</h1>';

        return $this->content;
    }

    // my moodle can only have SITEID and it's redundant here, so take it away
    public function applicable_formats() {
        return array('mod-quiz' => true);
    }

    public function instance_allow_multiple() {
          return true;
    }

    function has_config() {return true;}

    /**
     * Return the quiz activity's id.
     * @return stdclass the activity record.
     * @throws coding_exception
     */
    public function get_owning_activity() {
        global $DB;

        // Set some defaults.
        $result = new stdClass();
        $result->id = 0;

        if (empty($this->instance->parentcontextid)) {
            return $result;
        }
        $parentcontext = context::instance_by_id($this->instance->parentcontextid);
        if ($parentcontext->contextlevel != CONTEXT_MODULE) {
            return $result;
        }
        $cm = get_coursemodule_from_id($this->page->cm->modname, $parentcontext->instanceid);
        if (!$cm) {
            return $result;
        }

        return $cm;
    }
}

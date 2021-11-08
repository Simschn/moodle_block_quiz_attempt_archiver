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

require_once("$CFG->libdir/formslib.php");

class block_form_sign extends moodleform {
    /**
     * @throws coding_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore
        $mform->addElement('submit', 'signbutton', get_string('buttonsign','block_signed_quiz_export'));
        $mform->closeHeaderBefore('signbutton');
        $mform->setDefault('id',$this->_customdata['id']);
    }

    function validation($data, $files) {
        return array();
    }
}
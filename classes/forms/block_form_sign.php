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

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

class block_form_sign extends moodleform
{

  /**
   * @throws coding_exception
   */
  public function definition()
  {
    $mform = $this->_form;
    $mform->addElement('submit', 'signbutton', get_string('archive', 'block_quiz_attempt_archiver'));
    $mform->closeHeaderBefore('signbutton');
    $mform->setDefault('id', $this->_customdata['id']);
  }

  public function validation($data, $files)
  {
    return array();
  }
}

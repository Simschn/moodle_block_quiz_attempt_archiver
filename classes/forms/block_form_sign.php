<?php

require_once("$CFG->libdir/formslib.php");

class block_form_sign extends moodleform {
    //Add elements to form

    /**
     * @throws coding_exception
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form; // Don't forget the underscore
        $this->add_action_buttons(false, get_string('buttonsign','block_signed_quiz_export'));
        $mform->setDefault('id',$this->_customdata['id']);
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
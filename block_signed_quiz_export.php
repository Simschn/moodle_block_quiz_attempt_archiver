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
 *  Moodle Block Plugin Main file
 *
 * @package  signed quiz export
 * @copyright 2021 Simon Schniedenharn
 * @copyright based on work by 2020 CBlue Srl
 * @copyright based on work by 2014 Johannes Burk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/signed_quiz_export/classes/forms/block_form_sign.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/services/render_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/services/timestamp_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/tasks/render_task.php');

class block_signed_quiz_export extends block_base
{

  function init()
  {
    $this->title = get_string('pluginname', 'block_signed_quiz_export');
    $this->content_type = BLOCK_TYPE_TEXT;
  }

  /**
   * @throws coding_exception
   * @throws dml_exception
   */
  function get_content()
  {
    global $DB, $PAGE;
    if ($this->content !== null) {
      return $this->content;
    }
    $this->content = new stdClass();
    $this->content->text = '';
    $this->content->footer = '';
    $cm = $this->get_owning_activity();
    $mformSign = new block_form_sign($PAGE->url, array('id' => $cm->id));
    // render export download table
    try {
      $quiz_exports = $DB->get_records('signed_quiz_export', array('quizid' => $cm->instance));
      $this->content->text = 'Download Quiz results:';
      $this->content->text .= '<br>';
      foreach ($quiz_exports as $quiz_export) {
        $exportid = $quiz_export->id;
        $this->content->text .= html_writer::tag('a', 'Export from ' . date("Y-m-d H:i:s", $quiz_export->sdate), array('href' => '/blocks/signed_quiz_export/download_export.php?exportid=' . $exportid));
        if ($quiz_export->valid) {
          $this->content->text .= '<i class="fa fa-check"></i>';
        } else {
          $this->content->text .= '<i class="fa fa-times"></i>';
        }
        $this->content->text .= '<br>';
      }
    } catch (Exception $e) {
    }

    if ($mformSign->get_data()) {
      try {
        $quiz_attempts = $DB->get_records('quiz_attempts', array('quiz' => $cm->instance));
        $this->process_attempts($quiz_attempts);
      } catch (Exception $e) {
        return $this->content;
      }
    }
    $this->content->footer .= $mformSign->render();
    return $this->content;
  }

  // my moodle can only have SITEID and it's redundant here, so take it away
  public function applicable_formats()
  {
    return array('mod-quiz' => true);
  }

  public function instance_allow_multiple()
  {
    return true;
  }

  function has_config()
  {
    return true;
  }

  /**
   * Return the quiz activity's id.
   * @return stdclass the activity record.
   * @throws coding_exception
   */
  public function get_owning_activity()
  {

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


  /**
   * Export the quiz attempts
   * @param object $quiz the quiz settings
   * @param object $cm the course_module object.
   * @param array $attemptids the list of attempt ids to export.
   * @param array $allowed This list of userids that are visible in the report.
   *      Users can only export attempts that they are allowed to see in the report.
   *      Empty means all users.
   * @throws Exception
   */
  /*
  function sign_and_validate($attemptids, $tmp_zip_file)
  {
    global $CFG, $DB, $USER;
    $time = time(); // this will get you the current time in unix time format (seconds since 1/1/1970 GMT)
    $currentYear = userdate($time, '%Y');
    $backupTime = userdate($time, '%Y%m%d-%H%M'); // this will print the time in the timezone of the current user (formats)
    $quizattempt = quiz_attempt::create(current($attemptids));
    $backupPath = $CFG->dataroot . '/backups/' . $currentYear . '/' . $quizattempt->get_course()->fullname . '/' . $quizattempt->get_quiz_name();
    if (!is_dir($backupPath)) {
      mkdir($backupPath, 0777, true);
    }
    $backupFilePath =  $backupPath . '/' . $backupTime;
    copy($tmp_zip_file, $backupFilePath . '.zip');
    $requestFilePath = TrustedTimestamps::create_request_file($backupFilePath . '.zip');
    copy($requestFilePath, $backupFilePath . '.tsq');
    $response = TrustedTimestamps::sign_request_file($requestFilePath, get_config('block_signed_quiz_export', 'tsdomain'));
    $responseFile = fopen($backupFilePath . '.tsr', 'w+') or die("Unable to open file!");
    fwrite($responseFile, $response);
    fclose($responseFile);
    $isValid = TrustedTimestamps::validate($backupFilePath, $CFG->dirroot . '/blocks/signed_quiz_export/certs/dfn-cert.pem');
    if ($isValid) {
      $status = 'valid';
    } else {
      $status = 'invalid';
    }
    $DB->insert_record(
      "signed_quiz_export",
      [
        'teacherid' => $USER->id,
        'quizid' => $quizattempt->get_quizid(),
        'sdate' => $time,
        'status' => $status
      ]
    );
  }
   */

  function process_attempts($quiz_attempts)
  {
    global $DB, $USER;
    $quizattempt = quiz_attempt::create(current($quiz_attempts));
    $time = time(); // this will get you the current time in unix time format (seconds since 1/1/1970 GMT)
    $currentYear = userdate($time, '%Y');
    $backupTime = userdate($time, '%Y%m%d-%H%M'); // this will print the time in the timezone of the current user (formats)
    $record_id = $DB->insert_record(
      "signed_quiz_export",
      [
        'teacherid' => $USER->id,
        'quizid' => $quizattempt->get_quizid(),
        'sdate' => $time,
        'path' => '/backups/' . $currentYear . '/' . $quizattempt->get_course()->fullname . '/' . $quizattempt->get_quiz_name() . '/' . $backupTime . '.zip',
        'status' => 'pending'
      ]
    );
    $render_task = new render_task();
    $render_task->set_custom_data(array('quiz_attempts' => $quiz_attempts, 'record_id' => $record_id));
    $render_task->execute();
    $quiz_info = $this->get_owning_activity();
    $urltogo = new moodle_url('/mod/quiz/view.php', array('id' => $quiz_info->id));
    redirect($urltogo);
  }
}

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

require_once($CFG->dirroot . '/blocks/quiz_attempt_archiver/classes/forms/block_form_sign.php');
require_once($CFG->dirroot . '/blocks/quiz_attempt_archiver/classes/service/render_service.php');
require_once($CFG->dirroot . '/blocks/quiz_attempt_archiver/classes/service/timestamp_service.php');
require_once($CFG->dirroot . '/blocks/quiz_attempt_archiver/classes/task/render_task.php');

use \block_quiz_attempt_archiver\task\render_task;

class block_quiz_attempt_archiver extends block_base
{

  public function init()
  {
    $this->title = get_string('pluginname', 'block_quiz_attempt_archiver');
    $this->content_type = BLOCK_TYPE_TEXT;
  }

  /**
   * @throws coding_exception
   * @throws dml_exception
   */
  public function get_content()
  {
    global $DB;
    if ($this->content !== null) {
      return $this->content;
    }
    $this->content = new stdClass();
    $this->content->text = '';
    $this->content->footer = '';
    $cm = $this->get_owning_activity();
    $mformsign = new block_form_sign($this->page->url, array('id' => $cm->id));
    // Render export download table.
    try {
      $quizexports = $DB->get_records('quiz_attempt_archiver', array('quizid' => $cm->instance));
      $this->content->text = get_string('download', 'block_quiz_attempt_archiver');
      $this->content->text .= '<br>';
      foreach ($quizexports as $quizexport) {
        $exportid = $quizexport->id;
        $this->content->text .= html_writer::tag(
          'a',
          get_string('attemptfrom', 'block_quiz_attempt_archiver') . date(
            "Y-m-d H:i:s",
            $quizexport->sdate
          ),
          array('href' => '/blocks/quiz_attempt_archiver/download_export.php?exportid=' . $exportid)
        );
        if ($quizexport->status == 'valid') {
          $this->content->text .= '<i class="ml-1 fa fa-check"></i>';
        } else if ($quizexport->status == 'invalid') {
          $this->content->text .= '<i class="ml-1 fa fa-times"></i>';
        } else if ($quizexport->status == 'pending') {
          $this->content->text .= '<i class="ml-1 fa fa-clock-o"></i>';
        }
        $this->content->text .= '<br>';
      }
    } catch (Exception $e) {
      throw $e;
    }

    if ($mformsign->get_data()) {
      try {
        $quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $cm->instance));
        $this->process_attempts($quizattempts, $cm->instance);
      } catch (Exception $e) {
        throw $e;
      }
    }
    $this->content->footer .= $mformsign->render();
    return $this->content;
  }

  public function applicable_formats()
  {
    return array('mod-quiz' => true);
  }

  public function instance_allow_multiple()
  {
    return true;
  }

  public function has_config()
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
      $response = TrustedTimestamps::sign_request_file($requestFilePath, get_config('block_quiz_attempt_archiver', 'tsdomain'));
      $responseFile = fopen($backupFilePath . '.tsr', 'w+') or die("Unable to open file!");
      fwrite($responseFile, $response);
      fclose($responseFile);
      $isValid = TrustedTimestamps::validate($backupFilePath, $CFG->dirroot . '/blocks/quiz_attempt_archiver/certs/dfn-cert.pem');
      if ($isValid) {
        $status = 'valid';
      } else {
        $status = 'invalid';
      }
      $DB->insert_record(
        "quiz_attempt_archiver",
        [
          'teacherid' => $USER->id,
          'quizid' => $quizattempt->get_quizid(),
          'sdate' => $time,
          'status' => $status
        ]
      );
    }
   */

  public function process_attempts($quizattempts, $quizid)
  {
    global $DB, $USER;
    $quizattempt = quiz_attempt::create(current(array_keys($quizattempts)));
    $time = time();
    $currentyear = userdate($time, '%Y');
    $backuptime = userdate($time, '%Y%m%d-%H%M');
    $backuppath = '/backups/' . $currentyear . '/' . $quizattempt->get_course()->fullname . '/';
    $backuppath .= $quizattempt->get_quiz_name() . '/' . $backuptime . '.zip';
    $archiveid = $DB->insert_record(
      "quiz_attempt_archiver",
      [
        'teacherid' => $USER->id,
        'quizid' => $quizattempt->get_quizid(),
        'sdate' => $time,
        'path' => $backuppath,
        'status' => 'pending'
      ]
    );
    $rendertask = new render_task();
    $rendertask->set_custom_data(array('quizid' => $quizid, 'archiveid' => $archiveid));
    \core\task\manager::queue_adhoc_task($rendertask);
    $quizinfo = $this->get_owning_activity();
    $urltogo = new moodle_url('/mod/quiz/view.php', array('id' => $quizinfo->id));
    redirect($urltogo);
  }
}

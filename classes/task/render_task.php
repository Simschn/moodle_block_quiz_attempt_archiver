<?php


namespace block_signed_quiz_export\task;


defined('MOODLE_INTERNAL') || die();

use block_signed_quiz_export\service\timestamp_service;
use block_signed_quiz_export\service\render_service;
use block_signed_quiz_export\service\archive_service;
use quiz_attempt;
use Exception;

require_once($CFG->dirroot . '/blocks/signed_quiz_export/classes/service/render_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/classes/service/archive_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/classes/service/timestamp_service.php');
require_once($CFG->dirroot . '/mod/quiz/attemptlib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');



class render_task extends \core\task\adhoc_task
{

  public function execute()
  {
    global $CFG, $DB, $USER;
    $render_service = new render_service();
    $archive_service = new archive_service();
    $timestamp_service = new timestamp_service();
    $data = $this->get_custom_data();
    $archive_record = $DB->get_record('signed_quiz_export', ['id' => $data->archiveid]);
    $path = $CFG->dataroot . $archive_record->path;
    $usertopdf = [];
    $quizattempts = $DB->get_records('quiz_attempts', array('quiz' => $data->quizid));
    $quizattemptids = array_keys($quizattempts);
    foreach ($quizattemptids as $quizattemptid) {
      $quiz_attempt_obj = quiz_attempt::create($quizattemptid);
      $pdf = $render_service->attempt_to_pdf($quiz_attempt_obj);
      $usertopdf[$quiz_attempt_obj->get_userid()] = $pdf;
    }
    print_r('zip path: ' . $path);
    $archive_service->create_zip_archive_from_pdfs($path, $usertopdf);
    //$this->sign_and_validate(array_keys($data->$quiz_attempts), $tmp_zip_file);

    $request_file_path = timestamp_service::create_request_file($path);
    $response_file_path = timestamp_service::sign_request_file(
      $request_file_path,
      get_config(
        'block_signed_quiz_export',
        'tsdomain'
      )
    );
    //TODO: download cert if not found
    $isValid = $timestamp_service->validate(
      $response_file_path,
      $CFG->dirroot . '/blocks/signed_quiz_export/certs/dfn-cert.pem'
    );
    if ($isValid) {
      $status = 'valid';
    } else {
      $status = 'invalid';
    }
    $DB->update_record(
      "signed_quiz_export",
      [
        'id' => $data->archiveid,
        'status' => $status
      ]
    );
  }
}

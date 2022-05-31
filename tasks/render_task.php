<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/signed_quiz_export/services/render_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/services/timestamp_service.php');
require_once($CFG->dirroot . '/blocks/signed_quiz_export/services/archive_service.php');

class render_task extends \core\task\adhoc_task
{
  public function execute()
  {
    global $CFG, $DB, $USER;
    $render_service = new render_service();
    $archive_service = new archive_service();
    $data = $this->get_custom_data();
    $archive_record = $DB->get_record('signed_quiz_export', ['id' => $data['record_id']]);
    $path = $archive_record['path'];
    $attempts_to_pdf = [];
    foreach ($data['quiz_attempts'] as $quiz_attempt) {
      $pdf = $render_service->attempt_to_pdf($quiz_attempt);
      $attempts_to_pdf[$quiz_attempt] = $pdf;
    }

    $archive_service->create_zip_archive_from_pdfs($path, $attempts_to_pdf);
    //$this->sign_and_validate(array_keys($data->$quiz_attempts), $tmp_zip_file);

    $request_file_path = TrustedTimestamps::create_request_file($path);
    $response_file_path = TrustedTimestamps::sign_request_file($request_file_path, get_config('block_signed_quiz_export', 'tsdomain'));
    //TODO: download cert if not found
    $isValid = TrustedTimestamps::validate($response_file_path, $CFG->dirroot . '/blocks/signed_quiz_export/certs/dfn-cert.pem');
    if ($isValid) {
      $status = 'valid';
    } else {
      $status = 'invalid';
    }
    $DB->update_record(
      "signed_quiz_export",
      [
        'id' => $data['record_id'],
        'status' => $status
      ]
    );
  }
}

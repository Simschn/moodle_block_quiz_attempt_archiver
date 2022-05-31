<?php
require_once __DIR__ . '/../vendor/autoload.php';
class archive_service
{
  /* creates a zip archive and returns its path */
  public function create_zip_archive_from_pdfs($path, $attempt_to_pdf)
  {
    global $DB;
    $pdf_files = array();
    fopen($path, "w");
    chmod($path, 0644);
    $zip = new ZipArchive;
    $zip->open($path);

    foreach ($attempt_to_pdf as $attempt_id => $pdf_file) {
      $attemptobj = quiz_attempt::create($attempt_id);
      $pdf_files[] = $pdf_file;
      $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
      $zip->addFile($pdf_file, fullname($student, true) . "_" . $attempt_id . '.pdf');
    }

    $zip->close();
    foreach ($attempt_to_pdf as $attempt_id => $pdf_file) {
      unlink($pdf_file);
    }
  }
}

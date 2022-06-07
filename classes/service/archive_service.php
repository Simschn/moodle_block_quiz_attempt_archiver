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
 * Service file for Archiving of quiz results
 *
 * @package    block_quiz_attempt_archiver
 * @copyright  Simon Schniedenharn 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_quiz_attempt_archiver\service;

use ZipArchive;

class archive_service
{

  /* creates a zip archive and returns its path */
  public function create_zip_archive_from_pdfs($path, $useridtopdf)
  {
    global $DB;
    $zip = new ZipArchive();
    @mkdir(pathinfo($path)['dirname'], 0755, true);
    $zip->open($path, ZipArchive::CREATE);
    foreach ($useridtopdf as $userid => $pdf) {
      $student = $DB->get_record('user', array('id' => $userid));
      $zip->addFile($pdf, fullname($student, true) . '.pdf');
    }

    $zip->close();
  }
}

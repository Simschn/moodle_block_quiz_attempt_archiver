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
 * This file defines the export engine class.
 *
 * @package   quiz_export
 * @copyright Simon Schniedenharn 2021
 * @copyright based on Work by 2020 CBlue Srl
 * @copyright based on work by 2014 Johannes Burk
 * @note      Changes to the original file to better fit into the context of a block and updates to the result renderings
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_quiz_attempt_archiver\service;

use DOMDocument;
use DOMXPath;
use Exception;
use stdClass;
use question_display_options;
use quiz_attempt;
use user_picture;
use action_link;
use moodle_url;
use html_writer;

use mikehaertl\wkhtmlto\Pdf;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

class render_service
{
  /**
   * Exports the given quiz attempt to a pdf file.
   *
   * @param quiz_attempt $attemptobj The quiz attempt to export.
   * @return string          File path and name as string of the pdf file.
   */

  public function attempt_to_pdf($attemptobj)
  {
    // $attempt_info = $this->get_additionnal_informations($attemptobj);
    $pdf = new Pdf(array(
      'binary' => '/usr/local/bin/wkhtmltopdf',
      'no-outline',         // Make Chrome not complain
      'margin-top'    => 0,
      'margin-right'  => 0,
      'margin-bottom' => 0,
      'margin-left'   => 0,
      'disable-smart-shrinking',
      'user-style-sheet' => __DIR__ . '/../../style/styles.css',
    ));

    // Start output buffering html
    // $additionnal_informations = '<h3 class="text-center" style="margin-bottom: -20px;">' .
    // get_string('documenttitle', 'block_quiz_attempt_archiver', ['coursename' => $attemptobj->get_course()->fullname, 'quizname' => $attemptobj->get_quiz_name()]) .
    // '</h3>';

    $htmlfiles = $this->question_per_page($attemptobj);
    $currentpage = 0;
    foreach ($htmlfiles as $htmlfile) {
      ob_start();
      include $htmlfile;
      $contenthtml = ob_get_clean();
      // Start output buffering html
      // $contentHTML = preg_replace('/(<div class="r[0-9]">)(<input.*>)(<div.*>)(<span.*>)(<div.*>)(<p>)(.*)(<\/p>)(<\/div>)(<\/div>).(<\/div>)/U', '$1 $2 $3 $4 <span>  $7  </span> $9 $10', $contentHTML);
      // $contentHTML = preg_replace('/(<div class="r[0-9]">)(<input.*>)(<div.*>)(<span class="answernumber">)(.*)(<\/span>)(<div.*>)(<p>)(.*)(<\/p>)(<\/div>)(<\/div>).(<\/div>)/U', '$1 $2 <span> $5 $7 </span> $9 $10', $contentHTML);
      // $contentHTML = preg_replace('/(<div class="r[0-9].*">)(<input.*\/>)(<div.*>)(<span class="answernumber">)(.*)(<\/span>)(<div.*>)(<p.*>)(.*)(<\/p>)(<\/div>)(<\/div>)/U', '$1 $2 <span> $5 $9 </span> $11 $12', $contentHTML);
      // $contentHTML = preg_replace('/(<div class="r[0-9].*">)(<input.*\/>)(<div.*>)(<span class="answernumber">)(.*)(<\/span>)(<div.*>)(<p.*>)(.*)<br>(<\/p>)(<\/div>)(<\/div>)/U', '$1 $2 <span> $5 $9 </span>', $contentHTML);
      // $contentHTML = preg_replace('/<span[^>]*accesshide.*>.*<\/span>/U', '', $contentHTML);
      $contenthtml = preg_replace('/accesshide/U', '', $contenthtml);
      // $contentHTML = preg_replace('/size="[0-9]"/', 'size="10"', $contentHTML);
      // $contentHTML = preg_replace('/<script(.*?)>(.*?)<\/script>/is', '', $contentHTML);
      // $contentHTML = preg_replace('/visibleifjs/', '', $contentHTML);
      // $contentHTML = preg_replace('/<td class="control correct hiddenifjs">(.*?)<\/td>/', '', $contentHTML);
      /* $contentHTML = preg_replace('/<span class="feedbackspan accesshide"(.*)?>(.*)?<\/span>$/is', '', $contentHTML);
            $contentHTML = preg_replace('/<link(.*)?\/>/', '', $contentHTML); */
      $dom = new DOMDocument('1.0', 'UTF-8');
      libxml_use_internal_errors(true);
      $dom->loadHTML($contenthtml);
      $xpath = new DOMXPath($dom);

      $nodes = $xpath->query("//script");
      foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
      }
      $nodes = $xpath->query("//link");
      foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
      }

      // $nodes = $xpath->query('//input[@type="radio"][@checked="true"]');
      // foreach ($nodes as $node) {
      // $node->parentNode->removeChild($node);
      // }
      //
      // $nodes = $xpath->query(' //input[@type="radio"][@checked]');
      // foreach ($nodes as $node) {
      // $node->setAttribute('checked', 'true');
      // }
      $nodes = $xpath->query("//span[contains(@class, 'feedbackspan')]");
      foreach ($nodes as $node) {
        $parts = explode('<br/>', $node->ownerDocument->saveXML($node));
        $nodepost = $dom->createElement('div');
        foreach ($parts as $part) {
          $part = strip_tags($part);
          $nodepart = $dom->createElement('div', $part);
          $nodepost->appendChild($nodepart);
        }
        $nodepost->setAttribute('class', 'outcome');
        $node->parentNode->replaceChild($nodepost, $node);
      }

      $nodes = $xpath->query("//div[contains(@class, 'specificfeedback')]");
      foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
      }
      $nodes = $xpath->query("//div[contains(@class, 'comment')]");
      foreach ($nodes as $node) {
        $node->parentNode->removeChild($node);
      }
      $nodes = $xpath->query("//div[contains(@class, 'r')]/label");
      foreach ($nodes as $node) {
        $node->setAttribute('style', 'margin-left:10px');
      }
      $quizobj = $attemptobj->get_quizobj();

      $pdf->addPage($dom->saveHTML());

      $currentpage++;
      $quizobj = $attemptobj->get_quizobj();
      @mkdir('/tmp/html/' . $quizobj->get_quiz_name(), 0777, true);
      @mkdir('/tmp/pdf/' . $quizobj->get_quiz_name(), 0777, true);
      file_put_contents('/tmp/html/' . $quizobj->get_quiz_name() . '/' . $currentpage . '.html', $dom->saveHTML());
    }
    $tempfile = '/tmp/pdf/' . $quizobj->get_quiz_name() . '/' . $attemptobj->get_userid() . '.pdf';
    if (!$pdf->saveAs($tempfile)) {
      throw new Exception($pdf->getError());
    }
    // Cleanup
    $this->setup_new_page();
    return $tempfile;
  }

  protected function question_per_page($attemptobj)
  {
    $tmphtmlfiles = array();
    $showall = false;
    $numpages = $attemptobj->get_num_pages();
    for ($page = 0; $page < $numpages; $page++) {
      $questionids = $attemptobj->get_slots($page);
      $lastpage = $attemptobj->is_last_page($page);
      foreach ($questionids as $questionid) {
        // We have just one question id but an array is required from render function
        $slots = array();
        $slots[] = $questionid;
        $tmpdir = sys_get_temp_dir();
        $tmpfile = tempnam($tmpdir, "mdl-qexp_");
        $tmphtmlfile = $tmpfile . ".html";
        rename($tmpfile, $tmphtmlfile);
        chmod($tmphtmlfile, 0644);
        // have quiz summeray on every page as head
        $output = $this->get_review_html($attemptobj, $slots, $page, $showall, $lastpage);
        file_put_contents($tmphtmlfile, $output);
        $tmphtmlfiles[] = $tmphtmlfile;
      }
    }
    return $tmphtmlfiles;
  }

  /**
   * Render the main page
   *
   * @param $attemptobj
   * @param $slots
   * @param $page
   * @param $showall
   * @param $lastpage
   * @return mixed
   * @throws coding_exception
   * @throws moodle_exception
   */
  protected function get_review_html($attemptobj, $slots, $page, $showall, $lastpage)
  {
    $html = $this->render($attemptobj, $slots, $page, $showall, $lastpage);
    return $html;
  }

  /**
   * Return the main page in which the quiz export settings can be configured.
   *
   * @param $attemptobj
   * @param $slots
   * @param $page
   * @param $showall
   * @param $lastpage
   * @return mixed
   * @throws coding_exception
   * @throws moodle_exception
   */
  protected function render($attemptobj, $slots, $page, $showall, $lastpage)
  {
    global $PAGE;
    $options = $attemptobj->get_display_options(true);
    // Ugly hack to get a new page
    $this->setup_new_page();
    // $url = new moodle_url('/mod/quiz/report/export/a2pdf.php', array('attempt' => $attemptobj->get_attemptid()));
    // $PAGE->set_url($url);

    // Set up the page header.
    // $headtags = $attemptobj->get_html_head_contributions($page, $showall);
    // $PAGE->set_title($attemptobj->get_quiz_name());
    // $PAGE->set_heading($attemptobj->get_course()->fullname);
    $summarydata = $this->summary_table($attemptobj, $options);
    // Display only content
    // $PAGE->force_theme('boost');
    $PAGE->set_pagelayout('embedded');
    $output = $PAGE->get_renderer('mod_quiz');
    return $output->review_page($attemptobj, $slots, 0, $showall, $lastpage, $options, $summarydata);
  }

  /**
   * Generates a quiz review summary table.
   * The Code is original from mod/quiz/review.php and just wrapped to a function.
   * @param quiz_attempt $attemptobj The attempt object the summary is for.
   * @param mod_quiz_display_options $options Extra options for the attempt.
   * @return array contains all table data for summary table
   */
  protected function summary_table($attemptobj, $options)
  {
    global $USER, $DB;
    // Work out some time-related things.
    $attempt = $attemptobj->get_attempt();
    $quiz = $attemptobj->get_quiz();
    $overtime = 0;
    if ($attempt->state == quiz_attempt::FINISHED) {
      if ($timetaken = ($attempt->timefinish - $attempt->timestart)) {
        if ($quiz->timelimit && $timetaken > ($quiz->timelimit + 60)) {
          $overtime = $timetaken - $quiz->timelimit;
          $overtime = format_time($overtime);
        }
        $timetaken = format_time($timetaken);
      } else {
        $timetaken = "-";
      }
    } else {
      $timetaken = get_string('unfinished', 'quiz');
    }
    // Prepare summary informat about the whole attempt.
    $summarydata = array();
    if (!$attemptobj->get_quiz()->showuserpicture && $attemptobj->get_userid() != $USER->id) {
      // If showuserpicture is true, the picture is shown elsewhere, so don't repeat it.
      $student = $DB->get_record('user', array('id' => $attemptobj->get_userid()));
      $usrepicture = new user_picture($student);
      $usrepicture->courseid = $attemptobj->get_courseid();
      $summarydata['user'] = array(
        'title' => $usrepicture,
        'content' => new action_link(
          new moodle_url('/user/view.php', array(
            'id' => $student->id, 'course' => $attemptobj->get_courseid()
          )),
          fullname($student, true)
        ),
      );
    }

    // Timing information.
    $summarydata['startedon'] = array(
      'title' => get_string('startedon', 'quiz'),
      'content' => userdate($attempt->timestart),
    );

    $summarydata['state'] = array(
      'title' => get_string('attemptstate', 'quiz'),
      'content' => quiz_attempt::state_name($attempt->state),
    );

    if ($attempt->state == quiz_attempt::FINISHED) {
      $summarydata['completedon'] = array(
        'title' => get_string('completedon', 'quiz'),
        'content' => userdate($attempt->timefinish),
      );
      $summarydata['timetaken'] = array(
        'title' => get_string('timetaken', 'quiz'),
        'content' => $timetaken,
      );
    }

    if (!empty($overtime)) {
      $summarydata['overdue'] = array(
        'title' => get_string('overdue', 'quiz'),
        'content' => $overtime,
      );
    }

    // Show marks (if the user is allowed to see marks at the moment).
    $grade = quiz_rescale_grade($attempt->sumgrades, $quiz, false);
    if ($options->marks >= question_display_options::MARK_AND_MAX && quiz_has_grades($quiz)) {
      if ($attempt->state != quiz_attempt::FINISHED) {
        // Cannot display grade.
      } else if (is_null($grade)) {
        $summarydata['grade'] = array(
          'title' => get_string('grade', 'quiz'),
          'content' => quiz_format_grade($quiz, $grade),
        );
      } else {
        // Show raw marks only if they are different from the grade (like on the view page).
        if ($quiz->grade != $quiz->sumgrades) {
          $a = new stdClass();
          $a->grade = quiz_format_grade($quiz, $attempt->sumgrades);
          $a->maxgrade = quiz_format_grade($quiz, $quiz->sumgrades);
          $summarydata['marks'] = array(
            'title' => get_string('marks', 'quiz'),
            'content' => get_string('outofshort', 'quiz', $a),
          );
        }

        // Now the scaled grade.
        $a = new stdClass();
        $a->grade = html_writer::tag('b', quiz_format_grade($quiz, $grade));
        $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
        if ($quiz->grade != 100) {
          $a->percent = html_writer::tag('b', format_float(
            $attempt->sumgrades * 100 / $quiz->sumgrades,
            0
          ));
          $formattedgrade = get_string('outofpercent', 'quiz', $a);
        } else {
          $formattedgrade = get_string('outof', 'quiz', $a);
        }
        $summarydata['grade'] = array(
          'title' => get_string('grade', 'quiz'),
          'content' => $formattedgrade,
        );
      }
    }

    // Feedback if there is any, and the user is allowed to see it now.
    $feedback = $attemptobj->get_overall_feedback($grade);
    if ($options->overallfeedback && $feedback) {
      $summarydata['feedback'] = array(
        'title' => get_string('feedback', 'quiz'),
        'content' => $feedback,
      );
    }

    return $summarydata;
  }

  /**
   * Overwrites the $PAGE global with a new moodle_page instance.
   * Code is original from lib/setup.php and lib/adminlib.php
   *
   * @return void
   */
  protected function setup_new_page()
  {
    global $CFG, $PAGE;

    if (!empty($CFG->moodlepageclass)) {
      if (!empty($CFG->moodlepageclassfile)) {
        require_once($CFG->moodlepageclassfile);
      }
      $classname = $CFG->moodlepageclass;
    } else {
      $classname = 'moodle_page';
    }
    $PAGE = new $classname();
    unset($classname);

    $PAGE->set_context(null);
  }

  /**
   * Get student's firstname and lastname + quiz name
   * to display it at the top of the document
   *
   * @param quiz_attempt $attemptobj The attempt object the summary is for.
   * @return array contains additionnals informations
   */
  public function get_additionnal_informations($attemptobj)
  {
    global $DB;
    $userid = $attemptobj->get_userid();
    $userinformations = $DB->get_record('user', ['id' => $userid], 'firstname, lastname');
    return [
      'firstname' => $userinformations->firstname,
      'lastname' => $userinformations->lastname,
      'coursename' => $attemptobj->get_course()->fullname,
      'quizname' => $attemptobj->get_quiz_name()
    ];
  }
}
/**
 * Encode all images in base64 to render it in the pdf
 *
 * @param $html
 * @return string|string[]
  protected function preloadImageWithCurrentSession($html) {
    global $CFG;
    $matches = [];
    $matches_content = [];
    preg_match_all("/<img.*src=\"(https?:\/\/.*)\".*>/U", $html, $matches);

    if (count($matches[1]) > 0) {
      $cookieFile = '/tmp/cookie-pdf';
      file_put_contents($cookieFile, "MoodleSession=" . $_COOKIE['MoodleSession']);
      // Without that we have to wait the script eneded to load images => time out
      session_write_close();
      $uniqMatches = array_unique($matches[1]);
      foreach ($uniqMatches as $match) {
        $parsedmatch = preg_replace('/https/', 'http', $match);
        $parsedmatch = preg_replace('/' . preg_quote($_SERVER['SERVER_NAME']) . '/', 'localhost', $parsedmatch);
        $ch = curl_init($parsedmatch);
        $strCookie = session_name() . '=' . $_COOKIE[session_name()] . '; path=/';
        curl_setopt($ch, CURLOPT_COOKIE, $strCookie);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $header = curl_getinfo($ch);
        $result = curl_exec($ch);
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($result);
        $matches_content[] = "data:" . $mimeType . ";base64," . base64_encode($result);
        curl_close($ch);
      }
      $html = str_replace($uniqMatches, $matches_content, $html);
      $html = preg_replace('/https:\/\/' . preg_quote($_SERVER['SERVER_NAME']) . '\//', 'http://localhost/', $html);
    }
    return $html;
  }
 */

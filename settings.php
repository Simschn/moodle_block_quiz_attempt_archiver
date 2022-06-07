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
 * Signed quiz export block settings
 *
 * @package    quiz_attempt_archiver
 * @copyright  Simon Schniedenharn 2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings->add(new admin_setting_heading(
    'sampleheader',
    get_string('headerconfig', 'block_quiz_attempt_archiver'),
    get_string('descconfig', 'block_quiz_attempt_archiver')
));

$settings->add(new admin_setting_configtext(
    'block_quiz_attempt_archiver/tsdomain',
    get_string('tsdomain', 'block_quiz_attempt_archiver'),
    get_string('tsinfo', 'block_quiz_attempt_archiver'),
    'http://zeitstempel.dfn.de',
    PARAM_TEXT
));
$settings->add(new admin_setting_configtext(
    'block_quiz_attempt_archiver/tscert',
    get_string('tscert', 'block_quiz_attempt_archiver'),
    get_string('tscertinfo', 'block_quiz_attempt_archiver'),
    'https://pki.pca.dfn.de/dfn-ca-global-g2/pub/cacert/chain.txt',
    PARAM_TEXT
));

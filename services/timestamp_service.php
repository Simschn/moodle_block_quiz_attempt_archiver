<?php

/**
 * TrustedTimestamps.php - Creates Timestamp Requestfiles, processes the request at a Timestamp Authority (TSA) after RFC 3161
 * Released under the MIT license (opensource.org/licenses/MIT) Copyright (c) 2015 David Müller
 * bases on OpenSSL and RFC 3161: http://www.ietf.org/rfc/rfc3161.txt

 * @author Simon Schniedenharn
 * @copyright Simon Schniedenharn
 * @note The original File was heavily changed for it to work in the context of Moodle
 * @copyright based on work by David Müller 2015
 * @package trustedtimestamps
 */

require_once __DIR__ . '/../vendor/autoload.php';
class TrustedTimestamps
{
  /**
   * Creates a Timestamp Requestfile from a file
   *
   * @param string $filepath: Filepath to datafile
   * @return string: path of th e created timestamp-requestfile
   */
  public static function create_request_file($filepath)
  {
    $request_file_path = $filepath . '.tsq';
    $cmd = "openssl ts -query -data " . escapeshellarg($filepath) . " -cert -sha512 -no_nonce -out " . escapeshellarg($request_file_path);
    $retarray = array();
    exec($cmd . " 2>&1", $retarray, $retcode);
    if ($retcode !== 0)
      throw new Exception("OpenSSL does not seem to be installed: " . implode(", ", $retarray));
    if (stripos($retarray[0], "openssl:Error") !== false)
      throw new Exception("There was an error with OpenSSL. Is version >= 0.99 installed?: " . implode(", ", $retarray));
    return $request_file_path;
  }

  /**
   * Signs a timestamp requestfile at a TSA using CURL
   *
   * @param string $requestfile_path: The path to the Timestamp Requestfile as created by createRequestfile
   * @param string $tsa_url: URL of a TSA such as http://zeitstempel.dfn.de
   * @return string $binary_response_string
   */
  public static function sign_request_file($request_file_path, $tsa_url)
  {
    if (!file_exists($request_file_path))
      throw new Exception("The Requestfile was not found");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tsa_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($request_file_path));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
    $binary_response_string = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status != 200 || !strlen($binary_response_string))
      throw new Exception("The request failed");

    //$base64_response_string = base64_encode($binary_response_string);
    $parts = pathinfo($request_file_path);
    $response_file_path =  $parts['dirname'] . $parts['filename'] . '.tsr';
    //$response_time = self::getTimestampFromAnswer ($base64_response_string);
    $response_file = fopen($response_file_path, 'rw+');
    fwrite($response_file, $binary_response_string);
    fclose($response_file);
    return $response_file_path;
  }

  /**
   * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
   *
   * @param string $base64_response_string: Response string as returned by signRequestfile
   * @return int: unix timestamp
   */
  public static function getTimestampFromAnswer($base64_response_string)
  {
    $binary_response_string = base64_decode($base64_response_string);
    $responsefile = self::createTempFile($binary_response_string);
    $cmd = "openssl ts -reply -in " . escapeshellarg($responsefile) . " -text";
    $retarray = array();
    exec($cmd . " 2>&1", $retarray, $retcode);

    if ($retcode !== 0)
      throw new Exception("The reply failed: " . implode(", ", $retarray));
    $matches = array();
    $response_time = 0;

    /*
         * Format of answer:
         *
         * Foobar: some stuff
         * Time stamp: 21.08.2010 blabla GMT
         * Somestuff: Yayayayaya
         */
    foreach ($retarray as $retline) {
      if (preg_match("~^Time\sstamp\:\s(.*)~", $retline, $matches)) {
        $response_time = strtotime($matches[1]);
        break;
      }
    }
    if (!$response_time)
      throw new Exception("The Timestamp was not found");
    return $response_time;
  }

  /**
   *
   * @param string $hash: sha1 hash of the data which should be checked
   * @param string $base64_response_string: The response string as returned by signRequestfile
   * @param int $response_time: The response time, which should be checked
   * @param string $tsa_cert_file: The path to the TSAs certificate chain (e.g. https://pki.pca.dfn.de/global-services-ca/pub/cacert/chain.txt)
   * @return <type>
   */
  public static function validate($filepath, $tsa_cert_file)
  {
    global $CFG;
    $cmd = "openssl ts -verify -data " . escapeshellarg($filepath . '.zip') . " -in " . escapeshellarg($filepath . '.tsr') . " -CAfile " . escapeshellarg($tsa_cert_file);
    $retarray = array();
    exec($cmd . " 2>&1", $retarray, $retcode);
    $verification_result = fopen($CFG->dataroot . '/backups/verification', 'w+');
    fwrite($verification_result, json_encode($retarray));
    return preg_match('/Verification: OK/', implode('\n', $retarray));
  }

  /**
   * Create a tempfile in the systems temp path
   *
   * @param string $str: Content which should be written to the newly created tempfile
   * @return string: filepath of the created tempfile
   */
  public static function createTempFile($str = "")
  {
    $tempfilename = tempnam(sys_get_temp_dir(), rand());
    if (!file_exists($tempfilename))
      throw new Exception("Tempfile could not be created");
    if (!empty($str) && !file_put_contents($tempfilename, $str))
      throw new Exception("Could not write to tempfile");
    return $tempfilename;
  }
}

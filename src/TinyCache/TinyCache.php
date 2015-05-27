<?php
namespace TinyCache;

use Commando\Command;

class TinyCache {

  private $cmd;
  private $opts;
  private $api_key = '';
  private $url = 'https://tinycache.io/api/v1';

  /**
   * Setup options for the command line.
   *
   * @param string $api_key
   *   Override API Key set from environment variable.
   */
  public function __construct($api_key = '') {
    $cmd = new Command();
    $cmd->option()
      ->required()
      ->referToAs('HTTP Method')
      ->describedAs('Either GET, POST, PUT, or DELETE');
    $cmd->option()
      ->referToAs('Cache Key')
      ->describedAs('Cache key to interact with.');
    $cmd->option('k')
      ->aka('key')
      ->describedAs('Cache key to interact with.');
    $cmd->option('d')
      ->aka('data')
      ->describedAs('Data to send as params.');
    $cmd->option('j')
      ->aka('json')
      ->describedAs('Raw JSON to send to the API.');
    $cmd->option('x')
      ->aka('expire')
      ->describedAs('Expire time to set in POST or PUT.');
    $cmd->option('v')
      ->aka('value')
      ->describedAs('Cache value to POST or PUT.');
    $cmd->option('e')
      ->aka('encrypt')
      ->aka('decrypt')
      ->describedAs('Encryption/Decryption key.');
    $cmd->option('f')
      ->aka('file')
      ->describedAs('File to pass as data. Will be base64 encoded.');
    $cmd->option('q')
      ->aka('query')
      ->describedAs('Query params for doing an advanced cache query.');
    $this->cmd = $cmd;
    $this->opts = $cmd->getOptions();
    $this->api_key = $api_key ?: $_SERVER['TINYCACHE_API_KEY'];
  }

  /**
   * Runs the request.
   */
  public function run() {
    $valid_methods = ['GET', 'POST', 'PUT', 'DELETE'];
    if (!in_array(strtoupper($this->opts[0]->getValue()), $valid_methods)) {
      $this->cmd->error(new \Exception('Invalid method "' . $this->opts[0]->getValue() . '" given.'));
    }
    $method = 'request_' . strtoupper($this->opts[0]->getValue());
    return $this->$method();
  }

  /**
   * Performs a PUT request.
   */
  public function request_PUT() {
    $data = $this->request_POST($return_data = TRUE);
    return $this->getResponse('PUT', $data);
  }

  /**
   * Performs a POST request.
   *
   * @param boolean $return_data
   *   Will return the data object without performing the request.
   */
  public function request_POST($return_data = FALSE) {

    // Ensure we are POSTing to a key.
    if (!$this->opts[1]->getValue()) {
      $this->cmd->error(new \Exception('Cache key is required.'));
    }

    // Check if we are passing raw JSON.
    if ($json = $this->opts['json']->getValue()) {
      $data = json_decode($json);
      if (!$data) {
        $this->cmd->error(new \Exception('JSON could not be parsed.'));
      }
    }
    // Check if we are passing data as query params.
    elseif ($q = $this->opts['data']->getValue()) {
      $parsed = array();
      parse_str($q, $parsed);
      $data = (object) $parsed;
    }
    // Should be sending raw things.
    else {
      $data = new \stdClass();
    }

    // Check if we are explicitly setting the cache_value.
    if (!isset($data->cache_value) && $cache_value = $this->opts['value']->getValue()) {
      $data->cache_value = $cache_value;
    }

    // Check if we are explicitly setting the expire time.
    if (!isset($data->expire) && $expire = $this->opts['expire']->getValue()) {
      $data->expire = $expire;
    }

    // Check if we are explicitly setting the encrypt value.
    if (!isset($data->encrypt) && $encrypt = $this->opts['encrypt']->getValue()) {
      $data->encrypt = $encrypt;
    }

    if ($file = $this->opts['file']->getValue()) {
      $file_contents = file_get_contents($file);
      $data->cache_value = base64_encode($file_contents);
    }

    // Check to ensure that we have some cache_value we are sending.
    if (!isset($data->cache_value)) {
      $this->cmd->error(new \Exception('Cache value must be set.'));
    }

    // Returns the data object. Used by the PUT method.
    if ($return_data) {
      return $data;
    }

    return $this->getResponse('POST', $data);
  }

  /**
   * Performs a GET request.
   */
  public function request_GET() {
    $data = new \stdClass();

    if ($decrypt = $this->opts['decrypt']->getValue()) {
      $data->decrypt = $decrypt;
    }

    return $this->getResponse('GET', $data);
  }

  /**
   * Performs a DELETE request.
   */
  public function request_DELETE() {
    return $this->getResponse('DELETE');
  }

  /**
   * Gets a response from the API.
   *
   * @param string $method
   *   Request method. Either GET, POST, DELETE, or PUT.
   * @param object $data
   *   Optional data to pass to the API.
   * @return string
   *   Returns the curl response.
   *
   * @throws \Exception
   */
  public function getResponse($method, $data = NULL) {
    $curl_headers = array();
    $ch = curl_init();

    // Handle different methods.
    if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }

    // Set the API key in a header.
    if ($this->api_key) {
      $curl_headers[] = 'X-TINYCACHE-API-KEY: ' . $this->api_key;
    }
    else {
      $this->cmd->error(new \Exception('Invalid API key'));
    }

    // Pass encrypt string in a header.
    if (isset($data->encrypt)) {
      $curl_headers[] = 'X-TINYCACHE-ENCRYPT: ' . $data->encrypt;
      unset($data->encrypt);
    }

    // Pass decrypt strings in a header.
    if (isset($data->decrypt)) {
      $curl_headers[] = 'X-TINYCACHE-DECRYPT: ' . $data->decrypt;
      unset($data->decrypt);
    }

    // Set curl headers.
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);

    // Handle cache_key.
    if ($cache_key = $this->opts[1]->getValue()) {
      $this->url .= '/' . $cache_key;
    }

    // Setup query params.
    if ($query = $this->opts['query']->getValue()) {
      $this->url .= '?' . $query;
    }

    // Handle passing data.
    if (isset($data->cache_value)) {
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    // Make the curl request.
    curl_setopt($ch, CURLOPT_URL, $this->url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $curl_response = curl_exec($ch);
    curl_close($ch);
    return $curl_response . PHP_EOL;
  }

}

<?php
/*
Copyright 2014 Loop Science 

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

namespace TurretIO;

class TurretIO {

  private static $host = 'https://api.turret.io';

  private $key;
  private $secret;

  function __construct($key, $secret) {
    $this->key = $key;
    $this->secret = $secret;
  }

  private function getSecret() {
    return base64_decode($this->secret);
  }

  private function buildStringToSign($uri, $time, $data=null) {
    if(!is_null($data)) {
      return "{$uri}{$data}{$time}";
    }
    return "{$uri}{$time}";
  }

  private function makeRequest($uri, $time, $type, $data=null) {
    if($type == "GET") {
      $ch = curl_init(self::$host . $uri);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }

    if($type == "POST") {
      $ch = curl_init(self::$host . $uri);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, base64_encode($data));
    }

    // create signature
    $signature = base64_encode(hash_hmac("sha512", $this->buildStringToSign($uri, $time, $data), $this->getSecret(), true));

    // build headers
    $headers = array(
      "Content-Type: text/json",
      "X-LS-Time: $time",
      "X-LS-Key: {$this->key}",
      "X-LS-Auth: $signature"
    );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $data = curl_exec($ch);
    if(curl_errno($ch)) {
      echo "Error: " . curl_error($ch);
      curl_close($ch);
      return false;
    } else {
      return $data;
      curl_close($ch);
    }
  }

  public function GET($uri) {
    // setup request vars
    $time = time();
    $response = $this->makeRequest($uri, $time, "GET");
    return json_decode($response);
  }

  public function POST($uri, $data) {
    // setup request vars
    $time = time();
    $response = $this->makeRequest($uri, $time, "POST", json_encode($data));
    return json_decode($response);
  }

}

class Account extends TurretIO {
  public function fetch() {
    return $this->GET("/latest/account");
  }

  public function set($outgoing_method, $options=null) {
    switch($outgoing_method) {
      case 'turret.io':
        return $this->POST("/latest/account/me", array("type" => $outgoing_mehod));
        break;

      case 'aws':
        if(array_key_exists('aws_access_key', $options) && array_key_exists('aws_secret_access_key', $options)) {
          return $this->POST("/latest/account/me", array("type" => $options));
        }
        break;

      case 'smtp':
        if(array_key_exists('smtp_host', $options)
          && array_key_exists('smtp_username', $options)
          && array_key_exists('smtp_password', $options)) {
            return $this->POST("/latest/account/me", array("type" => $options));
          }
        break;
    }
  }
}

class Segment extends TurretIO {
  public function fetch($name) {
    return $this->GET("/latest/segment/$name");
  }

  public function create($name, $attribute_map) {
    return $this->POST("/latest/segment/$name", array("attributes" => $attribute_map));
  }

  public function update($name, $attribute_map) {
    return $this->POST("/latest/segment/$name", array("attributes" => $attribute_map));
  }
}

class SegmentEmail extends TurretIO {
  public function fetch($segment_name, $email_id) {
    return $this->GET("/latest/segment/$segment_name/email/$email_id");
  }

  public function create($segment_name, $subject, $html_body, $plain_body) {
    return $this->POST("/latest/segment/$segment_name/email", array("subject" => $subject, "html" => $html_body, "plain" => $plain_body));
  }

  public function update($segment_name, $email_id, $subject, $html_body, $plain_body) {
    return $this->POST("/latest/segment/$segment_name/email/$email_id", array("subject" => $subject, "html" => $html_body, "plain" => $plain_body));
  }

  public function sendTest($segment_name, $email_id, $email_from, $recipient) {
    return $this->POST("/latest/segment/$segment_name/email/$email_id/sendTestEmail", array("email_from" => $email_from, "recipient" => $recipient));
  }

  public function send($segment_name, $email_id, $email_from) {
    return $this->POST("/latest/segment/$segment_name/email/$email_id/sendEmail", array("email_from" => $email_from));
  }
}

class User extends TurretIO {
  public function fetch($email) {
    return $this->GET("/latest/user/$email");
  }

  public function set($email, $attribute_map, $property_map=null) {
    if(!is_null($property_map)) {
      $attribute_map['properties'] = $property_map;
    }

    return $this->POST("/latest/user/$email", $attribute_map);
  }
}

?>

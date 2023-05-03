<?php

require_once('vendor/autoload.php');

class block_extensao extends block_base {
  public function init () {
    $this->title = 'USP Extensão';
  }

  public function get_content () {
    global $OUTPUT;

    $this->content = new stdClass;
    $this->content->text = "olá mundo";

    return $this->content;
  }
}
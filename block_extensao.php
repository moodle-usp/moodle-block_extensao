<?php

require_once('src/Service/Query.php');
require_once('src/turmas.php');
require_once('vendor/autoload.php');

class block_extensao extends block_base {
  public function init () {
    $this->title = 'USP Extensão';
  }

  public function has_config () {
    return true;
  }

  public function get_content () {
    global $OUTPUT;

    $this->content = new stdClass;

    // lista das turmas nas quais o usuario eh ministrante, se for o caso
    $cursos_usuario = $this->lista_turmas_ministrante();

    // array da template
    $info = array(
      'inicio' => true,
      'cursos_docente' => $cursos_usuario
    );

    // template
    $this->content->text = $OUTPUT->render_from_template('block_extensao/bloco_inicial', $info);

    return $this->content;
  }

  /**
   * Lista as turmas nas quais o usuario logado eh ministrante e
   * cujos ambientes Moodle ainda nao tiverem sido criados;
   */
  private function lista_turmas_ministrante () {
    global $USER;

    // verifica se o usuario logado tem numero USP
    if (!isset($USER->idnumber) or empty($USER->idnumber))
      return false;
    
    // eh preciso capturar na base Moodle os curos nos quais o usuario eh docente e
    // cujo ambiente ainda nao foi criado
    $cursos_usuario = Turmas::docente_turmas($USER->idnumber);
    
    return $cursos_usuario; 
  }
}
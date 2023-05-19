<?php
/**
 * Observer
 * 
 * Funcoes que sao chamadas quando um gatilho eh acionado.
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../src/turmas.php');

class block_extensao_observer {
  /**
   * Observador para o evento de deletamento de curso, o
   * 'course_module_deleted'.
   * 
   * @param \core\event\course_deleted $evento
   */
  public static function curso_deletado(\core\event\course_deleted $evento) {
    global $DB;

    // altera na tabela mdl_block_extensao_turma
    $query = "
      UPDATE {block_extensao_turma} 
      SET id_moodle = NULL
      WHERE id_moodle = {$evento->get_data()["objectid"]}
      ";
    $query = $DB->execute($query);
    
    if ($query) {
      \core\notification::success('Base do plugin de Extensão atualizada!');
    } else {
      \core\notification::error('Base do plugin de Extensão não atualizada!');
    }

  }

}
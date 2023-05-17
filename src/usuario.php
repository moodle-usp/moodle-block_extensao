<?php

/**
 * Usuario
 * 
 * A ideia desse arquivo eh mexer com usuarios do Moodle, como na inscricao de
 * usuarios em cursos, atribuicao de papeis, etc.
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');

class Usuario {

  /**
   * Para inscrever um usuario em um curso com um determinado papel.
   * 
   * @param object $curso Objeto de curso criado por $this->criar_objeto_curso.
   * 
   * @return bool|object Erro ou curso criado.
   */
  public static function inscreve_usuario ($id_curso, $id_usuario, $id_papel) {
    // instancia do curso
    $instancia = array_values(enrol_get_instances($id_curso, true))[0];
    // plugin de 'enrol'
    $plugin = enrol_get_plugin('manual');
    // faz o 'enrol' entre o usuario e a instancia com o devido papel
    $plugin->enrol_user($instancia, $id_usuario, $id_papel);
    // captura o contexto do sistema
    $contexto = context_system::instance();
    // define o usuario com o devido papel no devido contexto
    role_assign($id_papel, $id_usuario, $contexto->id);
  }

  /**
   * Para habilitar o acesso de visitantes ("guests") a determinado
   * curso.
   * 
   * @param integer $id_curso Identificador do curso.
   */
  public static function libera_visitantes ($id_curso) {
    global $DB;

    // captura o usuario 'guest'
    $usuario_guest = $DB->get_record('user', ['username' => 'guest']);
    // captura o papel 'guest'
    $papel_guest = $DB->get_record('role', ['shortname' => 'guest']);

    // inscreve o usuario 'guest'
    self::inscreve_usuario($id_curso, $usuario_guest->id, $papel_guest->id);
  }
}
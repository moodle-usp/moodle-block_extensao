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

  /**
   * Para inscrever o usuario logado como "editingteacher".
   * 
   * @param integer $id_curso Identificador do curso.
   */
  public static function inscreve_criador ($id_curso) {
    global $DB, $USER;

    // captura o usuario que esta logado
    $id_usuario = $USER->id;

    // captura o papel do editingteacher
    $editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);

    // inscreve o usuario logado
    self::inscreve_usuario($id_curso, $id_usuario, $editingteacher->id);
  }

  /**
   * Captura as informacoes de uma lista de usuarios, procurando
   * primeiro no Moodle e, em caso de nao encontrar, depois no Apolo.
   * A busca eh feita atraves do 'codpes' (NUSP) e se retorna o proprio
   * 'codpes', o nome ('firstname' + 'fullname' no Moodle, 'nompes' no 
   * Apolo) e o 'id' no Moodle se for o caso.
   * 
   * @param array $lista_usuarios Lista de usuarios.
   * 
   * @return array Lista com informacoes de cada usuario.
   */
  public static function informacoes_usuarios ($lista_usuarios) {
    global $DB;

    // para separar usuarios que estao no Moodle dos que nao estao
    $usuarios = array('moodle' => array(), 'apolo' => array());

    foreach ($lista_usuarios as $usuario) {
      // tenta capturar o usuario no Moodle
      $info_usuario = $DB->get_record('user', ['idnumber' => $usuario->codpes]);
      if ($info_usuario)
        $usuarios['moodle'][] = $info_usuario;
      else {
        // nesse caso precisa buscar no Apolo
        $info_usuario = Apolo::info_usuario($usuario->codpes);
        if ($info_usuario)
          $usuarios['apolo'][] = $info_usuario;
      }
    }
    return $usuarios;
  }
}
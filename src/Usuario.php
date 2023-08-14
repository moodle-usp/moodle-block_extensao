<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Usuario
 * A ideia desse arquivo eh mexer com usuarios do Moodle, como na inscricao de
 * usuarios em cursos, atribuicao de papeis, etc.
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once(__DIR__ . '/Turmas.php');
require_once(__DIR__ . '/Service/Query.php');
//require_once(__DIR__ . '/../../../local/Notificacoes.php');
require_once(__DIR__ . '/Notificacoes.php');

use block_extensao\Service\Query;
use core\message\message;

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

    // inscreve o usuario logado
    self::matricula_professor($id_curso, $id_usuario);
  }

  /**
   * Para inscrever o professor ao curso
   * 
   * @param integer $id_curso para indicar o curso ao qual o professor sera matriculado.
   * @param integer $id_professor para identificar o professor por seu id.
   */
  public static function matricula_professor ($id_curso, $id_professor) {
    global $DB;

    // captura o papel do editingteacher
    $editingteacher = $DB->get_record('role', ['shortname' => 'editingteacher']);

    self::inscreve_usuario($id_curso, $id_professor, $editingteacher->id);
  } 

  /**
   * Captura as informacoes de uma lista de usuarios, procurando
   * primeiro no Moodle e, em caso de nao encontrar, depois no Apolo.
   * A busca eh feita atraves do 'codpes' (NUSP) e se retorna o proprio
   * 'codpes', o nome ('firstname' + 'fullname' no Moodle, 'nompes' no 
   * Apolo) e o 'id' no Moodle se for o caso.
   * 
   * @param array   $lista_usuarios Lista de usuarios.
   * @param integer $logado         Id do usuario logado, se for o caso
   * 
   * @return array Lista com informacoes de cada usuario.
   */
  public static function informacoes_usuarios ($lista_usuarios, $logado="") {
    global $DB;

    // para separar usuarios que estao no Moodle dos que nao estao
    $usuarios = array('moodle' => array(), 'apolo' => array());

    foreach ($lista_usuarios as $usuario) {

      // tenta capturar o usuario no Moodle
      $info_usuario = $DB->get_record('user', ['idnumber' => $usuario->codpes]);
      if ($info_usuario)
        $usuarios['moodle'][] = $info_usuario;
      else {
        // buscando se existe usuario pelos emails
        $emails = Query::emails($usuario->codpes);
        if($emails) {
          // pega os e-mails
          $emails = array_column($emails, 'codema');
          $emails = implode("','",$emails);
          // faz a query
          $sql = "SELECT * FROM {user} WHERE email IN ('{$emails}')"; 
          $info_usuario = $DB->get_record_sql($sql);
          // verifica se encontrou algo e se o encontrado nao eh o usuario logado
          if ($info_usuario && $info_usuario->idnumber != $logado)
            $usuarios['moodle'][] = $info_usuario;
        }
        // se nao existir, precisa buscar no Apolo
        $info_usuario = Query::info_usuario($usuario->codpes);
        if ($info_usuario)
          $usuarios['apolo'][] = $info_usuario;
      }
    }
    return $usuarios;
  }

  /**
   * Captura os ministrantes de uma turma, removendo, se informado,
   * o usuario logado.
   * 
   * @param string|integer $codofeatvceu Codigo de oferecimento da atividade.
   * @param string|integer $logado       Identificador do usuario logado.
   * 
   * @return array Lista com os ministrantes da turma buscada.
   */
  public static function ministrantes_turma ($codofeatvceu, $logado="") {
    // captura os ministrnates a partir do codofeatvceu
    $ministrantes = Turmas::codpes_ministrantes_turma($codofeatvceu);

    // remove o seu proprio se for o caso
    if ($logado != "")
      unset($ministrantes[$logado]);

    // captura as infos caso a lista nao seja vazia
    if (count($ministrantes) > 0)
      $ministrantes = self::informacoes_usuarios($ministrantes, $logado);
    
    return $ministrantes;
  }

  /**
   * Essa funcao tem como objetivo criar uma conta para um usuario que ainda nao possui. 
   * @param array $usuario eh o individuo que sera inscrito no Moodle.
   * @return array|boolean 
   */
  public static function cadastra_usuario($usuario) {
    global $DB;
 
    // Verificar se o usuario ja possui conta no Moodle
    $existeUsuario = $DB->get_record('user', ['username' => $usuario['codpes']]);
    if (!empty($existeUsuario)) {
        \core\notification::warning("O usuário " . $usuario['nompes']. " já possui uma conta no Moodle. O cadastro não sera realizado.");
        return false; 
    }

    // Criando objeto do usuario
    $nomeCompleto = $usuario['nompes'];
    $partesNome = explode(' ', $nomeCompleto); 
    $primeiroNome = $partesNome[0];
    $segundoNome = end($partesNome);

    $novoUsuario = new stdClass();
    $novoUsuario->username = (string) $usuario['codpes'];
    $novoUsuario->idnumber = $usuario['codpes'];
    $novoUsuario->password = "Euamoausp*555";
    $novoUsuario->firstname = $primeiroNome;
    $novoUsuario->lastname = $segundoNome;
    $novoUsuario->email = $usuario['codema'];
    $novoUsuario->auth = 'manual';

    try {
      // Chama a funcao user_create_user() para cadastrar o novo usuario
      $usuario_id = user_create_user($novoUsuario);
      $usuarioObj = $DB->get_record("user", ["id" => $usuario_id]);
      \core\notification::success("O usuário " . $nomeCompleto . " foi cadastrado no Moodle com sucesso!");
      return $usuarioObj;
    } catch (\Exception $e) {
      \core\notification::error("Erro ao cadastrar o usuário: " . $e->getMessage());
      return false;
    }
  }
}

 
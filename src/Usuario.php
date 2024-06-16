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
require_once(__DIR__ . '/Notificacoes.php');
require_once(__DIR__ . '/Atuacao.php');

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
   * Para inscrever o usuario logado como "editingteacher".
   * 
   * @param integer $id_curso Identificador do curso.
   * @param string $codofeatvceu Codigo de oferecimento da atividade..
   */
  public static function inscreve_criador ($id_curso, $codofeatvceu) {
    global $DB, $USER;

    // captura o usuario que esta logado
    $id_usuario = $USER->id;

    // captura o codigo de atuacao
    $atuacao = self::atuacao_ceu($USER->username, $codofeatvceu);

    // Se estiver vazio, provavelmente eh um gerente de categoria
    // Nesse caso, adiciona como 1 - editingteacher
    if (empty($atuacao)) $codatc = 1;
    else $codatc = $atuacao->codatc;

    // inscreve o usuario logado
    self::matricula_professor($id_curso, $id_usuario, $codatc);
  }

  /**
   * Para inscrever o professor ao curso
   * 
   * @param integer $id_curso para indicar o curso ao qual o professor sera matriculado.
   * @param integer $id_professor para identificar o professor por seu id.
   * @param integer $codatc       Codigo de atuacao (papel do usuario)
   */
  public static function matricula_professor ($id_curso, $id_professor, $codatc) {
    global $DB;

    // captura o shortname do codigo de atuacao
    $shortname_codatc = Atuacao::correspondencia_moodle($codatc);

    // captura o papel do shortname
    $role = $DB->get_record('role', ['shortname' => $shortname_codatc]);

    self::inscreve_usuario($id_curso, $id_professor, $role->id);
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
      if ($info_usuario) {
        // Se achar, captura o codatc no mdl_block_extensao_ministrante e salva
        $atuacao = self::atuacao_ceu($usuario->codpes, $usuario->codofeatvceu);
        $info_usuario->codatc = $atuacao->codatc;
        $info_usuario->dscatc = $atuacao->dscatc;
        $usuarios['moodle'][] = $info_usuario;
      }
      else {
        // buscando se existe usuario pelos emails
        if(!is_null($usuario->codema)) {
          // faz a query
          $sql = "SELECT * FROM {user} WHERE email = '{$usuario->codema}'"; 
          $info_usuario = $DB->get_record_sql($sql);
          // verifica se encontrou algo e se o encontrado nao eh o usuario logado
          if ($info_usuario && $info_usuario->idnumber != $logado) {
            $usuarios['moodle'][] = $info_usuario;
          }
          else {
            // se nao achar pelo codpes nem pelo codema, entao cria
            $usuarios['apolo'][] = $usuario;
          }
        }
        else {
          // se nao achar pelo codpes nem pelo codema, entao cria
          $usuarios['apolo'][] = $usuario;
        }
      }
    }
    return $usuarios;
  }

  /**
   * Captura a atuacao na tabela {block_extensao_ministrante} nos campos
   * `codatc` e `dscatc`.
   * 
   * @param string $codpes Codigo de pessoa USP (NUSP)
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * @return object Info do usuario correspondente.
   */
  public static function atuacao_ceu (string $codpes, string $codofeatvceu="") {
    global $DB;
    if ($codofeatvceu == "") $codofeatvceu = $_SESSION['codofeatvceu'];
    $comparacao  = $DB->sql_compare_text('codpes');
    $comparacao .= ' = ';
    $comparacao .= $DB->sql_compare_text(':codpes_usuario');
    $comparacao .= " AND ";
    $comparacao .= $DB->sql_compare_text('codofeatvceu');
    $comparacao .= " = ";
    $comparacao .= $DB->sql_compare_text($codofeatvceu);
    
    $ministrante = $DB->get_record_SQL("SELECT codatc, dscatc FROM {block_extensao_ministrante} WHERE $comparacao", array('codpes_usuario'=>$codpes));
    if (!empty($ministrante))
      return $ministrante;
    else [];
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
    // captura os ministrantes a partir do codofeatvceu
    $ministrantes = Turmas::codpes_ministrantes_turma($codofeatvceu);

    // remove o seu proprio se for o caso
    if ($logado != "")
      unset($ministrantes[$logado]);

    // captura as infos caso a lista nao seja vazia
    if (count($ministrantes) > 0)
      $ministrantes = self::informacoes_usuarios($ministrantes, $logado, $codofeatvceu);
    
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
    $existeUsuario = $DB->get_record('user', ['username' => $usuario->codpes]);
    if (!empty($existeUsuario)) {
      \core\notification::error(get_string('erro_padrao', 'block_extensao'));
      return false; 
    }

    // Verifica se o usuario possui e-mail cadastrado no banco de dados, caso nao, a conta nao eh criada.
    if (empty($usuario->codema)) {
      \core\notification::error("O usuário " . $usuario->nompes. " não possui um endereço de e-mail válido. A matrícula não será realizada.");
      return false;
    }

    // Criando objeto do usuario
    $nomeCompleto = $usuario->nompes;
    $partesNome = explode(" ", $nomeCompleto);
    $primeiroNome = array_shift($partesNome);
    $segundoNome = implode(" ", $partesNome); 

    $novoUsuario = new stdClass();
    $novoUsuario->username = (string) $usuario->codpes;
    $novoUsuario->idnumber = $usuario->codpes;
    $novoUsuario->firstname = $primeiroNome;
    $novoUsuario->lastname = $segundoNome;
    $novoUsuario->email = $usuario->codema;
    $novoUsuario->auth = 'shibboleth';

    try {
      // Chama a funcao user_create_user() para cadastrar o novo usuario
      $usuario_id = user_create_user($novoUsuario);

      // Verificar se o id do usuario foi criado com exito
      if ($usuario_id) {
        $usuarioObj = $DB->get_record("user", ["id" => $usuario_id]);
        return $usuarioObj;
      } else {
          \core\notification::error("Erro ao inscrever o usuário. Por favor contate o suporte.");
          return false;
      }
    } catch (\Exception $e) {
      \core\notification::error("Erro ao cadastrar o usuário: " . $e->getMessage());
      return false;
    }
  }
}
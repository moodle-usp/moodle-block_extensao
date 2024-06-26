<?php

/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Ambientes Moodle
 * Neste arquivo ficam as questoes relativas a criacao de ambientes
 * Moodle (cursos). Isso eh usado pelos docentes quando ha turmas
 * abertas em seu nome, por exemplo.
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/course/lib.php'); // biblioteca de cursos
require_once(__DIR__ . '/Turmas.php');
require_once(__DIR__ . '/Usuario.php');
require_once(__DIR__ . '/Service/Sincronizacao.php');
require_once(__DIR__ . '/Service/Query.php');

use block_extensao\Service\Query;

class Ambiente {

  /**
   * Para criar um curso usando a api do Moodle, passado um objeto de 
   * curso.
   * 
   * @param object $curso Objeto de curso criado por $this->criar_objeto_curso.
   * 
   * @return bool|object Erro ou curso criado.
   */
  public static function criar_ambiente ($info_forms, $ministrantes) {
    global $USER, $DB;

    // faz uma versao em array dos dados do forms
    $info_forms_array = json_decode(json_encode($info_forms), true);

    // eh preciso capturar outras informacoes do curso, como a unidade
    $info_curso_apolo = Turmas::info_turmas([$info_forms])[0];
    if (!$info_curso_apolo) {
      \core\notification::error('O código de oferecimento "' . $info_forms->codofeatvceu . '" não foi encontrado na base. Por favor, entre em contato com a administração.');
      return -1;
    }

    // transforma o enviado em um objeto de curso
    $curso = self::criar_objeto_curso($info_forms, $info_curso_apolo);

    // cria o curso
    $moodle_curso = \create_course($curso);
    
    // se a opcao de visitantes estiver habilitada, precisa adicionar o usuario guest 
    if ($info_forms->guest) {
      // Obter o plugin de matricula "guest"
      $enrol_guest = enrol_get_plugin('guest');
  
      // Adicionar uma instancia de matricula do tipo "guest" ao curso
      $instance_guest_id = $enrol_guest->add_instance($moodle_curso);
  
      if ($instance_guest_id) {
          \core\notification::success('Acesso de visitantes permitido com sucesso.');
      } else {
          \core\notification::error('Não foi possível habilitar o acesso de visitantes.');
      }
    } else {
      \core\notification::error('A opção de visitantes não está habilitada.');
    }
  
    // se der certo, eh necessario salvar isso na base
    Turmas::atualizar_id_moodle_turma($info_forms->codofeatvceu, $moodle_curso->id);
    \core\notification::success('Ambiente criado com sucesso!');

    // Por fim eh preenchido o campo sincronizado_apolo com 1 em relacao ao curso que foi criado
    Sincronizar::sincronizadoApolo($moodle_curso->id);

    // inscreve o usuario logado no curso
    Usuario::inscreve_criador($moodle_curso->id, $info_forms->codofeatvceu);
    \core\notification::success('Usuário criador matriculado como "professor".');

    // caso tenham sido passados outros usuarios, eh preciso inscreve-los
    if (isset($info_forms_array['ministrantes'])) {
      foreach ($info_forms_array['ministrantes'] as $id_ministrante=>$nome) {
        // se o nome for 0 entao nao foi selecionado
        if (!$nome) continue;
        // Captura o codpes do professor
        $usuario_moodle = $DB->get_record('user', ['id' => $id_ministrante]);
        // captura o papel do professor
        $atuacao = Usuario::atuacao_ceu($usuario_moodle->idnumber, $info_forms->codofeatvceu);
        // matricula o professor
        Usuario::matricula_professor($moodle_curso->id, $id_ministrante, $atuacao->codatc);
        // Nome do cargo
        $shortname_adaptado = $atuacao->dscatc;
        // notificacao
        \core\notification::success('Professor auxiliar ' . $nome . ' matriculado como "' . $shortname_adaptado . '".');
        // Notificacoes::notificacao_inscricao($usuario_moodle, $moodle_curso);
      }
    }
    
    // caso seja selecionado um professor sem conta moodle, eh criada a sua conta
    if (isset($info_forms_array['ministrantes_semconta'])) {
      foreach ($info_forms_array['ministrantes_semconta'] as $id_ministrante => $ministrante_semconta) {
        $info_ministrante = Turmas::ministrante_turma($id_ministrante, $info_forms->codofeatvceu);
        if (!isset($info_ministrante->nompes)) {
          // caso o nome nao esteja definido nas informacoes do usuario
          \core\notification::error('Erro ao matricular o professor, por favor contate o suporte. ');
          continue;
        }
        // tratamento para professor sem e-mail no sistema 
        if (!isset($info_ministrante->codema)) {
          // caso o nome nao esteja definido nas informacoes do usuario
          \core\notification::error('Não foi possivel matricular o professor ' . $info_ministrante->nompes . ' como ministrante, por favor contate o suporte.');
          continue;
        }

        //Nome do professor
        $nome = $info_ministrante->nompes; 
        //Para criar a conta do professor 
        $ministrante = Usuario::cadastra_usuario($info_ministrante);
        if (!$ministrante) {
          // Caso ocorra um problema ao cadastrar a conta do professor
          \core\notification::error('Não foi possível matricular o professor ' . $nome . ' como ministante adicional. Por favor contate o suporte.');
        }

        // Captura o codpes do professor
        $usuario_moodle = $DB->get_record('user', ['id' => $ministrante->id]);
        $atuacao = Usuario::atuacao_ceu($usuario_moodle->idnumber, $info_forms->codofeatvceu);
        // matricula o professor
        Usuario::matricula_professor($moodle_curso->id, $ministrante->id, $atuacao->codatc);
        // nome do cargo
        $shortname_adaptado = $atuacao->dscatc;
        // notificacao
        \core\notification::success('Professor auxiliar ' . $nome . ' matriculado como "' . $shortname_adaptado . '".');
        try {
          // Notificacoes::notificacao_inscricao($ministrante, $moodle_curso);
        } catch (Exception $e) {
          // Se ocorrer um erro, ele sera capturado aqui e podemos lidar com ele
          // Por exemplo, podemos exibir uma mensagem de erro ou registrar o erro em um arquivo de log.
          echo "Ocorreu um erro: " . $e->getMessage();
        }
      }
    }

    return $moodle_curso->id;
  }

  /**
   * Cria um objeto de curso.
   * 
   * @param object $info_forms Valores passados atraves do formulario.
   * @param object $info_curso_apolo Informacoes do curso extraidas do Apolo.
   * 
   * @return object Objeto de curso.
   */
  public static function criar_objeto_curso ($info_forms, $info_curso_apolo) {
    global $DB;    
    $curso = new stdClass;
    
    $curso->shortname = $info_forms->shortname;
    $curso->fullname = $info_forms->fullname;
    $curso->idnumber = $info_forms->codofeatvceu;
    $curso->visible = 1;

    // Formato padrao
    $formato_padrao = $DB->get_record('config_plugins', ['name'=>'format']);
    if (isset($formato_padrao)) 
      $curso->format = $formato_padrao->value;
    
    // Quantidade de secoes padrao
    $numsec_padrao = $DB->get_record('config_plugins', ['name'=>'numsections']);
    if (isset($numsec_padrao))
      $curso->numsections = $numsec_padrao->value;

    $curso->summary = $info_forms->summary['text']; 
    $curso->summaryformat = FORMAT_HTML;

    $curso->startdate = $info_forms->startdate;
    $curso->enddate = $info_forms->enddate;
    $curso->timemodified = time();

    // gera ou captura a categoria
    $categoria = self::turma_categoria($info_curso_apolo);

    if ($categoria) $curso->category = $categoria->id;
    else $curso->category = 1;
    
    return $curso;
  }

  /**
   * Define de um curso. Se a categoria nao existir, sera criada.
   * 
   * @param object $info_curso_apolo Informacoes do curso extraidas do Apolo
   * 
   * @return object Objeto de curso.
   */
  public static function turma_categoria ($info_curso_apolo) {
    global $DB;
    $Query = new Query();

    // captura as informaoces da unidade do curso  
    $infos = $Query->informacoes_unidade($info_curso_apolo['codund']);
    
    if (!$infos) return false;

    $info_campus = $infos['campus'];
    $categoria_campus = self::categoria(array(
      'idnumber'    => $info_campus['codcam'],
      'name'        => $info_campus["nomcam"],
      'parent'      => 0,
      'description' => $info_campus["nomcam"],
      'sortorder'   => $info_campus["codcam"]
    ));

    // captura a categoria de faculdade dentro do Moodle
    $info_unidade = $infos['unidade'];
    
    $categoria_faculdade = self::categoria(array(
      'idnumber'    => $info_curso_apolo['codund'],
      'name'        => $info_unidade["sglund"],
      'parent'      => $categoria_campus->id,
      'description' => $info_unidade["nomund"],
      'sortorder'   => $info_unidade["codund"]
    ));

    // agora a categoria do ano
    $ano = date('Y', $info_curso_apolo['startdate']);
    
    $categoria_ano = self::categoria(array(
      'name'        => $ano,
      'parent'      => $categoria_faculdade->id,
      'description' => $ano,
      'sortorder'   => $ano
    ));

    return $categoria_ano;
  }

  /**
   * Retorna uma categoria a partir de informacoes basicas. Se a categoria
   * nao for encontrada, ela sera criada.
   * 
   * @param object $info_categoria Informacoes da categoria.
   * 
   * @return object A categoria encontrada ou criada.
   */
  public static function categoria ($info_categoria) {
    global $DB;

    // verifica se a categoria ja esta na base
    $categoria = $DB->get_record('course_categories', array('name'=>$info_categoria['name'], 'parent'=>$info_categoria['parent']));

    // se estiver vazio, precisa criar a categoria
    if (empty($categoria) or !$categoria) {
      $nova_categoria = new \stdClass();
      if (isset($info_categoria['idnumber']))
        $nova_categoria->idnumber    = $info_categoria['idnumber'];
      $nova_categoria->name        = $info_categoria['name'];
      $nova_categoria->description = $info_categoria['description'];
      $nova_categoria->sortorder   = $info_categoria['sortorder'];
      $nova_categoria->parent      = $info_categoria['parent']; // filha da categoria base
      if(!$categoria = \core_course_category::create($nova_categoria))
        \core\notification::error("Erro ao criar a categoria '{$nova_categoria->name}'!");
      else
        \core\notification::success("Categoria '{$nova_categoria->name}' criada!");
      return self::categoria($info_categoria);
    }

    return $categoria;
  }

  /**
   * Verifica se um nome curto ("shortname") ja esta sendo utilizado por 
   * algum outro curso.
   * 
   * @param string $shortname Nome curto digitado.
   * 
   * @return bool Se esta ou nao sendo utilizado.
   */
  public static function shortname_em_uso ($shortname) {
    global $DB;
    return $DB->record_exists('course', array('shortname'=>$shortname));
  }
}
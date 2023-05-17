<?php

/**
 * Ambientes Moodle
 * 
 * Neste arquivo ficam as questoes relativas a criacao de ambientes
 * Moodle (cursos). Isso eh usado pelos docentes quando ha turmas
 * abertas em seu nome, por exemplo.
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/course/lib.php'); // biblioteca de cursos
require_once(__DIR__ . '/turmas.php');
require_once(__DIR__ . '/apolo.php');
require_once(__DIR__ . '/usuario.php');

class Ambiente {

  /**
   * Para criar um curso usando a api do Moodle, passado um objeto de 
   * curso.
   * 
   * @param object $curso Objeto de curso criado por $this->criar_objeto_curso.
   * 
   * @return bool|object Erro ou curso criado.
   */
  public static function criar_ambiente ($info_forms) {
    // eh preciso capturar outras informacoes do curso, como a unidade
    $info_curso_apolo = Apolo::informacoesTurma($info_forms->codofeatvceu);

    // transforma o enviado em um objeto de curso
    $curso = self::criar_objeto_curso($info_forms, $info_curso_apolo);

    // cria o curso
    $moodle_curso = \create_course($curso);
    
    // se a opcao de visitantes estiver habilitada, precisa adicionar o usuario guest 
    if ($info_forms->guest) {
      Usuario::libera_visitantes($moodle_curso->id);
    }

    // se der certo, eh necessario salvar isso na base
    Turmas::atualizar_id_moodle_turma($info_forms->codofeatvceu, $moodle_curso->id);
    \core\notification::success('Ambiente criado com sucesso!');

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
    $curso = new stdClass;
    
    $curso->shortname = $info_forms->shortname;
    $curso->fullname = $info_forms->fullname;
    $curso->idnumber = $info_forms->codofeatvceu;
    $curso->visible = 1;
    
    $curso->format = 'topics'; //?
    $curso->numsections = ''; //?

    $curso->summary = $info_forms->summary['text']; 
    $curso->summaryfomart = FORMAT_HTML;

    $curso->startdate = $info_forms->startdate;
    $curso->enddate = $info_forms->enddate;
    $curso->timemodified = time();

    // gera ou captura a categoria
    $categoria = self::turma_categoria($info_curso_apolo);
    $curso->category = $categoria->id;
    
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

    // captura as informaoces da unidade do curso
    $infos = Apolo::informacoes_unidade($info_curso_apolo->codund);

    $info_campus = $infos['campus'];
    $categoria_campus = self::categoria(array(
      'name'        => $info_campus["nomcam"],
      'parent'      => 0,
      'description' => $info_campus["nomcam"],
      'sortorder'   => $info_campus["codcam"]
    ));

    // captura a categoria de faculdade dentro do Moodle
    $info_unidade = $infos['unidade'];
    $categoria_faculdade = self::categoria(array(
      'name'        => $info_unidade["sglund"],
      'parent'      => $categoria_campus->id,
      'description' => $info_unidade["nomund"],
      'sortorder'   => $info_unidade["codund"]
    ));

    // agora a categoria do ano
    $ano = date('Y', $info_curso_apolo->startdate);
    
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
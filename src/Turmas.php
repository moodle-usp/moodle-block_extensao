<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Turmas
 * A ideia desse arquivo eh mexer com turmas dentro da base do Moodle. Ele vai 
 * vasculhar na base de dados do Moodle e trazer informacoes relevantes, a 
 * depender da funcao.
 */

// require_once('../../../config.php');

class Turmas {
  
  /**
   * Captura informacoes de turmas informadas a partir de seu codofeatvceu.
   * 
   * @param array $turmas Turmas.
   * 
   * @return array Turmas com informacoes.
   */
  public static function info_turmas ($turmas) {
    global $DB;

    $turmas_infos = array();
    foreach ($turmas as $turma) {
      $busca = $DB->get_record('block_extensao_turma', ['codofeatvceu' => $turma->codofeatvceu]);

      if ($busca->id_moodle) continue;

      $turmas_infos[] = array(
        'codofeatvceu' => $turma->codofeatvceu,
        'nome_curso_apolo' => $busca->nome_curso_apolo
      );
    }
    return $turmas_infos;
  }

  /**
   * Captura as turmas as quais um usuario eh ministrante.
   * 
   * @param string|integer $nusp_docente Numero USP do usuario.
   * 
   * @return array Cursos do usuario.
   */
  public static function docente_turmas ($nusp_docente) {
    global $DB;
    
    // captura as turmas relacionadas ao usuario
    $query = "SELECT id, codofeatvceu FROM {block_extensao_ministrante} WHERE codpes = '$nusp_docente' AND papel_usuario IN (1,2,5)";
    $usuario_turmas = $DB->get_records_sql($query, ['codpes' => $nusp_docente]);
    $cursos_usuario = Turmas::info_turmas($usuario_turmas);
    
    return $cursos_usuario;
  }

  /**
   * Captura as turmas as quais tem uma categoria informada.
   * 
   * @param string|integer $categoria Numero da categoria.
   * 
   * @return array Cursos.
   */
  public static function categoria_turmas ($categoria) {
    global $DB;

    // Captura as turmas com codcam ou codund = $categoria
    $query = "SELECT codofeatvceu FROM {block_extensao_turma} WHERE codcam = $categoria OR codund = $categoria";
    $turmas = $DB->get_records_sql($query);
    if (!empty($turmas)) {
      $turmas_array = Turmas::info_turmas($turmas);
    }
    else 
      $turmas_array = array();
    return $turmas_array;
  }

  /**
   * Captura as informacoes de uma turma na tabela extensao_turma a
   * partir de seu codigo de oferecimento.
   * 
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return object Resultado da busca na base.
   */
  public static function info_turma_id_extensao ($codofeatvceu) {
    global $DB;
    $infos = $DB->get_record('block_extensao_turma', ['codofeatvceu' => $codofeatvceu]);
    return $infos;
  }

  /**
   * Verifica se um usuario esta registrado como docente de uma turma.
   * 
   * @param integer|string $nusp_usuario Numero USP do usuario.
   * @param string         $codofeatvceu Codigo de oferecimento da turma.
   * 
   * @return bool Verdadeiro se o usuario estiver registrado como ministrante,
   *              falso caso contrario.
   */
  public static function usuario_docente_turma ($nusp_usuario, $codofeatvceu) {
    global $DB;
    // agora ve se esta associada ao usuario
    $query = "SELECT * FROM {block_extensao_ministrante} WHERE codofeatvceu = $codofeatvceu AND codpes = '$nusp_usuario'";
    $turma_associada = $DB->get_records_sql($query);
    return !empty($turma_associada);
  }

  /**
   * Verifica se uma turma no extensao_turma ja teve um ambiente criado
   * 
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return integer|null Id da turma caso ja tenha sido criado, NULL caso
   *                      contrario.
   */
  public static function ambiente_criado_turma ($codofeatvceu) {
    global $DB;
    $query = $DB->get_record('block_extensao_turma', ['codofeatvceu' => $codofeatvceu]);
    return $query->id_moodle;
  }

  /**
   * Atualiza o codigo da turma no Moodle na tabela extensao_turma,
   * chamado quando a area da turma eh criada no Moodle.
   * 
   * OBS: Parece que do jeito que foi feito, sem um id primary_key,
   * nao funciona a API do Moodle.
   * 
   * @param string  $codofeatvceu Codigo de oferecimento da atividade.
   * @param integer $id_moodle    Id do curso associado.
   * 
   * @return object Resultado da Query.
   */
  public static function atualizar_id_moodle_turma ($codofeatvceu, $id_moodle) {
    global $DB;
    $query = "UPDATE {block_extensao_turma} SET id_moodle = $id_moodle WHERE codofeatvceu = $codofeatvceu";
    $query = $DB->execute($query);
    return $query;
  }

  /**
   * Captura o codofeatvceu de um curso a partir do id do ambiente
   * criado no Moodle.
   * 
   * @param string|integer $id_moodle Identificador do ambiente no Moodle.
   * 
   * @return object|null Resultado da busca.
   */
  public static function codofeatvceu($id_moodle) {
    global $DB;
    return $DB->get_record('block_extensao_turma', ['id_moodle' => $id_moodle]);
  }

  /**
   * Retorna os 'codpes' (NUSP) dos ministrantes de uma determinada
   * turma a partir de seu codofeatvceu.
   * 
   * @param string|integer $codofeatvceu Codigo de oferecimento da atividade
   * 
   * @return array Resultado da Query
   */
  public static function codpes_ministrantes_turma ($codofeatvceu) {
    global $DB;
    $lista_codpes = $DB->get_records('block_extensao_ministrante', [
      'codofeatvceu' => $codofeatvceu
    ]);
    // gera uma lista cujos indices sao o codpes
    $lista = array();
    foreach ($lista_codpes as $usuario) $lista[$usuario->codpes] = $usuario;
    return $lista;
  }

  /**
   * Captura os cursos de um usuario a partir de seu id, formata o
   * nome e retorna no formato de Array indexado pelo codofeatvceu.
   * 
   * @param string|integer $id_usuario Identificador do usuario.
   * 
   * @return array Array de cursos.
   */
  public static function cursos_formatados_usuario ($id_usuario) {
    // Captura os cursos a partir do id de usuario
    $cursos_usuario = Turmas::docente_turmas($id_usuario);
    $cursos=[];
    
    foreach ($cursos_usuario as $curso) {
      // Captura o id da turma no plugin de extensao (codofeatvceu)
      $codofeatvceu = $curso['codofeatvceu'];
      // Remove as quebras no nome
      $nome_curso = str_replace(array("\r", "\n"), '', $curso['nome_curso_apolo']);
      // salva
      $cursos[$codofeatvceu] = $nome_curso;
    }
    return $cursos;
  }

  /**
   * Captura os cursos de um usuario a partir de uma categoria, formata o
   * nome e retorna no formato de Array indexado pelo codofeatvceu.
   * 
   * @param string|integer $categoria Identificador da categoria.
   * 
   * @return array Array de cursos.
   */
  public static function cursos_formatados_categoria ($categoria) {
    // Captura os cursos a partir do identificador da categoria
    $turmas_categoria = Turmas::categoria_turmas($categoria);
    $cursos=[];
    foreach ($turmas_categoria as $curso) {
      // Captura o id da turma no plugin de extensao (codofeatvceu)
      $codofeatvceu = $curso['codofeatvceu'];
      // Remove as quebras no nome
      $nome_curso = str_replace(array("\r", "\n"), '', $curso['nome_curso_apolo']) . " ($codofeatvceu)";
      // salva
      $cursos[$codofeatvceu] = $nome_curso;
    }
    return $cursos;
  }
}
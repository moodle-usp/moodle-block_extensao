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
require_once(__DIR__ . '/Edicao.php');


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
        'codofeatvceu'     => $turma->codofeatvceu,
        'nome_curso_apolo' => $busca->nome_curso_apolo,
        'codund'           => $busca->codund,
        'startdate'         => $busca->dtainiofeatv
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
    
    // Captura as turmas relacionadas ao usuario
    $query1 = "SELECT id, codofeatvceu FROM {block_extensao_ministrante} WHERE codpes = :nusp_docente AND codatc IN (1,2,5)";
    $usuario_turmas = $DB->get_records_sql($query1, ['nusp_docente' => $nusp_docente]);

    // Captura as turmas onde o usuario a responsavel pela edição
    $cursosEdicao = Edicao::responsavelEdicao($nusp_docente);

    if ($cursosEdicao)
    {
      // para combinar as 2 consultas
      $turmas_ids = array_column($cursosEdicao, 'codofeatvceu');
      
      $query2 = "SELECT id, codofeatvceu FROM {block_extensao_ministrante} WHERE codofeatvceu IN (" . implode(',', array_map('intval', $turmas_ids)) . ")";
      $responsavel_turmas = $DB->get_records_sql($query2);
      
      // resultados
      $cursos_usuario = array_merge($usuario_turmas, $responsavel_turmas);
    }
    else
      $cursos_usuario = $usuario_turmas;
    $cursos_usuario = Turmas::info_turmas($cursos_usuario);
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

    // tratamento de string vs numero
    if (is_numeric($categoria)) $query_categoria = $categoria;
    else $query_categoria = "'$categoria'";
    
    // Captura as turmas com codcam ou codund = $categoria
    $query = "SELECT codofeatvceu FROM {block_extensao_turma} WHERE codcam = $query_categoria OR codund = $query_categoria";
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
   * Captura as informacoes de um ministrante na tabela extensao_ministrante a
   * partir do $codpes e do $codofeatvceu.
   * 
   * @param string $codpes       Numero USP do usuario.
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * @return object Registro na base.
   */
  public static function ministrante_turma (string $codpes, string $codofeatvceu) {
    global $DB;
    // tratamento de string vs numero
    if (is_numeric($codofeatvceu)) $query_codofeatvceu = $codofeatvceu;
    else $query_codofeatvceu = "'$codofeatvceu'";
    // agora ve se esta associada ao usuario
    $query = "SELECT * FROM {block_extensao_ministrante} WHERE codofeatvceu = $query_codofeatvceu AND codpes = '$codpes'";
    $retorno = $DB->get_record_sql($query);
    return $retorno ?: [];
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
    $registro = self::ministrante_turma($nusp_usuario, $codofeatvceu);
    return !empty($registro);
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
    // tratamento de string vs numero
    if (is_numeric($codofeatvceu)) $query_codofeatvceu = $codofeatvceu;
    else $query_codofeatvceu = "'$codofeatvceu'";
    // busca
    $query = "UPDATE {block_extensao_turma} SET id_moodle = $id_moodle WHERE codofeatvceu = $query_codofeatvceu";
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
    return Turmas::turmas_formatar_array($cursos_usuario);
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
    return Turmas::turmas_formatar_array($turmas_categoria);
  }

  public static function turmas_formatar_array (array $turmas_array) {
    $turmas = [];
    foreach ($turmas_array as $turma) {
      // Captura o id da turma no plugin de extensao (codofeatvceu)
      $codofeatvceu = $turma['codofeatvceu'];
      // Remove as quebras no nome
      $nome_turma = str_replace(array("\r", "\n"), '', $turma['nome_curso_apolo']) . " ($codofeatvceu)";
      // salva
      $turmas[$codofeatvceu] = $nome_turma;
    }
    return $turmas;
  }
}
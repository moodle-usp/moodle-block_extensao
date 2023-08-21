<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Query
 * Aqui ficam as queries que fazem a conexao com o Sistema Apolo.
 */

namespace block_extensao\Service;

use stdClass;

require_once('USPDatabase.php');

class Query 
{
  /**
   * Para testar a conexao com o Apolo.
   */
  public static function testar_conexao () {
    return USPDatabase::fetch("SELECT 1");
  }

  /**
   * Captura as turmas abertas.
   * Sao consideradas como turmas abertas somente as turmas com
   * data de encerramento posterior a data de hoje.
   */
  public static function turmasAbertas () {
    $hoje = date("Y-m-d");
    $query = "
      SELECT
        o.codofeatvceu
        ,c.nomcurceu
      FROM " . USPDatabase::OFERECIMENTOATIVIDADECEU . " o
          LEFT JOIN " . USPDatabase::CURSOCEU . " c
            ON c.codcurceu = o.codcurceu
          LEFT JOIN " . USPDatabase::EDICAOCURSOOFECEU . " e
            ON o.codcurceu = e.codcurceu AND o.codedicurceu = e.codedicurceu
      WHERE e.dtainiofeedi >= '$hoje'
      ORDER BY codofeatvceu 
    ";

    return USPDatabase::fetchAll($query);
  }

  /**
   * Captura os ministrantes das turmas informadas.
   * 
   * Os codigos de atuacao (codatc) conforme ATUACAOCEU sao:
   * 1 - Professor USP
   * 2 - Especialista
   * 3 - Monitor
   * 4 - Servidor
   * 5 - Professor HC - FM-USP
   * 6 - Tutor
   * 7 - Docente (S)
   * 8 - Preceptor (S)
   * 9 - Tutor (S)
   * 
   * @param array $codofeatvceu_turmas Lista de codigos de oferecimento
   * das turmas.
   * @return array|null Resultado da busca.
   */
  public static function ministrantesTurmas ($codofeatvceu_turmas) {
    $turmas = implode(', ', $codofeatvceu_turmas);
    $hoje = date("Y-m-d");
    $query = "
      SELECT
        m.codofeatvceu,
        m.codpes,
        m.codatc,
        e.codema
      FROM " . USPDatabase::MINISTRANTECEU . " m
      LEFT JOIN " . USPDatabase::EMAILPESSOA . " e ON m.codpes = e.codpes
      WHERE m.codpes IS NOT NULL
        AND m.codofeatvceu IN ($turmas)
        AND m.dtainimisatv >= '$hoje'
      ORDER BY m.codofeatvceu
    ";
    return USPDatabase::fetchAll($query);
  }

  /**
   * A partir do codofeatvceu, captura as informacoes de uma
   * turma, como a data de inicio e tal.
   * 
   * @param int|string $codofeatvceu Codigo de oferecimento da atividade.
   * @return stdClass Objeto do curso.
   */
  public static function informacoesTurma ($codofeatvceu) {
    $query = "
      SELECT
        codund,
        dtainiofeatv,
        dtafimofeatv
      FROM " . USPDatabase::OFERECIMENTOATIVIDADECEU . "
      WHERE codofeatvceu = $codofeatvceu        
    ";
    $infos_curso = USPDatabase::fetch($query);
    $info_curso = new stdClass;
    $info_curso->codund = $infos_curso['codund'];
    $info_curso->codofeatvceu = $codofeatvceu;
    $info_curso->startdate = strtotime($infos_curso['dtainiofeatv']);
    $info_curso->enddate = strtotime($infos_curso['dtafimofeatv']);
    return $info_curso;
  }
  
  /**
   * Obtem o objetivo de um curso a partir de seu codigo
   * de oferecimento.
   * 
   * @param int|string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return object
   */
  public static function objetivo_extensao($codofeatvceu) {
    $obj = "
      SELECT 
        c.objcur 
      FROM " . USPDatabase::OFERECIMENTOATIVIDADECEU . " o 
      LEFT JOIN " . USPDatabase::CURSOCEU . " c 
        ON c.codcurceu = o.codcurceu 
      WHERE codofeatvceu = $codofeatvceu";
    return USPDatabase::fetch($obj)['objcur'];
  }

  /**
   * Obtem as informacoes de uma unidade a partir de seu codigo.
   * 
   * @param int|string $codund Codigo da unidade.
   * 
   * @return object
   */
  public static function informacoes_unidade ($codund) {
    // captura a unidade
    $info_unidade = USPDatabase::fetch("
      SELECT
        codund,
        sglund,
        nomund,
        codcam
      FROM " . USPDatabase::UNIDADE . "
      WHERE codund = $codund
    ");

    // captura o campus
    $info_campus = USPDatabase::fetch("
      SELECT
        codcam,
        nomcam
      FROM " . USPDatabase::CAMPUS . "
      WHERE codcam = " . $info_unidade['codcam']);

    return array(
      'unidade' => $info_unidade,
      'campus' => $info_campus
    );
  }

  /**
   * Obtem as datas de inicio e final dos cursos.
   * 
   * @param int|string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return object
   */
  public static function datas_curso ($codofeatvceu){
    $query = "
       SELECT 
        dtainiofeatv, 
        dtafimofeatv 
      FROM " . USPDatabase::OFERECIMENTOATIVIDADECEU . " 
      WHERE codofeatvceu = $codofeatvceu
      ORDER BY codofeatvceu";
    $info_datas = USPDatabase::fetch($query);
    $datas = new stdClass();
    $datas->startdate = $info_datas['dtainiofeatv'];
    $datas->enddate = $info_datas['dtafimofeatv'];
    return $datas;
  }
  
  /**
   * Captura informacoes basicas de um usuario a partir de seu 'codpes'
   * 
   * @param string $codpes Codigo de pessoa (NUSP).
   * 
   * @return object
   */
  public static function info_usuario ($codpes) {
    return USPDatabase::fetch("
      SELECT
        p.codpes,
        p.nompes,
        e.codema
      FROM " . USPDatabase::PESSOA . " p
      LEFT JOIN " . USPDatabase::EMAILPESSOA . " e ON p.codpes = e.codpes
      WHERE p.codpes = $codpes
   ");
  }

  /**
   * Captura emails de uma pessoa dado um 'codpes'
   * 
   * @param string $codpes Codigo de pessoa (NUSP).
   * 
   * @return object
   */
  public static function emails ($codpes) {
    return USPDatabase::fetchAll("
      SELECT
        codema
      FROM " . USPDatabase::EMAILPESSOA . "
      WHERE codpes = $codpes
    ");
  }
}
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
      FROM OFERECIMENTOATIVIDADECEU o
          LEFT JOIN CURSOCEU c
            ON c.codcurceu = o.codcurceu
          LEFT JOIN EDICAOCURSOOFECEU e
            ON o.codcurceu = e.codcurceu AND o.codedicurceu = e.codedicurceu
      WHERE e.dtainiofeedi >= '$hoje'
      ORDER BY codofeatvceu 
    ";

    return USPDatabase::fetchAll($query);
  }

  /**
   * Captura os ministrantes das turmas abertas.
   * Sao consideradas como turmas abertas somente as turmas com
   * data de encerramento posterior a data de hoje.
   * 
   * Os codigos de atuacao (coadtc) conforme ATUACAOCEU sao:
   * 1 - Professor USP
   * 2 - Especialista
   * 3 - Monitor
   * 4 - Servidor
   * 5 - Professor HC - FM-USP
   * 6 - Tutor
   * 7 - Docente
   * 8 - Preceptor
   * 9 - Tutor
   */
  public static function ministrantesTurmasAbertas () {
    $hoje = date("Y-m-d");
    $query = "
      SELECT
        m.codofeatvceu
        ,m.codpes
        ,m.codatc
      FROM dbo.MINISTRANTECEU m
      WHERE codpes IS NOT NULL
        AND m.dtainimisatv >= '$hoje'
      ORDER BY codofeatvceu
    ";

    return USPDatabase::fetchAll($query);
  }

  /**
   * A partir do codofeatvceu, captura as informacoes de uma
   * turma, como a data de inicio e tal.
   * 
   * @param int|string $codofeatvceu Codigo de oferecimento da atividade.
   */
  public static function informacoesTurma ($codofeatvceu) {
    $query = "
      SELECT
        codund,
        dtainiofeatv,
        dtafimofeatv
      FROM OFERECIMENTOATIVIDADECEU
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
      FROM OFERECIMENTOATIVIDADECEU o 
      LEFT JOIN CURSOCEU c 
        ON c.codcurceu = o.codcurceu 
      WHERE codofeatvceu = $codofeatvceu";
    return USPDatabase::fetch($obj);
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
      FROM UNIDADE
      WHERE codund = $codund
    ");

    // captura o campus
    $info_campus = USPDatabase::fetch("
      SELECT
        codcam,
        nomcam
      FROM CAMPUS
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
      FROM OFERECIMENTOATIVIDADECEU 
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
        codpes,
        nompes
      FROM PESSOA
      WHERE codpes = $codpes
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
      FROM EMAILPESSOA
      WHERE codpes = $codpes
    ");
  }
}
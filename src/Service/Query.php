<?php

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

  public static function turmasAbertas () {
    /**
     * Captura as turmas abertas.
     * Sao consideradas como turmas abertas somente as turmas com
     * data de encerramento posterior a data de hoje.
     */
    $hoje = date("Y-m-d");
    $query = "
      SELECT
        o.codofeatvceu
        ,c.nomcurceu
        ,o.dtainiofeatv
        ,o.dtafimofeatv
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

  public static function ministrantesTurmasAbertas () {
    /**
     * Captura os ministrantes das turmas abertas.
     * Sao consideradas como turmas abertas somente as turmas com
     * data de encerramento posterior a data de hoje.
     */
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

  public static function informacoesTurma ($codofeatvceu) {
    /**
     * A partir do codofeatvceu, captura as informacoes de uma
     * turma, como a data de inicio e tal.
     * 
     * [ a query sera posta aqui posteriormente ]
     */
    $query = "
      SELECT
        codund
      FROM OFERECIMENTOATIVIDADECEU
      WHERE codofeatvceu = $codofeatvceu        
    ";
    $infos_curso = USPDatabase::fetch($query);
    $info_curso = new stdClass;
    $info_curso->codund = $infos_curso['codund'];
    $info_curso->codofeatvceu = $codofeatvceu;
    $info_curso->startdate = strtotime("now");
    $info_curso->enddate = strtotime("+7 months");
    return $info_curso;
  }
  
  // Obtem o objetivo do curso explicitado 
  public static function objetivo_extensao($codofeatvceu) {
    $obj = "
    SELECT c.objcur FROM OFERECIMENTOATIVIDADECEU o LEFT JOIN CURSOCEU c ON c.codcurceu = o.codcurceu 
    WHERE codofeatvceu = $codofeatvceu";
    return USPDatabase::fetch($obj);
  }

  // Obtem as informacoes de uma unidade
  public static function informacoes_unidade ($codund) {
    return USPDatabase::fetch("
      SELECT
        codund,
        sglund,
        nomund
      FROM UNIDADE
      WHERE codund = $codund
    ");
  }

  // Obtem as datas de inicio e final dos cursos
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
}
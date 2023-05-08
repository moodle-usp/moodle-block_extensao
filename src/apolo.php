<?php

/**
 * Apolo
 * 
 * A ideia desse arquivo eh servir de ponte entre o Service/Query e o restante do plugin.
 * Ele contem algumas funcoes voltadas para a busca de dados dentro da base do Apolo,
 * sem a necessidade de chamar diretamente a classe de queries dentro de uma view, por
 * exemplo.
 */

require_once(__DIR__ . '/Service/Query.php');
use block_extensao\Service\Query;

class Apolo {

  /**
   * A partir do codofeatvceu, captura as informacoes de uma
   * turma, como a data de inicio e tal.
   * 
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return object Resultado da busca na base.
   */
  public static function informacoesTurma ($codofeatvceu) {
    return Query::informacoesTurma($codofeatvceu);
  }

  /**
   * Obtem o objetivo do curso tendo em vista que o banco de
   * dados nao possui a descricao do curso.
   * 
   * @param string $codofeatvceu Codigo de oferecimento da atividade.
   * 
   * @return object Resultado da busca na base.
   */
  public static function objetivo_extensao ($codofeatvceu) {
    return Query::objetivo_extensao($codofeatvceu)['objcur'];
  }

  /**
   * Obtem as informacoes de uma unidade da USP a partir de seu codund.
   * 
   * @param integer $codund Codigo doa unidade do curso.
   * 
   * @return object Objeto com informacoes da unidade.
   */ 
  public static function informacoes_unidade ($codund) {
    return Query::informacoes_unidade($codund);
  }
}
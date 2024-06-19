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

#[\AllowDynamicProperties]
class Query 
{
  /**
   * Construtor da classe Query.
   * Captura as tabelas e as armazena.
   */
  public function __construct() {
    $this->OFERECIMENTOATIVIDADECEU = get_config('block_extensao', 'tabela_oferecimentoatividadeceu');
    $this->ATIVIDADECEU             = get_config('block_extensao', 'tabela_atividadeceu');
    $this->CURSOCEU                 = get_config('block_extensao', 'tabela_cursoceu');
    $this->EDICAOCURSOOFECEU        = get_config('block_extensao', 'tabela_edicaocursoofeceu');
    $this->MINISTRANTECEU           = get_config('block_extensao', 'tabela_ministranteceu');
    $this->ATUACAOCEU               = get_config('block_extensao', 'tabela_atuacaoceu');
    $this->EMAILPESSOA              = get_config('block_extensao', 'tabela_emailpessoa');
    $this->UNIDADE                  = get_config('block_extensao', 'tabela_unidade');
    $this->CAMPUS                   = get_config('block_extensao', 'tabela_campus');
    $this->PESSOA                   = get_config('block_extensao', 'tabela_pessoa');
  }

  /**
   * Para testar a conexao com o Apolo.
   */
  public function testar_conexao () {
    return USPDatabase::fetch("SELECT 1");
  }

  /**
   * Captura as turmas abertas.
   * Sao consideradas como turmas abertas somente as turmas com
   * data de encerramento posterior a data de hoje.
   */

  public function turmasAbertas () {
    
    $periodo = get_config('block_extensao', 'periodo_curso');
    $diaAtual = date("Y-m-d");

    // opcoes para a pesquisa do inicio da busca por curso, coloquei as opcoes de 3, 6, 9 meses 
    // e 1 ano, no entanto, esse valor pode ser alterado caso seja pertinente.
    if (in_array($periodo, ["3", "6", "9"]))
      $periodo_str = "-$periodo months";
    else
      $periodo_str = "-1 year";
    $inicio_curso = date("Y-m-d", strtotime($periodo_str, strtotime($diaAtual)));

    $query = "
      SELECT 
        o.codofeatvceu,
        c.nomcurceu,
        c.codund,
        u.codcam,
        c.objcur,
        a.nomatvceu,
        a.codatvceu,
        o.numseqofeedi,
        e.dtainiofeedi,
        e.dtafimofeedi 
      FROM " . $this->OFERECIMENTOATIVIDADECEU . " o
      LEFT JOIN " . $this->CURSOCEU . " c
        ON c.codcurceu = o.codcurceu 
      LEFT JOIN " . $this->EDICAOCURSOOFECEU . " e
        ON o.codcurceu = e.codcurceu 
        AND o.codedicurceu = e.codedicurceu 
      LEFT JOIN " . $this->ATIVIDADECEU . " a
        ON a.codatvceu = o.codatvceu 
        AND a.codund = o.codund
      LEFT JOIN " . $this->UNIDADE . " u
        ON u.codund = o.codund
      WHERE e.dtainiofeedi >= '$inicio_curso'
      ORDER BY codofeatvceu 
    ";
    return USPDatabase::fetchAll($query);
  }

  /**
   * Captura os ministrantes das turmas informadas.
   * 
   * Os codigos de atuacao (codatc) conforme ATUACAOCEU sao:
   * 1  - Professor USP
   * 2  - Especialista
   * 3  - Monitor
   * 4  - Servidor
   * 5  - Professor HC - FM-USP
   * 6  - Tutor
   * 7  - Docente (S)
   * 8  - Preceptor (S)
   * 9  - Tutor (S)
   * 10 - Coordenador de Estágio (S)
   * 11 - Corresponsável
   * 11 - Responsável
   * 
   * @param array $codofeatvceu_turmas Lista de codigos de oferecimento
   * das turmas.
   * @return array|null Resultado da busca.
   */
  public function ministrantesTurmas ($codofeatvceu_turmas) {
    $turmas = implode(', ', $codofeatvceu_turmas);
    $query = "
      SELECT
        m.codofeatvceu,
        m.codpes,
        m.codatc,
        a.dscatc,
        p.nompes,
        COALESCE(email_preferencial.codema, email_disponivel.codema) AS codema
      FROM 
      " . $this->MINISTRANTECEU . "  m
      LEFT JOIN 
        (SELECT codpes, codema FROM  " . $this->EMAILPESSOA . "  WHERE stamtr = 'S') AS email_preferencial 
        ON m.codpes = email_preferencial.codpes
      LEFT JOIN 
        (SELECT codpes, codema FROM  " . $this->EMAILPESSOA . " ) AS email_disponivel 
        ON m.codpes = email_disponivel.codpes
      LEFT JOIN
        " . $this->ATUACAOCEU . " a
        ON a.codatc = m.codatc
      LEFT join
        " . $this->PESSOA . " p
        ON p.codpes = m.codpes
      WHERE 
        m.codpes IS NOT NULL
        AND m.codofeatvceu IN ($turmas)
      ORDER BY m.codofeatvceu
    ";
    return USPDatabase::fetchAll($query);
  }

  /**
   * Obtem as informacoes de uma unidade a partir de seu codigo.
   * 
   * @param int|string $codund Codigo da unidade.
   * 
   * @return object
   */
  public function informacoes_unidade ($codund) {
    // tratamento de erros
    if (is_numeric($codund)) $query_codund = $codund;
    else $query_codund = "'$codund'";

    // captura a unidade
    $info_unidade = USPDatabase::fetch("
      SELECT
        codund,
        sglund,
        nomund,
        codcam
      FROM " . $this->UNIDADE . "
      WHERE codund = $query_codund
    ");

    // se nao encontrar, retorna falso
    if (!$info_unidade) return false;

    // captura o campus
    $info_campus = USPDatabase::fetch("
      SELECT
        codcam,
        nomcam
      FROM " . $this->CAMPUS . "
      WHERE codcam = " . $info_unidade['codcam']);

    return array(
      'unidade' => $info_unidade,
      'campus' => $info_campus
    );
  }
  
  /**
   * Captura informacoes basicas de um usuario a partir de seu 'codpes'
   * 
   * @param string $codpes Codigo de pessoa (NUSP).
   * 
   * @return object
   */
  public function info_usuario ($codpes) {
    // tratamento de erros
    if (is_numeric($codpes)) $query_codpes = $codpes;
    else $query_codpes = "'$codpes'";
    return USPDatabase::fetch("
      SELECT
        p.codpes,
        p.nompes,
        e.codema
      FROM " . $this->PESSOA . " p
      LEFT JOIN " . $this->EMAILPESSOA . " e ON p.codpes = e.codpes
      WHERE p.codpes = $query_codpes
   ");
  }

  /**
   * Captura emails de uma pessoa dado um 'codpes'
   * 
   * @param string $codpes Codigo de pessoa (NUSP).
   * 
   * @return object
   */
  public function emails ($codpes) {
    // tratamento de erros
    if (is_numeric($codpes)) $query_codpes = $codpes;
    else $query_codpes = "'$codpes'";
    return USPDatabase::fetchAll("
      SELECT
        codema
      FROM " . $this->EMAILPESSOA . "
      WHERE codpes = $query_codpes
    ");
  }

  /**
   * Captura dos cargos cadastrados na base, para exibir as
   * descricoes.
   * 
   * @return array
   */
  public function cargos_atuacao () {
    return USPDatabase::fetchAll("SELECT * FROM " . $this->ATUACAOCEU);
  } 
}
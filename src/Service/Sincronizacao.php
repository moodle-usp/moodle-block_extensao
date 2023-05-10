<?php

/**
 * Aqui eh feita a sincronizacao de fato, constam as funcoes de
 * salvar dados, gerar objetos, buscar na Query, etc.
 * 
 * A classe Sincronizar eh chamada em:
 * - cli/sync.php
 */

require_once('Query.php');
use block_extensao\Service\Query;

class Sincronizar {

  /**
   * Apenas para nao inicializar com o metodo homonimo.
   */
  public function __construct() {}

  /**
   * Tenta conectar na base do Apolo para ver se as
   * credenciais foram informadas e se estao corretas.
   * 
   * @return bool 
   */
  private function conexao_apolo () {
    return Query::testar_conexao();
  }

  /**
   * Sincronizacao dos dados entre Apolo e Moodle
   * 
   * @param bool $apagar Para apagar os dados atuais antes de sincronizar
   */
  public function sincronizar ($parametros) {
    // verifica se as credenciais da base foram passadas
    $this->conexao_apolo();

    cli_writeln(PHP_EOL."/*********************************/");
    cli_writeln("/    SINCRONIZACAO COM O APOLO    /");
    cli_writeln("/*********************************/");

    // se quiser substituir, precisa apagar os dados de agora
    if ($parametros['apagar']) $this->apagar();

    // sincronizando as turmas
    $turmas = $this->sincronizarTurmas();
    // se $turmas for false, eh que a base ja esta sincronizada
    if (!$turmas) return;
  
    if (!$parametros['pular_ministrantes']) {
      // sincronizando os ministrantes
      $this->sincronizarMinistrantes();
    }

    // retorna a pagina de sincronizar
    cli_writeln(PHP_EOL . "Atualizado com sucesso!");

    cli_writeln(PHP_EOL."/*********************************/");
    cli_writeln("/     SINCRONIZACAO CONCLUIDA     /");
    cli_writeln("/*********************************/");
  }

  /**
   * Sincronizacao das turmas
   * 
   * @return array|bool
   */
  private function sincronizarTurmas () {
    cli_writeln(PHP_EOL . '[TURMAS]' . PHP_EOL . '# Capturando turmas...');

    // captura as turmas
    $turmas = Query::turmasAbertas();

    // monta o array que sera adicionado na mdl_extensao_turma
    $infos_turma = $this->filtrarInfosTurmas($turmas);

    // pega as turmas que nao estao na base
    $infos_turma = $this->turmasNaBase($infos_turma);

    // se estiver vazio nao tem por que continuar
    if (empty($infos_turma)) {
      cli_writeln('(X) A base já estava sincronizada!');
      return false;
    }

    try {
      cli_writeln('* Foram encontradas ' . count($infos_turma) . ' turmas!');
      // salva na mdl_extensao_turma
      cli_writeln('# Salvando turmas...');
      $this->salvarTurmasExtensao($infos_turma);
      cli_writeln('* Turmas sincronizadas!');
      return $infos_turma;
    } catch (Exception $e) {
      $this->mensagemErro('ERRO AO SINCRONIZAR AS TURMAS:', $e, true);
    }
  }

  /**
   * Sincronizacao dos ministrantes
   */
  private function sincronizarMinistrantes () {
    cli_writeln(PHP_EOL . '[MINISTRANTES]' . PHP_EOL . '# Capturando ministrantes...');
    // captura os ministrantes
    $ministrantes = Query::ministrantesTurmasAbertas();
    cli_writeln('* Foram encontrados ' . count($ministrantes) . ' ministrantes!');

    // monta o array que sera adicionado na mdl_extensao_ministrante
    cli_writeln('# Criando objetos...');
    $ministrantes = $this->objetoMinistrantes($ministrantes);
    
    // salva na mdl_extensao_ministrante
    try {
      cli_writeln('# Salvando ministrantes...');
      $this->salvarMinistrantesTurmas($ministrantes);
      cli_writeln('* Ministrantes sincronizados!');
      return true;
    } catch (Exception $e) {
      $this->mensagemErro('ERRO AO SINCRONIZAR OS MINISTRANTES:', $e, true);
    }
  }

  /**
   * Filtra as infos das turmas, condensando somente algumas em 
   * outro array
   * 
   * @param array $turmas Lista de turmas
   * 
   * @return array
   */
  private function filtrarInfosTurmas ($turmas) {
    return array_map(function($turma) {
      $obj = new stdClass;
      $obj->codofeatvceu = $turma['codofeatvceu'];
      $obj->nome_curso_apolo = $turma['nomcurceu'];
      return $obj;
    }, $turmas);
  }

  /**
   * Cria objetos para os arrays
   * 
   * @param array $ministrantes Lista de ministrantes
   * 
   * @return array
   */ 
  private function objetoMinistrantes ($ministrantes) {
    return array_map(function($ministrante) {
      $obj = new stdClass;
      $obj->codofeatvceu = $ministrante['codofeatvceu'];
      $obj->codpes = $ministrante['codpes'];
      $obj->papel_usuario = $ministrante['codatc'];
      return $obj;
    }, $ministrantes);
  }

  /**
   * Procura as turmas na base para ver se ja constam
   * O que fazemos no caso de a turma ja constar?
   * Ignorar ou substituir? por enquanto esta sendo 
   * apenas ignorado
   * 
   * @param array $turmas Lista de turmas
   * 
   * @return array
   */
  private function turmasNaBase ($turmas) {
    global $DB;

    $turmas_fora_base = array();

    // percorre as turmas e vai procurando na base
    foreach($turmas as $turma) {
      // procura pela turma na base
      $resultado_busca = $DB->record_exists('block_extensao_turma', array('codofeatvceu' => $turma->codofeatvceu));

      // se existir, vamos apenas remover do $turmas...
      if (!$resultado_busca)
        $turmas_fora_base[] = $turma;
    }
    
    return $turmas_fora_base;
  }

  /**
   * Para salvar as turmas de extensao.
   * 
   * @param array $cursos_turmas Turmas dos cursos.
   */
  private function salvarTurmasExtensao ($cursos_turmas) {
    global $DB;
    $DB->insert_records('block_extensao_turma', $cursos_turmas);
  }    
  
  /**
   * Para salvar as relacoes entre ministrante e turma
   * 
   * @param array $ministrantes Lista de ministrantes
   */
  private function salvarMinistrantesTurmas ($ministrantes) {
    global $DB;
    $DB->insert_records('block_extensao_ministrante', $ministrantes);
  }

  /**
   * Para apgar as informacoes existentes na base do
   * Moodle.
   */
  private function apagar () {
    global $DB;

    $DB->delete_records('block_extensao_turma', array('id_moodle' => NULL));
    $DB->delete_records('block_extensao_ministrante');
}

  /**
   * Para exibir mensagens de erro.
   * @param string $aviso Aviso que precede a mensagem de erro.
   * @param string $erro  Excecao de erro gerada pelo PHP.
   * @param bool   $parar Se quer que a mensagem seja um die() ou um nao.
   */
  private function mensagemErro ($aviso, $erro, $parar) {
    $msg = 'XXXXXXX' . PHP_EOL . $aviso . PHP_EOL . $erro . PHP_EOL . 'XXXXXXX' . PHP_EOL;
    if ($parar)
      die($msg);
    else
      echo $msg;
  }
}
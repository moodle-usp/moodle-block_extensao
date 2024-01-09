<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
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
    return (new Query())->testar_conexao();
  }

  /**
   * Sincronizacao dos dados entre Apolo e Moodle
   * 
   * @param bool $apagar Para apagar os dados atuais antes de sincronizar
   */
  public function sincronizar ($parametros) {
    // Verifica se as credenciais da base foram passadas
    $this->conexao_apolo();

    cli_writeln(PHP_EOL."/*********************************/");
    cli_writeln("/    SINCRONIZACAO COM O APOLO    /");
    cli_writeln("/*********************************/");

    // Sincronizando as turmas
    $turmas = $this->sincronizarTurmas();
    // Se $turmas for false, eh que a base ja esta sincronizada
    if (!$turmas) return;
  
    if (!$parametros['pular_ministrantes']) {
      // Sincronizando os ministrantes
      $this->sincronizarMinistrantes(array_keys($turmas));
    }

    // Retorna a pagina de sincronizar
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

    // Captura as turmas
    $turmas = (new Query())->turmasAbertas();

    // Se der erro na busca, ja para por aqui
    if (!$turmas) die(PHP_EOL);

    // Monta o array que sera adicionado na mdl_extensao_turma
    $infos_turma = $this->objetoTurmas($turmas);

    // Pega as turmas que nao estao na base
    $infos_turma = $this->turmasNaBase($infos_turma);

    // Se estiver vazio nao tem por que continuar
    if (empty($infos_turma)) {
      cli_writeln('(X) A base já estava sincronizada!');
      return false;
    }

    try {
      cli_writeln('* Foram encontradas ' . count($infos_turma) . ' turmas!');
      // Salva na mdl_extensao_turma
      cli_writeln('# Salvando turmas...');
      $this->salvarTurmasExtensao($infos_turma);
      cli_writeln('* Turmas sincronizadas!');
      return $infos_turma;
    } catch (Exception $e) {
      $this->mensagemErro('ERRO AO SINCRONIZAR AS TURMAS:', $e, true);
    }
  }

  /**
   * Sincronizacao dos ministrantes a partir das turmas informadas.
   * Os ministrantes das turmas informadas sao listados e adicionados
   * a tabela block_extensao_ministrante. 
   * 
   * @param array $turmas Lista de codofeatvceus.
   * @return bool Se deu certo ou nao.
   */
  private function sincronizarMinistrantes ($turmas) {
    cli_writeln(PHP_EOL . '[MINISTRANTES]' . PHP_EOL . '# Capturando ministrantes...');
    // Captura os ministrantes
    $ministrantes = (new Query())->ministrantesTurmas($turmas);
    // Indexa o array de ministrantes, para evitar as duplicatas do e-mail
    $ministrantes = $this->removerDuplicatasMinistrantes($ministrantes);

    if (!$ministrantes) {
      cli_writeln('* [PROVAVEL ERRO] Nenhum ministrante encontrado');
    }
    cli_writeln('* Foram encontrados ' . count($ministrantes) . ' ministrantes!');

    // Monta o array que sera adicionado na mdl_extensao_ministrante
    cli_writeln('# Criando objetos...');
    $ministrantes = $this->objetoMinistrantes($ministrantes);
    
    // Salva na mdl_extensao_ministrante
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
   * Gera um indice unico a partir do codpes e do codofeatvceu para
   * eliminar duplicatas (oriundas do 'left join' com a tabela de
   * e-mails).
   * 
   * @param array $ministrantes Lista de ministrantes obtidas do Apolo.
   * @return array Lista filtrada e indexada.
   */
  private function removerDuplicatasMinistrantes ($ministrantes) {
    $lista = array();
    foreach ($ministrantes as $ministrante) {
      $indice = $ministrante['codpes'] . "_" . $ministrante['codofeatvceu'];
      if (!array_key_exists($indice, $lista))
        $lista[$indice] = $ministrante;
    }
    return $lista;
  }

  /**
   * Filtra as infos das turmas, condensando somente algumas em 
   * outro array
   * 
   * @param array $turmas Lista de turmas
   * 
   * @return array
   */
  private function objetoTurmas ($turmas) {
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

    // Percorre as turmas e vai procurando na base
    foreach($turmas as $turma) {
      // Procura pela turma na base
      $resultado_busca = $DB->get_record('block_extensao_turma', array('codofeatvceu' => $turma->codofeatvceu));

      // Se nao existir ou se o campo 'id_moodle' for NULL, adiciona na lista
      if (!$resultado_busca)
        $turmas_fora_base[$turma->codofeatvceu] = $turma;
      else if (is_null($resultado_busca->id_moodle)) {
        // Se o ambiente nao existir, entao apaga os registros para adicionar novamente
        $DB->delete_records('block_extensao_turma', array('codofeatvceu' => $turma->codofeatvceu));
        $DB->delete_records('block_extensao_ministrante', array('codofeatvceu' => $turma->codofeatvceu));
        $turmas_fora_base[$turma->codofeatvceu] = $turma;
      }

      // Data de importacao
      date_default_timezone_set('America/Sao_Paulo');
      $turma->data_importacao = time();
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
   * Para exibir mensagens de erro.
   * @param string $aviso Aviso que precede a mensagem de erro.
   * @param string $erro  Excecao de erro gerada pelo PHP.
   * @param bool   $parar Se quer que a mensagem seja um die() ou um nao.
   */
  private function mensagemErro ($aviso, $erro, $parar) {
    $msg = 'XXXXXXX' . PHP_EOL . $aviso . PHP_EOL . $erro . PHP_EOL . 'XXXXXXX' . PHP_EOL;
    if ($parar) die($msg);
    else echo $msg;
  }

  /**
   * O objetivo dessa funcao eh verificar se o curso criado eh proveniente do sistema apolo,
   * logo eh marcado na tabela block_extensao_turma, na coluna sincronizado_apolo, sim (1)
   * para cursos sincronizados do apolo, e nao (0) para os que advindos de outra forma de criacao.
   * 
   * @param int $curso_id, que sinaliza o curso cujo id sera atualizado na tabela para indicar a sua criacao pelo plugin
   * @return bool a funcao reponde sucesso ou fracasso caso ocorra algum erro  
   */
  public static function sincronizadoApolo($curso_id) {
    global $DB;

    // Defina a consulta SQL para atualizar o campo 'sincronizado_apolo' para 1.
    $sql = "UPDATE {block_extensao_turma} SET sincronizado_apolo = 1 WHERE id_moodle = :curso_id";

    // Parametros para a consulta SQL.
    $params = array('curso_id' => $curso_id);

    try {
      // Execute a consulta SQL usando o metodo execute() do $DB.
      $DB->execute($sql, $params); 
      return true; // Indica que a atualizacao foi bem-sucedida.
    } catch (Exception $e) {
        // Em caso de erro, registre-o.
        error_log("Erro ao atualizar 'sincronizado_apolo' para o curso ID: " . $curso_id . ". Erro: " . $e->getMessage());
        return false; // Indica que houve um erro.
    }
  }
}

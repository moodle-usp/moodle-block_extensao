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
use core\notification;
require_once(__DIR__ . '/../Edicao.php');


class Sincronizar {

  /**
   * Apenas para nao inicializar com o metodo homonimo
   */
  public function __construct() {}

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
   * Tenta conectar na base do Apolo para ver se as credenciais
   * foram informadas e se estao corretas.
   * 
   * @return bool
   */
  private function conexao_apolo () {
    return (new Query())->testar_conexao();
  }

  /**
   * Sincronizacao dos dados entre Apolo e Moodle
   * 
   * @param array $parametros Parametros para a sincronizacao
   * @return bool
   */
  public function sincronizar (array $parametros) {
    cli_writeln(PHP_EOL."/*********************************/");
    cli_writeln("/    SINCRONIZACAO COM O APOLO    /");    
    cli_writeln("/*********************************/");
    
    // Verifica se as credenciais da base foram passadas
    $conexao = $this->conexao_apolo();
    if ($conexao) {
      cli_writeln(PHP_EOL."Conectado com o Apolo.");
    } else {
      cli_writeln(PHP_EOL."ERRO FATAL: Erro na conexão com o Apolo!");
      cli_writeln(PHP_EOL."Por favor, verifique se os dados de autenticação com a base estao corretos."); 
      cli_writeln(PHP_EOL."Abortando sincronizacao");
      return FALSE;
    }

    // Parametros
    $atualizar_ministrantes = !$parametros['pular_ministrantes'];

    // Sincronizando as turmas
    // A variavel $turmas contem todas as turmas que foram adicionadas
    // ou atualizadas na sincronizacao
    $turmas_retorno = $this->sincronizarTurmas($remover_ministrantes=$atualizar_ministrantes);
    
    // Sincronizando os ministrantes
    // A sincronizacao dos ministrantes ocorre a depender da escolha de
    // atualizar ministrantes ja cadastrados na base ou nao. Em ambos
    // os casos, porem, se alguma turma for nova na base, seus ministrantes
    // serao adicionados
    // As chaves do $turmas_retorno sao os codofeatvceu
    // Se nao tiver turmas novas e nao for atualizar, nao precisa rodar
    if (empty($turmas_retorno) && !$atualizar_ministrantes) {
      cli_writeln(PHP_EOL."Nenhuma turma nova! Os ministrantes nao serao atualizados.");
    } else if (!empty($turmas_retorno)) {
      $this->sincronizarMinistrantes(array_keys($turmas_retorno), $atualizar_ministrantes);
    } else {
      cli_writeln(PHP_EOL."Nenhuma turma encontrada!");
    }

    cli_writeln(PHP_EOL."Atualizado com sucesso!");
    cli_writeln(PHP_EOL."/*********************************/");
    cli_writeln("/     SINCRONIZACAO CONCLUIDA     /");
    cli_writeln("/*********************************/");
  }

  /**
   * Sincronizacao das turmas
   * 
   * Captura no Apolo as turmas abertas no periodo pre-configurado. As
   * turmas sao entao transformadas em objetos inseriveis no banco de
   * dados, as turmas antigas que ainda nao tenham ambiente Moodle sao
   * deletadas e todas sao inseridas de uma vez.
   * 
   * @param bool $remover_ministrantes Se deseja deletar os ministrantes
   *                                   junto das turmas sem ambiente.
   * @return array Turmas capturadas e turmas removidas da base
   */
  private function sincronizarTurmas ($remover_ministrantes=TRUE) {
    // Captura das turmas no Apolo
    cli_writeln(PHP_EOL."[TURMAS]".PHP_EOL."# Capturando turmas...");
    $turmas = (new Query())->turmasAbertas();
    cli_writeln('* Foram encontradas ' . count($turmas) . ' turmas!');

    // Se nenhuma turma for encontrada, encerra a sincronizacao
    if (!$turmas) {
      cli_writeln(PHP_EOL."[TURMAS]".PHP_EOL."! Nenhuma turma encontrada!");
      return $turmas; // = [];
    }

    // Monta o array que sera adicionado na mdl_extensao_turma
    cli_writeln('# Montando objetos...');
    $objetos_turmas = $this->objetoTurmas($turmas);

    // Remove as turmas encontradas que ja estao na base e que ainda nao tiveram seu
    // ambiente Moodle criado
    cli_writeln('# Removendo turmas sem ambiente da base local...');
    $turmas_novas = $this->removerTurmasSemAmbiente($objetos_turmas, $remover_ministrantes);

    // Agora adiciona todas as turmas
    cli_writeln("# Salvando turmas...");
    try {
      $this->salvarTurmasExtensao($objetos_turmas);
      cli_writeln("* Turmas sincronizadas!");
      // Se tiver removido os ministrantes, precisa retornar todas as turmas
      if ($remover_ministrantes)
        return $objetos_turmas;
      // Se nao, retorna so as novas
      else
        return $turmas_novas;
    } catch (Exception $e) {
      // Mensagem de erro e die()
      $this->mensagemErro("ERRO AO SINCRONIZAR AS TURMAS:", $e->getMessage(), true);
    }
  }

  /**
   * Objeto de turmas
   * 
   * Cria um array de objetos com as turmas, no padrao exigido pelo
   * Moodle para inserir as informacoes na base.
   * 
   * @param array $turmas Array com as turmas capturadas no Apolo
   * @return array 
   */
  private function objetoTurmas (array $turmas) {
    date_default_timezone_set('America/Sao_Paulo');
    $turmas_objetos = array();
    foreach ($turmas as $turma) {
      $obj = new stdClass;
      $obj->codofeatvceu = $turma['codofeatvceu'];
      $obj->nome_curso_apolo = $turma['nomcurceu'];
      $obj->codund = $turma['codund'];
      $obj->codcam = $turma['codcam'];
      $obj->objcur = $turma['objcur'];
      $obj->dtainiofeatv = is_null($turma['dtainiofeatv']) ? $turma['dtainiofeatv'] : strtotime($turma['dtainiofeatv']);
      $obj->dtafimofeatv = is_null($turma['dtafimofeatv']) ? $turma['dtafimofeatv'] : strtotime($turma['dtafimofeatv']);
      $obj->data_importacao = time(); // Data de importacao
      $turmas_objetos[$obj->codofeatvceu] = $obj;
    }
    return $turmas_objetos;
  }

  /**
   * Remocao de turmas sem ambiente
   * 
   * Dado um array de objetos de turmas, cada turma que constar na tabela de turmas
   * do plugin mas nao tiver um ambiente criado associado (i.e., `id_moodle=NULL`).
   * Somente as turmas no array informado serao verificadas.
   * 
   * O array informado deve ser uma lista de objetos de turmas, e cada objeto deve
   * ter a propriedade `codofeatvceu` (codigo de oferecimento).
   * 
   * @param array $turmas Array com as turmas 
   * @param bool $remover_ministrantes Se deseja remover os ministrantes tambem
   * @return array $turmas_novas Turmas que nao estavam na base
   */
  private function removerTurmasSemAmbiente(array $turmas, bool $remover_ministrantes) {
    global $DB;
    $turmas_novas = array();
    // Percorre a lista de turmas e vai procurando cada uma
    foreach ($turmas as $turma) {
      // Procura pela turma na base
      $query_where = array('codofeatvceu'=>$turma->codofeatvceu);
      $resultado_busca = $DB->get_record('block_extensao_turma', $query_where);

      // Se nao estiver na base, segue 
      if (!$resultado_busca) {
        $turmas_novas[$turma->codofeatvceu] = $turma;
        continue;
      } 
      
      // Se o campo `id_moodle` for NULL, remove
      else if (is_null($resultado_busca->id_moodle)) {
        // Apaga os registros
        $DB->delete_records('block_extensao_turma', $query_where);
        // Se quiser apagar os ministrantes tambem
        if ($remover_ministrantes) {
          $DB->delete_records('block_extensao_ministrante', $query_where);
        }
      }
    }
    return $turmas_novas;
  }

  /**
   * Para salvar as turmas no Moodle
   * 
   * @param array $turmas Lista de turmas
   * @return null
   */
  private function salvarTurmasExtensao (array $turmas) {
    global $DB;
    $DB->insert_records('block_extensao_turma', $turmas);
  }

  /**
   * Sincronizacao dos ministrantes
   * 
   * A partir das turmas capturadas na sincronizacao de turmas, captura os ministrantes
   * respectivos de cada turma, adapta ao formato exigido pelo Moodle e insere na base
   * de dados local.
   * 
   * Se `$atualizar_ministrantes=TRUE`, os ministrantes que ja estao na base e cujas turmas
   * associadas foram capturadas serao removidos e readicionados, se nao apenas os
   * ministrantes de turmas novas serao adicionados. Essa possibilidade foi adicionada
   * tendo em vista que eventualmente professores novos sao cadastrados em turmas ou mesmo
   * removidos no Apolo. A sincronizacao com atualizacao garantira que o sistema esta
   * sincronizado com o Apolo
   * 
   * @param array $turmas Lista de turmas capturadas no Apolo (objetos)
   * @param bool  $atualizar_ministrantes Se deseja atualizar os ministrantes ja existentes.
   * @return bool
   */
  private function sincronizarMinistrantes (array $turmas, bool $atualizar_ministrantes) {
    // Captura dos ministrantes no Apolo
    cli_writeln(PHP_EOL."[MINISTRANTES]".PHP_EOL."# Capturando ministrantes...");
    $ministrantes = (new Query())->ministrantesTurmas($turmas);
    // Indexa o array de ministrantes, para evitar as duplicatadas do e-mail
    $ministrantes = $this->removerMinistrantesDuplicados($ministrantes);

    if (!$ministrantes) {
      cli_writeln("* [PROVAVEL ERRO] Nenhum ministrante encontrado!");
    }
    cli_writeln('* Foram encontrados ' . count($ministrantes) . ' ministrantes!');

    // Monta o array que sera adicionado na block_extensao_ministrante
    cli_writeln('# Montando objetos...');
    $ministrantes = $this->objetoMinistrantes($ministrantes);
    
    // Salva os ministrantes
    cli_writeln("# Salvando ministrantes...");
    try {
        $this->salvarMinistrantes($ministrantes);
        cli_writeln("* Ministrantes sincronizados!");

        // Atribui permissoes de responsavel pela edicao ministrante responsaveis pela edicao
        cli_writeln("# Atribuindo permissões de edição...");
        foreach ($turmas as $turma_codofeatvceu) {
            Edicao::atribuiEdicao($turma_codofeatvceu);
            // } else {
            //     cli_writeln('Erro: Turma sem codofeatvceu.');
            //     var_dump($turma);
            // }
        }
        return TRUE;

    } catch (Exception $e) {
        // Mensagem de erro e die()
        $this->mensagemErro("ERRO AO SINCRONIZAR OS MINISTRANTES: ", $e->getMessage(), true);
        return FALSE;
    }
       
    // Salvando dados dos ministrantes
    cli_writeln("# Salvando ministrantes...");
    try {
      $this->salvarMinistrantes($ministrantes);
      cli_writeln("* Ministrantes sincronizados!");
      return TRUE;
    } catch (Exception $e) {
      // Mensagem de erro e die()
      $this->mensagemErro("ERRO AO SINCRONIZAR OS MINISTRANTES: ", $e->getMessage(), true);
    }
  }

  /**
   * Gera um indice unico a partir do codpes e do codofeatvceu para  eliminar duplicatas
   * (oriundas do `left join` com a tabela de e-mails).
   * 
   * @param array $ministrantes Lista de ministrantes obtidas do Apolo
   * @return array Lista filtrada e indexada
   */
  private function removerMinistrantesDuplicados (array $ministrantes) {
    $lista = array();
    foreach ($ministrantes as $ministrante) {
      $indice = $ministrante['codpes'] . "_" . $ministrante['codofeatvceu'];
      if (!array_key_exists($indice, $lista))
        $lista[$indice] = $ministrante;
    }
    return $lista;
  }

  /**
   * Objeto de ministrantes
   * 
   * Cria um array de objetos com os ministrantes, no padrao exigido pelo
   * Moodle para inserir as informacoes na base.
   * 
   * @param array $ministrantes Array com os ministrantes capturadas no Apolo
   * @return array 
   */
  private function objetoMinistrantes (array $ministrantes) {
    return array_map(function($ministrante) {
      $obj = new stdClass;
      $obj->codofeatvceu = $ministrante['codofeatvceu'];
      $obj->codpes = $ministrante['codpes'];
      $obj->codatc = $ministrante['codatc'];
      $obj->nompes = $ministrante['nompes'];
      $obj->dscatc = $ministrante['dscatc'];
      $obj->codema = $ministrante['codema'];
      return $obj;
    }, $ministrantes);
  }

  /**
   * Para salvar as relacoes entre ministrante e turma
   * 
   * @param array $ministrantes Lista de ministrantes
   * @return null
   */
  private function salvarMinistrantes (array $ministrantes) {
    global $DB;
    $DB->insert_records('block_extensao_ministrante', $ministrantes);
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
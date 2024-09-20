<?php
/**
 * Manipulacao de informacoes relacionadas as Atividades de Edicao dos Cursos de Extensao
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/Service/Query.php');
use block_extensao\Service\Query;

class Edicao {

    /**
     * Busca a partir do codofeatvceu da turma as informacoes sobre  
     * o responsavel pela edicao de algum curso. Para poder assim atribuir
     * ao responsavel o poder de criar qualquer curso da lista. 
     *
     * @param int $codofeatvceu - O identificador do usuario.
     * @return array|bool - Retorna os detalhes do docente responsavel ou false se nao houver.
     */
        public static function cursosResponsavel($codofeatvceu) {
    
            global $DB;
            $Query = new Query();
            
            // Busca o responsavel pela edicao para o curso especifico
            $cursos = $Query->responsavelEdicao($codofeatvceu);
            
            // Verifique explicitamente se ha cursos retornados e se é um array
            if (!is_array($cursos) || empty($cursos)) {
                return false;  // Retorna falso se não houver resultados
            }
            
            // Cria array para armazenar os cursos de responsabilidade do docente
            $cursosResponsavel = [];
            foreach ($cursos as $curso) {
                // Verifica se os dados necessarios estao presentes
                if (isset($curso['codofeatvceu'], $curso['codpes'])) {
                    $cursosResponsavel[] = [
                        'codofeatvceu'  => $curso['codofeatvceu'],
                        'codpes'     => $curso['codpes']
                    ];
                }
            }
        
            // Verifica se o array resultante esta vazio apos o loop
            if (empty($cursosResponsavel)) {
                return false;
            }
            
            // Retorna os detalhes dos cursos cujo docente eh responsavel pela edicao
            return $cursosResponsavel;
        }
        
    /**
     * O objetivo da funcao eh consultar se o usuario eh responsavel pela edicao
     * de algum curso a partir do seu codofeatvceu.
     * 
     * @param int $codofeatvceu e $codpes - O identificador da turma e do usuario.
     * @return true para casos em que o docente eh responsavel, e falso para os que nao eh
     * reponsavel.
     */
    public static function usuario_responsavel_edicao($codpes, $codofeatvceu) {
        global $DB;      
        // Turmas que o usuario eh responsavel pela edição
        $query = "SELECT * FROM {block_extensao_ministrante} WHERE codpes = ? AND codofeatvceu = ?";
        $edicaoCurso = $DB->get_record_sql($query, array($codpes, $codofeatvceu));

        // Verifica se o curso pesquisado esta na lista de cursos pelos quais o docente eh responsavel
        if ($edicaoCurso) {
            return true;
        }
        
        return false;
}
    /**
     * O objetivo desta funcao eh encontrar a partir do codpes do usuario os cursos
     * pelos quais ele eh responsavel pela edicao.
     * @param int $codpes - O identificador do usuario.
     * @return array Lista de cursos pelos quais o usuario eh responsavel.
     */

    public static function responsavelEdicao ($codpes) {
    global $DB;      
    
    // Turmas que o usuario eh responsavel pela edicao
    $query = "SELECT * FROM {block_extensao_ministrante} WHERE codpes = ? AND responsavel = 1";
    $cursos = $DB->get_records_sql($query, array($codpes));

    // Retorna os detalhes dos cursos cujo docente eh responsavel pela edicao
    $cursosResponsavel = [];
    foreach ($cursos as $curso) {
        $cursosResponsavel[] = [
            'codofeatvceu' => $curso->codofeatvceu,
            'codpes'       => $curso->codpes
        ];
    }

    return $cursosResponsavel;
}
    /**
     * Da a um individuo a partir do codofeatvceu a resonsabilidade 
     * de um curso.
     *
     * @param int $codofeatvceu da turma.
     * @return void
     */

    public static function atribuiEdicao($codofeatvceu) {
        global $DB;
        $Query = new Query();
        
        // Obtem a lista de turmas que o usuario pode editar
        $turmas = self::cursosResponsavel($codofeatvceu);
        
        foreach ($turmas as $turma) {

            // Verificar se o registro ja existe com esse codofeatvceu e codpes
            $registro = $DB->get_record('block_extensao_ministrante', ['codpes' => $turma['codpes'], 'codofeatvceu' => $codofeatvceu]);
        
            // Se nao houver registro, procurar outro registro para o mesmo codpes
            if (!$registro) {
                $registro_existente = $DB->get_record('block_extensao_ministrante', ['codpes' => $turma['codpes']]);
        
                if ($registro_existente) {
                    try {
                        // Copiar os dados do registro existente
                        $novos_dados = [
                            'codpes'       => $turma['codpes'],
                            'codofeatvceu' => $codofeatvceu,
                            'codatc'       => $registro_existente->codatc,
                            'dscatc'       => $registro_existente->dscatc,
                            'nompes'       => $registro_existente->nompes,
                            'codema'       => $registro_existente->codema,
                            'responsavel'  => 1
                        ];
                        
                        // Inserir o novo registro na tabela
                        $DB->insert_record('block_extensao_ministrante', $novos_dados);
                    } catch (Exception $e) {
                        cli_writeln("Erro ao adicionar o usuario {$turma['codpes']}: " . $e->getTraceAsString());
                    }
                } else {
                    // Tenta buscar informacoes adicionais do usuario usando a funcao info_usuario
                    $ministrante = $Query->informacoesUsuario($turma['codpes']);
                    try {                    
                        if ($ministrante) {
                            // Adicionar o novo registro diretamente se encontrar informacoes do usuario
                            $DB->insert_record('block_extensao_ministrante', [
                                'codpes'       => $turma['codpes'],
                                'codofeatvceu' => $codofeatvceu,
                                'nompes'       => $ministrante['nompes'],
                                'codatc'       => 0,
                                'dscatc'       => 'Responsável',
                                'codema'       => $ministrante['codema'],
                                'responsavel'  => 1
                            ]);
                        } else {
                            cli_writeln("Usuario {$turma['codpes']} está pendente, não foi possível obter informações.");
                        }
                    } catch (Exception $e) {
                        cli_writeln("Erro ao obter informações do usuario {$turma['codpes']}: {$e->getMessage()}");
                    }
                }
            } else {
                try {
                    // Se o registro ja existe, apenas atualiza o campo `responsavel`
                    $sql = "UPDATE {block_extensao_ministrante} 
                            SET responsavel = 1 
                            WHERE codofeatvceu = ? AND codpes = ?";
                    $DB->execute($sql, [$codofeatvceu, $turma['codpes']]);
                } catch (Exception $e) {
                }
            }
        }
    }
       
    }        

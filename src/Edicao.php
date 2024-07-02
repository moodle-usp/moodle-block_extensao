<?php
/**
 * Manipulacao de informacoes relacionadas as Atividades de Edicao dos Cursos de Extensao
 */
require_once(__DIR__ . '/Service/Query.php');
use block_extensao\Service\Query;

class Edicao {

    /**
     * Busca a partir do id do usuario a informaco se ele e 
     * responsavel pela edicao de algum curso. Caso ele seja sera
     * atribuido o poder de criar qualquer curso da lista. 
     *
     * @param int $codofeatvceu - O identificador do usuario.
     * @return array|bool - Retorna os detalhes do docente responsavel ou false se nao houver.
     */
    public static function cursosResponsavel($codpes) {
    
        // Converte $codpes para inteiro
        $codpes = intval($codpes);
    
        global $DB;
        $Query = new Query();
        
        // Busca o responsavel pela edicao para o curso especifico
        $cursos = $Query->responsavelEdicao($codpes);
        
        // Caso nao haja responsavel, retorna false
        if (!$cursos) {
            return false;
        }
        
        // Retorna os detalhes dos cursos cujo docente eh responsavel pela edicao
        $cursosResponsavel = [];
        foreach ($cursos as $curso) {
            $cursosResponsavel[] = [
                'codofeatvceu'  => $curso['codofeatvceu'],
                'nomatvceu'     => $curso['nomatvceu'],
                'codedicurceu'  => $curso['codedicurceu'],
                //'codcurceu'     => $curso['codcurceu'],
                //'dtainiofeatv'  => $curso['dtainiofeatv'],
                //'numseqofeedi'  => $curso['numseqofeedi'],
                //'dtafimofeatv'  => $curso['dtafimofeatv'],
                //'codpes'        => $curso['codpes'],
                //'codatvceu'     => $curso['codatvceu'],
                //'codedicurceu'  => $curso['codedicurceu'],
                //'codund'        => $curso['codund'],
               // 'nomcurceu'     => $curso['nomcurceu'],
               // 'codcam'        => $curso['codcam'],
               // 'objcur'        => $curso['objcur']
            ];
        }
        
        return $cursosResponsavel;
    }
    public static function usuario_responsavel_edicao($codpes, $codofeatvceu) {
        global $DB;
        
        // Turmas que o usuário é responsável pela edição
        $edicaoCursos = self::cursosResponsavel($codpes);
        
        // Verifica se o curso pesquisado esta na lista de cursos pelos quais o docente eh responsavel
        foreach ($edicaoCursos as $curso) {
            if ($curso['codofeatvceu'] == $codofeatvceu) {
                return true;
            }
        }
       return false;
    }
    
    

}

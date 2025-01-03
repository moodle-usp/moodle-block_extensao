<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * Este eh o bloco. Se verifica se o usuario possui um numero USP em seu 
 * cadastro no Moodle (idnumber), e se possuir verifica se tem algum
 * curso associado na base sincronizada com o Apolo, apresentando botoes
 * de criacao de ambiente ao usuario para os cursos para o qual este for
 * ministrante.
 */

require_once(__DIR__ . '/../../config.php');
require_once('src/Turmas.php');
require_once('src/Categorias.php');
require_once('utils/forms.php');

class block_extensao extends block_base {
    public function init() {
        $this->title = 'USP Extensão';
    }

    public function get_content() {
        global $USER, $OUTPUT, $CFG;
        
        // Para garantir que o formulario nao se duplique
        if ($this->content != null) return $this->content;
        else $this->content =  new stdClass;

        // caso tenha numero USP
        if (isset($USER->username) and !empty($USER->username)) {
            // precisamos capturar na base Moodle os cursos nos quais o usuario eh docente e
            // cujo ambiente ainda nao foi criado
            $cursos = Turmas::cursos_formatados_usuario($USER->username);
            
            // Precisamos tambem saber se o usuario eh gerente de alguma categoria
            $categorias = Categorias::usuario_gerente_categoria($USER->id);

            if (!empty($categorias)) {
                foreach ($categorias as $categoria) {
                    if (!is_null($categoria->idnumber)) {
                        $cursos_categoria = Turmas::cursos_formatados_categoria($categoria->idnumber);
                        $cursos = $cursos + $cursos_categoria;
                    }
                }
            }
        } else {
            $cursos = [];
            $categorias = [];
        }
        // TRUE se tiver 5 ou menos cursos
        $formulario_em_lista = (count($cursos) <= 5);

        // informacoes que serao enviadas ao template
        $infos = array(
            'sem_cursos'          => empty($cursos),
            'formulario_em_lista' => $formulario_em_lista
        );

        // formulario dinamico
        if ($formulario_em_lista) {
            // se for em lista, precisa criar um formulario para cada turma
            $cursos_docente = array();
            foreach ($cursos as $codofeatvceu => $nome_curso) {
                // cria um formulario
                $formurl = new moodle_url('/blocks/extensao/pages/criar_ambiente.php');
                $form = new redirecionamento_criacao_ambiente_lista($formurl, array('codofeatvceu' => $codofeatvceu));    
                $cursos_docente[] = array(
                    'nome_curso_apolo' => $nome_curso,
                    'formulario_curso' => $form->render()
                );
            }
            $infos['cursos_docente'] = $cursos_docente;
        }
        // Formulario como select
        else {
            $formurl = new moodle_url('/blocks/extensao/pages/criar_ambiente.php');
            $formulario = new redirecionamento_criacao_ambiente_select($formurl, array('cursos'=>$cursos));
            $infos['formulario'] = $formulario->render();
        }

        // template
        $this->content->text = $OUTPUT->render_from_template('block_extensao/extensao_block', $infos);
        return $this->content;
    }

    function has_config(){
        return true;
    }    

}

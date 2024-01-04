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

require_once('src/Service/Query.php');
require_once('src/Turmas.php');
require_once('vendor/autoload.php');
require_once('utils/forms.php');

class block_extensao extends block_base {
    public function init() {
        $this->title = 'USP Extensão';
    }

    public function get_content() {
        global $USER, $OUTPUT;
        
        // Para garantir que o formulario nao se duplique
        if ($this->content != null) return $this->content;
        else $this->content =  new stdClass;
        
        // caso tenha numero USP
        if (isset($USER->idnumber) and !empty($USER->idnumber)) {
            // precisamos capturar na base Moodle os cursos nos quais o usuario eh docente e
            // cujo ambiente ainda nao foi criado
            $cursos = Turmas::cursos_formatados($USER->idnumber);
        } else
            $cursos = [];

        // formulario
        $formulario = (new redirecionamento_criacao_ambiente('/blocks/extensao/pages/criar_ambiente.php', array('cursos'=>$cursos)))->render();

        // array da template
        $info = array(
            'sem_cursos' => empty($cursos),
            'formulario' => $formulario
        );
        // template
        $this->content->text = $OUTPUT->render_from_template('block_extensao/extensao_block', $info);

        return $this->content;
    }

    function has_config(){
        return true;
    }    

}
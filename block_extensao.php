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
        $this->content =  new stdClass;
        
        // caso tenha numero USP
        if (isset($USER->idnumber) and !empty($USER->idnumber)) {
            // precisamos capturar na base Moodle os cursos nos quais o usuario eh docente e
            // cujo ambiente ainda nao foi criado
            $cursos_usuario = Turmas::docente_turmas($USER->idnumber);
    
            // para cada curso, cria um formulario
            for ($i = 0; $i < count($cursos_usuario); $i++) {
                // captura o id da turma no plugin de extensao
                $codofeatvceu = $cursos_usuario[$i]['codofeatvceu'];
    
                // cria um formulario
                $form = new redirecionamento_criacao_ambiente('/blocks/extensao/pages/criar_ambiente.php', array('codofeatvceu' => $codofeatvceu));
    
                $cursos_usuario[$i]['formulario'] = $form->render();
            }
        } else
            $cursos_usuario = []; // para nao dar erro de variavel indefinida

        // array da template
        $info = array(
            'sem_cursos' => empty($cursos_usuario),
            'cursos_docente' => $cursos_usuario
        );
        // template
        $this->content->text = $OUTPUT->render_from_template('block_extensao/extensao_block', $info);
        
        return $this->content;
    }

    function has_config(){
        return true;
    }    

}
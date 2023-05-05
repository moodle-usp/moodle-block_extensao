<?php
/**
 * Aqui eh necessario capturar as informacoes que vieram do forms atraves
 * do campo hidden 'id_turma_extensao' e mostrar ao usuario/docente as 
 * informacoes basicas do ambiente que esta criando.
 */

// requerimentos
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../utils/forms.php');
require_once(__DIR__ . '/../src/ambiente.php');
require_once(__DIR__ . '/../src/apolo.php');
require_once(__DIR__ . '/../src/turmas.php');

global $USER, $PAGE, $OUTPUT;

$PAGE->set_pagelayout('admin');
$PAGE->set_url("/block/extensao/criar_ambiente");
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'block_extensao'));
require_login();


// captura os dados vindos do formulario
$forms = new criar_ambiente_moodle();
$info_forms = $forms->get_data();

// agora eh preciso capturar outras informacoes do curso, como datas de
// inicio e termino, etc, que nao vao estar disponiveis no forms de docente
$info_curso_apolo = Apolo::informacoesTurma($info_forms->codofeatvceu);

// transforma o enviado em um objeto de curso
$curso = Ambiente::criar_objeto_curso($info_forms, $info_curso_apolo);

// tenta criar o curso
$curso_criado = Ambiente::criar_ambiente($info_forms->codofeatvceu, $curso);

// exibe informacoes caso de errado, exibe o curso com uma mensagem de sucesso caso de certo
if (!$curso_criado) {
  \core\notification::error('Um curso com o mesmo nome curto já consta na base Moodle!');
  $_SESSION['codofeatvceu'] = $info_forms->codofeatvceu; // salva para poder usar novamente
  redirect(new moodle_url('criar_ambiente.php'));
} else {
  // se der certo, tambem eh necessario salvar isso na base
  Turmas::atualizar_id_moodle_turma($info_forms->codofeatvceu, $curso_criado->id);
  \core\notification::success('Ambiente criado com sucesso!');
  redirect(new moodle_url($CFG->wwwroot) . "/course/view.php?id={$curso_criado->id}");
}
<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * Aqui eh necessario capturar as informacoes que vieram do forms atraves
 * do autocomplete e mostrar ao usuario/docente as informacoes basicas do 
 * ambiente que esta criando.
 * 
 * Esta pagina processa tanto o formulario da pagina inicial quanto o
 * formulario para a criacao do ambiente de fato.
 */

require_once(__DIR__ . '/../../../config.php');
global $USER, $PAGE, $OUTPUT;

$PAGE->set_pagelayout('admin');
$PAGE->set_url("/block/extensao/criar_ambiente");
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'block_extensao'));
require_login();

require_once(__DIR__ . '/../utils/forms.php');
require_once(__DIR__ . '/../src/Turmas.php');
require_once(__DIR__ . '/../src/Service/Query.php');
require_once(__DIR__ . '/../src/Ambiente.php');
use block_extensao\Service\Query;
$Query = new Query();

/**
 * Tratamento do formulario de criacao de curso
 * 
 * Este eh o formulario para visualizacao e alteracao das informacoes para a
 * criacao de um curso que foi selecionado na pagina inicial do bloco.
 * 
 * Este formulario so eh tratado (gerado e processado) se a chave "codofeatvceu"
 * estiver definida na sessao. Ela eh definida na proxima sessao da pagina,
 * quando o usuario vem pelo formulario do bloco inicial.
 */

if (isset($_SESSION['codofeatvceu'])) {
  // captura os outros ministrantes a partir do codofeatvceu
  $ministrantes = Usuario::ministrantes_turma($_SESSION['codofeatvceu'], $USER->username);
  // cria o formulario para capturar as informacoes
  $forms = new criar_ambiente_moodle('', array('ministrantes' => $ministrantes));
  $info_forms = $forms->get_data();  
  
  // se tiver algo, entao sao dados validados e pode criar o curso
  if ($info_forms) {
    $novo_curso_id = Ambiente::criar_ambiente($info_forms, $ministrantes);
    unset($_SESSION['codofeatvceu']);
    redirect(new moodle_url($CFG->wwwroot) . "/course/view.php?id={$novo_curso_id}");
  }
}


/**
 * Tratamento do formulario do bloco inicial
 * 
 * O formulario com o select buscavel na pagina inicial eh tratado aqui. Eh
 * capturado o curso selecionado e seu codigo eh salvo na sessao, para que a secao
 * acima possa gerar o formulario de criacao de ambiente.
 */

// Eh preciso capturar na base do Moodle os cursos nos quais o usuario eh docente e 
// cujo ambiente ianda nao foi criado para poder gerar o forms.
$cursos = Turmas::cursos_formatados($USER->username);
// Gera o formulario para capturar o codfeatvceu
$forms = new redirecionamento_criacao_ambiente('', array('cursos'=>$cursos));
$info_forms = $forms->get_data();
if (!empty($info_forms)) {
  // Se o select nao estiver definido, deu algum problema e volta para o inicio
  if (!isset($info_forms->select_ambiente)) {
    \core\notification::error('Nenhuma turma selecionada');
    redirect($_SERVER['HTTP_REFERER']);
  }
  $codofeatvceu = $info_forms->select_ambiente;
  
  // Se for vazio, volta para a pagina
  if ($codofeatvceu == 0) redirect($_SERVER['HTTP_REFERER']);
  
  // Caso contrario, salva
  $_SESSION['codofeatvceu'] = $codofeatvceu;
}
// Se estiver vazio, tenta pegar via sessao
else $codofeatvceu = $_SESSION['codofeatvceu'];

// Verifica se a turma enviada eh do usuario logado
if (!Turmas::usuario_docente_turma($USER->username, $codofeatvceu) ) {
  \core\notification::error('A turma solicitada não está na sua lista de turmas!');
  redirect($_SERVER['HTTP_REFERER']);
}


/**
 * Visualizacao do formulario
 * 
 * Aqui sao capturadas informacoes adicionais para a criacao do formulario
 * de criacao de ambiente, exibido no final da pagina.
 */

// Aqui precisamos capturar as informacoes basicas do curso
// Foi adicionado o inicio e fim do curso 
$informacoes_turma = Turmas::info_turma_id_extensao($codofeatvceu);
$informacoes_turma->objetivo = $Query->objetivo_extensao($codofeatvceu);
$data_curso = $Query->datas_curso($codofeatvceu);
$informacoes_turma->inicio = $data_curso->startdate;
$informacoes_turma->fim = $data_curso->enddate;
// Lista de ministrantes
$ministrantes = Usuario::ministrantes_turma($codofeatvceu, $USER->username);

// Cria o formulario
$formulario = new criar_ambiente_moodle('', array(
  'codofeatvceu' => $codofeatvceu,
  'shortname' => $codofeatvceu,
  'fullname' => $informacoes_turma->nome_curso_apolo,
  'summary' => $informacoes_turma->objetivo,
  'startdate' => $informacoes_turma->inicio,
  'enddate' => $informacoes_turma->fim,
  'ministrantes' => $ministrantes
));

// Exibicao da pagina
// cabecalho
print $OUTPUT->header();
// template
print $OUTPUT->render_from_template('block_extensao/criar_ambiente', array(
  'formulario' => $formulario->render()
));
// rodape
print $OUTPUT->footer();
<?php 

// Neste código são criadas as inscrições dos estudantes de acordo com o seu curso, o objetivo é que ao criar o curso sejam importadas automaticamente 
// as informações dos alunos, de modo que, o aluno recebe um e-mail com o convite para acessar a plataforma do curso caso já possua cadastro, se nao
// ele recebe um e-mail convocando a sua autoinscricao. 

//require_once(dirname(__FILE__) . '/../../../config.php');
global $USER, $PAGE, $OUTPUT;

$PAGE->set_pagelayout('admin');
$PAGE->set_url("/block/extensao/cadastar_alunos");
$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'block_extensao'));
require_login();

// requerimentos
require_once('../utils/forms.php');
require_once('../src/turmas.php');
require_once('../src/Service/Query.php');
require_once('../../../config.php');
require_once("$CFG->dirroot/user/lib.php");
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/enrollib.php');
require_once($CFG->dirroot.'/course/lib.php');

use block_extensao\Service\Query;

class Cadastrar_estudantes {

    function __construct() {
        print "In BaseClass constructor\n";
    }



    // funcao que obtem os alunos na base de dados do moodle
    function obter_alunos(){
        global $DB;
        $alunos = $DB->get_records('extensao_aluno', array('id_moodle'=>NULL));
        return $alunos;
    }

    function obtem_usuario($field, $value) {
        global $DB;

        $params = array($field => $value);
        $users = $DB->get_records('user', $params);
        return $users;
    }

    // Funcao para cadastrar um aluno no Moodle
    function cadastra_usuario($alunos) {
        foreach($alunos as $estudante) {
            $newuser = new stdClass();
            $newuser->username = $estudante->codpes;
            $newuser->firstname = $estudante->nome;
            $newuser->email = $estudante->email;
            $newuser->id = $estudante->codpes;
            $base = array('field' => 'username', 'value' => $estudante->codpes);
            $existeuser = obtem_usuario($base['field'], $base['value']);
            if (!empty($existeuser)) {
                // Usuario ja existe, imprimir mensagem de que ja esta cadastrado no Moodle.
                echo ("O usuário " . $estudante->nome  ." já está cadastrado no sistema. <br>");
            } else {
                // Usuario nao existe, cadastrar no Moodle.
                $user_created = user_create_user($newuser);
                if ($user_created === false) {
                    // Tratamento de erro caso a funcao user_create_user() retorne false
                    echo ("Erro ao cadastrar o usuário " . $estudante->nome . ". <br>");
                } else {
                    \core\notification::success('Estudante ' . $estudante->nome . ' inscrito com sucesso!');
                }
            }
        }
    }


    // Funcao para matricular os alunos no curso conforme o codigo de oferecimento
        function matricula_usuario($alunos){
            global $DB, $CFG;

            foreach($alunos as $estudante) {
                $course = $DB->get_record('extensao_turma', ['codofeatvceu' => $estudante->codofeatvceu]);
                if (is_null($course->id_moodle)) {
                    echo "O curso de codofeatvceu $course->codofeatvceu ainda não tem um ambiente Moodle associado.<br>";
                    
                    continue;
                }

                $userid = $estudante->codpes;
                $user = $DB->get_record('user', ['username' => $userid]);
                
                $timestart = time(); 
                $timeend = 0; 
                $status = ENROL_USER_ACTIVE; 
                $userid = $estudante->codpes;
                // Matricula o usuario no curso

                $instances = enrol_get_instances($course->id_moodle, true);
                $instance = array_values($instances)[0];
                $plugin = enrol_get_plugin('manual');
                
                $plugin->enrol_user($instance, $user->id, $course->id_moodle);

                $student_role = 5; // role dos estudantes
                $context = context_system::instance();
                role_assign($student_role, $user->id, $context->id);

                // Verifica se a matricula foi realizada com sucesso
                if (!$instance) {
                    echo "Erro ao matricular o usuário no curso, por favor contate o suporte.<br>";
                } else {
                    echo "Usuário " . $estudante->nome . " matriculado com sucesso no curso<br>";
                }
            }
    }

  
        function inscrever_aluno(){
            $alunos = obter_alunos();
            echo "<pre>/";
            cadastra_usuario ($alunos);
            matricula_usuario($alunos);
            echo "Curso atualizado com Sucesso!";
        }


}

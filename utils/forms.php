<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Forms
 * Para facilitar o trabalho com formularios, este arquivo os centraliza. Assim eh
 * mais facil de encontrar a origem dos formularios e fazer alteracoes quando
 * necessario.
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once(__DIR__ . '/../src/Ambiente.php');
require_once(__DIR__ . '/../src/Turmas.php');
require_once(__DIR__ . '/../src/Atuacao.php');

// formulario para os docentes criarem um ambiente para um curso (versao select)

class redirecionamento_criacao_ambiente_select extends moodleform {
  public function definition () {
    global $CFG, $DB;
    // Captura a lista de cursos
    if (isset($this->_customdata['cursos'])) 
        $cursos = $this->_customdata['cursos'];
    else 
        $cursos = [];

    $options = array();
    foreach ($cursos as $indice => $nome_curso) {
      $turma = $DB->get_record('block_extensao_turma', ['codofeatvceu'=>$indice]);
  
      // Convertendo o formato da data
      $Inicio = !is_null($turma->dtainiofeedi) ? "(".date('d-m-Y', $turma->dtainiofeedi).")" : '';
  
      // Formatado com a data de inicio entre parenteses;
      $option_label = "$nome_curso $Inicio";
      $options[$indice] = $option_label;
    }
  
    $options = array('placeholder' => "Buscar") + $options;
    $this->_form->addElement('autocomplete', 'select_ambiente', 'Buscar por turma', $options);

    // botao de submit
    $this->_form->addElement('submit', 'redirecionar_criar_ambiente', 'Criar ambiente');
  }
}

// formulario para os docentes criarem um ambiente para um curso (versao lista com 5 ou menos cursos)
class redirecionamento_criacao_ambiente_lista extends moodleform {
  public function definition () {
    global $CFG, $DB;

    // input hidden com o id da turma no plugin Extensao
    $codofeatvceu = "";
    if (isset($this->_customdata['codofeatvceu'])) {
      $codofeatvceu = $this->_customdata['codofeatvceu'];

      // Obter informacoes do curso
      $curso = Turmas::info_turma_id_extensao($codofeatvceu);
      $nomeCurso = $curso->nome_curso_apolo;
      $dataInicio = $curso->dtainiofeedi;

      // Formatando a data de inicio
      $dataInicioFormatada = is_null($dataInicio) ? '-' : date('d/m/Y', $dataInicio);
      $inicioFormatado = "<span style='color: red; font-weight: bold'>".$dataInicioFormatada."</span>";

      // Construindo o rotulo do curso com a data de inicio estilizada
      $labelCurso = "Inicia em: $inicioFormatado";

      // Adicionando o elemento de texto com o nome do curso e a data de inicio estilizada
      $this->_form->addElement('static', 'curso_com_data_inicio', $labelCurso);
    }
    $this->_form->addElement('hidden', 'codofeatvceu', $codofeatvceu);
    $this->_form->setType('codofeatvceu', PARAM_TEXT);
    
    // botao de submit
    $this->_form->addElement('submit', 'redirecionar_criar_ambiente', 'Criar ambiente');
  }
}

// formulario para a criacao de ambientes no Moodle
class criar_ambiente_moodle extends moodleform {
  public function definition () {
    global $CFG;

    // input hidden com o id da turma no plugin Extensao
    $codofeatvceu = $this->define_campo('codofeatvceu');
    $this->_form->addElement('hidden', 'codofeatvceu', $codofeatvceu);
    $this->_form->setType('codofeatvceu', PARAM_TEXT);

    // nome curto do curso
    $shortname = $this->define_campo('shortname');
    $this->_form->addElement('text', 'shortname', 'Nome curto do curso', array('readonly' => 'true'));
    $this->_form->setDefault('shortname', $shortname);
    $this->_form->setType('shortname', PARAM_TEXT);
    
    // nome completo do curso
    $fullname = $this->define_campo('fullname');
    $this->_form->addElement('text', 'fullname', 'Nome completo do curso', array('readonly' => 'true'));
    
    $init_date = (int) $this->define_campo('startdate');
    $ano_curso = date('Y', $init_date);

    $this->_form->setDefault('fullname', "{$fullname} ({$ano_curso})");
    $this->_form->setType('fullname', PARAM_TEXT);

    // data de inicio do curso
    $this->_form->addElement('date_selector', 'startdate', 'Data de início do curso');
    $this->_form->setDefault('startdate', $init_date);

    // data do fim do curso
    $end_date = (int) $this->define_campo('enddate');
    $periodo_adicional = get_config('block_extensao', 'periodoAdicional');
    $end_date = strtotime("+$periodo_adicional months", $end_date);
    $this->_form->addElement('date_selector', 'enddate', 'Data do fim do curso');
    $this->_form->setDefault('enddate', $end_date);

    // Para definir um estilo 
    $end_date_formatted = date('d/m/Y', $end_date);
    $end_date_element = $this->_form->getElement('enddate');
    $end_date_element->setLabel('Data do fim do curso <span style="color: #ff0000; font-weight: bold;">' . $end_date_formatted . '</span>');

    // sumario (descricao) do curso
    $summary = $this->define_campo('summary');
    $this->_form->addElement('editor', 'summary', 'Descrição do curso')->setValue(array('text'=>$summary));
    $this->_form->setType('summary', PARAM_RAW);

    // opcao para acesso de visitantes
    $guest = $this->define_campo('guest');
    $options = array(0 => 'Não', 1 => 'Sim');
    $this->_form->addElement(
      'select',
      'guest',
      'Deseja que seu curso seja aberto ao público? Se sim, o conteúdo estará disponível na internet para qualquer visitante.',
      $options
    ); 

    // opcao para inscrever outros ministrantes
    $ministrantes = $this->define_campo('ministrantes');
    
    $this->_form->addElement('header', 'header_ministrantes', 'Outros ministrantes');

    if (!isset($ministrantes['moodle']) or $ministrantes == "") {
      $this->_form->addElement(
        'static',
        'aviso_ministrantes',
        'Você é o(a) único(a) ministrante da turma.'
      );
    } 
    else {  
      // para ministrantes que ja tem conta no Moodle
      $moodle = $ministrantes['moodle'];
      foreach ($moodle as $ministrante){
        $dscatc = $ministrante->dscatc;

        $nomeprofessor = sprintf('%s %s', $ministrante->firstname, $ministrante->lastname);
        $namecheckbox = "ministrantes[{$ministrante->id}]";
        $this->_form->addElement(
          'advcheckbox', 
          $namecheckbox,
          null,
          $nomeprofessor . " [{$dscatc}]",
          array(),
          array(1, $ministrante->firstname)
        );
        $this->_form->setDefault($namecheckbox, true);
      }
      // para ministrantes que nao tem conta no Moodle ainda
      if (isset($ministrantes['apolo'])) {
        foreach ($ministrantes['apolo'] as $ministrante) {
          $dscatc = $ministrante->dscatc;

          $namecheckbox = "ministrantes_semconta[{$ministrante->codpes}]";
          $this->_form->addElement(
            'checkbox',
            $namecheckbox,
            $ministrante->nompes . " [{$dscatc}]",
          );
          $this->_form->setDefault($namecheckbox, true);
        }
      }
    }

    // botao de submit
    $this->_form->addElement('submit', 'criar_ambiente_moodle_submit', 'Criar ambiente');
  }

  /**
   * Funcao para diminuir a verbosidade.
   * Dado um $nome, captura o $mforms->_customdata associado.
   * 
   * @param string $nome Nome do campo buscado.
   * @return string Valor no campo buscado ou vazio se nao for encontrado.
   */
  private function define_campo ($nome) {
    if (isset($this->_customdata[$nome])) return $this->_customdata[$nome];
    else return "";
  }

  /**
   * Validacao do formulario
   * Faz as verificacoes necessarias para garantir que a criacao do ambiente
   * sera feita corretamente.
   * 
   * @param array $data Dados preenchidos no formulario
   * @param array $files Arquivos
   * @return array Erros na validacao
   */ 
  public function validation($data, $files) {
    $errors= array();
    
    // validacao do shortname
    if (Ambiente::shortname_em_uso($data['shortname'])) {
      $msg_shortname = 'O nome curto "' . $data['shortname'] . '" já está em uso. Por favor, escolha outro.';
      \core\notification::error($msg_shortname);
      $errors['shortname'] = $msg_shortname;
    }

    // validacao do codofeatvceu
    if (Turmas::ambiente_criado_turma($data['codofeatvceu'])) {
      $msg_codofeatvceu = 'O curso de código de oferecimento ' . $data['codofeatvceu'] . '" já tem um ambiente Moodle associado.';
      \core\notification::error($msg_codofeatvceu);
      $errors['codofeatvceu'] = $msg_codofeatvceu;  
    }
    
    return $errors;
  }
}
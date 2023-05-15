<?php
/**
 * Forms
 * 
 * Para facilitar o trabalho com formularios, este arquivo os centraliza. Assim eh
 * mais facil de encontrar a origem dos formularios e fazer alteracoes quando
 * necessario.
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

// formulario escondido para os docentes criarem um ambiente para um curso
class redirecionamento_criacao_ambiente extends moodleform {
  public function definition () {
    // input hidden com o id da turma no plugin Extensao
    $codofeatvceu = "";
    if (isset($this->_customdata['codofeatvceu']))
      $codofeatvceu = $this->_customdata['codofeatvceu'];  
    $this->_form->addElement('hidden', 'codofeatvceu', $codofeatvceu);
    $this->_form->setType('codofeatvceu', PARAM_TEXT);
    
    // botao de submit
    $this->_form->addElement('submit', 'redirecionar_criar_ambiente', 'Criar ambiente');
  }
}

// formulario para a criacao de ambientes no Moodle
// OBS: addRule nao esta funcionando...
class criar_ambiente_moodle extends moodleform {
  public function definition () {
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
    $this->_form->addElement('text', 'fullname', 'Nome completo do curso');
    $this->_form->setDefault('fullname', $fullname);
    $this->_form->setType('fullname', PARAM_TEXT);


    // data de inicio do curso
    $init_date = $this->define_campo('startdate');
    $init_date_timestamp = strtotime($init_date);
    $this->_form->addElement('date_selector', 'startdate', 'Data de início do curso');
    $this->_form->setDefault('startdate', $init_date_timestamp);

    // data do fim do curso
    $end_date = $this->define_campo('enddate');
    $end_date_timestamp = strtotime($end_date);
    $this->_form->addElement('date_selector', 'enddate', 'Data do fim do curso');
    $this->_form->setDefault('enddate', $end_date_timestamp);

    // Para definir um estilo 
    
    $end_date_formatted = date('d/m/Y', $end_date_timestamp);
    $end_date_element = $this->_form->getElement('enddate');
    $end_date_element->setLabel('Data do fim do curso <span style="color: #ff0000; font-weight: bold;">' . $end_date_formatted . '</span>');

    // sumario (descricao) do curso
    $summary = $this->define_campo('summary');
    $this->_form->addElement('textarea', 'summary', 'Descrição do curso'); // devemos usar 'editor' ou 'textarea'?
    $this->_form->getElement('summary')->setRows(3); // numero de linhas
    $this->_form->getElement('summary')->setCols(50); // numero de colunas
    $this->_form->setDefault('summary', $summary);
    $this->_form->setDefault('summary', $summary);
    $this->_form->setType('summary', PARAM_TEXT);
    
    // botao de submit
    $this->_form->addElement('submit', 'criar_ambiente_moodle_submit', 'Criar ambiente');
  }

  // funcao para diminuir a verbosidade
  private function define_campo ($nome) {
    if (isset($this->_customdata[$nome])) return $this->_customdata[$nome];
    else return "";
  }
}
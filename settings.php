<?php

defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {

    // Credenciais de login na base
    $setting = new admin_setting_configtext('block_extensao/host', 'Host','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/port', 'Porta','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/database', 'Database','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/user', 'Usuário','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask('block_extensao/password', 'Senha','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtextarea('block_extensao/email_body_new_user', 
            'Corpo do e-mail para novos usuários',
            'Token de substituição: %profAux, %profTit, %curso e %turma, %data',
            "<p>Prezado(a) %profAux,</p>
    
            <p>Esta é uma notificação da criação do ambiente virtual  em <strong>%data</strong>, do seu curso <strong>%curso</strong>, turma <strong>%turma</strong> na plataforma de 
            Cultura e Extensão da USP. Foi atribuído a você o papel de ministrante nesta plataforma pelo professor 
            <strong>%profTit</strong>. Para acessá-lo, clique no seguinte link:</p>
            <p><a href=https://cursosextensao.usp.br/dashboard/>Acessar o Ambiente Virtual</a></p>
            
            <p>Atenciosamente,</p>
            <p>Equipe Moodle USP Extensão</p>",
            PARAM_RAW);

    $settings->add($setting);

    // Adicionado a configuração que permite a edição do texto através do editor Atto
    $setting->set_updatedcallback('editor::update_definition', array('email', 'block_extensao/email_body_new_user'));

    // Configuracoes de nomes de tabela
    // Por padrao vem com os valores do replicado
    $setting = new admin_setting_configtext('block_extensao/tabela_oferecimentoatividadeceu', 'OFERECIMENTOATIVIDADECEU', '', 'OFERECIMENTOATIVIDADECEU', PARAM_TEXT);
    $settings->add($setting);

    // Campo para tabela de "CURSOCEU"
    $setting = new admin_setting_configtext('block_extensao/tabela_cursoceu', 'CURSOCEU', '', 'CURSOCEU', PARAM_TEXT);
    $settings->add($setting);

    // Campo para a tabela de "EDICAOCURSOOFECEU"
    $setting = new admin_setting_configtext('block_extensao/tabela_edicaocursoofeceu', 'EDICAOCURSOOFECEU', '', 'EDICAOCURSOOFECEU', PARAM_TEXT);
    $settings->add($setting);

    // Campo para a tabela de "MINISTRANTECEU"
    $setting = new admin_setting_configtext('block_extensao/tabela_ministranteceu', 'MINISTRANTECEU', '', 'MINISTRANTECEU', PARAM_TEXT);
    $settings->add($setting);
    
    // Campo para a tabela de "EMAILPESSOA"
    $setting = new admin_setting_configtext('block_extensao/tabela_emailpessoa', 'EMAILPESSOA', '', 'EMAILPESSOA', PARAM_TEXT);
    $settings->add($setting);

    // Campo para a tabela de "UNIDADE"
    $setting = new admin_setting_configtext('block_extensao/tabela_unidade', 'UNIDADE', '', 'UNIDADE', PARAM_TEXT);
    $settings->add($setting);

    // Campo para a tabela de "CAMPUS"
    $setting = new admin_setting_configtext('block_extensao/tabela_campus', 'CAMPUS', '', 'CAMPUS', PARAM_TEXT);
    $settings->add($setting);

    // Campo para a tabela de "PESSOA"
    $setting = new admin_setting_configtext('block_extensao/tabela_pessoa', 'PESSOA', '', 'PESSOA', PARAM_TEXT);
    $settings->add($setting);

    // Campo de configuracao para busca de curso
    $options = array(
        '3' => '3 meses',
        '6' => '6 meses',
        '9' => '9 meses',
        '1' => '1 ano',
    );
    $setting = new admin_setting_configselect(
    'block_extensao/periodo_curso',
    'Selecione o período para pesquisa de cursos abertos. ', 
    'Escolha o período desejado: ', 
    '3',
    $options
);
    $settings->add($setting);
}

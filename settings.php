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


    // Configuracoes de nomes de tabela
    // Por padrao vem com os valores do replicado
    $setting = new admin_setting_configtext(
        'block_extensao/tabela_oferecimentoatividadeceu',
        'OFERECIMENTOATIVIDADECEU',
        '',
        'OFERECIMENTOATIVIDADECEU',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_cursoceu',
        'CURSOCEU',
        '',
        'CURSOCEU',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_edicaocursoofeceu',
        'EDICAOCURSOOFECEU',
        '',
        'EDICAOCURSOOFECEU',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_ministranteceu',
        'MINISTRANTECEU',
        '',
        'MINISTRANTECEU',
        PARAM_TEXT
    );
    $settings->add($setting);
    
    $setting = new admin_setting_configtext(
        'block_extensao/tabela_emailpessoa',
        'EMAILPESSOA',
        '',
        'EMAILPESSOA',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_unidade',
        'UNIDADE',
        '',
        'UNIDADE',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_campus',
        'CAMPUS',
        '',
        'CAMPUS',
        PARAM_TEXT
    );
    $settings->add($setting);

    $setting = new admin_setting_configtext(
        'block_extensao/tabela_pessoa',
        'PESSOA',
        '',
        'PESSOA',
        PARAM_TEXT
    );
    $settings->add($setting);
}
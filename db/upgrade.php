<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Atualizacao
 * O objetivo desse arquivo eh fazer a atualizacao automatica do plugin.
 * 
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_extensao_upgrade($versaoAnterior) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    // Criar a tabela 'block_extensao'
    if ($versaoAnterior < 2024061501) {
        // Criacao da tabela mdl_block_extensao
        $tabela = new xmldb_table('block_extensao');

        // Adicionar os campos a tabela
        $tabela->add_field('id', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tabela->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Cria a tabela se nao existir
        if (!$dbman->table_exists($tabela)) {
            $dbman->create_table($tabela);
        }

        // Agora cria a tabela mdl_block_extensao_turma
        $tabela = new xmldb_table('block_extensao_turma');

        // Adicionar campos a tabela
        $tabela->add_field('codofeatvceu', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('nome_curso_apolo', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('data_importacao', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $tabela->add_field('sincronizado_apolo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $tabela->add_field('codcam', XMLDB_TYPE_TEXT, '20', null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('id_moodle', XMLDB_TYPE_INTEGER, '16', null, null, null, null);
        $tabela->add_field('codund', XMLDB_TYPE_TEXT, '20', null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('objcur', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('dtainiofeatv', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $tabela->add_field('dtafimofeatv', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Define 'codofeatvceu' como chave primaria
        $tabela->add_key('primary', XMLDB_KEY_PRIMARY, ['codofeatvceu']);

        // Define 'id_moodle' como indice
        $tabela->add_index('idx_id_moodle', XMLDB_INDEX_NOTUNIQUE, ['id_moodle']);

        // Cria a tabela se nao existir
        if (!$dbman->table_exists($tabela)) {
            $dbman->create_table($tabela);
        }

        // E agora a tabela mdl_block_extensao_ministrante
        $tabela = new xmldb_table('block_extensao_ministrante');

        // Adicionar campos a tabela
        $tabela->add_field('id', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $tabela->add_field('codpes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('codofeatvceu', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('codatc', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('dscatc', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('nompes', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $tabela->add_field('codema', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $tabela->add_field('codedicurceu', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);

        // Define 'id' como chave primaria
        $tabela->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Cria a tabela se nao existir
        if (!$dbman->table_exists($tabela)) {
            $dbman->create_table($tabela);
        }

        // Registrar o ponto de salvamento apos a criacao da tabela
        upgrade_plugin_savepoint(true, 2024061501, 'block', 'extensao');
    }

    // Adiciona o campo "responsavel" na tabela mdl_block_extensao_ministrante
    // e torna o campo "codatc" passivel de ser nulo
    if ($versaoAnterior <= 2024070301) {
        $tabela = new xmldb_table('block_extensao_ministrante');

        // Muda "codatc" para NOTNULL=FALSE
        $campo_codatc = new xmldb_field('codatc', XMLDB_TYPE_INTEGER, '16', null, null, null, null);
        $dbman->change_field_notnull($tabela, $campo_codatc, $continue = true, $feedback = true);

        // Agora adiciona o campo "responsavel"
        $campo_responsavel = new xmldb_field('responsavel', XMLDB_TYPE_INTEGER, '16', null, XMLDB_NOTNULL, null, 0);
        $dbman->add_field($tabela, $campo_responsavel, $continue = true, $feedback = true);

    }

    \core\notification::success('O USP Extensão foi atualizado com sucesso!');
    return true;

}
<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Events
 * Para chamar funcoes quando gatilhos do Moodle forem acionados.
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
  // curso deletado  
  array(
    'eventname' => 'core\event\course_deleted',
    'callback' => 'block_extensao_observer::curso_deletado'
  ),
);
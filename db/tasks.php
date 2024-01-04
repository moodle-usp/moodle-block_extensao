<?php
/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Tasks
 * Para rodar eventos pre-programados, como a sincronizacao de cursos com o Apolo.
 */

$tasks = [
  [
    'classname' => 'block_extensao\task\sincronizar',
    'blocking'  => 0,
    'minute'    => '1',
    'hour'      => '0',
    'day'       => '*',
    'month'     => '*',
    'dayofweek' => '*',
  ]
];
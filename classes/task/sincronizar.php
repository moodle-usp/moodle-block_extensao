<?php

/**
 * Cursos de Extensao (Bloco)
 * Equipe de Moodle da USP
 * https://github.com/moodle-usp
 * 
 * # Task para sincronizacao automatica
 * Neste arquivo fica a task que roda automaticamente a sincronizacao da
 * base com o Apolo diariamente. Depende do Cron estar rodando.
 */

namespace block_extensao\task;
require_once(__DIR__ . '/../../src/Service/Sincronizacao.php');

class sincronizar extends \core\task\scheduled_task {

  /**
   * Retorna o nome da task para mostrar na tela de administracao
   * 
   * @return string
   */
  public function get_name () {
    return get_string('task_sincronizar', 'block_extensao');
  }

  /**
   * Executa a task.
   */
  public function execute () {
    try {
      // Faz a sincronizacao
      $sinc = new \Sincronizar();
      $sinc->sincronizar(['pular_ministrantes'=>false]);      
    } catch (Exception $e) {
      echo 'Erro: ' . $e->getMessage();
    }
  }

}
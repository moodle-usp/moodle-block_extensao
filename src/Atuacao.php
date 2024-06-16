<?php

class Atuacao {
  /**
   * Relacao atual:
   * 
   * Professor USP              => editingteacher
   * Especialista               => editingteacher
   * Monitor                    => teacher
   * Servidor                   => teacher
   * Professor HC - FM-USP      => editingteacher
   * Tutor                      => teacher
   * Docente (S)                => teacher
   * Preceptor (S)              => teacher
   * Tutor (S)                  => teacher
   * Coordenador de Estágio (S) => teacher
   * Corresponsável             => teacher
   * Responsável                => teacher
   */
  const CARGOS_EDITINGTEACHER = array(1,2,5,7);

  static public function correspondencia_moodle ($codatc) {
    if (array_search($codatc, self::CARGOS_EDITINGTEACHER))
      return 'editingteacher';
    else
      return 'teacher';
  }
}
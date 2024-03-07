<?php
/**
 * Manipulacao das categorias de curso
 */
defined('MOODLE_INTERNAL') || die();

class Categorias {
  /**
   * A partir do id de um usuario, verifica se ele eh gerente
   * de alguma categoria.
   */
  public static function usuario_gerente_categoria ($id_usuario) {
    global $DB;
    
    // Captura na base se o usuario tem registro na MDL_ROLE_ASSIGNMENTS com ROLEID=1 (MANAGER)
    $roles_usuario = $DB->get_records('role_assignments', [
      'userid' => $id_usuario,
      'roleid' => 1
    ]);
    
    if (empty($roles_usuario))
      return [];

    // Se tiver roles, entao vai em cada uma e procura pelos contextos, para saber se o CONTEXTLEVEL eh 40 (COURSECAT)
    $categorias = array();
    foreach ($roles_usuario as $role) {
      $contexto = $DB->get_record('context', [
        'id' => $role->contextid,
        'contextlevel' => 40
      ]);
      if (empty($contexto)) 
        continue;

      // Se tiver, entao captura qual a categoria corresponde
      $categoria = $DB->get_record('course_categories', [
        'id' => $contexto->instanceid
      ]);
      $categorias[] = $categoria;
    }

    // Tendo os contextos

    return $categorias;
  }

  /**
   * Verifica se um usuario eh gerente de alguma categoria
   * que englobe um determinado cursos.
   */
  public static function usuario_gerente_turma ($id_usuario, $codofeatvceu) {
    global $DB;

    // Captura as categorias do usuario
    $categorias = Categorias::usuario_gerente_categoria($id_usuario);

    // Captura a categoria da turma
    $query = "SELECT * FROM {block_extensao_turma} WHERE codofeatvceu = $codofeatvceu";
    $turma = $DB->get_record_sql($query);

    foreach ($categorias as $categoria) {
      if ($categoria->idnumber == $turma->codcam || $categoria->idnumber == $turma->codund)
        return true;
    }
    return false;
  }
}
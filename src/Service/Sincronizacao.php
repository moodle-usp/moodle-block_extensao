<?php

namespace block_extensao;

/**
 * Aqui eh feita a sincronizacao provisoriamente, depois
 * que eh apertado o botao.
 */

require_once('Query.php');
require_once('../../vendor/autoload.php');

use Query;

function capturar_dados () {

    $cursos = Query::getcourses();

    return $cursos;
}

capturar_dados();
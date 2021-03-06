<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'block_courses_vicensvives', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   block_courses_vicensvives
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Courses Vicens Vives';

// Settings.
$string['apiurl'] = 'URL API';
$string['sharekey'] = 'Clave compartida';
$string['sharepass'] = 'Secreto compartido';

$string['configapiurl'] = 'URL del web service de Vicens Vives';
$string['configsharekey'] = 'Clave  compartida con el Web Service de Vicens Vives para obtener la información de los libros.';
$string['configsharepass'] = 'Secreto compartido con el Web Service de Vicens Vives para obtener la información de los libros.';

$string['connectok'] = 'La conexión con Vicens Vives se ha establecido correctamente';
$string['connectfail'] = 'La conexión con Vicens Vives ha fallado, comprueba las credenciales';

$string['maxcourses'] = 'Número de cursos';
$string['defaultcategory'] = 'Categoría para cursos';

$string['configdefaultcategory'] = 'Categoría por defecto para los cursos de libros de Vicens Vives.';
$string['configmaxcourses'] = 'Número de cursos que se muestra en el bloque.';

$string['moodletoken'] = 'Token de Moodle';
$string['moodletokendesc'] = 'Se sincronizará este token de Moodle con Vicens Vives.';
:
// Errors
$string['wsnotconfigured'] = 'El Web Service de Vicens Vives no está configurado.';
$string['wsauthfailed'] = 'Ha fallado la autentificación con el Web Service de Vicens Vives.';
$string['wsunknownerror'] = 'Se ha producido un error inesperado en conectar con el Web Service de Vicens Vives.';

// Contenido del bloque.
$string['show_more'] = 'Ver más...';
$string['show_courses'] = 'Ver cursos';

// Páginas del bloque.
$string['courses'] = 'Cursos';
$string['books'] = 'Libros';
$string['addcourse'] = 'Crear un nuevo curso';
$string['nohaycursos'] = 'No hay cursos que mostrar';
$string['searchresult'] = '{$a->found} de {$a->total} libros';
$string['searchempty'] = '{$a} libros';

// Tabla libros

$string['fullname'] = 'Nombre';
$string['subject'] = 'Materia';
$string['idLevel'] = 'Nivel';
$string['isbn'] = 'ISBN';
$string['actions'] = 'Acciones';
$string['nobooksfound'] = 'No se han encontrado libros';

// Crear el curs.
$string['creatingcourse'] = 'Creando curso';
$string['redirectcourse'] = 'Redireccionando al curso.';
$string['editingteachernotexist'] = "No se ha podido matricular al usuario: El rol 'editingteacher' no existe.";
$string['manualnotenable'] = "No se ha podido matricular al usuario: La matriculación manual no está activada.";
$string['nofetchbook'] = 'No se ha podido obtener el libro';
$string['nocreatecourse'] = 'Nos se ha podido crear el curso';
$string['nocreatestructure'] = 'No se ha podido crear la estructura del curso';

// Permisos
$string['courses_vicensvives:addinstance'] = 'Crear instancia del bloque.';
$string['courses_vicensvives:myaddinstance'] = 'Crear instancia del bloque en my.';

// Event
$string['eventwebservicecalled'] = 'Web service llamado';

// Annadido Sallenet
$string['moodletokendesc'] = 'Token Moodle Desc';
$string['moodletoken'] = 'Token Moodle';


$string['eventwebservicecalled'] = 'Llamada a evento servicio web';
$string['webservicecalled'] = 'Llamada a servicio web';


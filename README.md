Courses Vicens Vives
====================

Bloque para mostrar los cursos que un usuario de moodle tiene relacionados con libros de Vicens Vives.

Bloque para la creación de cursos
Los  cursos  de  Vicens  Vives  se  identificarán  con  el  idnumber  en  moodle,  de  manera  que
podemos identificar qué cursos son del sistema de Vicens Vives (a convenir según JSON).

Las  opciones  de  visualización  e instanciación  del  bloque  serán  lo más standard posible para
bloques de profesores que también ven administradores con mayores permisos.

    * En  la  configuración  debe  haber  un  formulario  para  guardar  en   la  configuración  de
    moodle la clave y el secreto compartido para conectar al WS de Vicens Vives.
    En  el  momento  de  guardar dicha configuración se debería hacer la comprobación de la
    conexión  y   guardar  en  la  configuración  de   moodle  los  valores  de  anteriores  y  los  de
    Access Token y Refresh Token.

En  caso  de  error, se  debe  interpretar  el  error  de  retorno para mostrarlo correctamente
en pantalla.

El Contenido del bloque:
El  contenido  del  bloque  para  un  profesor  será  el  listado  de  enlaces  a  sus  cursos  de  Vicens
Vives  y  en  el   footer  del  bloque  un  enlace  para  crear  un  curso  nuevo.  Sería  necesario
identificar  un  número  máximo de  cursos,  a  partir  del  cual  en  lugar  de  mostrar  el  listado  en  el
bloque,  habrá un enlace a una página con el listado de cursos, esta será la visión del bloque que
tendrá el administrador de la plataforma.
El  enlace  crear  un curso nuevo enlaza con una página que mostrará el listado de libros donde
el profesor puede crear cursos, listado con las siguientes características:
 - Campos: Nombre, Materia, Nivel, ISBN y Acciones.
 - Ordenación: se aplicará ordenación clicando un campo si no complica el desarrollo.
 - Filtros: Se añadirán filtros por Materia y Nivel.
 - Acciones: en este caso se indicará un icono o texto con un enlace a Crear curso.
Acciones internas de creación de curso:
    Crear Curso.
    Matricular al profesor.
    Aplicar formato específico de Vicens Vives.
    Indicar matriculación manual.
    Crear estructura con actividades.
        Crear cada una de las actividades LTI/enlaces.
    Instanciar bloque de licencias.
Las  configuraciones  específicas  de  los  LTI  tendrán  clave  de  cliente  y  secreto  compartido,
independientes  al  de  la  conexión  con  el  WS  de   Vicens  Vives,  si  no  se  especifica  esta
información en el LTI, vendrá especificada en la estructura del curso y será global para todos.
Las elementos del curso deberán identificarse con un idnumber único al crearlos en moodle.

* Esta configuración se repetirá para los diferentes plugins con uso del WS de Vicens Vives.


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
 * Authentication Plugin:
 *
 * Checks against an external database.
 *
 * @package    courses_vicensvives
 * @author     CV&A Consulting
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

require_once($CFG->dirroot.'/blocks/courses_vicensvives/lib/vicensvives.php');
require_once($CFG->dirroot.'/course/modlib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->libdir.'/adminlib.php');

class courses_vicensvives_add_book {

    var $book;
    var $progress;
    var $current = 0;
    var $total;
    var $course;

    function __construct($bookid, $progress=null) {
        $ws = new vicensvives_ws();
        $this->book = $ws->book($bookid);
        if (!$this->book) {
            throw new moodle_exception('nofetchbook', 'block/courses_vicensvives');
        }
        $this->progress = $progress;
        $this->total = $this->mod_count() + 1;
    }

    function create_course() {
        global $CFG, $DB;

        require_once($CFG->dirroot.'/course/lib.php');

        $courseobj =  new stdClass();
        $courseobj->category = $CFG->block_courses_vicensvives_defaultcategory;

        // Random para no duplicar nombres.
        $rand = random_string(4);
        $courseobj->shortname = 'vv-'.$this->book->shortname.'-'.$rand;
        $courseobj->fullname = $this->book->fullname;
        $courseobj->idnumber = 'vv-'.$this->book->idBook.'-'.$rand.'-'.$this->book->subject;
        $courseobj->summary = $this->book->authors;
        // Comprobar q existe el formato de curso personalizado para aplicarlo.
        $courseobj->format = 'topics';
        $courseobj->numsections = count($this->book->units);
        $courseformats = get_plugin_list('format');
        if (isset($courseformats['vv'])) {
            $courseobj->format = 'vv';
        }
        // De esta manera no crearemos el foro de noticias.
        $courseobj->newsitems = 0;

        $this->course = create_course($courseobj);

        // Reescribir random.
        $this->course->shortname = str_replace($rand, $this->course->id, $this->course->shortname);
        $this->course->idnumber = str_replace($rand, $this->course->id, $this->course->idnumber);

        $DB->update_record('course', $this->course);

        return $this->course->id;
    }

    function enrol_user($user) {
        global $DB;

        if (!$role = $DB->get_record('role', array('shortname'=>'editingteacher'))) {
            return get_string('editingteachernotexist', 'block_courses_vicensvives');
        }

        // Matriculación manual.
        if (!enrol_is_enabled('manual')) {
            return get_string('manualnotenable', 'block_courses_vicensvives');
        }

        $contextcourse = context_course::instance($this->course->id);
        if (user_has_role_assignment($user->id, $role->id, $contextcourse->id)) {
            return;
        }

        $manual = enrol_get_plugin('manual');
        $instances = enrol_get_instances($this->course->id, false);
        foreach ($instances as $instance) {
            if ($instance->enrol === 'manual') {
                $manual->enrol_user($instance, $user->id, $role->id);
                break;
            }
        }
    }

    function create_course_content() {
        global $DB;

        $sectionnum = 1;
        foreach ($this->book->units as $unit) {
            $this->create_unit($unit, $sectionnum);
            $sectionnum++;
        }

        rebuild_course_cache($this->course->id);
        grade_regrade_final_grades($this->course->id);
        $this->update_progress();
    }

    function create_unit($unit, $sectionnum) {
        global $DB;

        $cw = new stdClass();
        $cw->course   = $this->course->id;
        $cw->section  = $sectionnum;
        $cw->name = $unit->label . '. ' . $unit->name;
        $cw->summary  = '';
        $cw->summaryformat = FORMAT_HTML;
        $cw->sequence = '';
        $cw->id = $DB->insert_record("course_sections", $cw);

        $cmids = array();

        // TODO: create unit lti?

        foreach ($unit->sections as $section) {
            $cmids = array_merge($cmids, $this->create_section($section, $cw));
        }

        $cw->sequence = implode(',', $cmids);
        $DB->update_record('course_sections', $cw);
    }

    function create_section($section, $cw) {
        global $DB;

        $cmids = array();
        $cmids[] = $this->create_label('[' . $section->label . '] ' . $section->name, $cw);
        $this->update_progress();

        if (!empty($section->lti)) {
            $cmids[] = $this->create_lti($section->lti, $cw);
            $this->update_progress();
        }

        if (!empty($section->questions)) {
            foreach ($section->questions as $question) {
                $cmids[] = $this->create_lti($question->lti, $cw);
                $this->update_progress();
            }
        }
        if (!empty($section->links)) {
            foreach ($section->links as $link) {
                $cmids[] = $this->create_link($link, $cw);
                $this->update_progress();
            }
        }
        if (!empty($section->documents)) {
            foreach ($section->documents as $document) {
                $cmids[] = $this->create_lti($document->lti, $cw);
                $this->update_progress();
            }
        }

        return $cmids;
    }

    function create_label($name, $cw) {
// echo '<br>create label<br>';
        return courses_vicensvives_create_mod($this->course, $cw, 'label', $name);
    }

    function create_link($link, $cw) {
// echo '<br>create link<br>';
        $params = array(
            'externalurl' => $link->url,
            'intro' => $link->summary,
            'display' => 0,
            'cmidnumber' => 'link_' . $link->id,
        );
        return courses_vicensvives_create_mod($this->course, $cw, 'url', $link->name, $params);
    }

    function create_lti($lti, $cw) {
// echo '<br>create lti<br>';
// print_object($lti);
        $params = array(
            'toolurl' => $lti->launchURL,
            'instructorchoicesendname' => true,
            'instructorchoicesendemailaddr' => true,
            'launchcontainer' => 4, // window
        );
        if (isset($lti->idnumber))  {
            $params['cmidnumber'] = $lti->idnumber;
        }
        if (isset($lti->activityDescription))  {
            $params['intro'] = $lti->activityDescription;
        }
        if (isset($lti->consumerKey)) {
            $params['resourcekey'] = $lti->consumerKey;
        }
        if (isset($lti->sharedSecret)) {
            $params['password'] = $lti->sharedSecret;
        }
        if (isset($lti->customParameters)) {
            $params['instructorcustomparameters'] = $lti->customParameters;
        }
        if (isset($lti->acceptGrades)) {
            $params['instructorchoiceacceptgrades'] = (int) $lti->acceptGrades;
        }
        return courses_vicensvives_create_mod($this->course, $cw, 'lti', $lti->activityName, $params);
    }

    function mod_count() {
        $count = 0;
        foreach ($this->book->units as $unit) {
            foreach ($unit->sections as $section) {
                $count++;
                if (!empty($section->lti)) {
                    $count++;
                }
                $count += count($section->questions);
                $count += count($section->links);
                $count += count($section->documents);
            }
        }
        return $count;
    }

    function update_progress() {
        $this->current++;
        if ($this->progress) {
            $this->progress->update($this->current, $this->total, '');
        }
    }

}


function courses_vicensvives_create_mod($course, $cw, $modname, $name, $params=null) {
    global $CFG, $DB;

    $fromform = new stdClass();
    $fromform->module = $DB->get_field('modules', 'id', array('name' => $modname));
    $fromform->modulename = $modname;
    $fromform->visible = true;
    $fromform->name = $name;
    $fromform->intro = $name;
    $fromform->introformat = 0;
    $fromform->availablefrom = 0;
    $fromform->availableuntil = 0;
    $fromform->showavailability = 0;
    $fromform->conditiongradegroup = array();
    $fromform->conditionfieldgroup = array();
    $fromform->conditioncompletiongroup = array();

    if ($params) {
        foreach ($params as $key => $value) {
            $fromform->$key = $value;
        }
    }

    courses_vicensvives_add_moduleinfo($fromform, $course, $cw);

    // Denegación del permiso de edición de la actividad
    $roleid = $DB->get_field('role', 'id', array('shortname' => 'user'));
    $context = context_module::instance($fromform->coursemodule);
    assign_capability('moodle/course:manageactivities', CAP_PROHIBIT, $roleid, $context);

    return $fromform->coursemodule;
}

// Función basa en add_moduleinfo, con estas diferencias:
//  - Se pasa el registro de sección para evitar consultar la base de datos
//  - Se ha eliminado código que procesa parámetros no utilitzados
//  - No se llama rebuild_course_cache (se llama una sola vez al final)
//  - No se llama grade_regrade_final_grades (se llama una sola vez al final)
//  - No se ñade el mòdulo a la sección (se añaden todos juntos posteriormente)
function courses_vicensvives_add_moduleinfo($moduleinfo, $course, $cw) {
    global $DB, $CFG;

    // Attempt to include module library before we make any changes to DB.
    include_modulelib($moduleinfo->modulename);

    $moduleinfo->course = $course->id;
    $moduleinfo = set_moduleinfo_defaults($moduleinfo);

    $moduleinfo->groupmode = 0; // Do not set groupmode.

    // First add course_module record because we need the context.
    $newcm = new stdClass();
    $newcm->course           = $course->id;
    $newcm->module           = $moduleinfo->module;
    $newcm->instance         = 0; // Not known yet, will be updated later (this is similar to restore code).
    $newcm->visible          = $moduleinfo->visible;
    $newcm->visibleold       = $moduleinfo->visible;
    if (isset($moduleinfo->cmidnumber)) {
        $newcm->idnumber         = $moduleinfo->cmidnumber;
    }
    $newcm->groupmode        = $moduleinfo->groupmode;
    $newcm->groupingid       = $moduleinfo->groupingid;
    $newcm->groupmembersonly = $moduleinfo->groupmembersonly;
    $newcm->showdescription = 0;

    // From this point we make database changes, so start transaction.
    $transaction = $DB->start_delegated_transaction();

    $newcm->added = time();
    $newcm->section = $cw->id;
    if (!$moduleinfo->coursemodule = $DB->insert_record("course_modules", $newcm)) {
        print_error('cannotaddcoursemodule');
    }

    $addinstancefunction    = $moduleinfo->modulename."_add_instance";
    try {
        $returnfromfunc = $addinstancefunction($moduleinfo, null);
    } catch (moodle_exception $e) {
        $returnfromfunc = $e;
    }
    if (!$returnfromfunc or !is_number($returnfromfunc)) {
        // Undo everything we can. This is not necessary for databases which
        // support transactions, but improves consistency for other databases.
        $modcontext = context_module::instance($moduleinfo->coursemodule);
        context_helper::delete_instance(CONTEXT_MODULE, $moduleinfo->coursemodule);
        $DB->delete_records('course_modules', array('id'=>$moduleinfo->coursemodule));

        if ($e instanceof moodle_exception) {
            throw $e;
        } else if (!is_number($returnfromfunc)) {
            print_error('invalidfunction', '', course_get_url($course, $cw->section));
        } else {
            print_error('cannotaddnewmodule', '', course_get_url($course, $cw->section), $moduleinfo->modulename);
        }
    }

    $moduleinfo->instance = $returnfromfunc;

    $DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$moduleinfo->coursemodule));

    // Update embedded links and save files.
    $modcontext = context_module::instance($moduleinfo->coursemodule);

    $hasgrades = plugin_supports('mod', $moduleinfo->modulename, FEATURE_GRADE_HAS_GRADE, false);
    $hasoutcomes = plugin_supports('mod', $moduleinfo->modulename, FEATURE_GRADE_OUTCOMES, true);

    // Sync idnumber with grade_item.
    if ($hasgrades && $grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$moduleinfo->modulename,
                 'iteminstance'=>$moduleinfo->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
        if ($grade_item->idnumber != $moduleinfo->cmidnumber) {
            $grade_item->idnumber = $moduleinfo->cmidnumber;
            $grade_item->update();
        }
    }

    $transaction->allow_commit();

    return $moduleinfo;
}


/**
 * Parámetro HTML para comprovar la conexión con el webservice de
 * Vicens Vives.
 */
class courses_vicensvives_setting_wscheck extends admin_setting {

    private $error;

    public function __construct() {
        parent::__construct('vicensvives_wscheck', '', '', null);
    }

    public function get_setting() {
        return true;
    }

    public function write_setting($data) {
        return '';
    }

    public function output_html($data, $query='') {
        $ws = new vicensvives_ws();
        try {
            $ws->books();
        } catch (vicensvives_ws_error $e) {
            return html_writer::tag('p', $e->getMessage(), array('class' => 'alert alert-error'));
        }
        return '';
    }
}

function courses_vicensvives_update_tokens() {
    global $CFG;

    unset_config('vicensvives_accesstoken');

    try {
        $ws = new vicensvives_ws();
        $ws->sendtoken($CFG->vicensvives_moodletoken);
    } catch (vicensvives_ws_error $e) {
        // Si hay algun error ya se mostrará en la página de
        // configuración
    }
}

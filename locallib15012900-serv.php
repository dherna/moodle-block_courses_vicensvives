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
        $courseobj->idnumber = 'vv-'.$this->book->idBook.'-'.$rand;
        $courseobj->summary = $this->book->authors;
        // Comprobar q existe el formato de curso personalizado para aplicarlo.
        $courseobj->format = 'topics';
        $courseformats = get_plugin_list('format');
        if (isset($courseformats['vv'])) {
            $courseobj->format = 'vv';
        }

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

        $DB->set_field('course', 'numsections', count($this->book->units), array('id' => $this->course->id));

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
echo '<br>crete unit<br>';
        $cw = get_course_section($sectionnum, $this->course->id);
        $cw->name = $unit->name;
        $DB->update_record('course_sections', $cw);

        // TODO: create unit lti?

        foreach ($unit->sections as $section) {
            $this->create_section($section, $sectionnum);
        }
    }

    function create_section($section, $sectionnum) {
        global $DB;
echo '<br>crete section<br>';

        $this->create_label($section->name, $sectionnum);

        // TODO: create section lti?

        if (!empty($section->questions)) {
            foreach ($section->questions as $question) {
                $this->create_lti($question->lti, $sectionnum);
            }
        }
        if (!empty($section->links)) {
            foreach ($section->links as $link) {
                $this->create_link($link, $sectionnum);
            }
        }
        if (!empty($section->documents)) {
            foreach ($section->documents as $document) {
                $this->create_lti($document->lti, $sectionnum);
            }
        }
    }

    function create_label($name, $sectionnum) {
echo '<br>crete label<br>';
        courses_vicensvives_create_mod($this->course, $sectionnum, 'label', $name);
        $this->update_progress();
    }

    function create_link($link, $sectionnum) {
echo '<br>crete link<br>';
        $params = array(
            'externalurl' => '', // 'externalurl' => $link->url,
            // 'intro' => $link->summary,
            'display' => 0,
        );
        courses_vicensvives_create_mod($this->course, $sectionnum, 'url', $link->name, $params);
        $this->update_progress();
    }

    function create_lti($lti, $sectionnum) {
echo '<br>crete lti<br>';
print_object($lti);
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
        courses_vicensvives_create_mod($this->course, $sectionnum, 'lti', $lti->activityName, $params);
        $this->update_progress();
    }

    function mod_count() {
        $count = 0;
        foreach ($this->book->units as $unit) {
            foreach ($unit->sections as $section) {
                $count++;
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


function courses_vicensvives_create_mod($course, $sectionnum, $modname, $name, $params=null) {
    global $CFG, $DB;

    require_once($CFG->libdir.'/gradelib.php');

    $module = $DB->get_record('modules', array('name'=>$modname), '*', MUST_EXIST);
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $cw = get_course_section($sectionnum, $course->id);

    $modlib = "$CFG->dirroot/mod/$module->name/lib.php";
    if (file_exists($modlib)) {
        include_once($modlib);
    } else {
        print_error('modulemissingcode', '', '', $modlib);
    }

    $fromform = new stdClass();
    $fromform->course = $course->id;
    $fromform->module = $module->id;
    $fromform->modulename = $modname;
    $fromform->visible = true;
    $fromform->name = $name;
    $fromform->intro = $name;
    $fromform->introformat = 0;
    $fromform->groupingid = $course->defaultgroupingid;
    $fromform->groupmembersonly = 0;
    $fromform->section = $sectionnum;
    $fromform->instance = '';
    $fromform->completion = COMPLETION_DISABLED;
    $fromform->completionview = COMPLETION_VIEW_NOT_REQUIRED;
    $fromform->completionusegrade = null;
    $fromform->completiongradeitemnumber = null;
    $fromform->groupmode = 0; // do not set groupmode.
    $fromform->instance     = '';
    $fromform->coursemodule = '';

    if ($params) {
        foreach ($params as $key => $value) {
            $fromform->$key = $value;
        }
    }

    $addinstancefunction    = $fromform->modulename."_add_instance";
    $updateinstancefunction = $fromform->modulename."_update_instance";

    // first add course_module record because we need the context
    $newcm = new stdClass();
    $newcm->course           = $course->id;
    $newcm->module           = $fromform->module;
    $newcm->instance         = 0; // not known yet, will be updated later (this is similar to restore code)
    $newcm->visible          = $fromform->visible;
    $newcm->visibleold       = $fromform->visible;
    $newcm->groupmode        = $fromform->groupmode;
    $newcm->groupingid       = $fromform->groupingid;
    $newcm->groupmembersonly = $fromform->groupmembersonly;
    if(!empty($CFG->enableavailability)) {
        $newcm->availablefrom             = $fromform->availablefrom;
        $newcm->availableuntil            = $fromform->availableuntil;
        $newcm->showavailability          = $fromform->showavailability;
    }
if ($fromform->modulename == 'lti') {
print_object($fromform);die;
}

    $newcm->showdescription = 0;
    if (!$fromform->coursemodule = add_course_module($newcm)) {
        print_error('cannotaddcoursemodule');
    }

    $returnfromfunc = $addinstancefunction($fromform, null);

    if (!$returnfromfunc or !is_number($returnfromfunc)) {
        // undo everything we can
        $modcontext = get_context_instance(CONTEXT_MODULE, $fromform->coursemodule);
        delete_context(CONTEXT_MODULE, $fromform->coursemodule);
        $DB->delete_records('course_modules', array('id'=>$fromform->coursemodule));

        if (!is_number($returnfromfunc)) {
            print_error('invalidfunction', '', course_get_url($course, $cw->section));
        } else {
            print_error('cannotaddnewmodule', '', course_get_url($course, $cw->section), $fromform->modulename);
        }
    }

    $fromform->instance = $returnfromfunc;

    $DB->set_field('course_modules', 'instance', $returnfromfunc, array('id'=>$fromform->coursemodule));

    // course_modules and course_sections each contain a reference
    // to each other, so we have to update one of them twice.
    $sectionid = add_mod_to_section($fromform);

    $DB->set_field('course_modules', 'section', $sectionid, array('id'=>$fromform->coursemodule));

    // make sure visibility is set correctly (in particular in calendar)
    // note: allow them to set it even without moodle/course:activityvisibility
    set_coursemodule_visible($fromform->coursemodule, $fromform->visible);

    if (isset($fromform->cmidnumber)) { //label
        // set cm idnumber - uniqueness is already verified by form validation
        set_coursemodule_idnumber($fromform->coursemodule, $fromform->cmidnumber);
    }

    // sync idnumber with grade_item
    if ($grade_item = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
                                              'iteminstance'=>$fromform->instance, 'itemnumber'=>0, 'courseid'=>$course->id))) {
        if ($grade_item->idnumber != $fromform->cmidnumber) {
            $grade_item->idnumber = $fromform->cmidnumber;
            $grade_item->update();
        }
    }
}

<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/blocks/courses_vicensvives/lib/vicensvives.php');

global $DB;
if ($ADMIN->fulltree) {

    require_once($CFG->dirroot.'/blocks/courses_vicensvives/locallib.php');

    $settings->add(new courses_vicensvives_setting_wscheck());

    $setting = new admin_setting_configtext('vicensvives_apiurl',
            get_string('apiurl', 'block_courses_vicensvives'), get_string('configapiurl', 'block_courses_vicensvives'),
            vicensvives_ws::WS_URL, PARAM_URL);
    $setting->set_updatedcallback('courses_vicensvives_update_tokens');
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask('vicensvives_sharekey',
            get_string('sharekey', 'block_courses_vicensvives'), get_string('configsharekey', 'block_courses_vicensvives'), '');
    $setting->set_updatedcallback('courses_vicensvives_update_tokens');
    $settings->add($setting);

    $setting = new admin_setting_configpasswordunmask('vicensvives_sharepass',
            get_string('sharepass', 'block_courses_vicensvives'), get_string('configsharepass', 'block_courses_vicensvives'), '');
    $setting->set_updatedcallback('courses_vicensvives_update_tokens');
    $settings->add($setting);

    $selectnum = range(0, 100);
    $settings->add( new admin_setting_configselect('block_courses_vicensvives_maxcourses',
            get_string('maxcourses', 'block_courses_vicensvives'),
            get_string('configmaxcourses', 'block_courses_vicensvives'), 10, $selectnum));

    $settings->add(new admin_settings_coursecat_select('block_courses_vicensvives_defaultcategory',
            get_string('defaultcategory', 'block_courses_vicensvives'),
            get_string('configdefaultcategory', 'block_courses_vicensvives'), 1));

    $options = array();
    $serviceid = $DB->get_field('external_services', 'id', array('component' => 'local_wsvicensvives'));
    foreach ($DB->get_records('external_tokens', array('externalserviceid' => $serviceid)) as $record) {
        $user = $DB->get_record('user', array('id' => $record->userid));
        $options[$record->token] = s($record->token) . ' (' . s(fullname($user)) . ')';
    }
    if (!$options) {
        $options[''] = '';
    }
    $setting = new admin_setting_configselect('vicensvives_moodletoken',
            get_string('moodletoken', 'block_courses_vicensvives'),
            get_string('moodletokendesc', 'block_courses_vicensvives'), '', $options);
    $setting->set_updatedcallback('courses_vicensvives_update_tokens');
    $settings->add($setting);
}

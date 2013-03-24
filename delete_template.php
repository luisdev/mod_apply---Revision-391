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
 * deletes a template
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once("../../config.php");
require_once("lib.php");
require_once('delete_template_form.php');
require_once($CFG->libdir.'/tablelib.php');

$current_tab = 'templates';

$id 		   = required_param('id', PARAM_INT);
$cancel_delete = optional_param('cancel_delete', false, PARAM_INT);
$should_delete = optional_param('should_delete', false, PARAM_INT);
$delete_templ  = optional_param('delete_templ',  false, PARAM_INT);
$courseid 	   = optional_param('courseid', 	 false, PARAM_INT);

$url = new moodle_url('/mod/apply/delete_template.php', array('id'=>$id));
if ($cancel_delete !== false) {
    $url->param('cancel_delete', $cancel_delete);
}
if ($should_delete !== false) {
    $url->param('should_delete', $should_delete);
}
if ($delete_templ !== false) {
    $url->param('delete_templ', $delete_templ);
}
if (!$courseid) $courseid = $course->id;

$PAGE->set_url($url);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

if ($cancel_delete==1) {
    $editurl = new moodle_url('/mod/apply/edit.php', array('id'=>$id, 'do_show'=>'templates'));
    redirect($editurl->out(false));
}

if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array('id'=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/apply:delete_template', $context);

$mform = new mod_apply_delete_template_form();
$newformdata = array('id'=>$id, 'delete_templ'=>$delete_templ, 'confirmdelete'=>'1');

$mform->set_data($newformdata);
$formdata = $mform->get_data();

$delete_url = new moodle_url('/mod/apply/delete_template.php', array('id'=>$id));

if ($mform->is_cancelled()) {
    redirect($delete_url->out(false));
}

if (isset($formdata->confirmdelete) AND $formdata->confirmdelete == 1) {
    if (!$template = $DB->get_record('apply_template', array('id'=>$delete_templ))) {
        print_error('error');
    }
    if ($template->ispublic) {
        $systemcontext = get_system_context();
        require_capability('mod/apply:createpublictemplate', $systemcontext);
        require_capability('mod/apply:delete_template', $systemcontext);
    }
    apply_delete_template($template);
    redirect($delete_url->out(false));
}


///////////////////////////////////////////////////////////////////////////
/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');
$strdeleteapply = get_string('delete_template', 'apply');

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

///////////////////////////////////////////////////////////////////////////
/// print the tabs
require('tabs.php');

///////////////////////////////////////////////////////////////////////////
/// Print the main part of the page
echo $OUTPUT->heading($strdeleteapply);

if ($should_delete==1) {
    echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
    echo $OUTPUT->heading(get_string('confirmdelete_template', 'apply'));
    $mform->display();
    echo $OUTPUT->box_end();
} 
else {
    //first we get the own templates
    $templates = apply_get_template_list($course, 'own');
    if (!is_array($templates)) {
        echo $OUTPUT->box(get_string('no_templates_available_yet', 'apply'), 'generalbox boxaligncenter');
    }
	else {
        echo $OUTPUT->heading(get_string('course'), 3);
        echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
        $tablecolumns = array('template', 'action');
        $tableheaders = array(get_string('template', 'apply'), '');
        $tablecourse = new flexible_table('apply_template_course_table');

        $tablecourse->define_columns($tablecolumns);
        $tablecourse->define_headers($tableheaders);
        $tablecourse->define_baseurl($delete_url);
        $tablecourse->column_style('action', 'width', '10%');

        $tablecourse->sortable(false);
        $tablecourse->set_attribute('width', '100%');
        $tablecourse->set_attribute('class', 'generaltable');
        $tablecourse->setup();

        foreach ($templates as $template) {
            $data = array();
            $data[] = $template->name;
            $url = new moodle_url($delete_url, array('id'=>$id, 'delete_templ'=>$template->id, 'should_delete'=>1,));

            $data[] = $OUTPUT->single_button($url, $strdeleteapply, 'post');
            $tablecourse->add_data($data);
        }
        $tablecourse->finish_output();
        echo $OUTPUT->box_end();
    }
    //now we get the public templates if it is permitted
    $systemcontext = get_system_context();
    if (has_capability('mod/apply:createpublictemplate', $systemcontext) AND
        has_capability('mod/apply:delete_template', $systemcontext)) {
        $templates = apply_get_template_list($course, 'public');
        if (!is_array($templates)) {
            echo $OUTPUT->box(get_string('no_templates_available_yet', 'apply'), 'generalbox boxaligncenter');
        }
		else {
            echo $OUTPUT->heading(get_string('public', 'apply'), 3);
            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
            $tablecolumns = array('template', 'action');
            $tableheaders = array(get_string('template', 'apply'), '');
            $tablepublic = new flexible_table('apply_template_public_table');

            $tablepublic->define_columns($tablecolumns);
            $tablepublic->define_headers($tableheaders);
            $tablepublic->define_baseurl($delet_eurl);
            $tablepublic->column_style('action', 'width', '10%');

            $tablepublic->sortable(false);
            $tablepublic->set_attribute('width', '100%');
            $tablepublic->set_attribute('class', 'generaltable');
            $tablepublic->setup();

            foreach ($templates as $template) {
                $data = array();
                $data[] = $template->name;
                $url = new moodle_url($delete_url, array('id'=>$id, 'delete_templ'=>$template->id, 'should_delete'=>1,));

                $data[] = $OUTPUT->single_button($url, $strdeleteapply, 'post');
                $tablepublic->add_data($data);
            }
            $tablepublic->finish_output();
            echo $OUTPUT->box_end();
        }
    }

    echo $OUTPUT->box_start('boxaligncenter boxwidthnormal');
    $url = new moodle_url($delete_url, array('id'=>$id, 'cancel_delete'=>1,));

    echo $OUTPUT->single_button($url, get_string('back'), 'post');
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();


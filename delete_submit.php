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
 * prints the form to confirm the deleting of a submit
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once("../../config.php");
require_once("lib.php");
require_once('delete_submit_form.php');


$id 		= required_param('id', PARAM_INT);
$submit_id 	= optional_param('submit_id', 0, PARAM_INT);
$return 	= optional_param('return',  'entries', PARAM_ALPHA);
$courseid 	= optional_param('courseid', false, PARAM_INT);


if ($submit_id==0) {
    print_error('no_submit_to_delete', 'apply', 'show_entries.php?id='.$id.'&do_show=showentries');
}

$PAGE->set_url('/mod/apply/delete_submit.php', array('id'=>$id, 'submit_id'=>$submit_id));

if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array('id'=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array('id'=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/apply:deletesubmissions', $context);

//
$mform = new mod_apply_delete_submit_form();
$newformdata = array('id'=>$id,
                     'submit_id'=>$submit_id,
                     'confirmdelete'=>'1',
                     'do_show'=>'edit',
                     'return'=>$return);

$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
	redirect('show_entries.php?id='.$id.'&do_show=showentries');
}


if (isset($formdata->confirmdelete) AND $formdata->confirmdelete==1) {
    if ($submit = $DB->get_record('apply_submit', array('id'=>$submit_id))) {
        apply_delete_submit($submit_id);
        add_to_log($course->id, 'apply', 'delete', 'view.php?id='.$cm->id, $apply->id, $cm->id);
        redirect('show_entries.php?id='.$id.'&do_show=showentries');
    }
}


///////////////////////////////////////////////////////////////////////////
// Print the page header
$strapplys = get_string('modulename_plural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->navbar->add(get_string('delete_entry', 'apply'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


///////////////////////////////////////////////////////////////////////////
///Print the main part of the page
echo $OUTPUT->heading(format_text($apply->name));
echo $OUTPUT->box_start('generalbox errorboxcontent boxaligncenter boxwidthnormal');
echo $OUTPUT->heading(get_string('confirmdeleteentry', 'apply'));
$mform->display();
echo $OUTPUT->box_end();

echo $OUTPUT->footer();

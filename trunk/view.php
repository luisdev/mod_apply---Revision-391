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
 * the first page to view the apply
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/tablelib.php');

apply_init_session();

//
$id = required_param('id', PARAM_INT);
$courseid  = optional_param('courseid', false, PARAM_INT);
$perpage   = optional_param('perpage',   APPLY_DEFAULT_PAGE_COUNT, PARAM_INT);
$show_all  = optional_param('show_all',  0, PARAM_INT);
$do_show   = optional_param('do_show', 'view', PARAM_ALPHAEXT);
$submit_id = optional_param('submit_id', 0, PARAM_INT);

$current_tab = 'view';


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

$SESSION->apply->is_started = false;
$context = context_module::instance($cm->id);

$apply_submit_cap = false;
if (has_capability('mod/apply:submit', $context)) {
	$apply_submit_cap = true;
}

$name_pattern = $apply->name_pattern;
$req_own_data = true;

//
require_login($course, true, $cm);
add_to_log($course->id, 'apply', 'view', 'view.php?id='.$cm->id, $apply->id, $cm->id);


///////////////////////////////////////////////////////////////////////////
// Print the page header

$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$url = new moodle_url('/mod/apply/view.php', array('id'=>$cm->id, 'do_show'=>'view'));
$PAGE->set_url($url);
$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

require('tabs.php');

//
$cap_viewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context);
if ((empty($cm->visible) and !$cap_viewhiddenactivities)) {
	notice(get_string('activityiscurrentlyhidden'));
}
if ((empty($cm->visible) and !$cap_viewhiddenactivities)) {
	notice(get_string('activityiscurrentlyhidden'));
}


///////////////////////////////////////////////////////////////////////////
// Print the main part of the page

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = '<a href="'.$CFG->wwwroot.'/mod/apply/print.php?id='.$id.'">'.$previewimg.'</a>';

echo $OUTPUT->heading(format_text($apply->name.' '.$previewlnk));

//show some infos to the apply
echo $OUTPUT->heading(get_string('description', 'apply'), 4);
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo format_module_intro('apply', $apply, $cm->id);

require('view_info.php');

echo $OUTPUT->box_end();



///////////////////////////////////////////////////////////////////////////
// Check
if (!$apply_submit_cap) {
	apply_print_error_messagebox('apply_is_disable', $courseid);
	exit;
}

$continue_link = $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$courseid);
//
$apply_can_submit = true;
if (!$apply->multiple_submit) {
	if (apply_get_valid_submits_count($apply->id, $USER->id)>0) {
		$apply_can_submit = false;
		apply_print_messagebox('apply_is_already_submitted', $continue_link);
	}
}

// Date
if ($apply_can_submit) {
	$checktime = time();
	$apply_is_not_open =  $apply->time_open>$checktime;
	$apply_is_closed   = ($apply->time_close<$checktime and $apply->time_close>0);
	if ($apply_is_not_open or $apply_is_closed) {
		if ($apply_is_not_open) apply_print_messagebox('apply_is_not_open', $continue_link);
		else					apply_print_messagebox('apply_is_closed',   $continue_link);
		$apply_can_submit = false;
	}
}

//
if ($apply_can_submit) {
	$submit_file = 'submit.php';
	$url_params  = array('id'=>$id, 'courseid'=>$courseid, 'go_page'=>0);
	$submit_url  = new moodle_url('/mod/apply/'.$submit_file, $url_params);
	$submit_link = '<div align="center">'.$OUTPUT->single_button($submit_url->out(), get_string('submit_form_button', 'apply')).'</div>';
	apply_print_messagebox('submit_new_apply', $submit_link, 'green');
}


///////////////////////////////////////////////////////////////////////////
//
if ($do_show=='view') {
	$submits = apply_get_all_submits($apply->id, $USER->id);
	if ($submits) {
		//
		$baseurl = new moodle_url('/mod/apply/view.php');
		$baseurl->params(array('id'=>$id, 'courseid'=>$courseid));
		$table = new flexible_table('apply-view-list-'.$courseid);
		$matchcount = apply_get_valid_submits_count($cm->instance);
		//
		require('show_entry_header.php');

		echo '<br />';
		echo $OUTPUT->heading(get_string('entries_list_title', 'apply'), 2);

		///////////////////////////////////////////////////////////////////////
		//
		echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
		echo $OUTPUT->box_start('mdl-align');

		////////////////////////////////////////////////////////////
		// Submits Data
		$submits = apply_get_submits_select($apply->id, $USER->id, $where, $params, $sort, $start_page, $page_count);

		foreach ($submits as $submit) {
			$student = apply_get_user_info($submit->user_id);
			if ($student) {
				$data = array();
				//
				require('show_entry_record.php');
				if (!empty($data)) $table->add_data($data);
			}
		}
		$table->print_html();

		$allurl = new moodle_url($baseurl);
		if ($show_all) {
			$allurl->param('show_all', 0);
			echo $OUTPUT->container(html_writer::link($allurl, get_string('show_perpage', 'apply', APPLY_DEFAULT_PAGE_COUNT)), array(), 'show_all');
		}
		else if ($matchcount>0 && $perpage<$matchcount) {
			$allurl->param('show_all', 1);
			echo $OUTPUT->container(html_writer::link($allurl, get_string('show_all', 'apply', $matchcount)), array(), 'show_all');
		}

		echo $OUTPUT->box_end();
		echo $OUTPUT->box_end();
	}
}


///////////////////////////////////////////////////////////////////////////
//
if ($do_show=='show_one_entry' and $submit_id) {
	$params = array('apply_id'=>$apply->id, 'user_id'=>$USER->id, 'id'=>$submit_id);
	$submit = $DB->get_record('apply_submit', $params);

	echo '<br />';
	if ($submit) {
		$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
		if (is_array($items)) require('show_entry_data.php');
	}
	else {
		echo $OUTPUT->heading(get_string('not_submit_data', 'apply'), 3);
	}
	echo $OUTPUT->continue_button(new moodle_url($url, array('do_show'=>'view')));
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();


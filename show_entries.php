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
 * print the single entries
 *
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

require_once('../../config.php');
require_once('lib.php');
require_once($CFG->libdir.'/tablelib.php');


////////////////////////////////////////////////////////
//get the params
$id		  = required_param('id', PARAM_INT);
$do_show  = required_param('do_show',  PARAM_ALPHAEXT);
$user_id  = optional_param('user_id',  0, PARAM_INT);
$perpage  = optional_param('perpage',  APPLY_DEFAULT_PAGE_COUNT, PARAM_INT);  // how many per page
$show_all = optional_param('show_all', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

$current_tab = $do_show;


////////////////////////////////////////////////////////
//get the objects
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

$url = new moodle_url('/mod/apply/show_entries.php', array('id'=>$cm->id, 'do_show'=>$do_show));
$PAGE->set_url($url);

$context = context_module::instance($cm->id);


////////////////////////////////////////////////////////
// Check
require_login($course, true, $cm);

$formdata = data_submitted();
if ($formdata) {
	if (!confirm_sesskey()) {
		print_error('invalidsesskey');
	}
	if ($user_id) {
		$formdata->user_id = intval($user_id);
	}
}

require_capability('mod/apply:viewreports', $context);


////////////////////////////////////////////////////////
/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');


///////////////////////////////////////////////////////////////////////////
// Print the main part of the page

if ($do_show=='show_entries') {
	//
	if (has_capability('mod/apply:viewreports', $context)) {
		////////////////////////////////////////////////////////////
		// Setup Table
		$baseurl = new moodle_url('/mod/apply/show_entries.php');
		$baseurl->params(array('id'=>$id, 'do_show'=>$do_show, 'show_all'=>$show_all));
		$name_pattern  = $apply->name_pattern;
		$table_columns = array('userpic', $name_pattern, 'title', 'time_modified', 'version', 'class', 'acked', 'execed', '');

		$title_pic  = get_string('user_pic',	 'apply');
		$title_name = get_string($name_pattern);
		$title_ttl  = get_string('title_title',	 'apply');
		$title_date = get_string('date');
		$title_ver  = get_string('title_version','apply');
		$title_clss = get_string('title_class',  'apply');
		$title_ack  = get_string('title_ack',	 'apply');
		$title_exec = get_string('title_exec',   'apply');
		$title_chk  = get_string('title_check',	 'apply');
		$table_headers = array($title_pic, $title_name, $title_ttl, $title_date, $title_ver, $title_clss, $title_ack, $title_exec, $title_chk, '');

		// 管理者
		if (has_capability('mod/apply:deletesubmissions', $context)) {
			$table_columns[] = 'delete_entry';
			$table_headers[] = '';
		}

		$table = new flexible_table('apply-show_entry-list-'.$course->id);

		$table->define_columns($table_columns);
		$table->define_headers($table_headers);
		$table->define_baseurl($baseurl);

		$table->sortable(true, 'lastname', SORT_DESC);
		$table->set_attribute('cellspacing', '0');
		$table->set_attribute('id', 'show_entrytable');
		$table->set_attribute('class', 'generaltable generalbox');
		$table->set_control_variables(array(
					TABLE_VAR_SORT  => 'ssort',
					TABLE_VAR_IFIRST=> 'sifirst',
					TABLE_VAR_ILAST => 'silast',
					TABLE_VAR_PAGE	=> 'spage'
					));
		$table->setup();

		//
		$sort = $table->get_sql_sort();
		if (!$sort) $sort = '';

		list($where, $params) = $table->get_sql_where();
		if ($where) $where .= ' AND';

		if ($name_pattern=='firstname') {
			$sifirst = optional_param('sifirst', '', PARAM_ALPHA);
			if ($sifirst) {
				$where .= "firstname LIKE :sifirst ESCAPE '\\\\' AND";
				$params['sifirst'] =  $sifirst.'%';
			}
		}
		if ($name_pattern=='lastname') {
			$silast  = optional_param('silast',  '', PARAM_ALPHA);
			if ($silast) {
				$where .= "lastname LIKE :silast ESCAPE '\\\\' AND";
				$params['silast'] =  $silast.'%';
			}
		}

		//
		$table->initialbars(true);

		if ($show_all) {
			$start_page = false;
			$page_count = false;
		}
		else {
			$matchcount = apply_get_valid_submits_count($cm->instance);
			$table->pagesize($perpage, $matchcount);
			$start_page = $table->get_page_start();
			$page_count = $table->get_page_size();
		}
		//
		echo $OUTPUT->box_start('mdl-align');
		echo '<h2>'.$apply->name.'</h2>';
		echo $OUTPUT->box_end();


		////////////////////////////////////////////////////////////
		// Print List of Students
		echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
		echo $OUTPUT->box_start('mdl-align');

		if ($name_pattern=='firstname') {
			apply_print_initials_bar($table, true, false);
			if ($show_all) echo '<br />';
		}
		else if ($name_pattern=='lastname') {
			apply_print_initials_bar($table, false, true);
			if ($show_all) echo '<br />';
		}


		////////////////////////////////////////////////////////////
		// User Data
		$submits = apply_get_submits_select($apply->id, $where, $params, $sort, $start_page, $page_count);

		if (!$submits) {
			$table->print_html();
		} 
		else {
			//
			foreach ($submits as $submit) {
				$student = apply_get_user_info($submit->user_id);
				if ($student) {
					if 		($name_pattern=='firstname') $user_name = $student->firstname;
					else if ($name_pattern=='lastname')  $user_name = $student->lastname;
					else								 $user_name = fullname($student); 
					//
					$user_url  = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
					$prof_link = '<strong><a href="'.$user_url.'">'.$user_name.'</a></strong>';
					$acked_url = $CFG->wwwroot.'/user/view.php?id='.$submit->acked_user.'&amp;course='.$courseid;
					$entry_url = new moodle_url($url, array('user_id'=>$student->id, 'do_show'=>'show_one_entry'));

					$data = array();
					//
					$data[] = $OUTPUT->user_picture($student, array('courseid'=>$courseid));
					$data[] = $prof_link;
					//
					$title = $submit->title;
					if ($title=='') $title = get_string('no_title', 'apply');
					$entry_link = '<a href="'.$entry_url->out().'">'.$title.'</a>';
					$data[] = $entry_link;
					//
					$mod_time = userdate($submit->time_modified, '%Y/%m/%d %H:%M');
					$entry_link = '<a href="'.$entry_url->out().'">'.$mod_time.'</a>';
					$data[] = $entry_link;
					//
					$data[] = $submit->version;
					//
					if 		($submit->class==APPLY_CLASS_DRAFT)  $class = get_string('class_draft',   'apply');
					else if ($submit->class==APPLY_CLASS_NEW)    $class = get_string('class_newpost', 'apply');
					else if ($submit->class==APPLY_CLASS_UPDATE) $class = get_string('class_update',  'apply');
					else if ($submit->class==APPLY_CLASS_CANCEL) $class = get_string('class_cancel',  'apply');
					$data[] = $class;
					//
					if 		($submit->acked==APPLY_ACKED_NOTYET) $acked = get_string('acked_notyet',  'apply');
					else if ($submit->acked==APPLY_ACKED_ACCEPT) $acked = get_string('acked_accept', 'apply');
					else if ($submit->acked==APPLY_ACKED_REJECT) $acked = get_string('acked_reject', 'apply');
					if ($submit->acked!=APPLY_ACKED_NOTYET) {
						$acked = '<a href="'.$acked_url->out().'">'.$acked.'</a>';
					}
					$data[] = $acked;
					//
					if ($submit->execed) $execed = get_string('execed_done',   'apply');
					else 				 $execed = get_string('execed_notyet', 'apply');
					$data[] = $execed;
					//
					$data[] = '';

					//link to delete the entry
					if (has_capability('mod/apply:deletesubmissions', $context)) {
						$delete_url_params = array('id'=>$cm->id, 'submit_id'=>$submit->id, 'do_show'=>'show_one_entry');
						$deleteentry_url = new moodle_url($CFG->wwwroot.'/mod/apply/delete_submit.php', $delete_url_params);
						$deleteentry_link = '<a href="'.$deleteentry_url->out().'">'.get_string('delete_entry', 'apply').'</a>';
						$data[] = $deleteentry_link;
					}

					$table->add_data($data);
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
		}

		echo $OUTPUT->box_end();
		echo $OUTPUT->box_end();
	}
}


////////////////////////////////////////////////////////
/// Print the responses of the given user
//get the responses of given user
if ($do_show=='show_one_entry') {
	$params = array('apply_id'=>$apply->id, 'user_id'=>$user_id);
	$apply_submits = $DB->get_records('apply_submit', $params); 
	$apply_items   = $DB->get_records('apply_item', array('apply_id'=>$apply->id), 'position');
}

if ($do_show=='show_one_entry') {
	echo $OUTPUT->heading(format_text($apply->name));

	//print the items
	if (is_array($apply_items)) {
		$align = right_to_left() ? 'right' : 'left';
		$usr = $DB->get_record('user', array('id'=>$user_id));

		if ($applycompleted) {
			echo $OUTPUT->heading(userdate($applycompleted->time_modified).' ('.fullname($usr).')', 3);
		}
		else {
			echo $OUTPUT->heading(get_string('not_completed_yet', 'apply'), 3);
		}

		echo $OUTPUT->box_start('apply_items');
		$itemnr = 0;
		foreach ($apply_items as $apply_item) {
			//get the values
			$params = array('submit_id'=>$applycompleted->id, 'item_id'=>$apply_item->id);
			$value = $DB->get_record('apply_value', $params);
			echo $OUTPUT->box_start('apply_item_box_'.$align);
			if ($apply_item->hasvalue==1) {
				$itemnr++;
				echo $OUTPUT->box_start('apply_item_number_'.$align);
				echo $itemnr;
				echo $OUTPUT->box_end();
			}

			if ($apply_item->typ != 'pagebreak') {
				echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
				if (isset($value->value)) {
					apply_print_item_show_value($apply_item, $value->value);
				}
				else {
					apply_print_item_show_value($apply_item, false);
				}
				echo $OUTPUT->box_end();
			}
			echo $OUTPUT->box_end();
		}
		echo $OUTPUT->box_end();
	}
	echo $OUTPUT->continue_button(new moodle_url($url, array('do_show'=>'show_entries')));
}


///////////////////////////////////////////////////////////////////////////
/// Finish the page
echo $OUTPUT->footer();


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
 * print a printview of apply-items
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);
$courseid   = optional_param('courseid', false, PARAM_INT);
$submit_id  = optional_param('submit_id', 0, PARAM_INT);
$submit_ver = optional_param('submit_ver', -1, PARAM_INT);
$prv_action = optional_param('action', 'view', PARAM_ALPHAEXT);

//
if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}
if (! $course = $DB->get_record('course', array("id"=>$cm->course))) {
    print_error('coursemisconf');
}
if (! $apply = $DB->get_record('apply', array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}
if (!$courseid) $courseid = $course->id;

$context = context_module::instance($cm->id);
$name_pattern = $apply->name_pattern;


require_login($course, true, $cm);
require_capability('mod/apply:view', $context);


$PAGE->set_url('/mod/apply/print.php', array('id'=>$id));
$PAGE->set_pagelayout('embedded');

$back_params = array('id'=>$id, 'courseid'=>$courseid, 'do_show'=>'view_one_entry', 'submit_id'=>$submit_id, 'submit_ver'=>$submit_ver);
if ($do_show=='view_one_entry') {
	$back_url = new moodle_url($CFG->wwwroot.'/mod/apply/view_entries.php', $back_params);
}
else {
	$back_url = new moodle_url($CFG->wwwroot.'/mod/apply/view.php', $back_params);
}






/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$apply_url = new moodle_url('/mod/apply/index.php', array('id'=>$course->id));
$PAGE->navbar->add($strapplys, $apply_url);
$PAGE->navbar->add(format_string($apply->name));

$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_text($apply->name));
echo '<br />';

$submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
if ($submit) {
	$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id), 'position');
	if (is_array($items)) {
		if ($submit_ver==-1 and apply_exist_draft_values($submit->id)) $submit_ver = 0;
		require('entry_view.php');
	}
}







///////////////////////////////////////////////////////////////////////////
/// Print the main part of the page

$applyitems = $DB->get_records('apply_item', array('apply_id'=>$apply->id), 'position');
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo $OUTPUT->continue_button('view.php?id='.$id);
if (is_array($applyitems)) {
    $itemnr = 0;
    $align = right_to_left() ? 'right' : 'left';

    echo $OUTPUT->box_start('apply_items printview');
    //check, if there exists required-elements
    $params = array('apply_id'=>$apply->id, 'required'=>1);
    $countreq = $DB->count_records('apply_item', $params);
    if ($countreq > 0) {
        echo '<span class="apply_required_mark">(*)';
        echo get_string('items_are_required', 'apply');
        echo '</span>';
    }
    //print the inserted items
    $itempos = 0;
    foreach ($applyitems as $applyitem) {
        echo $OUTPUT->box_start('apply_item_box_'.$align);
        $itempos++;
        //Items without value only are labels
        if ($applyitem->hasvalue==1) {
            $itemnr++;
                echo $OUTPUT->box_start('apply_item_number_'.$align);
                echo $itemnr;
                echo $OUTPUT->box_end();
        }
        echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
        if ($applyitem->typ != 'pagebreak') {
            apply_print_item_submit($applyitem, false, false);
        }
		else {
            echo $OUTPUT->box_start('apply_pagebreak');
            echo '<hr class="apply_pagebreak" />';
            echo $OUTPUT->box_end();
        }
        echo $OUTPUT->box_end();
        echo $OUTPUT->box_end();
    }
    echo $OUTPUT->box_end();
} 
else {
    echo $OUTPUT->box(get_string('no_items_available_yet', 'apply'), 'generalbox boxaligncenter boxwidthwide');
}
echo $OUTPUT->continue_button('view.php?id='.$id);
echo $OUTPUT->box_end();


///////////////////////////////////////////////////////////////////////////
/// Finish the page

echo $OUTPUT->footer();


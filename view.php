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

//
$id = required_param('id', PARAM_INT);
$courseid = optional_param('courseid', false, PARAM_INT);

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

$context = context_module::instance($cm->id);

$apply_complete_cap = false;
if (has_capability('mod/apply:complete', $context)) {
    $apply_complete_cap = true;
}

//
require_login($course, true, $cm);
add_to_log($course->id, 'apply', 'view', 'view.php?id='.$cm->id, $apply->id, $cm->id);


///////////////////////////////////////////////////////////////////////////
// Print the page header

$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

$PAGE->set_url('/mod/apply/view.php', array('id'=>$cm->id, 'do_show'=>'view'));
$PAGE->set_title(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();


//ishidden check.
//apply in courses
$cap_viewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context);
if ((empty($cm->visible) and !$cap_viewhiddenactivities)) {
    notice(get_string("activityiscurrentlyhidden"));
}

//ishidden check.
//apply on mainsite
if ((empty($cm->visible) and !$cap_viewhiddenactivities)) {
    notice(get_string("activityiscurrentlyhidden"));
}


///////////////////////////////////////////////////////////////////////////
// Print the main part of the page

/// print the tabs
require('tabs.php');

$previewimg = $OUTPUT->pix_icon('t/preview', get_string('preview'));
$previewlnk = '<a href="'.$CFG->wwwroot.'/mod/apply/print.php?id='.$id.'">'.$previewimg.'</a>';

echo $OUTPUT->heading(format_text($apply->name.' '.$previewlnk));

//show some infos to the apply
if (has_capability('mod/apply:edititems', $context)) {
    //get the groupid
    $groupselect = groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id, true);
    $mygroupid = groups_get_activity_group($cm);

    echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
    echo $groupselect.'<div class="clearer">&nbsp;</div>';
    $submitscount = apply_get_submits_group_count($apply, $mygroupid);

    echo $OUTPUT->box_start('apply_info');
    echo '<span class="apply_info">';
    echo get_string('submited_applys', 'apply').': ';
    echo '</span>';
    echo '<span class="apply_info_value">';
    echo $submitscount;
    echo '</span>';
    echo $OUTPUT->box_end();

    $params = array('apply_id'=>$apply->id, 'hasvalue'=>1);
    $itemscount = $DB->count_records('apply_item', $params);

    echo $OUTPUT->box_start('apply_info');
    echo '<span class="apply_info">';
    echo get_string('questions', 'apply').': ';
    echo '</span>';
    echo '<span class="apply_info_value">';
    echo $itemscount;
    echo '</span>';
    echo $OUTPUT->box_end();

    if ($apply->time_open) {
        echo $OUTPUT->box_start('apply_info');
        echo '<span class="apply_info">';
        echo get_string('time_open', 'apply').': ';
        echo '</span>';
        echo '<span class="apply_info_value">';
        echo userdate($apply->time_open);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
    if ($apply->time_close) {
        echo $OUTPUT->box_start('apply_info');
        echo '<span class="apply_info">';
        echo get_string('time_close', 'apply').': ';
        echo '</span>';
        echo '<span class="apply_info_value">';
        echo userdate($apply->time_close);
        echo '</span>';
        echo $OUTPUT->box_end();
    }
    echo $OUTPUT->box_end();
}

if (has_capability('mod/apply:edititems', $context)) {
    echo $OUTPUT->heading(get_string('description', 'apply'), 4);
}

echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
$options = (object)array('noclean'=>true);
echo format_module_intro('apply', $apply, $cm->id);
echo $OUTPUT->box_end();



//####### completed-start
if ($apply_complete_cap) {
    echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
    //check, whether the apply is open (time_open, time_close)
    $checktime = time();
    if (($apply->time_open > $checktime) OR
            ($apply->time_close < $checktime AND $apply->time_close > 0)) {

        echo '<h2><font color="red">'.get_string('apply_is_not_open', 'apply').'</font></h2>';
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        exit;
    }

    //check multiple Submit
    $apply_can_submit = true;
    if ($apply->multiple_submit == 0 ) {
        if (apply_is_already_submitted($apply->id, $courseid)) {
            $apply_can_submit = false;
        }
    }
    if ($apply_can_submit) {
        //if the user is not known so we cannot save the values temporarly
        $completefile = 'submit.php';
//        $guestid = false;

        $url_params = array('id'=>$id, 'courseid'=>$courseid, 'gopage'=>0);
        $completeurl = new moodle_url('/mod/apply/'.$completefile, $url_params);

//        $applycompletedtmp = apply_get_current_completed($apply->id, true, $courseid, $guestid);
//        if ($applycompletedtmp) {
//            if ($startpage = apply_get_page_to_continue($apply->id, $courseid, $guestid)) {
//                $completeurl->param('gopage', $startpage);
//            }
//            echo '<a href="'.$completeurl->out().'">'.get_string('continue_the_form', 'apply').'</a>';
//        } else {
            echo '<a href="'.$completeurl->out().'">'.get_string('complete_the_form', 'apply').'</a>';
//        }
    }
	else {
        echo '<h2><font color="red">';
        echo get_string('this_apply_is_already_submitted', 'apply');
        echo '</font></h2>';
        if ($courseid) {
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$courseid);
        } else {
            echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
        }
    }
    echo $OUTPUT->box_end();
}
//####### completed-end


/// Finish the page
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->footer();


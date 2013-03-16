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
 * print the confirm dialog to use template and create new items from template
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once("../../config.php");
require_once("lib.php");
require_once('use_templ_form.php');

$id = required_param('id', PARAM_INT);
$templateid = optional_param('templateid', false, PARAM_INT);
$deleteolditems = optional_param('deleteolditems', 0, PARAM_INT);

if (!$templateid) {
    redirect('edit.php?id='.$id);
}

$url = new moodle_url('/mod/apply/use_templ.php', array('id'=>$id, 'templateid'=>$templateid));
if ($deleteolditems !== 0) {
    $url->param('deleteolditems', $deleteolditems);
}
$PAGE->set_url($url);

if (! $cm = get_coursemodule_from_id('apply', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $apply = $DB->get_record("apply", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/apply:edititems', $context);

$mform = new mod_apply_use_templ_form();
$newformdata = array('id'=>$id,
                    'templateid'=>$templateid,
                    'confirmadd'=>'1',
                    'deleteolditems'=>'1',
                    'do_show'=>'edit');
$mform->set_data($newformdata);
$formdata = $mform->get_data();

if ($mform->is_cancelled()) {
    redirect('edit.php?id='.$id.'&do_show=templates');
}

if (isset($formdata->confirmadd) AND $formdata->confirmadd == 1) {
    apply_items_from_template($apply, $templateid, $deleteolditems);
    redirect('edit.php?id=' . $id);
}

/// Print the page header
$strapplys = get_string("modulenameplural", "apply");
$strapply  = get_string("modulename", "apply");

$PAGE->navbar->add($strapplys, new moodle_url('/mod/apply/index.php', array('id'=>$course->id)));
$PAGE->navbar->add(format_string($apply->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

/// Print the main part of the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
echo $OUTPUT->heading(format_text($apply->name));

echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthnormal');
echo $OUTPUT->heading(get_string('confirmusetemplate', 'apply'));

$mform->display();

echo $OUTPUT->box_end();

$templateitems = $DB->get_records('apply_item', array('template'=>$templateid), 'position');
if (is_array($templateitems)) {
    $templateitems = array_values($templateitems);
}

if (is_array($templateitems)) {
    $itemnr = 0;
    $align = right_to_left() ? 'right' : 'left';
    echo $OUTPUT->box_start('apply_items');
    foreach ($templateitems as $templateitem) {
        echo $OUTPUT->box_start('apply_item_box_'.$align);
        if ($templateitem->hasvalue == 1 AND $apply->autonumbering) {
            $itemnr++;
            echo $OUTPUT->box_start('apply_item_number_'.$align);
            echo $itemnr;
            echo $OUTPUT->box_end();
        }
        echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
        if ($templateitem->typ != 'pagebreak') {
            // echo '<div class="apply_item_'.$align.'">';
            apply_print_item_preview($templateitem);
        } else {
            echo $OUTPUT->box_start('apply_pagebreak');
            echo get_string('pagebreak', 'apply').'<hr class="apply_pagebreak" />';
            echo $OUTPUT->box_end();
        }
        echo $OUTPUT->box_end();
        echo $OUTPUT->box_end();
    }
    echo $OUTPUT->box_end();
} else {
    echo $OUTPUT->box(get_string('no_items_available_at_this_template', 'apply'),
                    'generalbox boxaligncenter boxwidthwide');
}

echo $OUTPUT->footer();


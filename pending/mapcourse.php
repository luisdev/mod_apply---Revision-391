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
 * print the form to map courses for global applys
 *
 * @author Andreas Grabs
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package apply
 */

require_once("../../config.php");
require_once("lib.php");
require_once("$CFG->libdir/tablelib.php");


$id 		  = required_param('id', PARAM_INT); // Course Module ID, or
$searchcourse = optional_param('searchcourse', '', PARAM_NOTAGS);
$coursefilter = optional_param('coursefilter', '', PARAM_INT);
$courseid 	  = optional_param('courseid', false, PARAM_INT);

$url = new moodle_url('/mod/apply/mapcourse.php', array('id'=>$id));
if ($searchcourse !== '') {
    $url->param('searchcourse', $searchcourse);
}
if ($coursefilter !== '') {
    $url->param('coursefilter', $coursefilter);
}
if ($courseid !== false) {
    $url->param('courseid', $courseid);
}
$PAGE->set_url($url);

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}

$current_tab = 'mapcourse';

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

require_capability('mod/apply:mapcourse', $context);

if ($coursefilter) {
    $map->applyid = $apply->id;
    $map->courseid = $coursefilter;
    // insert a map only if it does exists yet
    $sql = "SELECT id, applyid
              FROM {apply_sitecourse_map}
             WHERE applyid = ? AND courseid = ?";
    if (!$DB->get_records_sql($sql, array($map->applyid, $map->courseid))) {
        $DB->insert_record('apply_sitecourse_map', $map);
    }
}

/// Print the page header
$strapplys = get_string("modulenameplural", "apply");
$strapply  = get_string("modulename", "apply");

$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();

require('tabs.php');

echo $OUTPUT->box(get_string('mapcourseinfo', 'apply'), 'generalbox boxaligncenter boxwidthwide');
echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
echo '<form method="post">';
echo '<input type="hidden" name="id" value="'.$id.'" />';
echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';

$sql = "select c.id, c.shortname
          from {course} c
         where ".$DB->sql_like('c.shortname', '?', false)."
               OR ".$DB->sql_like('c.fullname', '?', false);
$params = array("%{$searchcourse}%", "%{$searchcourse}%");

if (($courses = $DB->get_records_sql_menu($sql, $params)) && !empty($searchcourse)) {
    echo ' '. html_writer::label(get_string('courses'), 'menucoursefilter', false). ': ';
    echo html_writer::select($courses, 'coursefilter', $coursefilter);
    echo '<input type="submit" value="'.get_string('mapcourse', 'apply').'"/>';
    echo $OUTPUT->help_icon('mapcourses', 'apply');
    echo '<input type="button" '.
                'value="'.get_string('searchagain').'" '.
                'onclick="document.location=\'mapcourse.php?id='.$id.'\'"/>';

    echo '<input type="hidden" name="searchcourse" value="'.$searchcourse.'"/>';
    echo '<input type="hidden" name="applyid" value="'.$apply->id.'"/>';
    echo $OUTPUT->help_icon('searchcourses', 'apply');
} else {
    echo '<input type="text" name="searchcourse" value="'.$searchcourse.'"/> ';
    echo '<input type="submit" value="'.get_string('searchcourses').'"/>';
    echo $OUTPUT->help_icon('searchcourses', 'apply');
}

echo '</form>';

if ($coursemap = apply_get_courses_from_sitecourse_map($apply->id)) {
    $table = new flexible_table('coursemaps');
    $table->define_columns( array('course'));
    $table->define_headers( array(get_string('mappedcourses', 'apply')));

    $table->setup();

    $unmapurl = new moodle_url('/mod/apply/unmapcourse.php');
    foreach ($coursemap as $cmap) {
        $cmapcontext = context_course::instance($cmap->id);
        $cmapshortname = format_string($cmap->shortname, true, array('context' => $cmapcontext));
        $coursecontext = context_course::instance($cmap->courseid);
        $cmapfullname = format_string($cmap->fullname, true, array('context' => $coursecontext));
        $unmapurl->params(array('id'=>$id, 'cmapid'=>$cmap->id));
        $anker  = '<a href="'.$unmapurl->out().'">';
        $anker .= '<img src="'.$OUTPUT->pix_url('t/delete').'" alt="Delete" />';
        $anker .= '</a>';
        $table->add_data(array($anker.' ('.$cmapshortname.') '.$cmapfullname));
    }

    $table->print_html();
} else {
    echo '<h3>'.get_string('mapcoursenone', 'apply').'</h3>';
}


echo $OUTPUT->box_end();

echo $OUTPUT->footer();

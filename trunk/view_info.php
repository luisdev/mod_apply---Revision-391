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
 * diplay info header
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
 */

defined('MOODLE_INTERNAL') OR die('not allowed');

//$groupselect = groups_print_activity_menu($cm, $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id, true);
//$mygroupid   = groups_get_activity_group($cm);

echo $OUTPUT->box_start('boxaligncenter boxwidthwide');
{
	$submitscount = apply_get_all_submits_count($apply->id);
//	$submitscount = apply_get_valid_submits_count($apply->id);

	echo $OUTPUT->box_start('apply_info');
	echo '<span class="apply_info">';
	echo get_string('submitted_applys', 'apply').': ';
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
}
echo $OUTPUT->box_end();

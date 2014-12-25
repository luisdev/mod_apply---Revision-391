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
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_apply/lib.php that by Andreas Grabs)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/eventslib.php');
require_once($CFG->dirroot.'/calendar/lib.php');



function apply_supports($feature)
{
	switch($feature) {
		case FEATURE_GROUPS:				  return false;
		case FEATURE_GROUPINGS:				  return false;
		case FEATURE_GROUPMEMBERSONLY:		  return false;
		case FEATURE_MOD_INTRO:				  return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
		case FEATURE_COMPLETION_HAS_RULES:	  return false;
		case FEATURE_GRADE_HAS_GRADE:		  return false;
		case FEATURE_GRADE_OUTCOMES:		  return false;
		case FEATURE_BACKUP_MOODLE2:		  return false;	// Backup at Settings (Admin block)
		case FEATURE_SHOW_DESCRIPTION:		  return true;

		default: return null;
	}
}


function apply_add_instance($apply)
{
	global $DB;

	$apply->time_modified = time();
	$apply->id = '';

	if (empty($apply->open_enable)) {
		$apply->time_open = 0;
	}
	if (empty($apply->close_enable)) {
		$apply->time_close = 0;
	}

	$apply_id = $DB->insert_record('apply', $apply);
	$apply->id = $apply_id;

	// Calendar
	apply_set_events($apply);

	if (!isset($apply->coursemodule)) {
		$cm = get_coursemodule_from_id('apply', $apply->id);
		$apply->coursemodule = $cm->id;
	}

	$DB->update_record('apply', $apply);

	return $apply_id;
}


function apply_update_instance($apply)
{
	global $DB;

	$apply->time_modified = time();
	$apply->id = $apply->instance;

	if (empty($apply->open_enable)) {
		$apply->time_open = 0;
	}
	if (empty($apply->close_enable)) {
		$apply->time_close = 0;
	}

	apply_set_events($apply);

	$DB->update_record('apply', $apply);

	return true;
}


function apply_delete_instance($apply_id) 
{
	global $DB;

	$apply_items = $DB->get_records('apply_item', array('apply_id'=>$apply_id));

	if (is_array($apply_items)) {
		foreach ($apply_items as $apply_item) {
			$DB->delete_records('apply_value', array('item_id'=>$apply_item->id));
		}
		if ($del_items = $DB->get_records('apply_item', array('apply_id'=>$apply_id))) {
			foreach ($del_items as $del_item) {
				apply_delete_item($del_item->id, false);
			}
		}
	}

	$ret = $DB->delete_records('apply_submit', array('apply_id'=>$apply_id));
	if ($ret) $ret = $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$apply_id));
	if ($ret) $ret = $DB->delete_records('apply', array('id'=>$apply_id));

	return $ret;
}


function apply_user_complete($course, $user, $mod, $apply)
{
	return false;
}


function apply_user_outline($course, $user, $mod, $apply)
{
	return null;
}


function appply_cron()
{
	return true;
}


function apply_print_recent_activity($course, $viewfullnames, $timestart)
{
	return false;
}


function apply_get_view_actions() 
{
	return array('view', 'view all');
}


function apply_get_post_actions() 
{
	return array('submit');
}


function apply_reset_userdata($data) 
{
	global $CFG, $DB;

	$resetapplys= array();
	$dropapplys	= array();
	$status 	= array();

	$componentstr = get_string('modulenameplural', 'apply');

	foreach ($data as $key=>$value) {
		switch(true) {
			case substr($key, 0, strlen(APPLY_RESETFORM_RESET))==APPLY_RESETFORM_RESET:
				if ($value==1) {
					$templist = explode('_', $key);
					if (isset($templist[3])) {
						$resetapplys[] = intval($templist[3]);
					}
				}
				break;
		  	case substr($key, 0, strlen(APPLY_RESETFORM_DROP))==APPLY_RESETFORM_DROP:
				if ($value==1) {
					$templist = explode('_', $key);
					if (isset($templist[3])) {
						$dropapplys[] = intval($templist[3]);
					}
				}
				break;
		}
	}

	foreach ($resetapplys as $id) {
		$apply = $DB->get_record('apply', array('id'=>$id));
		apply_delete_all_submit($id);
		$status[] = array('component'=>$componentstr.':'.$apply->name, 'item'=>get_string('resetting_data', 'apply'), 'error'=>false);
	}

	return $status;
}


function apply_set_events($apply)
{
    global $DB;

    $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$apply->id));

    if (!$apply->use_calendar) return;

    if (!isset($apply->coursemodule)) {
        $cm = get_coursemodule_from_id('apply', $apply->id);
        $apply->coursemodule = $cm->id;
    }

    // the open-event
    if ($apply->time_open>0) {
        $event = new stdClass();
        $event->name        = get_string('start', 'apply').' '.$apply->name;
        $event->description = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid    = $apply->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'apply';
        $event->instance    = $apply->id;
        $event->eventtype   = 'open';
        $event->timestart   = $apply->time_open;
        $event->visible     = instance_is_visible('apply', $apply);
        if ($apply->time_close>0) {
            $event->timeduration = ($apply->time_close - $apply->time_open);
        } else {
            $event->timeduration = 0;
        }

        calendar_event::create($event);
    }

    // the close-event
    if ($apply->time_close>0) {
        $event = new stdClass();
        $event->name        = get_string('stop', 'apply').' '.$apply->name;
        $event->description = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid    = $apply->course;
        $event->groupid     = 0;
        $event->userid      = 0;
        $event->modulename  = 'apply';
        $event->instance    = $apply->id;
        $event->eventtype   = 'close';
        $event->timestart   = $apply->time_close;
        $event->visible     = instance_is_visible('apply', $apply);
        $event->timeduration = 0;

        calendar_event::create($event);
    }
}

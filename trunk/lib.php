<?php

/**
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_apply/lib.php that by Andreas Grabs)
 */


defined('MOODLE_INTERNAL') || die;


/** Include eventslib.php */
//require_once($CFG->libdir.'/eventslib.php');
/** Include calendar/lib.php */
//require_once($CFG->dirroot.'/calendar/lib.php');


define('APPLY_DECIMAL', '.');
define('APPLY_THOUSAND', ',');
define('APPLY_RESETFORM_RESET', 'apply_reset_data_');
define('APPLY_RESETFORM_DROP',  'apply_drop_apply_');
define('APPLY_MAX_PIX_LENGTH',  '400'); 		//max. Breite des grafischen Balkens in der Auswertung
define('APPLY_DEFAULT_PAGE_COUNT', 20);




function apply_supports($feature)
{
	switch($feature) {
		case FEATURE_GROUPS:					return true;
		case FEATURE_GROUPINGS:					return true;
		case FEATURE_GROUPMEMBERSONLY:			return true;
		case FEATURE_MOD_INTRO:					return true;
		case FEATURE_COMPLETION_TRACKS_VIEWS:	return true;
		case FEATURE_COMPLETION_HAS_RULES:		return true;
		case FEATURE_GRADE_HAS_GRADE:			return false;
		case FEATURE_GRADE_OUTCOMES:			return false;
		case FEATURE_BACKUP_MOODLE2:			return true;
		case FEATURE_SHOW_DESCRIPTION:			return true;

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

	//saving the apply in db
	$apply_id = $DB->insert_record('apply', $apply);
	$apply->id = $apply_id;

	// Calendar
	//apply_set_events($apply);

	if (!isset($apply->coursemodule)) {
		$cm = get_coursemodule_from_id('apply', $apply->id);
		$apply->coursemodule = $cm->id;
	}
//	$context = context_module::instance($apply->coursemodule);
//	$editoroptions = apply_get_editor_options();

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

//	apply_set_events($apply);

//	$context = context_module::instance($apply->coursemodule);
//	$editoroptions = apply_get_editor_options();

	$DB->update_record('apply', $apply);

	return true;
}



function apply_delete_instance($apply_id) 
{
	global $DB;

	$apply_items = $DB->get_records('apply_item', array('apply_id'=>$apply_id));

	if (is_array($apply_items)) {
		foreach ($apply_items as $apply_item) {
			$DB->delete_records("apply_value", array('item_id'=>$apply_item->id));
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

	//get the relevant entries from $data
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

	//reset the selected applys
	foreach ($resetapplys as $id) {
		$apply = $DB->get_record('apply', array('id'=>$id));
		apply_delete_all_completeds($id);
		$status[] = array('component'=>$componentstr.':'.$apply->name, 'item'=>get_string('resetting_data', 'apply'), 'error'=>false);
	}

	return $status;
}



function apply_get_coursemodule_info($coursemodule)
{
	global $DB;

	if ($apply = $DB->get_record('apply', array('id'=>$coursemodule->instance), 'id, name, intro, introformat')) {
		if (empty($apply->name)) {
			$apply->name = "Apply_{$apply->id}";
			$DB->set_field('apply', 'name', $apply->name, array('id'=>$apply->id));
		}
		//
		$info = new stdClass();
		$info->extra = format_module_intro('apply', $apply, $coursemodule->id, false);
		$info->name  = $apply->name;
		return $info;
	} 
	else {
		return null;
	}
}



function apply_init_session()
{
	global $SESSION;

	if (!empty($SESSION)) {
		if (!isset($SESSION->apply) OR !is_object($SESSION->apply)) {
			$SESSION->apply = new stdClass();
		}
	}
}



/*
function apply_get_editor_options() 
{
	return array('maxfiles' => EDITOR_UNLIMITED_FILES, 'trusttext'=>true);
}
*/




///////////////////////////////////////////////////////////////////////////////////////////////
//
// Item Handing
//

function apply_get_item_class($typ)
{
	global $CFG;

	//get the class of item-typ
	$itemclass = 'apply_item_'.$typ;

	//get the instance of item-class
	if (!class_exists($itemclass)) {
		require_once($CFG->dirroot.'/mod/apply/item/'.$typ.'/lib.php');
	}
	return new $itemclass();
}



function apply_load_apply_items($dir='mod/apply/item')
{
	global $CFG;

	$names = get_list_of_plugins($dir);
	$ret_names = array();

	foreach ($names as $name) {
		require_once($CFG->dirroot.'/'.$dir.'/'.$name.'/lib.php');
		if (class_exists('apply_item_'.$name)) {
			$ret_names[] = $name;
		}
	}
	return $ret_names;
}



function apply_load_apply_items_options()
{
	global $CFG;

	$apply_options = array("pagebreak" => get_string('add_pagebreak', 'apply'));

	if (!$apply_names = apply_load_apply_items('mod/apply/item')) {
		return array();
	}

	foreach ($apply_names as $fn) {
		$apply_options[$fn] = get_string($fn, 'apply');
	}
	asort($apply_options);
	$apply_options = array_merge( array(' ' => get_string('select')), $apply_options );

	return $apply_options;
}



function apply_get_depend_candidates_for_item($apply, $item) 
{
	global $DB;	//all items for dependitem

	$where = "apply_id = ? AND typ != 'pagebreak' AND hasvalue = 1";
	$params = array($apply->id);
	if (isset($item->id) AND $item->id) {
		$where .= ' AND id != ?';
		$params[] = $item->id;
	}
	$dependitems = array(0 => get_string('choose'));
	$applyitems = $DB->get_records_select_menu('apply_item', $where, $params, 'position', 'id, label');

	if (!$applyitems) {
		return $dependitems;
	}
	//adding the choose-option
	foreach ($applyitems as $key => $val) {
		$dependitems[$key] = $val;
	}
	return $dependitems;
}



function apply_create_item($data)
{
	global $DB;

	$item = new stdClass();
	$item->apply_id = $data->apply_id;

	$item->template=0;
	if (isset($data->templateid)) {
		$item->template = intval($data->templateid);
	}

	$itemname = trim($data->itemname);
	$item->name = ($itemname ? $data->itemname : get_string('no_itemname', 'apply'));

	if (!empty($data->itemlabel)) {
		$item->label = trim($data->itemlabel);
	} else {
		$item->label = get_string('no_itemlabel', 'apply');
	}

	$itemobj = apply_get_item_class($data->typ);
	$item->presentation = ''; //the date comes from postupdate() of the itemobj
	$item->hasvalue = $itemobj->get_hasvalue();
	$item->typ 		= $data->typ;
	$item->position = $data->position;
	$item->required = 0;

	if (!empty($data->required)) {
		$item->required = $data->required;
	}

	$item->id = $DB->insert_record('apply_item', $item);

	//move all itemdata to the data
	$data->id 		= $item->id;
	$data->apply_id = $item->apply_id;
	$data->name 	= $item->name;
	$data->label 	= $item->label;
	$data->required = $item->required;

	return $itemobj->postupdate($data);
}



function apply_update_item($item) {
	global $DB;
	return $DB->update_record("apply_item", $item);
}



function apply_delete_item($item_id, $renumber=true, $template=false) 
{	
	global $DB;

	$item = $DB->get_record('apply_item', array('id'=>$item_id));

	//deleting the files from the item
	$fs = get_file_storage();

	if ($template) {
		if ($template->ispublic) {
			$context = get_system_context();
		} 
		else {
			$context = context_course::instance($template->course);
		}
		$templatefiles = $fs->get_area_files($context->id, 'mod_apply', 'template', $item->id, "id", false);

		if ($templatefiles) {
			$fs->delete_area_files($context->id, 'mod_apply', 'template', $item->id);
		}
	}
	//
	else {
		if (!$cm = get_coursemodule_from_instance('apply', $item->apply_id)) {
			return false;
		}
		$context = context_module::instance($cm->id);

		$itemfiles = $fs->get_area_files($context->id, 'mod_apply', 'item', $item->id, 'id', false);
		if ($itemfiles) {
			$fs->delete_area_files($context->id, 'mod_apply', 'item', $item->id);
		}
	}

	$DB->delete_records('apply_value', array('item_id'=>$item_id));

	$DB->set_field('apply_item', 'dependvalue', '', array('dependitem'=>$item_id));
	$DB->set_field('apply_item', 'dependitem',   0, array('dependitem'=>$item_id));

	$DB->delete_records('apply_item', array('id'=>$item_id));
	if ($renumber) {
		apply_renumber_items($item->apply_id);
	}
}



function apply_delete_all_items($apply_id)
{
	global $DB, $CFG;

//	require_once($CFG->libdir.'/completionlib.php');

	if (!$apply = $DB->get_record('apply', array('id'=>$apply_id))) {
		return false;
	}
	if (!$cm = get_coursemodule_from_instance('apply', $apply->id)) {
		return false;
	}
	if (!$course = $DB->get_record('course', array('id'=>$apply->course))) {
		return false;
	}
	if (!$items = $DB->get_records('apply_item', array('apply_id'=>$apply_id))) {
		return false;
	}

	foreach ($items as $item) {
		apply_delete_item($item->id, false);
	}

	if ($submits = $DB->get_records('apply_submit', array('apply_id'=>$apply->id))) {
		foreach ($submits as $submit) {
			$DB->delete_records('apply_submit', array('id'=>$submit->id));
		}
	}
}



function apply_switch_item_required($item)
{
	global $DB, $CFG;

	$itemobj = apply_get_item_class($item->typ);

	if ($itemobj->can_switch_require()) {
		$new_require_val = (int)!(bool)$item->required;
		$params = array('id'=>$item->id);
		$DB->set_field('apply_item', 'required', $new_require_val, $params);
	}
	return true;
}



function apply_renumber_items($apply_id)
{
	global $DB;

	$items = $DB->get_records('apply_item', array('apply_id'=>$apply_id), 'position');
	$pos = 1;
	if ($items) {
		foreach ($items as $item) {
			$DB->set_field('apply_item', 'position', $pos, array('id'=>$item->id));
			$pos++;
		}
	}
}



function apply_moveup_item($item)
{
	global $DB;

	if ($item->position==1) {
		return true;
	}

	$params = array('apply_id'=>$item->apply_id);
	if (!$items = $DB->get_records('apply_item', $params, 'position')) {
		return false;
	}

	$itembefore = null;
	foreach ($items as $i) {
		if ($i->id == $item->id) {
			if (is_null($itembefore)) {
				return true;
			}
			$itembefore->position = $item->position;
			$item->position--;
			apply_update_item($itembefore);
			apply_update_item($item);
			apply_renumber_items($item->apply_id);
			return true;
		}
		$itembefore = $i;
	}
	return false;
}



function apply_movedown_item($item)
{
	global $DB;

	$params = array('apply_id'=>$item->apply_id);
	if (!$items = $DB->get_records('apply_item', $params, 'position')) {
		return false;
	}

	$movedownitem = null;
	foreach ($items as $i) {
		if (!is_null($movedownitem) AND $movedownitem->id == $item->id) {
			$movedownitem->position = $i->position;
			$i->position--;
			apply_update_item($movedownitem);
			apply_update_item($i);
			apply_renumber_items($item->apply_id);
			return true;
		}
		$movedownitem = $i;
	}
	return false;
}



function apply_move_item($moveitem, $pos) {
	global $DB;

	$params = array('apply_id'=>$moveitem->apply_id);
	if (!$allitems = $DB->get_records('apply_item', $params, 'position')) {
		return false;
	}
	if (is_array($allitems)) {
		$index = 1;
		foreach ($allitems as $item) {
			if ($index == $pos) {
				$index++;
			}
			if ($item->id == $moveitem->id) {
				$moveitem->position = $pos;
				apply_update_item($moveitem);
				continue;
			}
			$item->position = $index;
			apply_update_item($item);
			$index++;
		}
		return true;
	}
	return false;
}



function apply_print_item_preview($item)
{
	global $CFG;

	if ($item->typ=='pagebreak') {
		return;
	}

	//get the instance of the item-class
	$itemobj = apply_get_item_class($item->typ);
	$itemobj->print_item_preview($item);
}



function apply_print_item_complete($item, $value=false, $highlightrequire=false)
{
	global $CFG;

	if ($item->typ=='pagebreak') {
		return;
	}

	//get the instance of the item-class
	$itemobj = apply_get_item_class($item->typ);
	$itemobj->print_item_complete($item, $value, $highlightrequire);
}



function apply_print_item_show_value($item, $value=false)
{
	global $CFG;

	if ($item->typ=='pagebreak') {
		return;
	}

	//get the instance of the item-class
	$itemobj = apply_get_item_class($item->typ);
	$itemobj->print_item_show_value($item, $value);
}



function apply_get_template_list($course, $onlyownorpublic='') 
{
	global $DB, $CFG;

	switch($onlyownorpublic) {
		case '':
			$templates = $DB->get_records_select('apply_template', 'course = ? OR ispublic=1', array($course->id), 'name');
			break;
		case 'own':
			$templates = $DB->get_records('apply_template', array('course'=>$course->id), 'name'); 
			break;
		case 'public':
			$templates = $DB->get_records('apply_template', array('ispublic'=>1), 'name');
			break;
	}
	return $templates;
}





///////////////////////////////////////////////////////////////////////////////////
//
// Groups
//

function apply_get_completeds_group($apply, $groupid=false) 
{
	global $CFG, $DB;

	if (intval($groupid)>0) {
		$query = "SELECT as.* FROM {apply_submit} as, {groups_members} gm 
						WHERE as.apply_id = ? AND gm.groupid = ?  AND as.user_id = gm.userid";
		$values = $DB->get_records_sql($query, array($apply->id, $groupid));
		if ($values) return $values;
		return false;
	} 
	//
	else {
		$values = $DB->get_records('apply_submit', array('apply_id'=>$apply->id));
		if ($values) return $values;
		return false;
	}
}



function apply_get_completeds_group_count($apply, $groupid=false) 
{
	if ($values = apply_get_completeds_group($apply, $groupid)) {
		return count($values);
	} 
	else {
		return false;
	}
}




///////////////////////////////////////////////////////////////////////////////////
//
// Page Break
//

function apply_create_pagebreak($apply_id) 
{
	global $DB;

	//check if there already is a pagebreak on the last position
	$lastposition = $DB->count_records('apply_item', array('apply_id'=>$apply_id));
	if ($lastposition==apply_get_last_break_position($apply_id)) {
		return false;
	}

	$item = new stdClass();
	$item->apply_id = $apply_id;
	$item->template = 0;
	$item->name 	= '';
	$item->presentation = '';
	$item->hasvalue = 0;
	$item->typ 		= 'pagebreak';
	$item->position = $lastposition + 1;
	$item->required = 0;

	return $DB->insert_record('apply_item', $item);
}



function apply_get_all_break_positions($apply_id) 
{
	global $DB;

	$params = array('typ'=>'pagebreak', 'apply_id'=>$apply_id);
	$allbreaks = $DB->get_records_menu('apply_item', $params, 'position', 'id, position');
	if (!$allbreaks) {
		return false;
	}
	return array_values($allbreaks);
}



function apply_get_last_break_position($apply_id)
{
	if (!$allbreaks=apply_get_all_break_positions($apply_id)) {
		return false;
	}
	return $allbreaks[count($allbreaks) - 1];
}



function apply_get_page_to_continue($apply_id)
{
	global $CFG, $USER, $DB;

	if (!$allbreaks = apply_get_all_break_positions($apply_id)) {
		return false;
	}

	$params = array();
	$userselect = "AND as.user_id = :userid";
	$usergroup  = "GROUP BY as.user_id";
	$params['user_id'] = $USER->id;

	$sql = "SELECT MAX(ai.position) FROM {apply_submit} as, {apply_value} av, {apply_item} ai
			  	WHERE as.id = av.submit_id AND as.apply_id = :apply_id AND ai.id = av.item_id $usergroup";
	$params['apply_id'] = $apply_id;

	$lastpos = $DB->get_field_sql($sql, $params);

	//the index of found pagebreak is the searched pagenumber
	foreach ($allbreaks as $pagenr => $br) {
		if ($lastpos<$br) {
			return $pagenr;
		}
	}
	return count($allbreaks);
}




///////////////////////////////////////////////////////////////////////////////////
//
// Submit Handling
//

function apply_get_current_submit($apply_id) //, $tmp=false, $courseid=false, $guestid=false)
{
	global $USER, $DB;

	$params = array('apply_id'=>$apply_id, 'user_id'=>$USER->id);
	return $DB->get_record('apply_submit', $params);
}



function apply_get_current_all_submit($apply_id)
{
	global $DB;

	// 権限チェック

	$params = array('apply_id'=>$apply_id);
	return $DB->get_record('apply_submit', $params);
}




///////////////////////////////////////////////////////////////////////////////////
//
// Value Handling
//

function apply_clean_input_value($item, $value) 
{
	$itemobj = apply_get_item_class($item->typ);
	return $itemobj->clean_input_value($value);
}



// return submit_id
function apply_save_values($user_id)
{
	global $DB;

	$submit_id = optional_param('submit_id', 0, PARAM_INT);

	$time = time();
	$time_modified = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));

	if ($user_id==0) {
		return apply_create_values($user_id, $time_modified);
	}

	$submit = $DB->get_record('apply_submit', array('id'=>$submit_id));
	if (!$submit) {
		return apply_create_values($user_id, $time_modified);
	}
	else {
		$submit->time_modified = $time_modified;
		return apply_update_values($submit);
	}
}



function apply_check_values($firstitem, $lastitem)
{
	global $DB, $CFG;

	$apply_id = optional_param('apply_id', 0, PARAM_INT);

	$select = "apply_id = ?  AND position >= ?  AND position <= ?  AND hasvalue = 1";
	$params = array($apply_id, $firstitem, $lastitem);

	if (!$items = $DB->get_records_select('apply_item', $select, $params)) {
		return true;
	}

	foreach ($items as $item) {
		$itemobj = apply_get_item_class($item->typ);
		$formvalname = $item->typ . '_' . $item->id;

		if ($itemobj->value_is_array()) {
			$value = optional_param_array($formvalname, null, PARAM_RAW);
		} 
		else {
			$value = optional_param($formvalname, null, PARAM_RAW);
		}
		$value = $itemobj->clean_input_value($value);

		if (is_null($value) AND $item->required==1) {
			return false;
		}
		if (!$itemobj->check_value($value, $item)) {
			return false;
		}
	}

	return true;
}



function apply_get_item_value($submit_id, $item_id) 
{
	global $DB;

	$params = array('submit_id'=>$submit_id, 'item_id'=>$item_id);
	return $DB->get_field('apply_value', 'value', $params);
}



function apply_compare_item_value($submit_id, $item_id, $dependvalue)
{
	global $DB, $CFG;

	$dbvalue = apply_get_item_value($submit_id, $item_id);
	$item = $DB->get_record('apply_item', array('id'=>$item_id));

	$itemobj = apply_get_item_class($item->typ);
	$ret = $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false

	return $ret;
}



function apply_check_values($firstitem, $lastitem)
{
	global $DB, $CFG;

	$apply_id = optional_param('apply_id', 0, PARAM_INT);

	//get all items between the first- and lastitem
	$select = "apply_id = ?  AND position >= ?  AND position <= ?  AND hasvalue = 1";
	$params = array($apply_id, $firstitem, $lastitem);

	if (!$items = $DB->get_records_select('apply_item', $select, $params)) {
		return true;
	}

	foreach ($items as $item) {
		$itemobj = apply_get_item_class($item->typ);
		$formvalname = $item->typ . '_' . $item->id;

		if ($itemobj->value_is_array()) {
			$value = optional_param_array($formvalname, null, PARAM_RAW);
		} 
		else {
			$value = optional_param($formvalname, null, PARAM_RAW);
		}
		$value = $itemobj->clean_input_value($value);

		if (is_null($value) AND $item->required == 1) {
			return false;
		}
		if (!$itemobj->check_value($value, $item)) {
			return false;
		}
	}

	return true;
}




function apply_create_values($usrid, $time_modified)
{
	global $DB;

	$apply_id = optional_param('apply_id', false, PARAM_INT);
//	$courseid = optional_param('courseid', false, PARAM_INT);

	$submit = new stdClass();
	$subnit->apply_id		= $apply_id;
	$submit->user_id		= $usrid;
	$submit->time_modified  = $time_modified;

	$submit_id = $DB->insert_record('apply_submit', $submit);
	$submit = $DB->get_record('apply_submit', array('id'=>$submit_id));

	if (!$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id))) {
		return false;
	}

	foreach ($items as $item) {
		//
		if (!$item->hasvalue) {
			continue;
		}
		$itemobj = apply_get_item_class($item->typ);
		$keyname = $item->typ.'_'.$item->id;

		if ($itemobj->value_is_array()) {
			$itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
		}
		else {
			$itemvalue = optional_param($keyname, null, $itemobj->value_type());
		}
		if (is_null($itemvalue)) {
			continue;
		}

		$value = new stdClass();
		$value->item_id   = $item->id;
		$value->submit_id = $submit->id;
		$value->value = $itemobj->create_value($itemvalue);

		$DB->insert_record('apply_value', $value);
	}

	return $submit->id;
}



function apply_update_values($submit)
{
	global $DB;

//	$courseid = optional_param('courseid', false, PARAM_INT);

	$DB->update_record('apply_submit', $submit);
	$values = $DB->get_records('apply_value', array('submit_id'=>$submit->id));

	if (!$items = $DB->get_records('apply_item', array('apply_id'=>$submit->apply_id))) {
		return false;
	}

	foreach ($items as $item) {
		//
		if (!$item->hasvalue) {
			continue;
		}
		$itemobj = apply_get_item_class($item->typ);
		$keyname = $item->typ.'_'.$item->id;

		if ($itemobj->value_is_array()) {
			$itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
		}
		else {
			$itemvalue = optional_param($keyname, null, $itemobj->value_type());
		}
		if (is_null($itemvalue)) {
			continue;
		}

		$newvalue = new stdClass();
		$newvalue->item_id 	 = $item->id;
		$newvalue->submit_id = $submit->id;
		$newvalue->value = $itemobj->create_value($itemvalue);

		$exist = false;
		foreach ($values as $value) {
			if ($value->item==$newvalue->item) {
				$newvalue->id = $value->id;
				$exist = true;
				break;
			}
		}
		if ($exist) {
			$DB->update_record('apply_value', $newvalue);
		}
		else {
			$DB->insert_record('apply_value', $newvalue);
		}
	}

	return $completed->id;
}



function apply_get_group_values($item, $groupid=false, $ignore_empty=false)
{
	global $CFG, $DB;

	if ($ignore_empty) {
		$ignore_empty_select = "AND value != '' AND value != '0'";
	}
	else {
		$ignore_empty_select = "";
	}

	if (intval($groupid)>0) {
		$query = 'SELECT av.* FROM {apply_value} av, {apply_submit} as, {groups_members} gm
	   				WHERE av.item_id = ? AND av.submit_id = as.id AND as.user_id = gm.userid '.
					$ignore_empty_select.' AND gm.groupid = ?
					ORDER BY av.time_modified';
		$values = $DB->get_records_sql($query, array($item->id, $groupid));
	}
	//
	else {
		$select = "item_id = ? ".$ignore_empty_select;
		$params = array($item->id);
		$values = $DB->get_records_select('apply_value', $select, $params);
	}

	return $values;
}



function apply_delete_all_submit($apply_id) 
{
	global $DB;

	if (!$submits = $DB->get_records('apply_submit', array('apply_id'=>$apply_id))) {
		return;
	}

	foreach ($submits as $submit) {
		apply_delete_submit($submit->id);
	}
}



function apply_delete_submit($submit_id)
{
	global $DB, $CFG;

//	require_once($CFG->libdir.'/completionlib.php');

	if (!$submit = $DB->get_record('apply_submit', array('id'=>$submit_id))) {
		return false;
	}
	if (!$apply = $DB->get_record('apply', array('id'=>$submit->apply_id))) {
		return false;
	}
	if (!$course = $DB->get_record('course', array('id'=>$apply->course))) {
		return false;
	}
	if (!$cm = get_coursemodule_from_instance('apply', $apply->id)) {
		return false;
	}

	$DB->delete_records('apply_value', array('submit_id'=>$submit->id));

	$completion = new completion_info($course);
	if ($completion->is_enabled($cm) && $apply->completionsubmit) {
		$completion->update_state($cm, COMPLETION_INCOMPLETE, $submit->user_id);
	}

	$ret = $DB->delete_records('apply_submit', array('id'=>$submit->id));
	return ret;
}



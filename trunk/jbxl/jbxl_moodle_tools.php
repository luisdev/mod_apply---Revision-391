<?php
//
// by Fumi.Iseki 2012/04/12
//               2014/05/14
//               2014/06/08
//

//
// About Capabilities
//	please see http://docs.moodle.org/dev/Roles#Capability-locality_changes_in_v1.9
//

defined('MOODLE_INTERNAL') || die();

$jbxl_moodle_tools_ver = 2014060800;


//
if (defined('JBXL_MOODLE_TOOLS_VER') or defined('_JBXL_MOODLE_TOOLS')) {
	if (defined('JBXL_MOODLE_TOOLS_VER')) {
		if (JBXL_MOODLE_TOOLS_VER < $jbxl_moodle_tools_ver) {
			print_error('JBXL_MOODLE_TOOLS: old version is used. '.JBXL_MOODLE_TOOLS_VER.' < '.$jbxl_moodle_tools_ver);
		}
	}
}
else {

define('JBXL_MOODLE_TOOLS_VER', $jbxl_moodle_tools_ver);



/*******************************************************************************
//
// cntxt: id or context of course
//

// function  jbxl_is_admin($uid)
// function  jbxl_is_teacher($uid, $cntxt)
// function  jbxl_is_assistant($uid, $cntxt)
// function  jbxl_is_student($uid, $cntxt)
// function  jbxl_has_role($uid, $cntxt, $rolename)
//
// function  jbxl_get_course_users($cntxt, $sort='')
// function  jbxl_get_course_students($cntxt, $sort='')
// function  jbxl_get_course_tachers($cntxt, $sort='')
// function  jbxl_get_course_assistants($cntxt, $sort='')
// function  jbxl_get_moodle_version()
//
// function  jbxl_get_user_first_grouping($courseid, $userid)
//
// function  jbxl_db_exist_table($table, $lower_case=true)
//
// function  jbxl_download_data($format, $headers, $datas, $filename='')
//

*******************************************************************************/



function  jbxl_is_admin($uid)
{
	$admins = get_admins();
	foreach ($admins as $admin) {
		if ($uid==$admin->id) return true;
	}
	return false;
}



function  jbxl_is_teacher($uid, $cntxt, $inc_admin=true)
{
	global $DB;

	if (!$cntxt) return false;
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);

	$ret = false;
	$roles = $DB->get_records('role', array('archetype'=>'editingteacher'), 'id', 'id'); 
	foreach($roles as $role) {
		$ret = user_has_role_assignment($uid, $role->id, $cntxt->id);
		if ($ret) return $ret;
	}

	if ($inc_admin) {
		$ret = jbxl_is_admin($uid); 
		if (!$ret) $ret = jbxl_has_role($uid, $cntxt, 'manager');
		if (!$ret) $ret = jbxl_has_role($uid, $cntxt, 'coursecreator');
	}
	return $ret;
}



function  jbxl_is_assistant($uid, $cntxt)
{
	global $DB;

	if (!$cntxt) return false;
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);

	$roles = $DB->get_records('role', array('archetype'=>'teacher'), 'id', 'id'); 
	foreach($roles as $role) {
		$ret = user_has_role_assignment($uid, $role->id, $cntxt->id);
		if ($ret) return $ret;
	}
	return false;
}



function  jbxl_is_student($uid, $cntxt)
{
	global $DB;

	if (!$cntxt) return false;
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);

	$roles = $DB->get_records('role', array('archetype'=>'student'), 'id', 'id'); 
	foreach($roles as $role) {
		$ret = user_has_role_assignment($uid, $role->id, $cntxt->id);	// slow?
		if ($ret) return $ret;
	}
	return false;
}



function  jbxl_has_role($uid, $cntxt, $rolename)
{
	global $DB;

	if (!$cntxt) return false;
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);

	$roles = $DB->get_records('role', array('archetype'=>$rolename), 'id', 'id'); 
	foreach($roles as $role) {
		$ret = user_has_role_assignment($uid, $role->id, $cntxt->id);
		if ($ret) return $ret;
	}
	return false;
}



function jbxl_get_course_users($cntxt, $sort='')
{
	global $DB;

	if (!$cntxt) return '';

	if ($sort) $sort = ' ORDER BY u.'.$sort;
	$sql = 'SELECT u.* FROM {role_assignments} r, {user} u WHERE r.contextid = ? AND r.userid = u.id '.$sort;
	//
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);
	$users = $DB->get_records_sql($sql, array($cntxt->id));

	return $users;
}



function jbxl_get_course_students($cntxt, $sort='')
{
	global $DB;

	if (!$cntxt) return '';

	$roles = $DB->get_records('role', array('archetype'=>'student'), 'id', 'id'); 
	if (empty($roles)) return '';

	$roleid = '';
	foreach($roles as $role) {
		if (!empty($roleid)) $roleid.= ' OR ';
		$roleid.= 'r.roleid = '.$role->id;
	}
	if ($sort) $sort = ' ORDER BY u.'.$sort;

	$sql = 'SELECT u.* FROM {role_assignments} r, {user} u '.
					 ' WHERE r.contextid = ? AND ('.$roleid.') AND r.userid = u.id '.$sort;
	//
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);
	$users = $DB->get_records_sql($sql, array($cntxt->id));

	return $users;
}



function jbxl_get_course_tachers($cntxt, $sort='')
{
	global $DB;

	if (!$cntxt) return '';

	$roles = $DB->get_records('role', array('archetype'=>'editingteacher'), 'id', 'id'); 
	if (empty($roles)) return '';

	$roleid = '';
	foreach($roles as $role) {
		if (!empty($roleid)) $roleid.= ' OR ';
		$roleid.= 'r.roleid = '.$role->id;
	}
	if ($sort) $sort = ' ORDER BY u.'.$sort;

	$sql = 'SELECT u.* FROM {role_assignments} r, {user} u '. 
					 ' WHERE r.contextid = ? AND ('.$roleid.') AND r.userid = u.id '.$sort;
	//
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);
	$users = $DB->get_records_sql($sql, array($cntxt->id));

	return $users;
}



function jbxl_get_course_assistants($cntxt, $sort='')
{
	global $DB;

	if (!$cntxt) return '';

	$roles = $DB->get_records('role', array('archetype'=>'teacher'), 'id', 'id'); 
	if (empty($roles)) return '';

	$roleid = '';
	foreach($roles as $role) {
		if (!empty($roleid)) $roleid.= ' OR ';
		$roleid.= 'r.roleid = '.$role->id;
	}
	if ($sort) $sort = ' ORDER BY u.'.$sort;

	$sql = 'SELECT u.* FROM {role_assignments} r, {user} u '.
					 ' WHERE r.contextid = ? AND ('.$roleid.') AND r.userid = u.id '.$sort;
	//
	if (!is_object($cntxt)) $cntxt = get_context_instance(CONTEXT_COURSE, $cntxt);
	$users = $DB->get_records_sql($sql, array($cntxt->id));

	return $users;
}




function  jbxl_get_moodle_version()
{
	// see http://docs.moodle.org/dev/Releases

	global $CFG;

	if 		($CFG->version>=2014051200) return 2.7;
	else if ($CFG->version>=2013111800) return 2.6;
	else if ($CFG->version>=2013051400) return 2.5;
	else if ($CFG->version>=2012120300) return 2.4;
	else if ($CFG->version>=2012062500) return 2.3;
	else if ($CFG->version>=2011120500) return 2.2;
	else if ($CFG->version>=2011070100) return 2.1;
	else if ($CFG->version>=2010112400) return 2.0;
	else if ($CFG->version>=2007101509) return 1.9;

	return 1.8;
}




/*
function jbxl_get_user_first_grouping($courseid, $userid)
{
	/////////////////////////////////
	return 0;	// for DEBUG
	/////////////////////////////////


	if (!$courseid or !$userid) return 0;

	$groupings = groups_get_user_groups($courseid, $userid);
	if (!is_array($groupings)) return 0;

	$keys = array_keys($groupings);
	if (count($keys)>1 && $keys[0]==0) return $keys[1];
	else return $keys[0];
}
*/




//
// Moodle DB (MySQL)
//

function jbxl_db_exist_table($table, $lower_case=true)
{
	global $DB;

	$ret = false;

	$results = $DB->get_records_sql('SHOW TABLES');
	if (is_array($results)) {
		$db_tbls = array_keys($results);
		foreach($db_tbls as $db_tbl) {
			if ($lower_case) $db_tbl = strtolower($db_tbl);
			if ($db_tbl==$table) {
				$ret = true;
				break;
			}
		}
	}

	return $ret;
}			




//
// $datas: 2次元のデータ配列
//
function  jbxl_download_data($format, $datas, $filename='')
{
	global $CFG;

	if (empty($datas->data)) return;
	if (empty($filename)) {
		$filename = 'jbxl_download_'.date('YmdHis');
	}

	//
	if ($format==='xls') {
		$excellib_version = 0;
		if (file_exists ($CFG->dirroot.'/lib/excellib.class.php')) {
			$excellib_version = 2;
			$tocode = 'utf-8';
			require_once($CFG->dirroot.'/lib/excellib.class.php');
		}
		else {
			$excellib_version = 1;
			$tocode = 'sjis-win';
			require_once($CFG->dirroot.'/lib/excel/Worksheet.php');
			require_once($CFG->dirroot.'/lib/excel/Workbook.php');
		}

		//
		header("Content-type: application/vnd.ms-excel");
		header("Content-Disposition: attachment; filename=\"$filename.xls\"");
	
		/// Creating a workbook
		if ($excellib_version==2) {
			$workbook = new MoodleExcelWorkbook('-', 'Excel5');
			$workbook->send($filename);
		}	
		else {
			$workbook = new Workbook('-');
		}
		$myxls = $workbook->add_worksheet('data');
		
		//
		$i = 0;
		foreach ($datas->data as $line=>$data) {
			$j = 0;
			foreach ($data as $colm=>$val) {
				if ($datas->attr[$line][$colm]==='number') {
					$myxls->write_number($i, $j++, $val);
				}
				else {
					$myxls->write_string($i, $j++, mb_convert_encoding($val,  $tocode, 'auto'));
				}
			}
			$i++;
		}
		$workbook->close();	
	}

	//
	else if ($format==='txt') {
		$tocode = 'utf-8';
		//
		header("Content-Type: application/download\n"); 
		header("Content-Disposition: attachment; filename=\"$filename.txt\"");

		foreach ($datas->data as $data) {
			foreach ($data as $val) {
				echo mb_convert_encoding($val, $tocode, 'auto')."\t";
			}
			echo "\r\n";
		}
	}	
		
	return;
}	




}		// !defined('JBXL_MOODLE_TOOLS_VER')

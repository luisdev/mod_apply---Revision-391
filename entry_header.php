<?php

// needs $req_own_data, $table, $courseid, $show_all, ....

$title_ttl  = get_string('title_title',	 'apply');
$title_date = get_string('date');
$title_ver  = get_string('title_version','apply');
$title_clss = get_string('title_class',  'apply');
$title_ack  = get_string('title_ack',	 'apply');
$title_exec = get_string('title_exec',   'apply');
$title_chk  = get_string('title_check',	 'apply');

//
if ($req_own_data) {
	$title_draft   = get_string('title_draft', 'apply');
	$table_columns = array('title', 'time_modified', 'version', 'class', 'draft', 'acked', 'execd', 'edit', 'discard');
	$table_headers = array($title_ttl, $title_date, $title_ver, $title_clss, $title_draft, $title_ack, $title_exec, '-', '-');
}
else {
	$title_pic  = get_string('user_pic', 'apply');
	$title_name = get_string($name_pattern);
	$table_columns = array('userpic', $name_pattern, 'title', 'time_modified', 'version', 'class', 'acked', 'execd', 'operation');
	$table_headers = array($title_pic, $title_name, $title_ttl, $title_date, $title_ver, $title_clss, $title_ack, $title_exec, '-');
}

//
$table->define_columns($table_columns);
$table->define_headers($table_headers);
$table->define_baseurl($base_url);

if ($req_own_data) {
	$table->sortable(true, 'time_modified', SORT_DESC);
	$table->no_sorting('edit');
	$table->no_sorting('discard');
	$table->no_sorting('draft');
}
else {
	$table->sortable(true, 'lastname', SORT_ASC);
	$table->no_sorting('operation');
}

//
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
if ($where) $where .= ' AND ';

//
if ($name_pattern=='firstname') {
	$sifirst = optional_param('sifirst', '', PARAM_ALPHA);
	if ($sifirst) {
		$where .= "firstname LIKE :sifirst ESCAPE '\\\\' AND ";
		$params['sifirst'] =  $sifirst.'%';
	}
}
if ($name_pattern=='lastname') {
	$silast  = optional_param('silast',  '', PARAM_ALPHA);
	if ($silast) {
		$where .= "lastname LIKE :silast ESCAPE '\\\\' AND ";
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
	$table->pagesize($perpage, $matchcount);
	$start_page = $table->get_page_start();
	$page_count = $table->get_page_size();
}


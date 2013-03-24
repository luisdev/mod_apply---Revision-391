<?php

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

if (!$course = $DB->get_record('course', array('id'=>$id))) {
	print_error('invalidcourseid');
}

require_login($course);

redirect('view.php?id='.$id.'&do_show=view');



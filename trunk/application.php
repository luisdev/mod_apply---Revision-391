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
 * prints the form so the user can fill out the apply
 *
 * @package apply
 * @author  Fumi.Iseki
 * @license GNU Public License
 * @attention modified from mod_feedback that by Andreas Grabs
 */

require_once("../../config.php");
require_once("lib.php");
require_once($CFG->libdir . '/completionlib.php');

apply_init_session();

$id 			= required_param('id', PARAM_INT);
//$completedid 	= optional_param('completedid', false, PARAM_INT);
$app_id			= optional_param('app_id', false, PARAM_INT);
$preservevalues = optional_param('preservevalues', 0,  PARAM_INT);
$courseid 	  	= optional_param('courseid', false, PARAM_INT);
$gopage 		= optional_param('gopage', -1, PARAM_INT);
$lastpage 	  	= optional_param('lastpage', false, PARAM_INT);
$startitempos	= optional_param('startitempos', 0, PARAM_INT);
$lastitempos  	= optional_param('lastitempos', 0, PARAM_INT);

$highlightrequired = false;

if (($formdata = data_submitted()) AND !confirm_sesskey()) {
    print_error('invalidsesskey');
}


// Page
//if the use hit enter into a textfield so the form should not submit
if ( isset($formdata->sesskey) AND
    !isset($formdata->savevalues) AND
    !isset($formdata->gonextpage) AND
    !isset($formdata->gopreviouspage)) {

    $gopage = $formdata->lastpage;
}

if (isset($formdata->savevalues)) {
    $savevalues = true;
} 
else {
   $savevalues = false;
}

if ($gopage<0 AND !$savevalues) {
    if (isset($formdata->gonextpage)) {
        $gopage = $lastpage + 1;
        $gonextpage = true;
        $gopreviouspage = false;
    } else if (isset($formdata->gopreviouspage)) {
        $gopage = $lastpage - 1;
        $gonextpage = false;
        $gopreviouspage = true;
    } else {
        print_error('missingparameter');
    }
} else {
    $gonextpage = $gopreviouspage = false;
}


//
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
if (!$apply_complete_cap) {
    print_error('error');
}

/*
//check whether the apply is located and! started from the mainsite
if ($course->id==SITEID AND !$courseid) {
    $courseid = SITEID;
}

//check whether the apply is mapped to the given courseid
if ($course->id == SITEID AND !has_capability('mod/apply:edititems', $context)) {
    if ($DB->get_records('apply_sitecourse_map', array('apply_id'=>$apply->id))) {
        $params = array('apply_id'=>$apply->id, 'courseid'=>$courseid);
        if (!$DB->get_record('apply_sitecourse_map', $params)) {
            print_error('notavailable', 'apply');
        }
    }
}

if ($course->id==SITEID) {
	require_login($course, true);
} 
else {
	require_login($course, true, $cm);
}
*/


require_login($course, true, $cm);


/*
//check whether the given courseid exists
if ($courseid AND $courseid!=SITEID) {
    if ($course2 = $DB->get_record('course', array('id'=>$courseid))) {
        require_course_login($course2); //this overwrites the object $course :-(
        $course = $DB->get_record("course", array("id"=>$cm->course)); // the workaround
    } else {
        print_error('invalidcourseid');
    }
}

if (!$apply_complete_cap) {
    print_error('error');
}
*/


// Mark activity viewed for completion-tracking
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

/// Print the page header
$strapplys = get_string('modulenameplural', 'apply');
$strapply  = get_string('modulename', 'apply');

/*
if ($course->id == SITEID) {
    $PAGE->set_cm($cm, $course); // set's up global $COURSE
    $PAGE->set_pagelayout('incourse');
}
*/

$PAGE->navbar->add(get_string('apply:applies', 'apply'));
$urlparams = array('id'=>$cm->id, 'gopage'=>$gopage, 'courseid'=>$course->id);
$PAGE->set_url('/mod/apply/applies.php', $urlparams);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_title(format_string($apply->name));
echo $OUTPUT->header();


//ishidden check.
//apply in courses
//if ((empty($cm->visible) AND !has_capability('moodle/course:viewhiddenactivities', $context)) AND $course->id != SITEID) {
if ((empty($cm->visible) AND !has_capability('moodle/course:viewhiddenactivities', $context))) {
    notice(get_string("activityiscurrentlyhidden"));
}

//ishidden check.
//apply on mainsite
if ((empty($cm->visible) AND !has_capability('moodle/course:viewhiddenactivities', $context))) {
    notice(get_string("activityiscurrentlyhidden"));
}

//check, if the apply is open (time_open, time_close)
$checktime = time();
$apply_is_closed = ($apply->time_open>$checktime) OR ($apply->time_close<$checktime AND $apply->time_close>0);

if ($apply_is_closed) {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
        echo '<h2><font color="red">';
        echo get_string('apply_is_not_open', 'apply');
        echo '</font></h2>';
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    exit;
}



//additional check for multiple-submit (prevent browsers back-button).
//the main-check is in view.php
$apply_can_submit = true;
if ($apply->multiple_submit==0) {
    if (apply_is_already_submitted($apply->id, $courseid)) {
        $apply_can_submit = false;
    }
}

if ($apply_can_submit) {
    //preserving the items
    if ($preservevalues==1) {
        if (!isset($SESSION->apply->is_started) OR !$SESSION->apply->is_started==true) {
            print_error('error', '', $CFG->wwwroot.'/course/view.php?id='.$course->id);
        }
        //checken, ob alle required items einen wert haben
        if (apply_check_values($startitempos, $lastitempos)) {
            $userid = $USER->id; //arb
            if ($app_id = apply_save_values($USER->id, true)) {
                if ($userid > 0) {
                    add_to_log($course->id, 'apply', 'start_apply', 'view.php?id='.$cm->id, $apply->id, $cm->id, $userid);
                }
                if (!$gonextpage AND !$gopreviouspage) {
                    $preservevalues = false;//es kann gespeichert werden
                }

            } else {
                $savereturn = 'failed';
                if (isset($lastpage)) {
                    $gopage = $lastpage;
                } else {
                    print_error('missingparameter');
                }
            }
        } else {
            $savereturn = 'missing';
            $highlightrequired = true;
            if (isset($lastpage)) {
                $gopage = $lastpage;
            } else {
                print_error('missingparameter');
            }

        }
    }

    //saving the items
    if ($savevalues AND !$preservevalues) {
        //exists there any pagebreak, so there are values in the apply_valuetmp
        $userid = $USER->id; //arb

//        if ($apply->anonymous == APPLY_ANONYMOUS_NO) {
            $applycompleted = apply_get_current_completed($apply->id, false, $courseid);
//        } else {
//            $applycompleted = false;
//        }
        $params = array('id' => $completedid);
        $applycompletedtmp = $DB->get_record('apply_completedtmp', $params);
        //fake saving for switchrole
        $is_switchrole = apply_check_is_switchrole();
        if ($is_switchrole) {
            $savereturn = 'saved';
            apply_delete_completedtmp($completedid);
        } else {
            $new_completed_id = apply_save_tmp_values($applycompletedtmp,
                                                         $applycompleted,
                                                         $userid);
            if ($new_completed_id) {
                $savereturn = 'saved';
//                if ($apply->anonymous == APPLY_ANONYMOUS_NO) {
                    add_to_log($course->id, 'apply', 'submit', 'view.php?id='.$cm->id, $apply->id, $cm->id, $userid);

                    apply_send_email($cm, $apply, $course, $userid);
/*
                } else {
                    apply_send_email_anonym($cm, $apply, $course, $userid);
                }
*/
                //tracking the submit
/*
                $tracking = new stdClass();
                $tracking->userid = $USER->id;
                $tracking->apply_id = $apply->id;
                $tracking->completed = $new_completed_id;
                $DB->insert_record('apply_tracking', $tracking);
                unset($SESSION->apply->is_started);
*/

                // Update completion state
                $completion = new completion_info($course);
                if ($completion->is_enabled($cm) && $apply->completionsubmit) {
                    $completion->update_state($cm, COMPLETION_COMPLETE);
                }

            } else {
                $savereturn = 'failed';
            }
        }

    }


    if ($allbreaks = apply_get_all_break_positions($apply->id)) {
        if ($gopage <= 0) {
            $startposition = 0;
        } else {
            if (!isset($allbreaks[$gopage - 1])) {
                $gopage = count($allbreaks);
            }
            $startposition = $allbreaks[$gopage - 1];
        }
        $ispagebreak = true;
    } else {
        $startposition = 0;
        $newpage = 0;
        $ispagebreak = false;
    }

    //get the applyitems after the last shown pagebreak
    $select = 'apply_id = ? AND position > ?';
    $params = array($apply->id, $startposition);
    $applyitems = $DB->get_records_select('apply_item', $select, $params, 'position');

    //get the first pagebreak
    $params = array('apply_id' => $apply->id, 'typ' => 'pagebreak');
    if ($pagebreaks = $DB->get_records('apply_item', $params, 'position')) {
        $pagebreaks = array_values($pagebreaks);
        $firstpagebreak = $pagebreaks[0];
    } else {
        $firstpagebreak = false;
    }
    $maxitemcount = $DB->count_records('apply_item', array('apply_id'=>$apply->id));

    //get the values of completeds before done. Anonymous user can not get these values.
/*
    if ((!isset($SESSION->apply->is_started)) AND (!isset($savereturn))) {
                          //($apply->anonymous == APPLY_ANONYMOUS_NO)) {

        $applycompletedtmp = apply_get_current_completed($apply->id, true, $courseid);
        if (!$applycompletedtmp) {
            $applycompleted = apply_get_current_completed($apply->id, false, $courseid);
            if ($applycompleted) {
                //copy the values to apply_valuetmp create a completedtmp
                $applycompletedtmp = apply_set_tmp_values($applycompleted);
            }
        }
    } else {
        $applycompletedtmp = apply_get_current_completed($apply->id, true, $courseid);
    }
*/

    /// Print the main part of the page
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////
    $analysisurl = new moodle_url('/mod/apply/analysis.php', array('id'=>$id));
    if ($courseid > 0) {
        $analysisurl->param('courseid', $courseid);
    }
    echo $OUTPUT->heading(format_text($apply->name));

/*
    if ( (intval($apply->publish_stats) == 1) AND
            ( has_capability('mod/apply:viewanalysepage', $context)) AND
            !( has_capability('mod/apply:viewreports', $context)) ) {

        $params = array('userid' => $USER->id, 'apply' => $apply->id);
        if ($multiple_count = $DB->count_records('apply_tracking', $params)) {
            echo $OUTPUT->box_start('mdl-align');
            echo '<a href="'.$analysisurl->out().'">';
            echo get_string('completed_applys', 'apply').'</a>';
            echo $OUTPUT->box_end();
        }
    }
*/

    if (isset($savereturn) && $savereturn == 'saved') {
/*
        if ($apply->page_after_submit) {

            require_once($CFG->libdir . '/filelib.php');

            $page_after_submit_output = file_rewrite_pluginfile_urls($apply->page_after_submit,
                                                                    'pluginfile.php',
                                                                    $context->id,
                                                                    'mod_apply',
                                                                    'page_after_submit',
                                                                    0);

            echo $OUTPUT->box_start('generalbox boxaligncenter boxwidthwide');
            echo format_text($page_after_submit_output,
                             $apply->page_after_submitformat,
                             array('overflowdiv' => true));
            echo $OUTPUT->box_end();
        } else {
*/
            echo '<p align="center">';
            echo '<b><font color="green">';
            echo get_string('entries_saved', 'apply');
            echo '</font></b>';
            echo '</p>';
/*
            if ( intval($apply->publish_stats) == 1) {
                echo '<p align="center"><a href="'.$analysisurl->out().'">';
                echo get_string('completed_applys', 'apply').'</a>';
                echo '</p>';
            }
        }
*/

/*
        if ($apply->site_after_submit) {
            $url = apply_encode_target_url($apply->site_after_submit);
        } else {
*/
            if ($courseid) {
                if ($courseid == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$courseid;
                }
            } else {
                if ($course->id == SITEID) {
                    $url = $CFG->wwwroot;
                } else {
                    $url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
                }
            }
//        }
        echo $OUTPUT->continue_button($url);
    } else {
        if (isset($savereturn) && $savereturn == 'failed') {
            echo $OUTPUT->box_start('mform error');
            echo get_string('saving_failed', 'apply');
            echo $OUTPUT->box_end();
        }

        if (isset($savereturn) && $savereturn == 'missing') {
            echo $OUTPUT->box_start('mform error');
            echo get_string('saving_failed_because_missing_or_false_values', 'apply');
            echo $OUTPUT->box_end();
        }

        //print the items
        if (is_array($applyitems)) {
            echo $OUTPUT->box_start('apply_form');
            echo '<form action="applies.php" method="post" onsubmit=" ">';
            echo '<fieldset>';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo $OUTPUT->box_start('apply_anonymousinfo');

//                    echo '<input type="hidden" name="anonymous" value="0" />';
//                    $inputvalue = 'value="'.APPLY_ANONYMOUS_NO.'"';
//                    echo '<input type="hidden" name="anonymous_response" '.$inputvalue.' />';
//                    echo get_string('mode', 'apply').': ';
//                    echo get_string('non_anonymous', 'apply');

            echo $OUTPUT->box_end();
            //check, if there exists required-elements
            $params = array('apply_id' => $apply->id, 'required' => 1);
            $countreq = $DB->count_records('apply_item', $params);
            if ($countreq > 0) {
                echo '<span class="apply_required_mark">(*)';
                echo get_string('items_are_required', 'apply');
                echo '</span>';
            }
            echo $OUTPUT->box_start('apply_items');

            unset($startitem);
            $select = 'apply_id = ? AND hasvalue = 1 AND position < ?';
            $params = array($apply->id, $startposition);
            $itemnr = $DB->count_records_select('apply_item', $select, $params);
            $lastbreakposition = 0;
            $align = right_to_left() ? 'right' : 'left';

            foreach ($applyitems as $applyitem) {
                if (!isset($startitem)) {
                    //avoid showing double pagebreaks
                    if ($applyitem->typ == 'pagebreak') {
                        continue;
                    }
                    $startitem = $applyitem;
                }

                if ($applyitem->dependitem > 0) {
                    //chech if the conditions are ok
                    $fb_compare_value = apply_compare_item_value($applycompletedtmp->id,
                                                                    $applyitem->dependitem,
                                                                    $applyitem->dependvalue,
                                                                    true);
                    if (!isset($applycompletedtmp->id) OR !$fb_compare_value) {
                        $lastitem = $applyitem;
                        $lastbreakposition = $applyitem->position;
                        continue;
                    }
                }

                if ($applyitem->dependitem > 0) {
                    $dependstyle = ' apply_complete_depend';
                } else {
                    $dependstyle = '';
                }

                echo $OUTPUT->box_start('apply_item_box_'.$align.$dependstyle);
                $value = '';
                //get the value
                $frmvaluename = $applyitem->typ . '_'. $applyitem->id;
                if (isset($savereturn)) {
                    $value = isset($formdata->{$frmvaluename}) ? $formdata->{$frmvaluename} : null;
                    $value = apply_clean_input_value($applyitem, $value);
                } else {
                    if (isset($applycompletedtmp->id)) {
                        $value = apply_get_item_value($applycompletedtmp->id,
                                                         $applyitem->id,
                                                         true);
                    }
                }
                if ($applyitem->hasvalue==1) {
                    $itemnr++;
                    echo $OUTPUT->box_start('apply_item_number_'.$align);
                    echo $itemnr;
                    echo $OUTPUT->box_end();
                }
                if ($applyitem->typ != 'pagebreak') {
                    echo $OUTPUT->box_start('box generalbox boxalign_'.$align);
                    apply_print_item_complete($applyitem, $value, $highlightrequired);
                    echo $OUTPUT->box_end();
                }

                echo $OUTPUT->box_end();

                $lastbreakposition = $applyitem->position; //last item-pos (item or pagebreak)
                if ($applyitem->typ == 'pagebreak') {
                    break;
                } else {
                    $lastitem = $applyitem;
                }
            }
            echo $OUTPUT->box_end();
            echo '<input type="hidden" name="id" value="'.$id.'" />';
            echo '<input type="hidden" name="applyid" value="'.$apply->id.'" />';
            echo '<input type="hidden" name="lastpage" value="'.$gopage.'" />';
            if (isset($applycompletedtmp->id)) {
                $inputvalue = 'value="'.$applycompletedtmp->id.'"';
            } else {
                $inputvalue = 'value=""';
            }
            echo '<input type="hidden" name="completedid" '.$inputvalue.' />';
            echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
            echo '<input type="hidden" name="preservevalues" value="1" />';
            if (isset($startitem)) {
                echo '<input type="hidden" name="startitempos" value="'.$startitem->position.'" />';
                echo '<input type="hidden" name="lastitempos" value="'.$lastitem->position.'" />';
            }

            if ( $ispagebreak AND $lastbreakposition > $firstpagebreak->position) {
                $inputvalue = 'value="'.get_string('previous_page', 'apply').'"';
                echo '<input name="gopreviouspage" type="submit" '.$inputvalue.' />';
            }
            if ($lastbreakposition < $maxitemcount) {
                $inputvalue = 'value="'.get_string('next_page', 'apply').'"';
                echo '<input name="gonextpage" type="submit" '.$inputvalue.' />';
            }
            if ($lastbreakposition >= $maxitemcount) { //last page
                $inputvalue = 'value="'.get_string('save_entries', 'apply').'"';
                echo '<input name="savevalues" type="submit" '.$inputvalue.' />';
            }

            echo '</fieldset>';
            echo '</form>';
            echo $OUTPUT->box_end();

            echo $OUTPUT->box_start('apply_complete_cancel');
            if ($courseid) {
                $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$courseid.'"';
            } else {
                if ($course->id == SITEID) {
                    $action = 'action="'.$CFG->wwwroot.'"';
                } else {
                    $action = 'action="'.$CFG->wwwroot.'/course/view.php?id='.$course->id.'"';
                }
            }
            echo '<form '.$action.' method="post" onsubmit=" ">';
            echo '<fieldset>';
            echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
            echo '<input type="hidden" name="courseid" value="'. $courseid . '" />';
            echo '<button type="submit">'.get_string('cancel').'</button>';
            echo '</fieldset>';
            echo '</form>';
            echo $OUTPUT->box_end();
            $SESSION->apply->is_started = true;
        }
    }
} else {
    echo $OUTPUT->box_start('generalbox boxaligncenter');
        echo '<h2>';
        echo '<font color="red">';
        echo get_string('this_apply_is_already_submitted', 'apply');
        echo '</font>';
        echo '</h2>';
        echo $OUTPUT->continue_button($CFG->wwwroot.'/course/view.php?id='.$course->id);
    echo $OUTPUT->box_end();
}
/// Finish the page
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////

echo $OUTPUT->footer();

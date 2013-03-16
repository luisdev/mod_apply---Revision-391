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
 * Library of functions and constants for module apply
 * includes the main-part of apply-functions
 *
 * @author  Fumi Iseki
 * @license GNU Public License
 * @package mod_apply (modified from mod_feedback that by Andreas Grabs)
**/


/** Include eventslib.php */
require_once($CFG->libdir.'/eventslib.php');

/** Include calendar/lib.php */
require_once($CFG->dirroot.'/calendar/lib.php');


//define('APPLY_ANONYMOUS_YES', 1);
//define('APPLY_ANONYMOUS_NO', 2);
//define('APPLY_MIN_ANONYMOUS_COUNT_IN_GROUP', 2);
define('APPLY_DECIMAL', '.');
define('APPLY_THOUSAND', ',');
define('APPLY_RESETFORM_RESET', 'apply_reset_data_');
define('APPLY_RESETFORM_DROP', 'apply_drop_apply_');
define('APPLY_MAX_PIX_LENGTH', '400'); //max. Breite des grafischen Balkens in der Auswertung
define('APPLY_DEFAULT_PAGE_COUNT', 20);


/**
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_GROUPMEMBERSONLY
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function apply_supports($feature)
{
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_COMPLETION_HAS_RULES:    return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;

        default: return null;
    }
}


/**
 * this will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $apply the object given by mod_apply_mod_form
 * @return int
 */
function apply_add_instance($apply)
{
    global $DB;

    $apply->time_modified = time();
    $apply->id = '';

    //check if open_enable and/or close_enable is set and set correctly to save in db
    if (empty($apply->open_enable)) {
        $apply->time_open = 0;
    }
    if (empty($apply->close_enable)) {
        $apply->time_close = 0;
    }
/*
    if (empty($apply->site_after_submit)) {
        $apply->site_after_submit = '';
    }
*/

    //saving the apply in db
    $applyid = $DB->insert_record("apply", $apply);

    $apply->id = $applyid;

    apply_set_events($apply);

    if (!isset($apply->coursemodule)) {
        $cm = get_coursemodule_from_id('apply', $apply->id);
        $apply->coursemodule = $cm->id;
    }
    $context = context_module::instance($apply->coursemodule);
    $editoroptions = apply_get_editor_options();

    $DB->update_record('apply', $apply);

    return $applyid;
}


/**
 * this will update a given instance
 *
 * @global object
 * @param object $apply the object given by mod_apply_mod_form
 * @return boolean
 */
function apply_update_instance($apply) {
    global $DB;

    $apply->time_modified = time();
    $apply->id = $apply->instance;

    //check if open_enable and/or close_enable is set and set correctly to save in db
    if (empty($apply->open_enable)) {
        $apply->time_open = 0;
    }
    if (empty($apply->close_enable)) {
        $apply->time_close = 0;
    }
//    if (empty($apply->site_after_submit)) {
//        $apply->site_after_submit = '';
//    }

    //save the apply into the db
    $DB->update_record("apply", $apply);

    //create or update the new events
    apply_set_events($apply);

    $context = context_module::instance($apply->coursemodule);
    $editoroptions = apply_get_editor_options();

    $DB->update_record('apply', $apply);

    return true;
}

/**
 * Serves the files included in apply items like label. Implements needed access control ;-)
 *
 * There are two situations in general where the files will be sent.
 * 1) filearea = item, 2) filearea = template
 *
 * @package  mod_apply
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - justsend the file
 */
function apply_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    if ($filearea === 'item' or $filearea === 'template') {
        $itemid = (int)array_shift($args);
        //get the item what includes the file
        if (!$item = $DB->get_record('apply_item', array('id'=>$itemid))) {
            return false;
        }
        $applyid = $item->apply;
        $templateid = $item->template;
    }

//    if ($filearea === 'page_after_submit' or $filearea === 'item') {
    if ($filearea === 'item') {
        if (! $apply = $DB->get_record("apply", array("id"=>$cm->instance))) {
            return false;
        }

        $applyid = $apply->id;

        //if the filearea is "item" so we check the permissions like view/complete the apply
        $canload = false;
        //first check whether the user has the complete capability
        if (has_capability('mod/apply:complete', $context)) {
            $canload = true;
        }

        //now we check whether the user has the view capability
        if (has_capability('mod/apply:view', $context)) {
            $canload = true;
        }

        //if the apply is on frontpage and anonymous and the fullanonymous is allowed
        //so the file can be loaded too.
/*
        if (isset($CFG->apply_allowfullanonymous)
                    AND $CFG->apply_allowfullanonymous
                    AND $course->id == SITEID
                    AND $apply->anonymous == APPLY_ANONYMOUS_YES ) {
            $canload = true;
        }
*/

        if (!$canload) {
            return false;
        }
    } else if ($filearea === 'template') { //now we check files in templates
        if (!$template = $DB->get_record('apply_template', array('id'=>$templateid))) {
            return false;
        }

        //if the file is not public so the capability edititems has to be there
        if (!$template->ispublic) {
            if (!has_capability('mod/apply:edititems', $context)) {
                return false;
            }
        } else { //on public templates, at least the user has to be logged in
            if (!isloggedin()) {
                return false;
            }
        }
    } else {
        return false;
    }

/*
    if ($context->contextlevel == CONTEXT_MODULE) {
        if ($filearea !== 'item' and $filearea !== 'page_after_submit') {
            return false;
        }
    }
*/

    if ($context->contextlevel == CONTEXT_COURSE || $context->contextlevel == CONTEXT_SYSTEM) {
        if ($filearea !== 'template') {
            return false;
        }
    }

    $relativepath = implode('/', $args);
//    if ($filearea === 'page_after_submit') {
//        $fullpath = "/{$context->id}/mod_apply/$filearea/$relativepath";
//    } else {
        $fullpath = "/{$context->id}/mod_apply/$filearea/{$item->id}/$relativepath";
//    }

    $fs = get_file_storage();

    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // finally send the file
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!

    return false;
}

/**
 * this will delete a given instance.
 * all referenced data also will be deleted
 *
 * @global object
 * @param int $id the instanceid of apply
 * @return boolean
 */
function apply_delete_instance($id) {
    global $DB;

    //get all referenced items
    $applyitems = $DB->get_records('apply_item', array('apply'=>$id));

    //deleting all referenced items and values
    if (is_array($applyitems)) {
        foreach ($applyitems as $applyitem) {
            $DB->delete_records("apply_value", array("item"=>$applyitem->id));
            $DB->delete_records("apply_valuetmp", array("item"=>$applyitem->id));
        }
        if ($delitems = $DB->get_records("apply_item", array("apply"=>$id))) {
            foreach ($delitems as $delitem) {
                apply_delete_item($delitem->id, false);
            }
        }
    }

    //deleting the referenced tracking data
    $DB->delete_records('apply_tracking', array('apply'=>$id));

    //deleting the completeds
    $DB->delete_records("apply_completed", array("apply"=>$id));

    //deleting the unfinished completeds
    $DB->delete_records("apply_completedtmp", array("apply"=>$id));

    //deleting old events
    $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$id));
    return $DB->delete_records("apply", array("id"=>$id));
}

/**
 * this is called after deleting all instances if the course will be deleted.
 * only templates have to be deleted
 *
 * @global object
 * @param object $course
 * @return boolean
 */
function apply_delete_course($course) {
    global $DB;

    //delete all templates of given course
    return $DB->delete_records('apply_template', array('course'=>$course->id));
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $apply
 * @return object
 */
function apply_user_outline($course, $user, $mod, $apply) {
    return null;
}

/**
 * Returns all users who has completed a specified apply since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @global object
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @param array $activities Passed by reference
 * @param int $index Passed by reference
 * @param int $time_modified Timestamp
 * @param int $courseid
 * @param int $cmid
 * @param int $userid
 * @param int $groupid
 * @return void
 */
function apply_get_recent_mod_activity(&$activities, &$index,
                                          $time_modified, $courseid,
                                          $cmid, $userid="", $groupid="") {

    global $CFG, $COURSE, $USER, $DB;

    if ($COURSE->id == $courseid) {
        $course = $COURSE;
    } else {
        $course = $DB->get_record('course', array('id'=>$courseid));
    }

    $modinfo = get_fast_modinfo($course);

    $cm = $modinfo->cms[$cmid];

    $sqlargs = array();

    //TODO: user user_picture::fields;
    $sql = " SELECT fk . * , fc . * , u.firstname, u.lastname, u.email, u.picture, u.email
                                            FROM {apply_completed} fc
                                                JOIN {apply} fk ON fk.id = fc.apply
                                                JOIN {user} u ON u.id = fc.userid ";

    if ($groupid) {
        $sql .= " JOIN {groups_members} gm ON  gm.userid=u.id ";
    }

    $sql .= " WHERE fc.time_modified > ? AND fk.id = ? ";
    $sqlargs[] = $time_modified;
    $sqlargs[] = $cm->instance;

    if ($userid) {
        $sql .= " AND u.id = ? ";
        $sqlargs[] = $userid;
    }

    if ($groupid) {
        $sql .= " AND gm.groupid = ? ";
        $sqlargs[] = $groupid;
    }

    if (!$applyitems = $DB->get_records_sql($sql, $sqlargs)) {
        return;
    }

    $cm_context      = context_module::instance($cm->id);
    $accessallgroups = has_capability('moodle/site:accessallgroups', $cm_context);
    $viewfullnames   = has_capability('moodle/site:viewfullnames', $cm_context);
    $groupmode       = groups_get_activity_groupmode($cm, $course);

    if (is_null($modinfo->groups)) {
        // load all my groups and cache it in modinfo
        $modinfo->groups = groups_get_user_groups($course->id);
    }

    $aname = format_string($cm->name, true);
    foreach ($applyitems as $applyitem) {
        if ($applyitem->userid != $USER->id) {

            if ($groupmode == SEPARATEGROUPS and !$accessallgroups) {
                $usersgroups = groups_get_all_groups($course->id,
                                                     $applyitem->userid,
                                                     $cm->groupingid);
                if (!is_array($usersgroups)) {
                    continue;
                }
                $usersgroups = array_keys($usersgroups);
                $intersect = array_intersect($usersgroups, $modinfo->groups[$cm->id]);
                if (empty($intersect)) {
                    continue;
                }
            }
        }

        $tmpactivity = new stdClass();

        $tmpactivity->type      = 'apply';
        $tmpactivity->cmid      = $cm->id;
        $tmpactivity->name      = $aname;
        $tmpactivity->sectionnum= $cm->sectionnum;
        $tmpactivity->timestamp = $applyitem->time_modified;

        $tmpactivity->content = new stdClass();
        $tmpactivity->content->applyid = $applyitem->id;
        $tmpactivity->content->applyuserid = $applyitem->userid;

        $userfields = explode(',', user_picture::fields());
        $tmpactivity->user = new stdClass();
        foreach ($userfields as $userfield) {
            if ($userfield == 'id') {
                $tmpactivity->user->{$userfield} = $applyitem->userid; // aliased in SQL above
            } else {
                if (!empty($applyitem->{$userfield})) {
                    $tmpactivity->user->{$userfield} = $applyitem->{$userfield};
                } else {
                    $tmpactivity->user->{$userfield} = null;
                }
            }
        }
        $tmpactivity->user->fullname = fullname($applyitem, $viewfullnames);

        $activities[$index++] = $tmpactivity;
    }

    return;
}

/**
 * Prints all users who has completed a specified apply since a given time
 * many thanks to Manolescu Dorel, who contributed these two functions
 *
 * @global object
 * @param object $activity
 * @param int $courseid
 * @param string $detail
 * @param array $modnames
 * @return void Output is echo'd
 */
function apply_print_recent_mod_activity($activity, $courseid, $detail, $modnames) {
    global $CFG, $OUTPUT;

    echo '<table border="0" cellpadding="3" cellspacing="0" class="forum-recent">';

    echo "<tr><td class=\"userpicture\" valign=\"top\">";
    echo $OUTPUT->user_picture($activity->user, array('courseid'=>$courseid));
    echo "</td><td>";

    if ($detail) {
        $modname = $modnames[$activity->type];
        echo '<div class="title">';
        echo "<img src=\"" . $OUTPUT->pix_url('icon', $activity->type) . "\" ".
             "class=\"icon\" alt=\"$modname\" />";
        echo "<a href=\"$CFG->wwwroot/mod/apply/view.php?id={$activity->cmid}\">{$activity->name}</a>";
        echo '</div>';
    }

    echo '<div class="title">';
    echo '</div>';

    echo '<div class="user">';
    echo "<a href=\"$CFG->wwwroot/user/view.php?id={$activity->user->id}&amp;course=$courseid\">"
         ."{$activity->user->fullname}</a> - ".userdate($activity->timestamp);
    echo '</div>';

    echo "</td></tr></table>";

    return;
}

/**
 * Obtains the automatic completion state for this apply based on the condition
 * in apply settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function apply_get_completion_state($course, $cm, $userid, $type) {
    global $CFG, $DB;

    // Get apply details
    $apply = $DB->get_record('apply', array('id'=>$cm->instance), '*', MUST_EXIST);

    // If completion option is enabled, evaluate it and return true/false
    if ($apply->completionsubmit) {
        $params = array('userid'=>$userid, 'apply'=>$apply->id);
        return $DB->record_exists('apply_tracking', $params);
    } else {
        // Completion option is not enabled so just return $type
        return $type;
    }
}


/**
 * Print a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $apply
 * @return bool
 */
function apply_user_complete($course, $user, $mod, $apply) {
    return true;
}

/**
 * @return bool true
 */
function apply_cron () {
    return true;
}

/**
 * @return bool false
 */
function apply_scale_used ($applyid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of apply
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any assignment
 */
function apply_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * @return array
 */
function apply_get_view_actions() {
    return array('view', 'view all');
}

/**
 * @return array
 */
function apply_get_post_actions() {
    return array('submit');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * This function will remove all responses from the specified apply
 * and clean up any related data.
 *
 * @global object
 * @global object
 * @uses APPLY_RESETFORM_RESET
 * @uses APPLY_RESETFORM_DROP
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function apply_reset_userdata($data) {
    global $CFG, $DB;

    $resetapplys = array();
    $dropapplys = array();
    $status = array();
    $componentstr = get_string('modulenameplural', 'apply');

    //get the relevant entries from $data
    foreach ($data as $key => $value) {
        switch(true) {
            case substr($key, 0, strlen(APPLY_RESETFORM_RESET)) == APPLY_RESETFORM_RESET:
                if ($value == 1) {
                    $templist = explode('_', $key);
                    if (isset($templist[3])) {
                        $resetapplys[] = intval($templist[3]);
                    }
                }
            break;
            case substr($key, 0, strlen(APPLY_RESETFORM_DROP)) == APPLY_RESETFORM_DROP:
                if ($value == 1) {
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
        $status[] = array('component'=>$componentstr.':'.$apply->name,
                        'item'=>get_string('resetting_data', 'apply'),
                        'error'=>false);
    }

    return $status;
}

/**
 * Called by course/reset.php
 *
 * @global object
 * @uses APPLY_RESETFORM_RESET
 * @param object $mform form passed by reference
 */
function apply_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'applyheader', get_string('modulenameplural', 'apply'));

    if (!$applys = $DB->get_records('apply', array('course'=>$COURSE->id), 'name')) {
        return;
    }

    $mform->addElement('static', 'hint', get_string('resetting_data', 'apply'));
    foreach ($applys as $apply) {
        $mform->addElement('checkbox', APPLY_RESETFORM_RESET.$apply->id, $apply->name);
    }
}

/**
 * Course reset form defaults.
 *
 * @global object
 * @uses APPLY_RESETFORM_RESET
 * @param object $course
 */
function apply_reset_course_form_defaults($course) {
    global $DB;

    $return = array();
    if (!$applys = $DB->get_records('apply', array('course'=>$course->id), 'name')) {
        return;
    }
    foreach ($applys as $apply) {
        $return[APPLY_RESETFORM_RESET.$apply->id] = true;
    }
    return $return;
}

/**
 * Called by course/reset.php and shows the formdata by coursereset.
 * it prints checkboxes for each apply available at the given course
 * there are two checkboxes:
 * 1) delete userdata and keep the apply
 * 2) delete userdata and drop the apply
 *
 * @global object
 * @uses APPLY_RESETFORM_RESET
 * @uses APPLY_RESETFORM_DROP
 * @param object $course
 * @return void
 */
function apply_reset_course_form($course) {
    global $DB, $OUTPUT;

    echo get_string('resetting_applys', 'apply'); echo ':<br />';
    if (!$applys = $DB->get_records('apply', array('course'=>$course->id), 'name')) {
        return;
    }

    foreach ($applys as $apply) {
        echo '<p>';
        echo get_string('name', 'apply').': '.$apply->name.'<br />';
        echo html_writer::checkbox(APPLY_RESETFORM_RESET.$apply->id,
                                1, true,
                                get_string('resetting_data', 'apply'));
        echo '<br />';
        echo html_writer::checkbox(APPLY_RESETFORM_DROP.$apply->id,
                                1, false,
                                get_string('drop_apply', 'apply'));
        echo '</p>';
    }
}

/**
 * This gets an array with default options for the editor
 *
 * @return array the options
 */
function apply_get_editor_options() {
    return array('maxfiles' => EDITOR_UNLIMITED_FILES,
                'trusttext'=>true);
}

/**
 * This creates new events given as time_open and closeopen by $apply.
 *
 * @global object
 * @param object $apply
 * @return void
 */
function apply_set_events($apply) {
    global $DB;

    // adding the apply to the eventtable (I have seen this at quiz-module)
    $DB->delete_records('event', array('modulename'=>'apply', 'instance'=>$apply->id));

    if (!isset($apply->coursemodule)) {
        $cm = get_coursemodule_from_id('apply', $apply->id);
        $apply->coursemodule = $cm->id;
    }

    // the open-event
    if ($apply->time_open > 0) {
        $event = new stdClass();
        $event->name         = get_string('start', 'apply').' '.$apply->name;
        $event->description  = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid     = $apply->course;
        $event->groupid      = 0;
        $event->userid       = 0;
        $event->modulename   = 'apply';
        $event->instance     = $apply->id;
        $event->eventtype    = 'open';
        $event->timestart    = $apply->time_open;
        $event->visible      = instance_is_visible('apply', $apply);
        if ($apply->time_close > 0) {
            $event->timeduration = ($apply->time_close - $apply->time_open);
        } else {
            $event->timeduration = 0;
        }

        calendar_event::create($event);
    }

    // the close-event
    if ($apply->time_close > 0) {
        $event = new stdClass();
        $event->name         = get_string('stop', 'apply').' '.$apply->name;
        $event->description  = format_module_intro('apply', $apply, $apply->coursemodule);
        $event->courseid     = $apply->course;
        $event->groupid      = 0;
        $event->userid       = 0;
        $event->modulename   = 'apply';
        $event->instance     = $apply->id;
        $event->eventtype    = 'close';
        $event->timestart    = $apply->time_close;
        $event->visible      = instance_is_visible('apply', $apply);
        $event->timeduration = 0;

        calendar_event::create($event);
    }
}

/**
 * this function is called by {@link apply_delete_userdata()}
 * it drops the apply-instance from the course_module table
 *
 * @global object
 * @param int $id the id from the coursemodule
 * @return boolean
 */
function apply_delete_course_module($id) {
    global $DB;

    if (!$cm = $DB->get_record('course_modules', array('id'=>$id))) {
        return true;
    }
    return $DB->delete_records('course_modules', array('id'=>$cm->id));
}



////////////////////////////////////////////////
//functions to handle capabilities
////////////////////////////////////////////////

/**
 * returns the context-id related to the given coursemodule-id
 *
 * @staticvar object $context
 * @param int $cmid the coursemodule-id
 * @return object $context
 */
function apply_get_context($cmid) {
    static $context;

    if (isset($context)) {
        return $context;
    }

    $context = context_module::instance($cmid);
    return $context;
}

/**
 *  returns true if the current role is faked by switching role feature
 *
 * @global object
 * @return boolean
 */
function apply_check_is_switchrole() {
    global $USER;
    if (isset($USER->switchrole) AND
            is_array($USER->switchrole) AND
            count($USER->switchrole) > 0) {

        return true;
    }
    return false;
}

/**
 * count users which have not completed the apply
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @param object $cm
 * @param int $group single groupid
 * @param string $sort
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 */
function apply_get_incomplete_users($cm,
                                       $group = false,
                                       $sort = '',
                                       $startpage = false,
                                       $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    //first get all user who can complete this apply
    $cap = 'mod/apply:complete';
    $fields = 'u.id, u.username';
    if (!$allusers = get_users_by_capability($context,
                                            $cap,
                                            $fields,
                                            $sort,
                                            '',
                                            '',
                                            $group,
                                            '',
                                            true)) {
        return false;
    }
    $allusers = array_keys($allusers);

    //now get all completeds
    $params = array('apply'=>$cm->instance);
    if (!$completedusers = $DB->get_records_menu('apply_completed', $params, '', 'userid,id')) {
        return $allusers;
    }
    $completedusers = array_keys($completedusers);

    //now strike all completedusers from allusers
    $allusers = array_diff($allusers, $completedusers);

    //for paging I use array_slice()
    if ($startpage !== false AND $pagecount !== false) {
        $allusers = array_slice($allusers, $startpage, $pagecount);
    }

    return $allusers;
}

/**
 * count users which have not completed the apply
 *
 * @global object
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 */
function apply_count_incomplete_users($cm, $group = false) {
    if ($allusers = apply_get_incomplete_users($cm, $group)) {
        return count($allusers);
    }
    return 0;
}

/**
 * count users which have completed a apply
 *
 * @global object
 * @uses APPLY_ANONYMOUS_NO
 * @param object $cm
 * @param int $group single groupid
 * @return int count of userrecords
 */
function apply_count_complete_users($cm, $group = false) {
    global $DB;

//    $params = array(APPLY_ANONYMOUS_NO, $cm->instance);
    $params = array($cm->instance);

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = ? AND g.userid = c.userid';
        $params[] = $group;
    }

    $sql = 'SELECT COUNT(u.id) FROM {user} u, {apply_completed} c'.$fromgroup.'
              WHERE u.id = c.userid AND c.apply = ?
              '.$wheregroup;

//            WHERE anonymous_response = ? AND u.id = c.userid AND c.apply = ?

    return $DB->count_records_sql($sql, $params);

}

/**
 * get users which have completed a apply
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @uses APPLY_ANONYMOUS_NO
 * @param object $cm
 * @param int $group single groupid
 * @param string $where a sql where condition (must end with " AND ")
 * @param array parameters used in $where
 * @param string $sort a table field
 * @param int $startpage
 * @param int $pagecount
 * @return object the userrecords
 */
function apply_get_complete_users($cm,
                                     $group = false,
                                     $where = '',
                                     array $params = null,
                                     $sort = '',
                                     $startpage = false,
                                     $pagecount = false) {

    global $DB;

    $context = context_module::instance($cm->id);

    $params = (array)$params;

//    $params['anon'] = APPLY_ANONYMOUS_NO;
    $params['instance'] = $cm->instance;

    $fromgroup = '';
    $wheregroup = '';
    if ($group) {
        $fromgroup = ', {groups_members} g';
        $wheregroup = ' AND g.groupid = :group AND g.userid = c.userid';
        $params['group'] = $group;
    }

    if ($sort) {
        $sortsql = ' ORDER BY '.$sort;
    } else {
        $sortsql = '';
    }

    $ufields = user_picture::fields('u');
    $sql = 'SELECT DISTINCT '.$ufields.', c.time_modified as completed_time_modified
            FROM {user} u, {apply_completed} c '.$fromgroup.'
            WHERE '.$where.' u.id = c.userid
                AND c.apply = :instance
              '.$wheregroup.$sortsql;

//            WHERE '.$where.' anonymous_response = :anon
//                AND u.id = c.userid

    if ($startpage === false OR $pagecount === false) {
        $startpage = false;
        $pagecount = false;
    }
    return $DB->get_records_sql($sql, $params, $startpage, $pagecount);
}

/**
 * get users which have the viewreports-capability
 *
 * @uses CONTEXT_MODULE
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 */
function apply_get_viewreports_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
                            'mod/apply:viewreports',
                            '',
                            'lastname',
                            '',
                            '',
                            $groups,
                            '',
                            false);
}

/**
 * get users which have the receivemail-capability
 *
 * @uses CONTEXT_MODULE
 * @param int $cmid
 * @param mixed $groups single groupid or array of groupids - group(s) user is in
 * @return object the userrecords
 */
function apply_get_receivemail_users($cmid, $groups = false) {

    $context = context_module::instance($cmid);

    //description of the call below:
    //get_users_by_capability($context, $capability, $fields='', $sort='', $limitfrom='',
    //                          $limitnum='', $groups='', $exceptions='', $doanything=true)
    return get_users_by_capability($context,
                            'mod/apply:receivemail',
                            '',
                            'lastname',
                            '',
                            '',
                            $groups,
                            '',
                            false);
}

////////////////////////////////////////////////
//functions to handle the templates
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * creates a new template-record.
 *
 * @global object
 * @param int $courseid
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return int the new templateid
 */
function apply_create_template($courseid, $name, $ispublic = 0) {
    global $DB;

    $templ = new stdClass();
    $templ->course   = ($ispublic ? 0 : $courseid);
    $templ->name     = $name;
    $templ->ispublic = $ispublic;

    $templid = $DB->insert_record('apply_template', $templ);
    return $DB->get_record('apply_template', array('id'=>$templid));
}

/**
 * creates new template items.
 * all items will be copied and the attribute apply will be set to 0
 * and the attribute template will be set to the new templateid
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CONTEXT_COURSE
 * @param object $apply
 * @param string $name the name of template shown in the templatelist
 * @param int $ispublic 0:privat 1:public
 * @return boolean
 */
function apply_save_as_template($apply, $name, $ispublic = 0) {
    global $DB;
    $fs = get_file_storage();

    if (!$applyitems = $DB->get_records('apply_item', array('apply'=>$apply->id))) {
        return false;
    }

    if (!$newtempl = apply_create_template($apply->course, $name, $ispublic)) {
        return false;
    }

    //files in the template_item are in the context of the current course or
    //if the template is public the files are in the system context
    //files in the apply_item are in the apply_context of the apply
    if ($ispublic) {
        $s_context = get_system_context();
    } else {
        $s_context = context_course::instance($newtempl->course);
    }
    $cm = get_coursemodule_from_instance('apply', $apply->id);
    $f_context = context_module::instance($cm->id);

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($applyitems as $item) {

        $t_item = clone($item);

        unset($t_item->id);
        $t_item->apply = 0;
        $t_item->template     = $newtempl->id;
        $t_item->id = $DB->insert_record('apply_item', $t_item);
        //copy all included files to the apply_template filearea
        $itemfiles = $fs->get_area_files($f_context->id,
                                    'mod_apply',
                                    'item',
                                    $item->id,
                                    "id",
                                    false);
        if ($itemfiles) {
            foreach ($itemfiles as $ifile) {
                $file_record = new stdClass();
                $file_record->contextid = $s_context->id;
                $file_record->component = 'mod_apply';
                $file_record->filearea = 'template';
                $file_record->itemid = $t_item->id;
                $fs->create_file_from_storedfile($file_record, $ifile);
            }
        }

        $itembackup[$item->id] = $t_item->id;
        if ($t_item->dependitem) {
            $dependitemsmap[$t_item->id] = $t_item->dependitem;
        }

    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('apply_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('apply_item', $newitem);
    }

    return true;
}

/**
 * deletes all apply_items related to the given template id
 *
 * @global object
 * @uses CONTEXT_COURSE
 * @param object $template the template
 * @return void
 */
function apply_delete_template($template) {
    global $DB;

    //deleting the files from the item is done by apply_delete_item
    if ($t_items = $DB->get_records("apply_item", array("template"=>$template->id))) {
        foreach ($t_items as $t_item) {
            apply_delete_item($t_item->id, false, $template);
        }
    }
    $DB->delete_records("apply_template", array("id"=>$template->id));
}

/**
 * creates new apply_item-records from template.
 * if $deleteold is set true so the existing items of the given apply will be deleted
 * if $deleteold is set false so the new items will be appanded to the old items
 *
 * @global object
 * @uses CONTEXT_COURSE
 * @uses CONTEXT_MODULE
 * @param object $apply
 * @param int $templateid
 * @param boolean $deleteold
 */
function apply_items_from_template($apply, $templateid, $deleteold = false) {
    global $DB, $CFG;

    require_once($CFG->libdir.'/completionlib.php');

    $fs = get_file_storage();

    if (!$template = $DB->get_record('apply_template', array('id'=>$templateid))) {
        return false;
    }
    //get all templateitems
    if (!$templitems = $DB->get_records('apply_item', array('template'=>$templateid))) {
        return false;
    }

    //files in the template_item are in the context of the current course
    //files in the apply_item are in the apply_context of the apply
    if ($template->ispublic) {
        $s_context = get_system_context();
    } else {
        $s_context = context_course::instance($apply->course);
    }
    $course = $DB->get_record('course', array('id'=>$apply->course));
    $cm = get_coursemodule_from_instance('apply', $apply->id);
    $f_context = context_module::instance($cm->id);

    //if deleteold then delete all old items before
    //get all items
    if ($deleteold) {
        if ($applyitems = $DB->get_records('apply_item', array('apply'=>$apply->id))) {
            //delete all items of this apply
            foreach ($applyitems as $item) {
                apply_delete_item($item->id, false);
            }
            //delete tracking-data
            $DB->delete_records('apply_tracking', array('apply'=>$apply->id));

            $params = array('apply'=>$apply->id);
            if ($completeds = $DB->get_records('apply_completed', $params)) {
                $completion = new completion_info($course);
                foreach ($completeds as $completed) {
                    // Update completion state
                    if ($completion->is_enabled($cm) && $apply->completionsubmit) {
                        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
                    }
                    $DB->delete_records('apply_completed', array('id'=>$completed->id));
                }
            }
            $DB->delete_records('apply_completedtmp', array('apply'=>$apply->id));
        }
        $positionoffset = 0;
    } else {
        //if the old items are kept the new items will be appended
        //therefor the new position has an offset
        $positionoffset = $DB->count_records('apply_item', array('apply'=>$apply->id));
    }

    //create items of this new template
    //depend items we are storing temporary in an mapping list array(new id => dependitem)
    //we also store a mapping of all items array(oldid => newid)
    $dependitemsmap = array();
    $itembackup = array();
    foreach ($templitems as $t_item) {
        $item = clone($t_item);
        unset($item->id);
        $item->apply = $apply->id;
        $item->template = 0;
        $item->position = $item->position + $positionoffset;

        $item->id = $DB->insert_record('apply_item', $item);

        //moving the files to the new item
        $templatefiles = $fs->get_area_files($s_context->id,
                                        'mod_apply',
                                        'template',
                                        $t_item->id,
                                        "id",
                                        false);
        if ($templatefiles) {
            foreach ($templatefiles as $tfile) {
                $file_record = new stdClass();
                $file_record->contextid = $f_context->id;
                $file_record->component = 'mod_apply';
                $file_record->filearea = 'item';
                $file_record->itemid = $item->id;
                $fs->create_file_from_storedfile($file_record, $tfile);
            }
        }

        $itembackup[$t_item->id] = $item->id;
        if ($item->dependitem) {
            $dependitemsmap[$item->id] = $item->dependitem;
        }
    }

    //remapping the dependency
    foreach ($dependitemsmap as $key => $dependitem) {
        $newitem = $DB->get_record('apply_item', array('id'=>$key));
        $newitem->dependitem = $itembackup[$newitem->dependitem];
        $DB->update_record('apply_item', $newitem);
    }
}

/**
 * get the list of available templates.
 * if the $onlyown param is set true so only templates from own course will be served
 * this is important for droping templates
 *
 * @global object
 * @param object $course
 * @param string $onlyownorpublic
 * @return array the template recordsets
 */
function apply_get_template_list($course, $onlyownorpublic = '') {
    global $DB, $CFG;

    switch($onlyownorpublic) {
        case '':
            $templates = $DB->get_records_select('apply_template',
                                                 'course = ? OR ispublic = 1',
                                                 array($course->id),
                                                 'name');
            break;
        case 'own':
            $templates = $DB->get_records('apply_template',
                                          array('course'=>$course->id),
                                          'name');
            break;
        case 'public':
            $templates = $DB->get_records('apply_template', array('ispublic'=>1), 'name');
            break;
    }
    return $templates;
}

////////////////////////////////////////////////
//Handling der Items
////////////////////////////////////////////////
////////////////////////////////////////////////

/**
 * load the lib.php from item-plugin-dir and returns the instance of the itemclass
 *
 * @global object
 * @param object $item
 * @return object the instanz of itemclass
 */
function apply_get_item_class($typ) {
    global $CFG;

    //get the class of item-typ
    $itemclass = 'apply_item_'.$typ;
    //get the instance of item-class
    if (!class_exists($itemclass)) {
        require_once($CFG->dirroot.'/mod/apply/item/'.$typ.'/lib.php');
    }
    return new $itemclass();
}

/**
 * load the available item plugins from given subdirectory of $CFG->dirroot
 * the default is "mod/apply/item"
 *
 * @global object
 * @param string $dir the subdir
 * @return array pluginnames as string
 */
function apply_load_apply_items($dir = 'mod/apply/item') {
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

/**
 * load the available item plugins to use as dropdown-options
 *
 * @global object
 * @return array pluginnames as string
 */
function apply_load_apply_items_options() {
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

/**
 * load the available items for the depend item dropdown list shown in the edit_item form
 *
 * @global object
 * @param object $apply
 * @param object $item the item of the edit_item form
 * @return array all items except the item $item, labels and pagebreaks
 */
function apply_get_depend_candidates_for_item($apply, $item) {
    global $DB;
    //all items for dependitem
    $where = "apply = ? AND typ != 'pagebreak' AND hasvalue = 1";
    $params = array($apply->id);
    if (isset($item->id) AND $item->id) {
        $where .= ' AND id != ?';
        $params[] = $item->id;
    }
    $dependitems = array(0 => get_string('choose'));
    $applyitems = $DB->get_records_select_menu('apply_item',
                                                  $where,
                                                  $params,
                                                  'position',
                                                  'id, label');

    if (!$applyitems) {
        return $dependitems;
    }
    //adding the choose-option
    foreach ($applyitems as $key => $val) {
        $dependitems[$key] = $val;
    }
    return $dependitems;
}

/**
 * creates a new item-record
 *
 * @global object
 * @param object $data the data from edit_item_form
 * @return int the new itemid
 */
function apply_create_item($data) {
    global $DB;

    $item = new stdClass();
    $item->apply = $data->applyid;

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

    $item->typ = $data->typ;
    $item->position = $data->position;

    $item->required=0;
    if (!empty($data->required)) {
        $item->required = $data->required;
    }

    $item->id = $DB->insert_record('apply_item', $item);

    //move all itemdata to the data
    $data->id = $item->id;
    $data->apply = $item->apply;
    $data->name = $item->name;
    $data->label = $item->label;
    $data->required = $item->required;
    return $itemobj->postupdate($data);
}

/**
 * save the changes of a given item.
 *
 * @global object
 * @param object $item
 * @return boolean
 */
function apply_update_item($item) {
    global $DB;
    return $DB->update_record("apply_item", $item);
}

/**
 * deletes an item and also deletes all related values
 *
 * @global object
 * @uses CONTEXT_MODULE
 * @param int $itemid
 * @param boolean $renumber should the kept items renumbered Yes/No
 * @param object $template if the template is given so the items are bound to it
 * @return void
 */
function apply_delete_item($itemid, $renumber = true, $template = false) {
    global $DB;

    $item = $DB->get_record('apply_item', array('id'=>$itemid));

    //deleting the files from the item
    $fs = get_file_storage();

    if ($template) {
        if ($template->ispublic) {
            $context = get_system_context();
        } else {
            $context = context_course::instance($template->course);
        }
        $templatefiles = $fs->get_area_files($context->id,
                                    'mod_apply',
                                    'template',
                                    $item->id,
                                    "id",
                                    false);

        if ($templatefiles) {
            $fs->delete_area_files($context->id, 'mod_apply', 'template', $item->id);
        }
    } else {
        if (!$cm = get_coursemodule_from_instance('apply', $item->apply)) {
            return false;
        }
        $context = context_module::instance($cm->id);

        $itemfiles = $fs->get_area_files($context->id,
                                    'mod_apply',
                                    'item',
                                    $item->id,
                                    "id", false);

        if ($itemfiles) {
            $fs->delete_area_files($context->id, 'mod_apply', 'item', $item->id);
        }
    }

    $DB->delete_records("apply_value", array("item"=>$itemid));
    $DB->delete_records("apply_valuetmp", array("item"=>$itemid));

    //remove all depends
    $DB->set_field('apply_item', 'dependvalue', '', array('dependitem'=>$itemid));
    $DB->set_field('apply_item', 'dependitem', 0, array('dependitem'=>$itemid));

    $DB->delete_records("apply_item", array("id"=>$itemid));
    if ($renumber) {
        apply_renumber_items($item->apply);
    }
}

/**
 * deletes all items of the given applyid
 *
 * @global object
 * @param int $applyid
 * @return void
 */
function apply_delete_all_items($applyid) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if (!$apply = $DB->get_record('apply', array('id'=>$applyid))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('apply', $apply->id)) {
        return false;
    }

    if (!$course = $DB->get_record('course', array('id'=>$apply->course))) {
        return false;
    }

    if (!$items = $DB->get_records('apply_item', array('apply'=>$applyid))) {
        return;
    }
    foreach ($items as $item) {
        apply_delete_item($item->id, false);
    }
    if ($completeds = $DB->get_records('apply_completed', array('apply'=>$apply->id))) {
        $completion = new completion_info($course);
        foreach ($completeds as $completed) {
            // Update completion state
            if ($completion->is_enabled($cm) && $apply->completionsubmit) {
                $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
            }
            $DB->delete_records('apply_completed', array('id'=>$completed->id));
        }
    }

    $DB->delete_records('apply_completedtmp', array('apply'=>$applyid));

}

/**
 * this function toggled the item-attribute required (yes/no)
 *
 * @global object
 * @param object $item
 * @return boolean
 */
function apply_switch_item_required($item) {
    global $DB, $CFG;

    $itemobj = apply_get_item_class($item->typ);

    if ($itemobj->can_switch_require()) {
        $new_require_val = (int)!(bool)$item->required;
        $params = array('id'=>$item->id);
        $DB->set_field('apply_item', 'required', $new_require_val, $params);
    }
    return true;
}

/**
 * renumbers all items of the given applyid
 *
 * @global object
 * @param int $applyid
 * @return void
 */
function apply_renumber_items($applyid) {
    global $DB;

    $items = $DB->get_records('apply_item', array('apply'=>$applyid), 'position');
    $pos = 1;
    if ($items) {
        foreach ($items as $item) {
            $DB->set_field('apply_item', 'position', $pos, array('id'=>$item->id));
            $pos++;
        }
    }
}

/**
 * this decreases the position of the given item
 *
 * @global object
 * @param object $item
 * @return bool
 */
function apply_moveup_item($item) {
    global $DB;

    if ($item->position == 1) {
        return true;
    }

    $params = array('apply'=>$item->apply);
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
            apply_renumber_items($item->apply);
            return true;
        }
        $itembefore = $i;
    }
    return false;
}

/**
 * this increased the position of the given item
 *
 * @global object
 * @param object $item
 * @return bool
 */
function apply_movedown_item($item) {
    global $DB;

    $params = array('apply'=>$item->apply);
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
            apply_renumber_items($item->apply);
            return true;
        }
        $movedownitem = $i;
    }
    return false;
}

/**
 * here the position of the given item will be set to the value in $pos
 *
 * @global object
 * @param object $moveitem
 * @param int $pos
 * @return boolean
 */
function apply_move_item($moveitem, $pos) {
    global $DB;

    $params = array('apply'=>$moveitem->apply);
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

/**
 * prints the given item as a preview.
 * each item-class has an own print_item_preview function implemented.
 *
 * @global object
 * @param object $item the item what we want to print out
 * @return void
 */
function apply_print_item_preview($item) {
    global $CFG;
    if ($item->typ == 'pagebreak') {
        return;
    }
    //get the instance of the item-class
    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_preview($item);
}

/**
 * prints the given item in the completion form.
 * each item-class has an own print_item_complete function implemented.
 *
 * @param object $item the item what we want to print out
 * @param mixed $value the value
 * @param boolean $highlightrequire if this set true and the value are false on completing so the item will be highlighted
 * @return void
 */
function apply_print_item_complete($item, $value = false, $highlightrequire = false) {
    global $CFG;
    if ($item->typ == 'pagebreak') {
        return;
    }

    //get the instance of the item-class
    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_complete($item, $value, $highlightrequire);
}

/**
 * prints the given item in the show entries page.
 * each item-class has an own print_item_show_value function implemented.
 *
 * @param object $item the item what we want to print out
 * @param mixed $value
 * @return void
 */
function apply_print_item_show_value($item, $value = false) {
    global $CFG;
    if ($item->typ == 'pagebreak') {
        return;
    }

    //get the instance of the item-class
    $itemobj = apply_get_item_class($item->typ);
    $itemobj->print_item_show_value($item, $value);
}

/**
 * if the user completes a apply and there is a pagebreak so the values are saved temporary.
 * the values are not saved permanently until the user click on save button
 *
 * @global object
 * @param object $applycompleted
 * @return object temporary saved completed-record
 */
function apply_set_tmp_values($applycompleted) {
    global $DB;

    //first we create a completedtmp
    $tmpcpl = new stdClass();
    foreach ($applycompleted as $key => $value) {
        $tmpcpl->{$key} = $value;
    }
    unset($tmpcpl->id);
    $tmpcpl->time_modified = time();
    $tmpcpl->id = $DB->insert_record('apply_completedtmp', $tmpcpl);
    //get all values of original-completed
    if (!$values = $DB->get_records('apply_value', array('completed'=>$applycompleted->id))) {
        return;
    }
    foreach ($values as $value) {
        unset($value->id);
        $value->completed = $tmpcpl->id;
        $DB->insert_record('apply_valuetmp', $value);
    }
    return $tmpcpl;
}

/**
 * this saves the temporary saved values permanently
 *
 * @global object
 * @param object $applycompletedtmp the temporary completed
 * @param object $applycompleted the target completed
 * @param int $userid
 * @return int the id of the completed
 */
function apply_save_tmp_values($applycompletedtmp, $applycompleted, $userid) {
    global $DB;

    $tmpcplid = $applycompletedtmp->id;
    if ($applycompleted) {
        //first drop all existing values
        $DB->delete_records('apply_value', array('completed'=>$applycompleted->id));
        //update the current completed
        $applycompleted->time_modified = time();
        $DB->update_record('apply_completed', $applycompleted);
    } else {
        $applycompleted = clone($applycompletedtmp);
        $applycompleted->id = '';
        $applycompleted->userid = $userid;
        $applycompleted->time_modified = time();
        $applycompleted->id = $DB->insert_record('apply_completed', $applycompleted);
    }

    //save all the new values from apply_valuetmp
    //get all values of tmp-completed
    $params = array('completed'=>$applycompletedtmp->id);
    if (!$values = $DB->get_records('apply_valuetmp', $params)) {
        return false;
    }
    foreach ($values as $value) {
        //check if there are depend items
        $item = $DB->get_record('apply_item', array('id'=>$value->item));
        if ($item->dependitem > 0) {
            $check = apply_compare_item_value($tmpcplid,
                                        $item->dependitem,
                                        $item->dependvalue,
                                        true);
        } else {
            $check = true;
        }
        if ($check) {
            unset($value->id);
            $value->completed = $applycompleted->id;
            $DB->insert_record('apply_value', $value);
        }
    }
    //drop all the tmpvalues
    $DB->delete_records('apply_valuetmp', array('completed'=>$tmpcplid));
    $DB->delete_records('apply_completedtmp', array('id'=>$tmpcplid));
    return $applycompleted->id;

}

/**
 * deletes the given temporary completed and all related temporary values
 *
 * @global object
 * @param int $tmpcplid
 * @return void
 */
function apply_delete_completedtmp($tmpcplid) {
    global $DB;

    $DB->delete_records('apply_valuetmp', array('completed'=>$tmpcplid));
    $DB->delete_records('apply_completedtmp', array('id'=>$tmpcplid));
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the pagebreaks
////////////////////////////////////////////////

/**
 * this creates a pagebreak.
 * a pagebreak is a special kind of item
 *
 * @global object
 * @param int $applyid
 * @return mixed false if there already is a pagebreak on last position or the id of the pagebreak-item
 */
function apply_create_pagebreak($applyid) {
    global $DB;

    //check if there already is a pagebreak on the last position
    $lastposition = $DB->count_records('apply_item', array('apply'=>$applyid));
    if ($lastposition == apply_get_last_break_position($applyid)) {
        return false;
    }

    $item = new stdClass();
    $item->apply = $applyid;

    $item->template=0;

    $item->name = '';

    $item->presentation = '';
    $item->hasvalue = 0;

    $item->typ = 'pagebreak';
    $item->position = $lastposition + 1;

    $item->required=0;

    return $DB->insert_record('apply_item', $item);
}

/**
 * get all positions of pagebreaks in the given apply
 *
 * @global object
 * @param int $applyid
 * @return array all ordered pagebreak positions
 */
function apply_get_all_break_positions($applyid) {
    global $DB;

    $params = array('typ'=>'pagebreak', 'apply'=>$applyid);
    $allbreaks = $DB->get_records_menu('apply_item', $params, 'position', 'id, position');
    if (!$allbreaks) {
        return false;
    }
    return array_values($allbreaks);
}

/**
 * get the position of the last pagebreak
 *
 * @param int $applyid
 * @return int the position of the last pagebreak
 */
function apply_get_last_break_position($applyid) {
    if (!$allbreaks = apply_get_all_break_positions($applyid)) {
        return false;
    }
    return $allbreaks[count($allbreaks) - 1];
}

/**
 * this returns the position where the user can continue the completing.
 *
 * @global object
 * @global object
 * @global object
 * @param int $applyid
 * @param int $courseid
 * @param string $guestid this id will be saved temporary and is unique
 * @return int the position to continue
 */
function apply_get_page_to_continue($applyid, $courseid = false, $guestid = false) {
    global $CFG, $USER, $DB;

    //is there any break?

    if (!$allbreaks = apply_get_all_break_positions($applyid)) {
        return false;
    }

    $params = array();
    if ($courseid) {
        $courseselect = "AND fv.course_id = :courseid";
        $params['courseid'] = $courseid;
    } else {
        $courseselect = '';
    }

    if ($guestid) {
        $userselect = "AND fc.guestid = :guestid";
        $usergroup = "GROUP BY fc.guestid";
        $params['guestid'] = $guestid;
    } else {
        $userselect = "AND fc.userid = :userid";
        $usergroup = "GROUP BY fc.userid";
        $params['userid'] = $USER->id;
    }

    $sql =  "SELECT MAX(fi.position)
               FROM {apply_completedtmp} fc, {apply_valuetmp} fv, {apply_item} fi
              WHERE fc.id = fv.completed
                    $userselect
                    AND fc.apply = :applyid
                    $courseselect
                    AND fi.id = fv.item
         $usergroup";
    $params['applyid'] = $applyid;

    $lastpos = $DB->get_field_sql($sql, $params);

    //the index of found pagebreak is the searched pagenumber
    foreach ($allbreaks as $pagenr => $br) {
        if ($lastpos < $br) {
            return $pagenr;
        }
    }
    return count($allbreaks);
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle the values
////////////////////////////////////////////////

/**
 * cleans the userinput while submitting the form.
 *
 * @param mixed $value
 * @return mixed
 */
function apply_clean_input_value($item, $value) {
    $itemobj = apply_get_item_class($item->typ);
    return $itemobj->clean_input_value($value);
}

/**
 * this saves the values of an completed.
 * if the param $tmp is set true so the values are saved temporary in table apply_valuetmp.
 * if there is already a completed and the userid is set so the values are updated.
 * on all other things new value records will be created.
 *
 * @global object
 * @param int $userid
 * @param boolean $tmp
 * @return mixed false on error or the completeid
 */
function apply_save_values($usrid, $tmp = false) {
    global $DB;

    $completedid = optional_param('completedid', 0, PARAM_INT);

    $tmpstr = $tmp ? 'tmp' : '';
    $time = time();
    $time_modified = mktime(0, 0, 0, date('m', $time), date('d', $time), date('Y', $time));

    if ($usrid == 0) {
        return apply_create_values($usrid, $time_modified, $tmp);
    }
    $completed = $DB->get_record('apply_completed'.$tmpstr, array('id'=>$completedid));
    if (!$completed) {
        return apply_create_values($usrid, $time_modified, $tmp);
    } else {
        $completed->time_modified = $time_modified;
        return apply_update_values($completed, $tmp);
    }
}

/**
 * this saves the values from anonymous user such as guest on the main-site
 *
 * @global object
 * @param string $guestid the unique guestidentifier
 * @return mixed false on error or the completeid
 */
function apply_save_guest_values($guestid) {
    global $DB;

    $completedid = optional_param('completedid', false, PARAM_INT);

    $time_modified = time();
    if (!$completed = $DB->get_record('apply_completedtmp', array('id'=>$completedid))) {
        return apply_create_values(0, $time_modified, true, $guestid);
    } else {
        $completed->time_modified = $time_modified;
        return apply_update_values($completed, true);
    }
}

/**
 * get the value from the given item related to the given completed.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp
 *
 * @global object
 * @param int $completeid
 * @param int $itemid
 * @param boolean $tmp
 * @return mixed the value, the type depends on plugin-definition
 */
function apply_get_item_value($completedid, $itemid, $tmp = false) {
    global $DB;

    $tmpstr = $tmp ? 'tmp' : '';
    $params = array('completed'=>$completedid, 'item'=>$itemid);
    return $DB->get_field('apply_value'.$tmpstr, 'value', $params);
}

/**
 * compares the value of the itemid related to the completedid with the dependvalue.
 * this is used if a depend item is set.
 * the value can come as temporary or as permanently value. the deciding is done by $tmp.
 *
 * @global object
 * @global object
 * @param int $completeid
 * @param int $itemid
 * @param mixed $dependvalue
 * @param boolean $tmp
 * @return bool
 */
function apply_compare_item_value($completedid, $itemid, $dependvalue, $tmp = false) {
    global $DB, $CFG;

    $dbvalue = apply_get_item_value($completedid, $itemid, $tmp);

    //get the class of the given item-typ
    $item = $DB->get_record('apply_item', array('id'=>$itemid));

    //get the instance of the item-class
    $itemobj = apply_get_item_class($item->typ);
    return $itemobj->compare_value($item, $dbvalue, $dependvalue); //true or false
}

/**
 * this function checks the correctness of values.
 * the rules for this are implemented in the class of each item.
 * it can be the required attribute or the value self e.g. numeric.
 * the params first/lastitem are given to determine the visible range between pagebreaks.
 *
 * @global object
 * @param int $firstitem the position of firstitem for checking
 * @param int $lastitem the position of lastitem for checking
 * @return boolean
 */
function apply_check_values($firstitem, $lastitem) {
    global $DB, $CFG;

    $applyid = optional_param('applyid', 0, PARAM_INT);

    //get all items between the first- and lastitem
    $select = "apply = ?
                    AND position >= ?
                    AND position <= ?
                    AND hasvalue = 1";
    $params = array($applyid, $firstitem, $lastitem);
    if (!$applyitems = $DB->get_records_select('apply_item', $select, $params)) {
        //if no values are given so no values can be wrong ;-)
        return true;
    }

    foreach ($applyitems as $item) {
        //get the instance of the item-class
        $itemobj = apply_get_item_class($item->typ);

        //the name of the input field of the completeform is given in a special form:
        //<item-typ>_<item-id> eg. numeric_234
        //this is the key to get the value for the correct item
        $formvalname = $item->typ . '_' . $item->id;

        if ($itemobj->value_is_array()) {
            //get the raw value here. It is cleaned after that by the object itself
            $value = optional_param_array($formvalname, null, PARAM_RAW);
        } else {
            //get the raw value here. It is cleaned after that by the object itself
            $value = optional_param($formvalname, null, PARAM_RAW);
        }
        $value = $itemobj->clean_input_value($value);

        //check if the value is set
        if (is_null($value) AND $item->required == 1) {
            return false;
        }

        //now we let check the value by the item-class
        if (!$itemobj->check_value($value, $item)) {
            return false;
        }
    }
    //if no wrong values so we can return true
    return true;
}

/**
 * this function create a complete-record and the related value-records.
 * depending on the $tmp (true/false) the values are saved temporary or permanently
 *
 * @global object
 * @param int $userid
 * @param int $time_modified
 * @param boolean $tmp
 * @param string $guestid a unique identifier to save temporary data
 * @return mixed false on error or the completedid
 */
function apply_create_values($usrid, $time_modified, $tmp = false, $guestid = false) {
    global $DB;

    $applyid = optional_param('applyid', false, PARAM_INT);
//    $anonymous_response = optional_param('anonymous_response', false, PARAM_INT);
    $courseid = optional_param('courseid', false, PARAM_INT);

    $tmpstr = $tmp ? 'tmp' : '';
    //first we create a new completed record
    $completed = new stdClass();
    $completed->apply           = $applyid;
    $completed->userid             = $usrid;
    $completed->guestid            = $guestid;
    $completed->time_modified       = $time_modified;
//    $completed->anonymous_response = $anonymous_response;

    $completedid = $DB->insert_record('apply_completed'.$tmpstr, $completed);

    $completed = $DB->get_record('apply_completed'.$tmpstr, array('id'=>$completedid));

    //the keys are in the form like abc_xxx
    //with explode we make an array with(abc, xxx) and (abc=typ und xxx=itemnr)

    //get the items of the apply
    if (!$allitems = $DB->get_records('apply_item', array('apply'=>$completed->apply))) {
        return false;
    }
    foreach ($allitems as $item) {
        if (!$item->hasvalue) {
            continue;
        }
        //get the class of item-typ
        $itemobj = apply_get_item_class($item->typ);

        $keyname = $item->typ.'_'.$item->id;

        if ($itemobj->value_is_array()) {
            $itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
        } else {
            $itemvalue = optional_param($keyname, null, $itemobj->value_type());
        }

        if (is_null($itemvalue)) {
            continue;
        }

        $value = new stdClass();
        $value->item = $item->id;
        $value->completed = $completed->id;
        $value->course_id = $courseid;

        //the kind of values can be absolutely different
        //so we run create_value directly by the item-class
        $value->value = $itemobj->create_value($itemvalue);
        $DB->insert_record('apply_value'.$tmpstr, $value);
    }
    return $completed->id;
}

/**
 * this function updates a complete-record and the related value-records.
 * depending on the $tmp (true/false) the values are saved temporary or permanently
 *
 * @global object
 * @param object $completed
 * @param boolean $tmp
 * @return int the completedid
 */
function apply_update_values($completed, $tmp = false) {
    global $DB;

    $courseid = optional_param('courseid', false, PARAM_INT);
    $tmpstr = $tmp ? 'tmp' : '';

    $DB->update_record('apply_completed'.$tmpstr, $completed);
    //get the values of this completed
    $values = $DB->get_records('apply_value'.$tmpstr, array('completed'=>$completed->id));

    //get the items of the apply
    if (!$allitems = $DB->get_records('apply_item', array('apply'=>$completed->apply))) {
        return false;
    }
    foreach ($allitems as $item) {
        if (!$item->hasvalue) {
            continue;
        }
        //get the class of item-typ
        $itemobj = apply_get_item_class($item->typ);

        $keyname = $item->typ.'_'.$item->id;

        if ($itemobj->value_is_array()) {
            $itemvalue = optional_param_array($keyname, null, $itemobj->value_type());
        } else {
            $itemvalue = optional_param($keyname, null, $itemobj->value_type());
        }

        //is the itemvalue set (could be a subset of items because pagebreak)?
        if (is_null($itemvalue)) {
            continue;
        }

        $newvalue = new stdClass();
        $newvalue->item = $item->id;
        $newvalue->completed = $completed->id;
        $newvalue->course_id = $courseid;

        //the kind of values can be absolutely different
        //so we run create_value directly by the item-class
        $newvalue->value = $itemobj->create_value($itemvalue);

        //check, if we have to create or update the value
        $exist = false;
        foreach ($values as $value) {
            if ($value->item == $newvalue->item) {
                $newvalue->id = $value->id;
                $exist = true;
                break;
            }
        }
        if ($exist) {
            $DB->update_record('apply_value'.$tmpstr, $newvalue);
        } else {
            $DB->insert_record('apply_value'.$tmpstr, $newvalue);
        }
    }

    return $completed->id;
}

/**
 * get the values of an item depending on the given groupid.
 * if the apply is anonymous so the values are shuffled
 *
 * @global object
 * @global object
 * @param object $item
 * @param int $groupid
 * @param int $courseid
 * @param bool $ignore_empty if this is set true so empty values are not delivered
 * @return array the value-records
 */
function apply_get_group_values($item,
                                   $groupid = false,
                                   $courseid = false,
                                   $ignore_empty = false) {

    global $CFG, $DB;

    //if the groupid is given?
    if (intval($groupid) > 0) {
        if ($ignore_empty) {
            $ignore_empty_select = "AND fbv.value != '' AND fbv.value != '0'";
        } else {
            $ignore_empty_select = "";
        }

        $query = 'SELECT fbv .  *
                    FROM {apply_value} fbv, {apply_completed} fbc, {groups_members} gm
                   WHERE fbv.item = ?
                         AND fbv.completed = fbc.id
                         AND fbc.userid = gm.userid
                         '.$ignore_empty_select.'
                         AND gm.groupid = ?
                ORDER BY fbc.time_modified';
        $values = $DB->get_records_sql($query, array($item->id, $groupid));

    } else {
        if ($ignore_empty) {
            $ignore_empty_select = "AND value != '' AND value != '0'";
        } else {
            $ignore_empty_select = "";
        }

        if ($courseid) {
            $select = "item = ? AND course_id = ? ".$ignore_empty_select;
            $params = array($item->id, $courseid);
            $values = $DB->get_records_select('apply_value', $select, $params);
        } else {
            $select = "item = ? ".$ignore_empty_select;
            $params = array($item->id);
            $values = $DB->get_records_select('apply_value', $select, $params);
        }
    }
/*
    $params = array('id'=>$item->apply);
    if ($DB->get_field('apply', 'anonymous', $params) == APPLY_ANONYMOUS_YES) {
        if (is_array($values)) {
            shuffle($values);
        }
    }
*/
    return $values;
}

/**
 * check for multiple_submit = false.
 * if the apply is global so the courseid must be given
 *
 * @global object
 * @global object
 * @param int $applyid
 * @param int $courseid
 * @return boolean true if the apply already is submitted otherwise false
 */
function apply_is_already_submitted($applyid, $courseid = false) {
    global $USER, $DB;

    $params = array('userid'=>$USER->id, 'apply'=>$applyid);
    if (!$trackings = $DB->get_records_menu('apply_tracking', $params, '', 'id, completed')) {
        return false;
    }

    if ($courseid) {
        $select = 'completed IN ('.implode(',', $trackings).') AND course_id = ?';
        if (!$values = $DB->get_records_select('apply_value', $select, array($courseid))) {
            return false;
        }
    }

    return true;
}


/**
 * if the completion of a apply will be continued eg.
 * by pagebreak or by multiple submit so the complete must be found.
 * if the param $tmp is set true so all things are related to temporary completeds
 *
 * @global object
 * @global object
 * @global object
 * @param int $applyid
 * @param int $courseid
 * @return int the id of the found completed
 */
//function apply_get_current_application($applyid, $tmp = false, $courseid = false, $guestid = false) 
function apply_get_current_application($applyid, $courseid = false) 
{
    global $USER, $CFG, $DB;

    if (!$courseid) {
        $params = array('apply'=>$applyid, 'userid'=>$USER->id);
        return $DB->get_record('apply_application', $params);
    }

    $params = array();
    $userselect = " AND fa.userid = :userid ";
    $params['userid'] = $USER->id;

    //if courseid is set the apply is global.
    //there can be more than one completed on one apply
    $sql = "SELECT DISTINCT fa.* FROM {apply_value} fv, {apply_application} fa
              	WHERE fv.course_id=:courseid AND fv.apply_id=fa.id AND fa.userid=:userid AND fa.apply=:applyid";
    $params['courseid'] = intval($courseid);
    $params['applyid']  = $applyid;

    if (!$sqlresult = $DB->get_records_sql($sql, $params)) {
        return false;
    }

    foreach ($sqlresult as $r) {
        return $DB->get_record('apply_applications', array('id'=>$r->id));
    }
}



/**
 * get the completeds depending on the given groupid.
 *
 * @global object
 * @global object
 * @param object $apply
 * @param int $groupid
 * @param int $courseid
 * @return mixed array of found completeds otherwise false
 */
function apply_get_completeds_group($apply, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if (intval($groupid) > 0) {
        $query = "SELECT fbc.*
                    FROM {apply_completed} fbc, {groups_members} gm
                   WHERE fbc.apply = ?
                         AND gm.groupid = ?
                         AND fbc.userid = gm.userid";
        if ($values = $DB->get_records_sql($query, array($apply->id, $groupid))) {
            return $values;
        } else {
            return false;
        }
    } else {
        if ($courseid) {
            $query = "SELECT DISTINCT fbc.*
                        FROM {apply_completed} fbc, {apply_value} fbv
                        WHERE fbc.id = fbv.completed
                            AND fbc.apply = ?
                            AND fbv.course_id = ?
                        ORDER BY random_response";
            if ($values = $DB->get_records_sql($query, array($apply->id, $courseid))) {
                return $values;
            } else {
                return false;
            }
        } else {
            if ($values = $DB->get_records('apply_completed', array('apply'=>$apply->id))) {
                return $values;
            } else {
                return false;
            }
        }
    }
}

/**
 * get the count of completeds depending on the given groupid.
 *
 * @global object
 * @global object
 * @param object $apply
 * @param int $groupid
 * @param int $courseid
 * @return mixed count of completeds or false
 */
function apply_get_completeds_group_count($apply, $groupid = false, $courseid = false) {
    global $CFG, $DB;

    if ($courseid > 0 AND !$groupid <= 0) {
        $sql = "SELECT id, COUNT(item) AS ci
                  FROM {apply_value}
                 WHERE course_id  = ?
              GROUP BY item ORDER BY ci DESC";
        if ($foundrecs = $DB->get_records_sql($sql, array($courseid))) {
            $foundrecs = array_values($foundrecs);
            return $foundrecs[0]->ci;
        }
        return false;
    }
    if ($values = apply_get_completeds_group($apply, $groupid)) {
        return count($values);
    } else {
        return false;
    }
}

/**
 * deletes all completed-recordsets from a apply.
 * all related data such as values also will be deleted
 *
 * @global object
 * @param int $applyid
 * @return void
 */
function apply_delete_all_completeds($applyid) {
    global $DB;

    if (!$completeds = $DB->get_records('apply_completed', array('apply'=>$applyid))) {
        return;
    }
    foreach ($completeds as $completed) {
        apply_delete_completed($completed->id);
    }
}

/**
 * deletes a completed given by completedid.
 * all related data such values or tracking data also will be deleted
 *
 * @global object
 * @param int $completedid
 * @return boolean
 */
function apply_delete_completed($completedid) {
    global $DB, $CFG;
    require_once($CFG->libdir.'/completionlib.php');

    if (!$completed = $DB->get_record('apply_completed', array('id'=>$completedid))) {
        return false;
    }

    if (!$apply = $DB->get_record('apply', array('id'=>$completed->apply))) {
        return false;
    }

    if (!$course = $DB->get_record('course', array('id'=>$apply->course))) {
        return false;
    }

    if (!$cm = get_coursemodule_from_instance('apply', $apply->id)) {
        return false;
    }

    //first we delete all related values
    $DB->delete_records('apply_value', array('completed'=>$completed->id));

    //now we delete all tracking data
    $params = array('completed'=>$completed->id, 'apply'=>$completed->apply);
    if ($tracking = $DB->get_record('apply_tracking', $params)) {
        $DB->delete_records('apply_tracking', array('completed'=>$completed->id));
    }

    // Update completion state
    $completion = new completion_info($course);
    if ($completion->is_enabled($cm) && $apply->completionsubmit) {
        $completion->update_state($cm, COMPLETION_INCOMPLETE, $completed->userid);
    }
    //last we delete the completed-record
    return $DB->delete_records('apply_completed', array('id'=>$completed->id));
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//functions to handle sitecourse mapping
////////////////////////////////////////////////

/**
 * checks if the course and the apply is in the table apply_sitecourse_map.
 *
 * @global object
 * @param int $applyid
 * @param int $courseid
 * @return int the count of records
 */
function apply_is_course_in_sitecourse_map($applyid, $courseid) {
    global $DB;
    $params = array('applyid'=>$applyid, 'courseid'=>$courseid);
    return $DB->count_records('apply_sitecourse_map', $params);
}

/**
 * checks if the apply is in the table apply_sitecourse_map.
 *
 * @global object
 * @param int $applyid
 * @return boolean
 */
function apply_is_apply_in_sitecourse_map($applyid) {
    global $DB;
    return $DB->record_exists('apply_sitecourse_map', array('applyid'=>$applyid));
}

/**
 * gets the applys from table apply_sitecourse_map.
 * this is used to show the global applys on the apply block
 * all applys with the following criteria will be selected:<br />
 *
 * 1) all applys which id are listed together with the courseid in sitecoursemap and<br />
 * 2) all applys which not are listed in sitecoursemap
 *
 * @global object
 * @param int $courseid
 * @return array the apply-records
 */
function apply_get_applys_from_sitecourse_map($courseid) {
    global $DB;

    //first get all applys listed in sitecourse_map with named courseid
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.name AS name,
                   f.time_open AS time_open,
                   f.time_close AS time_close
            FROM {apply} f, {course_modules} cm, {apply_sitecourse_map} sm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '".SITEID."'
                   AND m.id = cm.module
                   AND m.name = 'apply'
                   AND sm.courseid = ?
                   AND sm.applyid = f.id";

    if (!$applys1 = $DB->get_records_sql($sql, array($courseid))) {
        $applys1 = array();
    }

    //second get all applys not listed in sitecourse_map
    $applys2 = array();
    $sql = "SELECT f.id AS id,
                   cm.id AS cmid,
                   f.name AS name,
                   f.time_open AS time_open,
                   f.time_close AS time_close
            FROM {apply} f, {course_modules} cm, {modules} m
            WHERE f.id = cm.instance
                   AND f.course = '".SITEID."'
                   AND m.id = cm.module
                   AND m.name = 'apply'";
    if (!$allapplys = $DB->get_records_sql($sql)) {
        $allapplys = array();
    }
    foreach ($allapplys as $a) {
        if (!$DB->record_exists('apply_sitecourse_map', array('applyid'=>$a->id))) {
            $applys2[] = $a;
        }
    }

    return array_merge($applys1, $applys2);

}

/**
 * gets the courses from table apply_sitecourse_map.
 *
 * @global object
 * @param int $applyid
 * @return array the course-records
 */
function apply_get_courses_from_sitecourse_map($applyid) {
    global $DB;

    $sql = "SELECT f.id, f.courseid, c.fullname, c.shortname
              FROM {apply_sitecourse_map} f, {course} c
             WHERE c.id = f.courseid
                   AND f.applyid = ?
          ORDER BY c.fullname";

    return $DB->get_records_sql($sql, array($applyid));

}

/**
 * removes non existing courses or applys from sitecourse_map.
 * it shouldn't be called all too often
 * a good place for it could be the mapcourse.php or unmapcourse.php
 *
 * @global object
 * @return void
 */
function apply_clean_up_sitecourse_map() {
    global $DB;

    $maps = $DB->get_records('apply_sitecourse_map');
    foreach ($maps as $map) {
        if (!$DB->get_record('course', array('id'=>$map->courseid))) {
            $params = array('courseid'=>$map->courseid, 'applyid'=>$map->applyid);
            $DB->delete_records('apply_sitecourse_map', $params);
            continue;
        }
        if (!$DB->get_record('apply', array('id'=>$map->applyid))) {
            $params = array('courseid'=>$map->courseid, 'applyid'=>$map->applyid);
            $DB->delete_records('apply_sitecourse_map', $params);
            continue;
        }

    }
}

////////////////////////////////////////////////
////////////////////////////////////////////////
////////////////////////////////////////////////
//not relatable functions
////////////////////////////////////////////////

/**
 * prints the option items of a selection-input item (dropdownlist).
 * @param int $startval the first value of the list
 * @param int $endval the last value of the list
 * @param int $selectval which item should be selected
 * @param int $interval the stepsize from the first to the last value
 * @return void
 */
function apply_print_numeric_option_list($startval, $endval, $selectval = '', $interval = 1) {
    for ($i = $startval; $i <= $endval; $i += $interval) {
        if ($selectval == ($i)) {
            $selected = 'selected="selected"';
        } else {
            $selected = '';
        }
        echo '<option '.$selected.'>'.$i.'</option>';
    }
}

/**
 * sends an email to the teachers of the course where the given apply is placed.
 *
 * @global object
 * @global object
 * @uses APPLY_ANONYMOUS_NO
 * @uses FORMAT_PLAIN
 * @param object $cm the coursemodule-record
 * @param object $apply
 * @param object $course
 * @param int $userid
 * @return void
 */
function apply_send_email($cm, $apply, $course, $userid) {
    global $CFG, $DB;

    if ($apply->email_notification == 0) {  // No need to do anything
        return;
    }

    $user = $DB->get_record('user', array('id'=>$userid));

    if (isset($cm->groupmode) && empty($course->groupmodeforce)) {
        $groupmode =  $cm->groupmode;
    } else {
        $groupmode = $course->groupmode;
    }

    if ($groupmode == SEPARATEGROUPS) {
        $groups = $DB->get_records_sql_menu("SELECT g.name, g.id
                                               FROM {groups} g, {groups_members} m
                                              WHERE g.courseid = ?
                                                    AND g.id = m.groupid
                                                    AND m.userid = ?
                                           ORDER BY name ASC", array($course->id, $userid));
        $groups = array_values($groups);

        $teachers = apply_get_receivemail_users($cm->id, $groups);
    } else {
        $teachers = apply_get_receivemail_users($cm->id);
    }

    if ($teachers) {

        $strapplys = get_string('modulenameplural', 'apply');
        $strapply  = get_string('modulename', 'apply');
        $strcompleted  = get_string('completed', 'apply');

//        if ($apply->anonymous == APPLY_ANONYMOUS_NO) {
            $printusername = fullname($user);
//        } else {
//            $printusername = get_string('anonymous_user', 'apply');
//        }

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->apply = format_string($apply->name, true);
            $info->url = $CFG->wwwroot.'/mod/apply/show_entries.php?'.
                            'id='.$cm->id.'&'.
                            'userid='.$userid.'&'.
                            'do_show=showentries';

            $postsubject = $strcompleted.': '.$info->username.' -> '.$apply->name;
            $posttext = apply_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = apply_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

//            if ($apply->anonymous == APPLY_ANONYMOUS_NO) {
                $eventdata = new stdClass();
                $eventdata->name             = 'submission';
                $eventdata->component        = 'mod_apply';
                $eventdata->userfrom         = $user;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = '';
                message_send($eventdata);
/*
            } else {
                $eventdata = new stdClass();
                $eventdata->name             = 'submission';
                $eventdata->component        = 'mod_apply';
                $eventdata->userfrom         = $teacher;
                $eventdata->userto           = $teacher;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->smallmessage     = '';
                message_send($eventdata);
            }
*/
        }
    }
}

/**
 * sends an email to the teachers of the course where the given apply is placed.
 *
 * @global object
 * @uses FORMAT_PLAIN
 * @param object $cm the coursemodule-record
 * @param object $apply
 * @param object $course
 * @return void
 */
/*
function apply_send_email_anonym($cm, $apply, $course) {
    global $CFG;

    if ($apply->email_notification == 0) { // No need to do anything
        return;
    }

    $teachers = apply_get_receivemail_users($cm->id);

    if ($teachers) {

        $strapplys = get_string('modulenameplural', 'apply');
        $strapply  = get_string('modulename', 'apply');
        $strcompleted  = get_string('completed', 'apply');
        $printusername = get_string('anonymous_user', 'apply');

        foreach ($teachers as $teacher) {
            $info = new stdClass();
            $info->username = $printusername;
            $info->apply = format_string($apply->name, true);
            $info->url = $CFG->wwwroot.'/mod/apply/show_entries_anonym.php?id='.$cm->id;

            $postsubject = $strcompleted.': '.$info->username.' -> '.$apply->name;
            $posttext = apply_send_email_text($info, $course);

            if ($teacher->mailformat == 1) {
                $posthtml = apply_send_email_html($info, $course, $cm);
            } else {
                $posthtml = '';
            }

            $eventdata = new stdClass();
            $eventdata->name             = 'submission';
            $eventdata->component        = 'mod_apply';
            $eventdata->userfrom         = $teacher;
            $eventdata->userto           = $teacher;
            $eventdata->subject          = $postsubject;
            $eventdata->fullmessage      = $posttext;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml  = $posthtml;
            $eventdata->smallmessage     = '';
            message_send($eventdata);
        }
    }
}
*/

/**
 * send the text-part of the email
 *
 * @param object $info includes some infos about the apply you want to send
 * @param object $course
 * @return string the text you want to post
 */
function apply_send_email_text($info, $course) {
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $posttext  = $courseshortname.' -> '.get_string('modulenameplural', 'apply').' -> '.
                    $info->apply."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    $posttext .= get_string("emailteachermail", "apply", $info)."\n";
    $posttext .= '---------------------------------------------------------------------'."\n";
    return $posttext;
}


/**
 * send the html-part of the email
 *
 * @global object
 * @param object $info includes some infos about the apply you want to send
 * @param object $course
 * @return string the text you want to post
 */
function apply_send_email_html($info, $course, $cm) {
    global $CFG;
    $coursecontext = context_course::instance($course->id);
    $courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
    $course_url = $CFG->wwwroot.'/course/view.php?id='.$course->id;
    $apply_all_url = $CFG->wwwroot.'/mod/apply/index.php?id='.$course->id;
    $apply_url = $CFG->wwwroot.'/mod/apply/view.php?id='.$cm->id;

    $posthtml = '<p><font face="sans-serif">'.
            '<a href="'.$course_url.'">'.$courseshortname.'</a> ->'.
            '<a href="'.$apply_all_url.'">'.get_string('modulenameplural', 'apply').'</a> ->'.
            '<a href="'.$apply_url.'">'.$info->apply.'</a></font></p>';
    $posthtml .= '<hr /><font face="sans-serif">';
    $posthtml .= '<p>'.get_string('emailteachermailhtml', 'apply', $info).'</p>';
    $posthtml .= '</font><hr />';
    return $posthtml;
}

/**
 * @param string $url
 * @return string
 */
function apply_encode_target_url($url) {
    if (strpos($url, '?')) {
        list($part1, $part2) = explode('?', $url, 2); //maximal 2 parts
        return $part1 . '?' . htmlentities($part2);
    } else {
        return $url;
    }
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $applynode The node to add module settings to
 */
function apply_extend_settings_navigation(settings_navigation $settings,
                                             navigation_node $applynode) {

    global $PAGE, $DB;

    if (!$context = context_module::instance($PAGE->cm->id, IGNORE_MISSING)) {
        print_error('badcontext');
    }

    if (has_capability('mod/apply:edititems', $context)) {
        $questionnode = $applynode->add(get_string('questions', 'apply'));

        $questionnode->add(get_string('edit_items', 'apply'),
                    new moodle_url('/mod/apply/edit.php',
                                    array('id' => $PAGE->cm->id,
                                          'do_show' => 'edit')));

        $questionnode->add(get_string('export_questions', 'apply'),
                    new moodle_url('/mod/apply/export.php',
                                    array('id' => $PAGE->cm->id,
                                          'action' => 'exportfile')));

        $questionnode->add(get_string('import_questions', 'apply'),
                    new moodle_url('/mod/apply/import.php',
                                    array('id' => $PAGE->cm->id)));

        $questionnode->add(get_string('templates', 'apply'),
                    new moodle_url('/mod/apply/edit.php',
                                    array('id' => $PAGE->cm->id,
                                          'do_show' => 'templates')));
    }

    if (has_capability('mod/apply:viewreports', $context)) {
        $apply = $DB->get_record('apply', array('id'=>$PAGE->cm->instance));
        if ($apply->course == SITEID) {
            $applynode->add(get_string('analysis', 'apply'),
                    new moodle_url('/mod/apply/analysis_course.php',
                                    array('id' => $PAGE->cm->id,
                                          'course' => $PAGE->course->id,
                                          'do_show' => 'analysis')));
        } else {
            $applynode->add(get_string('analysis', 'apply'),
                    new moodle_url('/mod/apply/analysis.php',
                                    array('id' => $PAGE->cm->id,
                                          'course' => $PAGE->course->id,
                                          'do_show' => 'analysis')));
        }

        $applynode->add(get_string('show_entries', 'apply'),
                    new moodle_url('/mod/apply/show_entries.php',
                                    array('id' => $PAGE->cm->id,
                                          'do_show' => 'showentries')));
    }
}

function apply_init_apply_session() {
    //initialize the apply-Session - not nice at all!!
    global $SESSION;
    if (!empty($SESSION)) {
        if (!isset($SESSION->apply) OR !is_object($SESSION->apply)) {
            $SESSION->apply = new stdClass();
        }
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function apply_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-apply-*'=>get_string('page-mod-apply-x', 'apply'));
    return $module_pagetype;
}

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
 * Strings for component 'apply', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_apply
 * @copyright Fumi.Iseki http://www.nsl.tuis.ac.jp/
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

//
$string['modulenameplural'] = 'Application Forms';
$string['modulename'] = 'Application Form';
$string['modulename_help'] = 'You can make simple Application Forms and make a user submit it.';
$string['description'] = 'Description';


// Button
$string['save_entry_button']  = ' Submit this Application ';
$string['save_draft_button']  = ' Save as Draft ';
$string['submit_form_button'] = ' Sumbit... ';


// Menu
$string['apply:submit'] = 'Submit a Application';
//
$string['apply:addinstance'] = 'Add a new apply';
$string['apply:applies'] = 'issue a apply';
$string['apply:createprivatetemplate'] = 'Create private template';
$string['apply:createpublictemplate'] = 'Create public template';
$string['apply:deletesubmissions'] = 'Delete completed submissions';
$string['apply:deletetemplate'] = 'Delete template';
$string['apply:edititems'] = 'Edit items';
$string['apply:mapcourse'] = 'Map courses to global applys';
$string['apply:receivemail'] = 'Receive email notification';
$string['apply:view'] = 'View a apply';
$string['apply:viewanalysepage'] = 'View the analysis page after submit';
$string['apply:viewreports'] = 'View reports';


// Title
$string['title_title'] = 'Title';
$string['title_version'] = 'Ver.';
$string['title_acked'] = 'Ack.';
$string['title_exec']  = 'Exec.';
$string['title_check'] = 'Check';


// tabs
$string['overview'] = 'Overview';
$string['show_entries'] = 'Show Applications';
$string['edit_items'] = 'Edit Items';
$string['templates'] = 'Templates';


// mod_form
$string['name'] = 'Name';
$string['time_open'] = 'Time to open';
$string['time_open_help'] = 'You can specify times when the apply is accessible for people to answer the applications. If the checkbox is not ticked there is no limit defined.';
$string['time_close'] = 'Time to close';
$string['time_close_help'] = 'You can specify times when the apply is accessible for people to answer the applications. If the checkbox is not ticked there is no limit defined.';
$string['apply_options'] = 'Apply options';

$string['email_notification'] = 'Send e-mail notifications';
$string['email_notification_help'] = 'If enabled, administrators receive email notification of apply submissions.';
$string['multiple_submit'] = 'Multiple submissions';
$string['multiple_submit_help'] = 'If enabled for anonymous surveys, users can submit apply an unlimited number of times.';

$string['use_calendar'] = 'Use Calendar';
$string['use_calendar_help'] = 'The period for submission of an application is registered into a calendar.';


// view
$string['entries_list_title'] = 'List of submitted Applications';


// submit
$string['entry_saved'] = 'Your applocation have been saved. Thank you.';
$string['saving_failed'] = 'Saving failed';
$string['saving_failed_because_missing_or_false_values'] = 'Saving failed because missing or false values.';


// show_entries
$string['delete_entry'] = 'Delete Entry';
$string['acked_notyet'] = 'Not Yet';
$string['acked_accept'] = 'Accept';
$string['acked_reject'] = 'Reject';
$string['exec_done'] = 'Done';
$string['exec_notyet'] = 'Not Yet';
$string['cancel_disable']  = 'Canceled';
$string['cancel_enable']   = 'Enable';
$string['no_title'] = 'No Title';



$string['template'] = 'Template';


$string['name_required'] = 'Name required';




$string['add_item']  = 'Add application item to activity';
$string['add_items'] = 'Add application items to activity';




$string['apply_is_not_open']  = 'This Apply is not open yet';
$string['apply_is_not_ready'] = 'This Apply is not ready';
$string['apply_is_closed']    = 'This Apply is closed';
$string['apply_is_disable']  = 'You ca not use this Apply';
$string['apply_is_already_submitted'] = 'You\'ve already submitted this Apply';
$string['apply_is_enable']  = 'Submit a this Apply';


$string['submit_new_apply']   = 'Submit a new Apply';

$string['add_pagebreak'] = 'Add a page break';
$string['adjustment'] = 'Adjustment';
$string['after_submit'] = 'After submitting';
$string['allowfullanonymous'] = 'Allow full anonymous';
$string['analysis'] = 'Analysis';
$string['anonymous'] = 'Anonymous';
$string['anonymous_edit'] = 'Record user names';
$string['anonymous_entries'] = 'Anonymous entries';
$string['anonymous_user'] = 'Anonymous user';
$string['append_new_items'] = 'Append new items';
$string['autonumbering'] = 'Automated numbers';
$string['autonumbering_help'] = 'Enables or disables automated numbers for each question';
$string['average'] = 'Average';
$string['bold'] = 'Bold';
$string['cancel_moving'] = 'Cancel moving';
$string['cannotmapapply'] = 'Database problem, unable to map apply to course';
$string['cannotsavetempl'] = 'saving templates is not allowed';
$string['cannotunmap'] = 'Database problem, unable to unmap';
$string['captcha'] = 'Captcha';
$string['captchanotset'] = 'Captcha hasn\'t been set.';
$string['submitted'] = 'submitted';
$string['submitted_applys'] = 'Submitted applies';
$string['submit_the_form'] = 'Submit a application...';
$string['completionsubmit'] = 'View as completed if the apply is submitted';
$string['configallowfullanonymous'] = 'If this option is set yes so the apply can be completed without any preceding logon. It only affects applys on the homepage.';
$string['confirmdeleteentry'] = 'Are you sure you want to delete this entry?';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this element?';
$string['confirmdeletetemplate'] = 'Are you sure you want to delete this template?';
$string['confirmusetemplate'] = 'Are you sure you want to use this template?';
$string['continue_the_form'] = 'Continue the form';
$string['count_of_nums'] = 'Count of numbers';
$string['courseid'] = 'courseid';
$string['creating_templates'] = 'Save these questions as a new template';
$string['delete_item'] = 'Delete question';
$string['delete_old_items'] = 'Delete old items';
$string['delete_template'] = 'Delete template';
$string['delete_templates'] = 'Delete template...';
$string['depending'] = 'Dependencies';
$string['depending_help'] = 'It is possible to show an item depending on the value of another item.<br />
<strong>Here is an example.</strong><br />
<ul>
<li>First, create an item on which another item will depend on.</li>
<li>Next, add a pagebreak.</li>
<li>Then add the items dependant on the value of the item created before. Choose the item from the list labelled "Dependence item" and write the required value in the textbox labelled "Dependence value".</li>
</ul>
<strong>The item structure should look like this.</strong>
<ol>
<li>Item Q: Do you have a car? A: yes/no</li>
<li>Pagebreak</li>
<li>Item Q: What colour is your car?<br />
(this item depends on item 1 with value = yes)</li>
<li>Item Q: Why don\'t you have a car?<br />
(this item depends on item 1 with value = no)</li>
<li> ... other items</li>
</ol>';
$string['dependitem'] = 'Dependence item';
$string['dependvalue'] = 'Dependence value';
$string['do_not_analyse_empty_submits'] = 'Do not analyse empty submits';
$string['dropdown'] = 'Multiple choice - single answer allowed (dropdownlist)';
$string['dropdownlist'] = 'Multiple choice - single answer (dropdown)';
$string['dropdownrated'] = 'Dropdownlist (rated)';
$string['dropdown_values'] = 'Answers';
$string['drop_apply'] = 'Remove from this course';
$string['edit_item'] = 'Edit question';
$string['emailteachermail'] = '{$a->username} has completed apply activity : \'{$a->apply}\'

You can view it here:

{$a->url}';
$string['emailteachermailhtml'] = '{$a->username} has completed apply activity : <i>\'{$a->apply}\'</i><br /><br />
You can view it <a href="{$a->url}">here</a>.';
$string['export_questions'] = 'Export questions';
$string['export_to_excel'] = 'Export to Excel';

$string['apply_closes'] = 'Apply closes';


$string['apply_is_not_for_anonymous'] = 'apply is not for anonymous';
$string['apply_opens'] = 'Apply opens';
$string['file'] = 'File';
$string['filter_by_course'] = 'Filter by course';
$string['handling_error'] = 'Error occurred in apply module action handling';
$string['hide_no_select_option'] = 'Hide the "Not selected" option';
$string['horizontal'] = 'horizontal';
$string['check'] = 'Multiple choice - multiple answers';
$string['checkbox'] = 'Multiple choice - multiple answers allowed (check boxes)';
$string['check_values'] = 'Possible responses';
$string['choosefile'] = 'Choose a file';
$string['chosen_apply_response'] = 'chosen apply response';
$string['importfromthisfile'] = 'Import from this file';
$string['import_questions'] = 'Import questions';
$string['import_successfully'] = 'Import successfully';
$string['info'] = 'Information';
$string['infotype'] = 'Information-Type';
$string['insufficient_responses_for_this_group'] = 'There are insufficient responses for this group';
$string['insufficient_responses'] = 'insufficient responses';
$string['insufficient_responses_help'] = 'There are insufficient responses for this group.

To keep the apply anonymous, a minimum of 2 responses must be done.';
$string['item_label'] = 'Label';
$string['item_name'] = 'Question';
$string['items_are_required'] = 'Answers are required to starred questions.';
$string['label'] = 'Label';
$string['line_values'] = 'Rating';
$string['mapcourseinfo'] = 'This is a site-wide apply that is available to all courses using the apply block. You can however limit the courses to which it will appear by mapping them. Search the course and map it to this apply.';
$string['mapcoursenone'] = 'No courses mapped. Apply available to all courses';
$string['mapcourse'] = 'Map apply to courses';
$string['mapcourse_help'] = 'By default, apply forms created on your homepage are available site-wide
and will appear in all courses using the apply block. You can force the apply form to appear by making it a sticky block or limit the courses in which a apply form will appear by mapping it to specific courses.';
$string['mapcourses'] = 'Map apply to courses';
$string['mapcourses_help'] = 'Once you have selected the relevant course(s) from your search,
you can associate them with this apply using map course(s). Multiple courses may be selected by holding down the Apple or Ctrl key whilst clicking on the course names. A course may be disassociated from a apply at any time.';
$string['mappedcourses'] = 'Mapped courses';
$string['max_args_exceeded'] = 'Max 6 arguments can be handled, too many arguments for';
$string['maximal'] = 'maximal';
$string['messageprovider:message'] = 'Apply reminder';
$string['messageprovider:submission'] = 'Apply notifications';
$string['mode'] = 'Mode';
$string['modulename_link'] = 'mod/apply/view';
$string['movedown_item'] = 'Move this question down';
$string['move_here'] = 'Move here';
$string['move_item'] = 'Move this question';
$string['moveup_item'] = 'Move this question up';
$string['multichoice'] = 'Multiple choice';
$string['multichoicerated'] = 'Multiple choice (rated)';
$string['multichoicetype'] = 'Multiple choice type';
$string['multichoice_values'] = 'Multiple choice values';
$string['next_page'] = 'Next page';
$string['no_handler'] = 'No action handler exists for';
$string['no_itemlabel'] = 'No label';
$string['no_itemname'] = 'No itemname';
$string['no_items_available_yet'] = 'No questions have been set up yet';
$string['non_anonymous'] = 'User\'s name will be logged and shown with answers';
$string['non_anonymous_entries'] = 'non anonymous entries';
$string['non_respondents_students'] = 'non respondents students';
$string['notavailable'] = 'this apply is not available';
$string['not_completed_yet'] = 'Not completed yet';
$string['not_started'] = 'not started';
$string['no_templates_available_yet'] = 'No templates available yet';
$string['not_selected'] = 'Not selected';
$string['numeric'] = 'Numeric answer';
$string['numeric_range_from'] = 'Range from';
$string['numeric_range_to'] = 'Range to';
$string['of'] = 'of';
$string['oldvaluespreserved'] = 'All old questions and the assigned values will be preserved';
$string['oldvalueswillbedeleted'] = 'The current questions and all your user\'s responses will be deleted';
$string['only_one_captcha_allowed'] = 'Only one captcha is allowed in a apply';
$string['page'] = 'Page';
$string['page-mod-apply-x'] = 'Any apply module page';
$string['page_after_submit'] = 'Page after submit';
$string['pagebreak'] = 'Page break';
$string['parameters_missing'] = 'Parameters missing from';
$string['picture'] = 'Picture';
$string['picture_file_list'] = 'List of pictures';
$string['picture_values'] = 'Choose one or more<br />picture files from the list:';
$string['pluginadministration'] = 'Apply administration';
$string['pluginname'] = 'Apply';
$string['position'] = 'Position';
$string['preview'] = 'Preview';
$string['preview_help'] = 'In the preview you can change the order of questions.';
$string['previous_page'] = 'Previous page';
$string['public'] = 'Public';
$string['question'] = 'Question';
$string['questions'] = 'Questions';
$string['radio'] = 'Multiple choice - single answer';
$string['radiobutton'] = 'Multiple choice - single answer allowed (radio buttons)';
$string['radiobutton_rated'] = 'Radiobutton (rated)';
$string['radiorated'] = 'Radiobutton (rated)';
$string['radio_values'] = 'Responses';
$string['ready_applys'] = 'Ready applys';
$string['relateditemsdeleted'] = 'All your user\'s responses for this question will also be deleted';
$string['required'] = 'Required';
$string['resetting_data'] = 'Reset apply responses';
$string['resetting_applys'] = 'Resetting applys';
$string['response_nr'] = 'Response number';
$string['responses'] = 'Responses';
$string['responsetime'] = 'Responsestime';
$string['save_as_new_item'] = 'Save as new question';
$string['save_as_new_template'] = 'Save as new template';
$string['save_entries'] = 'Submit your answers';
$string['save_item'] = 'Save question';
$string['search_course'] = 'Search course';
$string['searchcourses'] = 'Search courses';
$string['searchcourses_help'] = 'Search for the code or name of the course(s) that you wish to associate with this apply.';
$string['selected_dump'] = 'Selected indexes of $SESSION variable are dumped below:';
$string['send'] = 'send';
$string['send_message'] = 'send message';
$string['separator_decimal'] = '.';
$string['separator_thousand'] = ',';
$string['show_all'] = 'Show all';
$string['show_analysepage_after_submit'] = 'Show analysis page after submit';
$string['show_entry'] = 'Show response';
$string['show_nonrespondents'] = 'Show non-respondents';
$string['site_after_submit'] = 'Site after submit';
$string['sort_by_course'] = 'Sort by course';
$string['start'] = 'Start';
$string['started'] = 'started';
$string['stop'] = 'End';
$string['subject'] = 'Subject';
$string['switch_group'] = 'Switch group';
$string['switch_item_to_not_required'] = 'switch to: answer not required';
$string['switch_item_to_required'] = 'switch to: answer required';
$string['template_saved'] = 'Template saved';
$string['textarea'] = 'Longer text answer';
$string['textarea_height'] = 'Number of lines';
$string['textarea_width'] = 'Width';
$string['textfield'] = 'Short text answer';
$string['textfield_maxlength'] = 'Maximum characters accepted';
$string['textfield_size'] = 'Textfield width';
$string['there_are_no_settings_for_recaptcha'] = 'There are no settings for captcha';


$string['typemissing'] = 'missing value "type"';
$string['update_item'] = 'Save changes to question';
$string['url_for_continue'] = 'URL for continue-button';
$string['url_for_continue_help'] = 'By default after a apply is submitted the target of the continue button is the course page. You can define here another target URL for this continue button.';
$string['url_for_continue_button'] = 'URL for continue button';
$string['use_one_line_for_each_value'] = '<br />Use one line for each answer!';
$string['use_this_template'] = 'Use this template';
$string['using_templates'] = 'Use a template';
$string['vertical'] = 'vertical';
$string['viewcompleted'] = 'completed applys';
$string['viewcompleted_help'] = 'You may view completed apply forms, searchable by course and/or by question.
Application responses may be exported to Excel.';

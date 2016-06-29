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
 * @package    mod
 * @subpackage apply
 * @copyright  2016 Fumi.Iseki
 */

/**
 * Define all the backup steps that will be used by the backup_apply_activity_task
 */

/**
 * Define the complete apply structure for backup, with file and id annotations
 */
class backup_apply_activity_structure_step extends backup_activity_structure_step
{
    protected function define_structure()
	{
        global $DB;

        // To know if we are including userinfo
        //$userinfo = $this->get_setting_value('userinfo');

        //
        // Define each element separated
        $apply = new backup_nested_element('apply', array('id'), array(
            'name', 'intro', 'introformat', 'email_notification', 'email_notification_user', 'multiple_submit', 'use_calendar', 
			'name_pattern', 'enable_deletemode', 'time_open', 'time_close', 'timemodified'));

        //
        // Build the tree
        // (none)

        //
        // Define sources
        $apply->set_source_table('apply', array('id' => backup::VAR_ACTIVITYID));

        //
        // Define id annotations
        // (none)

        //
        // Define file annotations
        $apply->annotate_files('apply', 'intro', null); // This file area hasn't itemid

        //
        // Return the root element (apply) wrapped into standard activity structure
        return $this->prepare_activity_structure($apply);
    }
}

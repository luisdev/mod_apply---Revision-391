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
 * Define all the restore steps that will be used by the restore_url_activity_task
 */

/**
 * Structure step to restore one apply activity
 */
class restore_apply_activity_structure_step extends restore_activity_structure_step
{
    protected function define_structure()
	{
        $paths = array();
        $paths[] = new restore_path_element('apply', '/activity/apply');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    //
    protected function process_apply($data)
	{
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        //$data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('apply', $data);
        $this->apply_activity_instance($newitemid);
    }


    //
    protected function after_execute()
	{
        // Add apply related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_apply', 'intro', null);
    }
}

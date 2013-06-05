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
 * Apply version information
 *
 * @package    mod
 * @subpackage apply 
 * @author     Fumi Iseki
 * @license    GPL
 * @attention  modified from mod_feedback that by Andreas Grabs
 */

defined('MOODLE_INTERNAL') || die();

$module->requires  = 2012112900;    // Moodle 2.4
$module->component = 'mod_apply';   // Full name of the plugin (used for diagnostics)
$module->cron      = 0;
$module->maturity  = MATURITY_STABLE;

$module->version   = 2013053000;    // The current module version (Date: YYYYMMDDXX)
$module->release   = '1.0.4';

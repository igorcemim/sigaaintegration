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
 * Scheduled tasks configuration for the local_sigaaintegration plugin.
 *
 * @package   local_sigaaintegration
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_sigaaintegration\task\import_enrollments_task',

        'blocking' => 0,

        // Every month.
        'month' => '*',

        // Every day.
        'day' => '*',

        // Every day of week.
        'dayofweek' => '*',

        // At 4am
        'hour' => '4',

        // At a random minute
        'minute' => 'R',
    ],
];
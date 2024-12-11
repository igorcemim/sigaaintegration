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
 * A scheduled task.
 *
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sigaaintegration\task;

use core\task\scheduled_task;
use local_sigaaintegration\sigaa_enrollments_sync;
use local_sigaaintegration\sigaa_periodo_letivo;

class import_enrollments_task extends scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('importenrollments_scheduled', 'local_sigaaintegration');
    }

    public function execute() {
        $period = sigaa_periodo_letivo::buildNew();
        $enrollmentssync = new sigaa_enrollments_sync($period->getAno(), $period->getPeriodo());
        $enrollmentssync->sync();
    }

}

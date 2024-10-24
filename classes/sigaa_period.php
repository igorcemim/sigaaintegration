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
 * Semestre
 *
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sigaaintegration;

class sigaa_period {

    public static function get_year_period() {
        return self::get_year() . '/' . self::get_period();
    }

    public static function get_year() {
        return date("Y");
    }

    public static function get_period() {
        return intval(date("m")) <= 6 ? 1 : 2;
    }

    public static function validate($period) {
        if (empty($period)) {
            return false;
        }

        $parts = explode("/", $period);
        if (count($parts) < 2) {
            return false;
        }

        $year = $parts[0];
        $semester = $parts[1];
        if (strlen($year) < 4) {
            return false;
        }
        if (strlen($semester) > 1) {
            return false;
        }

        return true;
    }
}
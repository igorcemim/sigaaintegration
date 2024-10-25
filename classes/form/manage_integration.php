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
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sigaaintegration\form;

use local_sigaaintegration\sigaa_periodo_letivo;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Manage integration.
 */
class manage_integration extends \moodleform
{

    public function definition()
    {
        $this->_form->addElement('text', 'period', get_string('period', 'local_sigaaintegration'));
        $this->_form->setType('period', PARAM_RAW);
        $this->_form->addHelpButton('period', 'period', 'local_sigaaintegration');

        $this->_form->addElement('header', 'importcoursesheader', get_string('importcourses', 'local_sigaaintegration'));
        $this->_form->addElement('submit', 'courses', get_string('import', 'local_sigaaintegration'));
        $this->_form->setExpanded('importcoursesheader');

        $this->_form->addElement('header', 'importenrollmentsheader', get_string('importenrollments', 'local_sigaaintegration'));
        $this->_form->addElement('submit', 'enrollments', get_string('import', 'local_sigaaintegration'));
        $this->_form->setExpanded('importenrollmentsheader');

        $this->_form->addElement('header', 'archivecoursesheader', get_string('archivecourses', 'local_sigaaintegration'));

        $this->_form->addElement('text', 'periodarchive', get_string('period', 'local_sigaaintegration'));
        $this->_form->setType('periodarchive', PARAM_RAW);
        $this->_form->addHelpButton('periodarchive', 'period', 'local_sigaaintegration');

        $this->_form->addElement('submit', 'archivecourses', get_string('archive', 'local_sigaaintegration'));
        $this->_form->setExpanded('archivecoursesheader');
    }

    public function validation($data, $files)
    {
        $errors = [];
        if (empty($data['period'])) {
            $errors['period'] = 'O período deve ser informado.';
        }
        if (!empty($data['period']) && !sigaa_periodo_letivo::validate($data['period'])) {
            $errors['period'] = 'O período informado deve ser válido, utilize o formato ano/semestre. Exemplo: 2024/1';
        }
        if (!empty($data['archivecourses']) && empty($data['periodarchive'])) {
            $errors['periodarchive'] = 'O período deve ser informado.';
        }
        if (!empty($data['periodarchive']) && !sigaa_periodo_letivo::validate($data['periodarchive'])) {
            $errors['periodarchive'] = 'O período informado deve ser válido, utilize o formato ano/semestre. Exemplo: 2024/1';
        }
        return $errors;
    }
}

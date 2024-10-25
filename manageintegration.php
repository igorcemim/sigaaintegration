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
 * @package    local_sigaaintegration
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_sigaaintegration\form\manage_integration;
use local_sigaaintegration\sigaa_periodo_letivo;
use local_sigaaintegration\task\archive_courses_adhoc_task;
use local_sigaaintegration\task\import_courses_adhoc_task;
use local_sigaaintegration\task\import_enrollments_adhoc_task;

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$returnurl = new moodle_url('/local/sigaaintegration/manageintegration.php');

admin_externalpage_setup('local_sigaaintegration_manageintegration');

$form = new manage_integration();
$form->set_data([
    'period' => sigaa_periodo_letivo::buildNew()->getPeriodoFormatado(),
]);

if ($data = $form->get_data()) {

    if (isset($data->enrollments)) {
        $message = "Importação de matrículas adicionada na fila para processamento.";
        $task = new import_enrollments_adhoc_task();
    }

    if (isset($data->courses)) {
        $message = "Importação de disciplinas e categorias adicionada na fila para processamento.";
        $task = new import_courses_adhoc_task();
    }

    if (isset($data->archivecourses)) {
        $message = "Arquivamento de disciplinas adicionado na fila para processamento.";
        $task = new archive_courses_adhoc_task();
    }

    if (!empty($task)) {
        $periodoletivo = sigaa_periodo_letivo::buildFromPeriodoFormatado($data->period);
        if (isset($data->archivecourses)) {
            $periodoletivo = sigaa_periodo_letivo::buildFromPeriodoFormatado($data->periodarchive);
        }

        $task->set_custom_data((object) [
            'ano' => $periodoletivo->getAno(),
            'periodo' => $periodoletivo->getPeriodo(),
        ]);

        \core\task\manager::queue_adhoc_task($task);
    }

    if (!empty($message)) {
        \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        redirect($returnurl);
    }

}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageintegration', 'local_sigaaintegration'));
echo $form->render();
echo $OUTPUT->footer();

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

use core\task\adhoc_task;
use core_course_category;
use Exception;
use local_sigaaintegration\configuration;
use local_sigaaintegration\sigaa_periodo_letivo;
use stdClass;

class archive_courses_adhoc_task extends adhoc_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('archivecourses', 'local_sigaaintegration');
    }

    public function retry_until_success(): bool {
        return false;
    }

    public function execute() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        mtrace('Arquivando disciplinas...');
        $parameters = $this->get_custom_data();

        $nomecategoriadisciplinasarquivadas = configuration::getNomeCampoDisciplinasArquivadas();
        $campoperiodoletivo = configuration::getCampoPeriodoLetivo();
        $campometadata = configuration::getCampoMetadata();

        $periodoletivo = sigaa_periodo_letivo::buildFromParameters($parameters->ano, $parameters->periodo);

        $sql = 'select
                c.*,
                cfd_metadata.value as metadata
            from
                {course} c 
            inner join {customfield_data} cfd_period 
                on cfd_period.instanceid = c.id and cfd_period.fieldid = :period_field_id
            inner join {customfield_data} cfd_metadata
                on cfd_metadata.instanceid = c.id and cfd_metadata.fieldid = :metadata_field_id
            where
                cfd_period.value = :period
            order by
                c.timecreated asc';

        $limitfrom = 0;
        $limitnum = 50;
        $enddate = time();

        do {
            $params = [
                'period_field_id' => $campoperiodoletivo->id,
                'metadata_field_id' => $campometadata->id,
                'period' => $periodoletivo->getPeriodoFormatado(),
            ];
            $courses = $DB->get_records_sql(
                $sql,
                $params,
                $limitfrom,
                $limitnum
            );

            foreach ($courses as $course) {
                try {
                    $categoriadisciplinasarquivadas = $this->buscar_categoria_disciplinas_arquivadas($course->metadata, $nomecategoriadisciplinasarquivadas);
                    if (!$categoriadisciplinasarquivadas) {
                        mtrace(sprintf(
                            'ERRO: Não foi possível buscar a categoria de disciplinas arquivadas. disciplina: %s',
                            print_r($course, true)
                        ));
                        continue;
                    }

                    if ($course->category == $categoriadisciplinasarquivadas->id) {
                        mtrace(sprintf(
                            'INFO: Disciplina já está arquivada. id: %s, idnumber: %s',
                            $course->id,
                            $course->idnumber
                        ));
                        continue;
                    }

                    // Move o curos para a categoria de disciplinas arquivadas
                    $course->category = $categoriadisciplinasarquivadas->id;

                    // Encerra os cursos sem data de término
                    if (empty($course->enddate) && !empty($course->startdate)) {
                        $course->enddate = $enddate;
                    }

                    update_course($course);

                    mtrace(sprintf(
                        'INFO: Disciplina arquivada com sucesso. id: %s, idnumber: %s',
                        $course->id,
                        $course->idnumber
                    ));
                } catch (Exception $e) {
                    mtrace(sprintf(
                        'ERRO: Falha ao atualizar ao tentar arquivar a disciplina. disciplina: %s, erro: %s',
                        print_r($course, true),
                        $e->getMessage()
                    ));
                }
            }

            $limitfrom = $limitfrom + $limitnum;
        } while (!empty($courses));
    }

    private function buscar_categoria_disciplinas_arquivadas($coursemetadata, $nomecategoria): ?object {
        global $DB;

        $metadata = json_decode($coursemetadata, true);
        $idnumbercategoriadisciplinasarquivadas = $metadata['id_curso'] . '-archive';
        $idnumbercategoriacurso = $metadata['id_curso'];

        $categoria = $DB->get_record('course_categories', ['idnumber' => $idnumbercategoriadisciplinasarquivadas]);
        if (!$categoria) {

            $categoriacurso = $DB->get_record('course_categories', ['idnumber' => $idnumbercategoriacurso]);
            if (!$categoriacurso) {
                mtrace(sprintf(
                    'ERRO: Categoria de curso não encontrada. idnumbercategoriacurso: %s',
                    $idnumbercategoriacurso
                ));
                return null;
            }

            $dadoscategoria = new stdClass();
            $dadoscategoria->name = $nomecategoria;
            $dadoscategoria->parent = $categoriacurso->id;
            $dadoscategoria->idnumber = $idnumbercategoriadisciplinasarquivadas;

            $categoria = core_course_category::create($dadoscategoria);
        }

        return $categoria;
    }
}

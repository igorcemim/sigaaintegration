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
 *
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sigaaintegration;

use core\context;
use core_course_category;
use Exception;
use moodle_exception;

class sigaa_enrollments_sync
{

    private string $ano;

    private string $periodo;

    private array $courseNotFound = [];

    private int $studentroleid;

    public function __construct(string $ano, string $periodo)
    {
        $studentroleid = (int) get_config('local_sigaaintegration', 'studentroleid');
        if (!$studentroleid) {
            throw new moodle_exception('ERRO: O papel de estudante não foi configurado.');
        }

        $this->ano = $ano;
        $this->periodo = $periodo;
        $this->studentroleid = $studentroleid;
    }

    /**
     * Consulta as matrículas para o período informado na API do SIGAA
     * e realiza as inscrições dos estudantes nas disciplinas.
     */
    public function sync(): void
    {
        // Carrega as credenciais de acesso a API do SIGAA
        $apibaseurl = get_config('local_sigaaintegration', 'apibaseurl');
        $apiclientid = get_config('local_sigaaintegration', 'apiclientid');
        $apiclientsecret = get_config('local_sigaaintegration', 'apiclientsecret');

        // Consulta as matrículas
        $client = new sigaa_api_client($apibaseurl, $apiclientid, $apiclientsecret);
        $periodoletivo = sigaa_periodo_letivo::buildFromParameters($this->ano, $this->periodo);
        $enrollments = $client->get_enrollments($periodoletivo);

        mtrace('INFO: Início da importação de matrículas');

        foreach ($enrollments as $key => $value) {
            mtrace('INFO: Início importação. matrícula: ' . $key);

            try {
                $this->enroll_student_into_courses($value);
            } catch (Exception $e) {
                mtrace(sprintf(
                    'ERRO: Falha ao processar todas as inscrições do estudante. matrícula: %s, erro: %s',
                    $key,
                    $e->getMessage()
                ));
            }

            mtrace('INFO: Fim importação. matrícula: ' . $key);
        }

        mtrace('INFO: Fim da importação de mátriculas');
    }

    /**
     * Busca disciplina pelo código de integração.
     */
    private function search_course(string $courseidnumber): ?object
    {
        /**
         * Evita busca repetida por disciplinas não encontradas.
         */
        if (array_search($courseidnumber, $this->courseNotFound)) {
            return null;
        }

        $results = core_course_category::search_courses(['search' => $courseidnumber]);
        if (count($results) > 0) {
            return current($results);
        }

        $this->courseNotFound[] = $courseidnumber;
        return null;
    }

    /**
     * Monta o código de integração do curso concatenando os campos Período + "-" + Código da Disciplina.
     *
     * Exemplo:
     * - Período: "2024/1"
     * - Código da Disciplina: "POA-SSI405"
     *
     * Retorno esperado: "2024/1-POA-SSI405"
     */
    private function build_course_idnumber(array $enrollment): string
    {
        return $enrollment['periodo'] . '-' . $enrollment['cod_disciplina'];
    }

    /**
     * Busca estudante pelo login/CPF.
     */
    private function search_student(string $login): object|false
    {
        global $DB;
        return $DB->get_record('user', ['username' => $login]);
    }

    /**
     * Inscreve o estudante em uma disciplina.
     */
    private function enroll_student(object $course, object $user): void
    {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        if (is_enrolled(context\course::instance($course->id), $user)) {
            mtrace(sprintf(
                "INFO: O estudante já está inscrito na disciplina. usuário: %s, disciplina: %s",
                $user->username,
                $course->idnumber
            ));
            return;
        }

        $enrolinstances = enrol_get_instances($course->id, true);
        $manualenrolinstance = current(array_filter($enrolinstances, function ($instance) {
            return $instance->enrol == 'manual';
        }));
        if (empty($manualenrolinstance)) {
            mtrace(
                'ERRO: o plugin Inscrição Manual ativado é um pré-requisito para o funcionamento da ' .
                'integração com o SIGAA. Ative o plugin Inscrição Manual e execute o processo de integração novamente.'
            );
            return;
        }

        $manualenrol = enrol_get_plugin('manual');
        $manualenrol->enrol_user($manualenrolinstance, $user->id, $this->studentroleid);
        mtrace(sprintf(
            "INFO: O estudante foi inscrito na disciplina com sucesso. usuário: %s, disciplina: %s",
            $user->username,
            $course->idnumber
        ));
    }

    /**
     * Tenta inscrever o estudante nas disciplinas retornadas pela API do SIGAA.
     */
    public function enroll_student_into_courses(array $enrollment): void
    {
        $user = $this->search_student($enrollment['login']);
        if (!$user) {
            mtrace(sprintf('ERRO: Usuário não encontrado. usuário: %s', $enrollment['login']));
            return;
        }

        foreach ($enrollment['disciplinas'] as $course_enrollment) {
            try {
                $courseidnumber = $this->build_course_idnumber($course_enrollment);
                $this->enroll_student_into_single_course($user, $courseidnumber);
            } catch (Exception $e) {
                mtrace(sprintf(
                    'ERRO: Falha ao processar inscrição de estudante em uma disciplina. ' .
                    'matrícula: %s, usuário: %s, disciplina: %s, erro: %s',
                    $enrollment['matricula'],
                    $user->username,
                    $courseidnumber,
                    $e->getMessage()
                ));
            }
        }
    }

    /**
     * Tenta increver o estudante em uma determinada disciplina retornada pela API do SIGAA.
     */
    private function enroll_student_into_single_course(object $user, string $courseidnumber) :void
    {
        $course = $this->search_course($courseidnumber);
        if (!$course) {
            mtrace(sprintf(
                'ERRO: Disciplina não encontrada. Inscrição não realizada. usuário: %s, disciplina: %s',
                $user->username,
                $courseidnumber
            ));
            return;
        }

        $this->enroll_student($course, $user);
    }

}

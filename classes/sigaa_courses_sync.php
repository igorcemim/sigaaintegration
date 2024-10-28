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
use dml_exception;
use Exception;
use moodle_exception;
use stdClass;

class sigaa_courses_sync {

    private string $ano;

    private string $periodo;

    private array $arvorecategorias;

    private array $disciplinascriadas = [];

    private int $basecategoryid;

    private int $editingteacherroleid;

    private object $campocpf;

    private object $campoperiodoletivo;

    private object $campometadata;

    public function __construct(string $ano, string $periodo) {
        global $DB;

        $idpapelprofessor = get_config('local_sigaaintegration', 'teacherroleid');
        if (!$idpapelprofessor) {
            throw new moodle_exception('ERRO: O papel de professor não foi configurado.');
        }

        $nomecampocpf = get_config('local_sigaaintegration', 'cpffieldname');
        if (!$nomecampocpf) {
            throw new moodle_exception('ERRO: O campo de CPF não foi configurado.');
        }

        $campocpf = $DB->get_record('user_info_field', ['shortname' => $nomecampocpf]);
        if (!$campocpf) {
            throw new moodle_exception(
                'ERRO: O campo de CPF configurado não foi encontrado. nomeCampo: ' . $nomecampocpf
            );
        }

        $nomecampoperiodoletivo = get_config('local_sigaaintegration', 'periodfieldname');
        if (!$nomecampoperiodoletivo) {
            throw new moodle_exception('ERRO: O campo de Período Letivo não foi configurado.');
        }

        $campoperiodoletivo = $DB->get_record('customfield_field', ['shortname' => $nomecampoperiodoletivo]);
        if (!$campoperiodoletivo) {
            throw new moodle_exception(
                'ERRO: O campo de Período Letivo configurado não foi encontrado. nomeCampo: ' . $nomecampoperiodoletivo
            );
        }

        $metadatafieldname = get_config('local_sigaaintegration', 'metadatafieldname');
        if (!$metadatafieldname) {
            throw new moodle_exception('ERRO: O campo de Metadados não foi configurado.');
        }

        $campometadata = $DB->get_record('customfield_field', ['shortname' => $metadatafieldname]);
        if (!$campometadata) {
            throw new moodle_exception(
                'ERRO: O campo de Metadados configurado não foi encontrado. nomeCampo: ' . $metadatafieldname
            );
        }

        $idcategoriabase = get_config('local_sigaaintegration', 'basecategory');

        $this->ano = $ano;
        $this->periodo = $periodo;
        $this->arvorecategorias = core_course_category::get_all();
        $this->editingteacherroleid = (int) $idpapelprofessor;
        $this->basecategoryid = (int) $idcategoriabase;
        $this->campocpf = $campocpf;
        $this->campoperiodoletivo = $campoperiodoletivo;
        $this->campometadata = $campometadata;
    }

    public function sync(): void {
        mtrace('INFO: Importando disciplinas e categorias...');

        // Consulta as matrículas
        $client = sigaa_api_client::create();
        $periodoletivo = sigaa_periodo_letivo::buildFromParameters($this->ano, $this->periodo);
        $matriculas = $client->get_enrollments($periodoletivo);

        foreach ($matriculas as $matricula) {
            foreach ($matricula['disciplinas'] as $disciplina) {
                try {
                    $this->importar_disciplina($matricula, $disciplina);
                } catch (Exception $e) {
                    mtrace(sprintf(
                        'ERRO: Falha ao importar disciplina. disciplina: %s, erro: %s',
                        $disciplina['cod_disciplina'],
                        $e->getMessage()
                    ));
                }
            }
        }
    }

    private function getInformacoesDisciplina($dadosdisciplina, string $codigodisciplina): stdClass {
        // Nome da disciplina.
        // Exemplo: 2024/1 - Redes de Computadores I - POA-SSI306
        $nomedisciplina = $dadosdisciplina['periodo']
            . ' - ' . string_helper::capitalize($dadosdisciplina['disciplina'])
            . ' - ' . $dadosdisciplina['cod_disciplina'];

        // Código da disciplina.
        // Exemplo: 2024/1-POA-SSI306
        $idnumber = $dadosdisciplina['periodo'] . '-' . $codigodisciplina;

        $infodisciplina = new stdClass();
        $infodisciplina->fullname = $nomedisciplina;
        $infodisciplina->shortname = $nomedisciplina;
        $infodisciplina->idnumber = $idnumber;
        $infodisciplina->summary = '';
        $infodisciplina->summaryformat = FORMAT_PLAIN;
        $infodisciplina->format = 'topics';

        return $infodisciplina;
    }

    private function buscar_professor_por_cpf(string $cpf): object|false {
        global $DB;

        $sql = <<<EOF
            select
                u.*
            from {user} u
            inner join {user_info_data} infd on
                infd.userid = u.id
                and infd.fieldid = :cpf_field_id
            where
                infd.data = :cpf
            EOF;

        $argumentos = [
            'cpf_field_id' => $this->campocpf->id,
            'cpf' => str_pad($cpf, '11', '0', STR_PAD_LEFT),
        ];
        return $DB->get_record_sql($sql, $argumentos);
    }

    private function importar_disciplina($dadosmatricula, $dadosdisciplina): void {
        global $DB;

        $codigodisciplina = $dadosdisciplina['periodo'] . '-' . $dadosdisciplina['cod_disciplina'];

        if (array_search($codigodisciplina, $this->disciplinascriadas)) {
            return;
        }

        // Dados da disciplina/curso
        $infodisciplina = $this->getInformacoesDisciplina($dadosdisciplina, $codigodisciplina);

        // Categoria da disciplina
        $semestreidnumber = $dadosmatricula['id_curso'] . '-' . (int) $dadosdisciplina['semestre_oferta_disciplina'];
        $semestrecurso = (int) $dadosdisciplina['semestre_oferta_disciplina'];
        $categoriadisciplina = $this->criar_arvore_categorias(
            $dadosmatricula['curso'],
            $dadosmatricula['id_curso'],
            $semestrecurso,
            $semestreidnumber
        );

        $transaction = $DB->start_delegated_transaction();
        try {
            $disciplina = $this->criar_disciplina($categoriadisciplina, $infodisciplina, $dadosmatricula, $dadosdisciplina);

            // Vincula o(s) professor(es)
            foreach ($dadosdisciplina['docentes'] as $docente) {
                // Busca o usuário pelo CPF
                if (empty($docente['cpf_docente'])) {
                    mtrace(sprintf(
                        'ERRO: Professor sem CPF cadastrado no SIGAA. Não é possível inscrever na disciplina. nome: %s',
                        $docente['docente']
                    ));
                    continue;
                }

                $usuariodocente = $this->buscar_professor_por_cpf($docente['cpf_docente']);
                if (!$usuariodocente) {
                    throw new moodle_exception(sprintf(
                        'ERRO: Professor não encontrado. Disciplina não foi criada. professor: %s, disciplina: %s',
                        $docente['cpf_docente'],
                        $disciplina->idnumber
                    ));
                }

                // Realiza inscrição
                $this->vincular_professor_disciplina($disciplina, $usuariodocente);
            }

            $transaction->allow_commit();

            $this->disciplinascriadas[] = $codigodisciplina;
        } catch (Exception $exception) {
            mtrace('ERRO: Falha ao importar disciplina. erro:' . $exception->getMessage());
            $transaction->rollback($exception);
        }
    }

    /**
     * Cria a árvore de categorias se necessário e retorna a categoria de semestre do curso/semestre informados.
     *
     * Verifica:
     * - Se é necessário criar a categoria do curso informado, cria se necessário
     * - Se é necessário criar a categoria do semestre informado, cria se necessário
     *
     * Retorna a categoria do semestre ou do curso.
     *
     * @throws moodle_exception
     */
    public function criar_arvore_categorias(
        string $nomecurso,
        string $categoriacursoidnumber,
        int $semestrecurso,
        string $categoriasemestreidnumber
    ): object {
        $categoriacurso = current(array_filter($this->arvorecategorias, function($cat) use ($categoriacursoidnumber) {
            return $cat->idnumber == $categoriacursoidnumber;
        }));

        if (!$categoriacurso) {
            $category = new stdClass();
            $category->name = string_helper::capitalize($nomecurso);
            $category->idnumber = $categoriacursoidnumber;
            $category->parent = $this->basecategoryid;

            $categoriacurso = core_course_category::create($category);

            $this->arvorecategorias[$categoriacurso->id] = $categoriacurso;
            mtrace(sprintf(
                'INFO: Categoria de curso criada. idnumbercategoria: %s, curso: %s',
                $categoriacurso->idnumber,
                $nomecurso
            ));
        }

        /**
         * Caso a disciplina não tenha semestre cadastrado retorna a categoria do curso.
         */
        if ($semestrecurso === 0) {
            return $categoriacurso;
        }

        $categoriasemestre = current(array_filter($this->arvorecategorias, function($cat) use ($categoriasemestreidnumber) {
            return $cat->idnumber == $categoriasemestreidnumber;
        }));

        if (!$categoriasemestre) {
            $category = new stdClass();
            $category->name = "Semestre {$semestrecurso}";
            $category->parent = $categoriacurso->id;
            $category->idnumber = $categoriasemestreidnumber;

            $categoriasemestre = core_course_category::create($category);

            $this->arvorecategorias[$categoriasemestre->id] = $categoriasemestre;

            mtrace(sprintf(
                'INFO: Categoria de semestre criada. idnumbercategoria: %s, curso: %s, semestre: %s',
                $categoriasemestre->idnumber,
                $nomecurso,
                $semestrecurso
            ));
        }

        /**
         * Retorna a categoria do semestre.
         */
        return $categoriasemestre;
    }

    /**
     * Cria o curso se necesário e retorna o objeto do curso.
     *
     * @throws moodle_exception
     */
    public function criar_disciplina($categoriadisciplina, $infodisciplina, $dadosmatricula, $dadosdisciplina): object {
        global $CFG;
        require_once($CFG->dirroot . '/course/lib.php');

        /**
         * Verifica se a disciplina já existe
         */
        $results = core_course_category::search_courses(['search' => $infodisciplina->idnumber]);
        if (count($results) > 0) {
            return current($results);
        }

        // Monta o objeto de metadados
        $metadata = [
            'id_curso' => $dadosmatricula['id_curso'],
            'periodo' => $dadosdisciplina['periodo'],
            'cod_disciplina' => $dadosdisciplina['cod_disciplina'],
            'semestre_oferta_disciplina' => $dadosdisciplina['semestre_oferta_disciplina'],
        ];

        $course = new stdClass();
        $course->fullname = $infodisciplina->fullname;
        $course->shortname = $infodisciplina->shortname;
        $course->summary = $infodisciplina->summary;
        $course->summaryformat = $infodisciplina->summaryformat;
        $course->format = $infodisciplina->format;
        $course->idnumber = $infodisciplina->idnumber;
        $course->category = $categoriadisciplina->id;

        // Monta os campos customizados com a origem e os metadados da disciplina
        $course->{'customfield_' . $this->campoperiodoletivo->shortname} = $metadata['periodo'];
        $course->{'customfield_' . $this->campometadata->shortname} = json_encode($metadata);

        $novocurso = create_course($course);

        mtrace(sprintf(
            'INFO: Disciplina criada. idnumber: %s, fullname: %s',
            $novocurso->idnumber,
            $novocurso->fullname
        ));

        return $novocurso;
    }

    /**
     * Inscreve o professor ao curso e vincula as roles necessárias no contexto do curso.
     *
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function vincular_professor_disciplina(object $course, object $user): void {
        global $CFG;
        require_once($CFG->dirroot . '/lib/enrollib.php');

        if (is_enrolled(context\course::instance($course->id), $user)) {
            mtrace(sprintf(
                'INFO: Professor já está inscrito na disciplina. usuário: %s, disciplina: %s',
                $user->username,
                $course->idnumber
            ));
            return;
        }

        $enrolinstances = enrol_get_instances($course->id, true);
        $manualenrolinstance = current(array_filter($enrolinstances, function($instance) {
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
        $manualenrol->enrol_user($manualenrolinstance, $user->id, $this->editingteacherroleid);

        mtrace(sprintf(
            "INFO: Professor inscrito na disciplina com sucesso. professor: %s, disciplina: %s",
            $user->username,
            $course->idnumber
        ));
    }

}

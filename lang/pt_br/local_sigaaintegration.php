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
 * Languages configuration for the local_sigaaintegration plugin.
 *
 * @package   local_sigaaintegration
 * @copyright 2024, Igor Ferreira Cemim
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Integração SIGAA';
$string['settings'] = 'Integração SIGAA - Configurações';
$string['apisettings'] = 'Configurações da API';
$string['apisettings_information'] = 'Configurações de URL e credenciais de acesso para a API do SIGAA.';
$string['apibaseurl'] = 'URL base';
$string['apibaseurl_information'] = 'URL base da API do SIGAA.';
$string['apiclientid'] = 'Client ID';
$string['apiclientid_information'] = 'Client ID da API do SIGAA.';
$string['apiclientsecret'] = 'Client Secret';
$string['apiclientsecret_information'] = 'Client Secret da API do SIGAA.';
$string['othersettings'] = 'Outras Configurações';
$string['cpffieldname'] = 'Nome do Campo de CPF';
$string['cpffieldname_information'] = 'Nome breve do campo personalizado utilizado para armazenar o CPF do professor.';
$string['originfieldname'] = 'Nome do Campo de Origem';
$string['originfieldname_information'] = 'Nome breve do campo personalizado utilizado para armazenar a origem do curso.';
$string['metadatafieldname'] = 'Nome do Campo de Metadados';
$string['metadatafieldname_information'] = 'Nome breve do campo personalizado utilizado para armazenar os metadados do curso.';
$string['basecategory'] = 'Categoria Base';
$string['basecategory_information'] = 'Categoria onde serão inseridas as categorias e disciplinas importadas.';
$string['studentroleid'] = 'Papel de estudante';
$string['studentroleid_information'] = 'Papel utilizado para inscrever os estudantes nas disciplinas ao importar as matrículas.';
$string['teacherroleid'] = 'Papel de professor';
$string['teacherroleid_information'] = 'Papel utilizado para inscrever os professores nas disciplinas ao importar as disciplinas.';
$string['manageintegration'] = 'Integração SIGAA - Gerenciar Integração';
$string['period'] = 'Período (ano/semestre)';
$string['period_help'] = 'Informe o período (ano/semestre) para qual o processamento será realizado.';
$string['importenrollments'] = 'Importar matrículas';
$string['importcourses'] = 'Importar disciplinas e categorias';
$string['archivecourses'] = 'Arquivar disciplinas';
$string['import'] = 'Importar';
$string['archive'] = 'Arquivar';
$string['sync_task_name'] = 'Sync Task';
$string['error:no_enrol_instance'] = 'Manual enrol plugin is disabled.';
$string['error:user_already_enrolled'] = 'User "{$a->userid}" is already enrolled into course "{$a->courseid}"';
$string['error:course_already_exists'] = 'Course already exists.';

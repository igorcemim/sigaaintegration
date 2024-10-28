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
 * Adds settings links to admin tree.
 *
 * @package   local_sigaaintegration
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage(
    'local_sigaaintegration',
     new lang_string('settings', 'local_sigaaintegration')
);
$ADMIN->add('root', $settings);

$manageintegration = new admin_externalpage(
    'local_sigaaintegration_manageintegration',
    new lang_string('manageintegration', 'local_sigaaintegration'),
    new moodle_url('/local/sigaaintegration/manageintegration.php')
);
$ADMIN->add('root', $manageintegration);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading(
        'apisettings',
        new lang_string('apisettings', 'local_sigaaintegration'),
        new lang_string('apisettings_information', 'local_sigaaintegration')
    ));

    $apibaseurl = new admin_setting_configtext(
        'local_sigaaintegration/apibaseurl',
        new lang_string('apibaseurl', 'local_sigaaintegration'),
        new lang_string('apibaseurl_information', 'local_sigaaintegration'),
        '',
        PARAM_URL
    );
    $settings->add($apibaseurl);

    $apiclientid = new admin_setting_configtext(
        'local_sigaaintegration/apiclientid',
        new lang_string('apiclientid', 'local_sigaaintegration'),
        new lang_string('apiclientid_information', 'local_sigaaintegration'),
        ''
    );
    $settings->add($apiclientid);

    $apiclientsecret = new admin_setting_configpasswordunmask(
        'local_sigaaintegration/apiclientsecret',
        new lang_string('apiclientsecret', 'local_sigaaintegration'),
        new lang_string('apiclientsecret_information', 'local_sigaaintegration'),
        ''
    );
    $settings->add($apiclientsecret);

    $settings->add(new admin_setting_heading(
        'userfields_settings',
        new lang_string('userfields_settings', 'local_sigaaintegration'),
        new lang_string('userfields_settings_information', 'local_sigaaintegration')
    ));

    $cpffieldname = new admin_setting_configtext(
        'local_sigaaintegration/cpffieldname',
        new lang_string('cpffieldname', 'local_sigaaintegration'),
        new lang_string('cpffieldname_information', 'local_sigaaintegration'),
        'cpf_sigaa',
        PARAM_ALPHANUMEXT
    );
    $settings->add($cpffieldname);

    $settings->add(new admin_setting_heading(
        'coursefields_settings',
        new lang_string('coursefields_settings', 'local_sigaaintegration'),
        new lang_string('coursefields_settings_information', 'local_sigaaintegration')
    ));

    $periodfieldname = new admin_setting_configtext(
        'local_sigaaintegration/periodfieldname',
        new lang_string('periodfieldname', 'local_sigaaintegration'),
        new lang_string('periodfieldname_information', 'local_sigaaintegration'),
        'periodo_letivo',
        PARAM_ALPHANUMEXT
    );
    $settings->add($periodfieldname);

    $metadatafieldname = new admin_setting_configtext(
        'local_sigaaintegration/metadatafieldname',
        new lang_string('metadatafieldname', 'local_sigaaintegration'),
        new lang_string('metadatafieldname_information', 'local_sigaaintegration'),
        'metadata',
        PARAM_ALPHANUMEXT
    );
    $settings->add($metadatafieldname);

    $settings->add(new admin_setting_heading(
        'othersettings',
        new lang_string('othersettings', 'local_sigaaintegration'),
        ''
    ));

    $basecategory = new admin_settings_coursecat_select(
        'local_sigaaintegration/basecategory',
        new lang_string('basecategory', 'local_sigaaintegration'),
        new lang_string('basecategory_information', 'local_sigaaintegration')
    );
    $settings->add($basecategory);

    $archivecategoryname = new admin_setting_configtext(
        'local_sigaaintegration/archivecategoryname',
        new lang_string('archivecategoryname', 'local_sigaaintegration'),
        new lang_string('archivecategoryname_information', 'local_sigaaintegration'),
        'Disciplinas antigas'
    );
    $settings->add($archivecategoryname);

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());

        $student = get_archetype_roles('student');
        $student = reset($student);
        $studentroleid = new admin_setting_configselect(
            'local_sigaaintegration/studentroleid',
            new lang_string('studentroleid', 'local_sigaaintegration'),
            new lang_string('studentroleid_information', 'local_sigaaintegration'),
            $student->id ?? null,
            $options
        );

        $editingteacher = get_archetype_roles('editingteacher');
        $editingteacher = reset($editingteacher);
        $teacherroleid = new admin_setting_configselect(
            'local_sigaaintegration/teacherroleid',
            new lang_string('teacherroleid', 'local_sigaaintegration'),
            new lang_string('teacherroleid_information', 'local_sigaaintegration'),
            $editingteacher->id ?? null,
            $options
        );

        $settings->add($studentroleid);
        $settings->add($teacherroleid);
    }
}

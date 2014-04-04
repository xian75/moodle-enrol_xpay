<?php

defined('MOODLE_INTERNAL') or die();

if ($ADMIN->fulltree) {
    //--- settings ------------------------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_xpay_settings', '', get_string('pluginname_desc', 'enrol_xpay')));
    $settings->add(new admin_setting_configtext('enrol_xpay/userid', get_string('userid', 'enrol_xpay'), get_string('userid_desc', 'enrol_xpay'), ''));
    $settings->add(new admin_setting_configpasswordunmask('enrol_xpay/key', get_string('key', 'enrol_xpay'), get_string('key_desc', 'enrol_xpay'), ''));


    //--- enrol instance defaults ----------------------------------------------------------------------------
    $settings->add(new admin_setting_heading('enrol_xpay_defaults',
        get_string('enrolinstancedefaults', 'admin'), get_string('enrolinstancedefaults_desc', 'admin')));

    $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                     ENROL_INSTANCE_DISABLED => get_string('no'));
    $settings->add(new admin_setting_configselect('enrol_xpay/status',
        get_string('status', 'enrol_xpay'), get_string('status_desc', 'enrol_xpay'), ENROL_INSTANCE_DISABLED, $options));

    $settings->add(new admin_setting_configtext('enrol_xpay/cost', get_string('cost', 'enrol_xpay'), '', 0, PARAM_FLOAT, 4));

    $xpaycurrencies = array(
        'AUD' => 'AUD',
        'CAD' => 'CAD',
        'CHF' => 'CHF',
        'EUR' => 'EUR',
        'FJD' => 'FJD',
        'FRF' => 'FRF',
        'GBP' => 'GBP',
        'HKD' => 'HKD',
        'JPY' => 'JPY',
        'KWD' => 'KWD',
        'MYR' => 'MYR',
        'NZD' => 'NZD',
        'PNG' => 'PNG',
        'SBD' => 'SBD',
        'SGD' => 'SGD',
        'TOP' => 'TOP',
        'USD' => 'USD',
        'VUV' => 'VUV',
        'WST' => 'WST',
        'ZAR' => 'ZAR',
    );
    $settings->add(new admin_setting_configselect('enrol_xpay/currency', get_string('currency', 'enrol_xpay'), '', 'EUR', $xpaycurrencies));

    if (!during_initial_install()) {
        $options = get_default_enrol_roles(get_context_instance(CONTEXT_SYSTEM));
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect('enrol_xpay/roleid',
            get_string('defaultrole', 'enrol_xpay'), get_string('defaultrole_desc', 'enrol_xpay'), $student->id, $options));
    }

    $settings->add(new admin_setting_configtext('enrol_xpay/enrolperiod',
        get_string('enrolperiod', 'enrol_xpay'), get_string('enrolperiod_desc', 'enrol_xpay'), 0, PARAM_INT));
}


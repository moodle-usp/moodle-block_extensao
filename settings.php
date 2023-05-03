<?php

defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {

    $setting = new admin_setting_configtext('block_extensao/host', 'Host','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/port', 'Port','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/database', 'Database','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/user', 'User','', '', PARAM_TEXT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_extensao/password', 'Password','', '', PARAM_TEXT);
    $settings->add($setting);

}
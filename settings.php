<?php
/** 
 * Automate CSV user group movements with cron process
 * 
 * Admin settings
 * 
 * @package    local 
 * @subpackage movegroup 
 * @author     Ken Chang <kenc@pukunui.com>, Pukunui 
 * @copyright  2018 onwards, Pukunui 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later 
 */ 
 
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uploaduser/locallib.php');



// No external navigation as there is no frontend for this plugin.

// Capability check to filter the users.
if (has_capability('local/movegroup:upload', context_system::instance())) {
//if (has_capability('moodle/site:uploadusers', context_system::instance())) {
    // To create specific settings for the plugin.

  $settings = new admin_settingpage('local_movegroup_settings', 
  new lang_string('pluginname', 'local_movegroup'), 
  'local/movegroup:upload');
           
    $settings->add(new admin_setting_configtext(
                'local_movegroup/clifilelocation',
                new lang_string('clifilelocation', 'local_movegroup'),
                '',
                $CFG->dataroot.'/movegroup',
                PARAM_URL,
                80
                ));

    $settings->add(new admin_setting_configtext(
                'local_movegroup/cliprocessedlocation',
                new lang_string('cliprocessedlocation', 'local_movegroup'),
                '',
                $CFG->dataroot.'/movegroup/processed',
                PARAM_URL,
                80
                ));
                
     $choices = array(0 => get_string('userdeletemonths', 'local_movegroup'),
                      1 => '1',
                      2 => '2',
                      3 => '3',
                      4 => '4',
                      5 => '5',
                      6 => '6',
                      7 => '7',
                      8 => '8',
                      9 => '9',
                      10 => '10',
                      11 => '11',
                      12 => '12');
                     
    $settings->add(new admin_setting_configselect(
               'local_movegroup/regularydelete',
               new lang_string('regularydeleteexpiredfile', 'local_movegroup'),
               '',
               2,
               $choices
               ));
    $admin = get_admin();
    $ADMIN->add('localplugins', $settings);
}


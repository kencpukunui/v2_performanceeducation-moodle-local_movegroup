<?php
/**
 * Automate CSV user movement to occur with cron process
 *
 * String definitions
 *
 * @package    local
 * @subpackage movegroup
 * @author     Ken Chang <kenc@pukunui.com>, Pukunui
 * @copyright  2018 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL') || die();

$string['clicronenabled'] = 'Cron enabled';
$string['clifilelocation'] = 'CSV File Directory';
$string['cliprocessedlocation'] = 'CSV Processed File Directory';
$string['cliemailreports'] = 'Email Reports';
$string['enrolementdate'] = 'Enrolmentdate';
$string['pluginname'] = 'Automated User Group Movement';
$string['movegroup:upload'] = 'Upload csv file and move group';
$string['userrenamed'] = 'User renamed';
$string['useraccountupdated'] = 'User updated';
$string['useraccountuptodate'] = 'User account up-to-date';
$string['userdeleted'] = 'User deleted';
$string['uupasswordcron'] = 'Generated in cron';
$string['userdeletemonths'] = 'Please select a number of month to delete';
$string['regularydeleteexpiredfile'] = 'Delete processed csv files after month(s)';
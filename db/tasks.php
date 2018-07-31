<?php
/**
 *
 * Scheduled task Definition
 *
 * @package   local_movegroup
 * @author    Ken Chang <kenc@pukunui.com>, Pukunui
 * @copyright 2018 onwards, Pukunui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$tasks = array(
             array(
                 'classname' => 'local_movegroup\task\movegrouptask',
                 'blocking'  => 0,
                 'minute'    => '30',
                 'hour'      => '0',
                 'day'       => '*',
                 'dayofweek' => '*',
                 'month'     => '*'
             )
         );
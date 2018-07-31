<?php
/**
 * Automate CSV user uploads to occur with cron process
 *
 * Default landing page. There is nothing to do here, so simply redirect.
 *
 * @package    local
 * @subpackage movegroup
 * @author     Ken Chang <kenc@pukunui.com>, Pukunui
 * @copyright  2018 onwards, Pukunui
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

redirect($CFG->wwwroot);
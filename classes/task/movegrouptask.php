<?php
/**
 *
 * Class definition for schedule task
 *
 * @package   local_movegroup
 * @author    Ken Chang <kenc@pukunui.com>, Pukunui
 * @copyright 2018 onwards, Pukunui
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_movegroup\task;

require_once($CFG->dirroot.'/local/movegroup/lib.php');

/**
 * Extend core scheduled task
 */
class movegrouptask extends \core\task\scheduled_task {
    /**
     * Return name of the Task
     * 
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_movegroup');
    }
    
    /**
     * Perform the task
     */
    public function execute() {
        local_movegroup_movegrouptask('auto');
    }
}
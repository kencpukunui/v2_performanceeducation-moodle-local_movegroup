<?php
/**
 * Automate CSV user group movements with cron process 
 *
 * Library functions
 *
 * @package    local
 * @subpackage movegroup
 * @author     Ken Chang, Pukunui {@link http://pukunui.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/'.$CFG->admin.'/tool/uploaduser/locallib.php');
require_once($CFG->libdir.'/csvlib.class.php');
require_once($CFG->dirroot.'/user/profile/lib.php');
require_once($CFG->dirroot.'/group/lib.php');
require_once($CFG->dirroot.'/lib/grouplib.php');
require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * A simple cron function to check settings and automatically process.
 */
function local_movegroup_movegrouptask() {
    mtrace('movegroup: local_movegroup_cron() started at '.date('H:i:s')); // Moodle-show in cron logs
    $clifilelocation      = get_config('local_movegroup', 'clifilelocation'); // Path--> /home/xxxx/moodledata/movegroup
    $cliprocessedlocation = get_config('local_movegroup', 'cliprocessedlocation'); // Path--> /home/xxxx/moodledata/movegroup/processed
    if (empty($clifilelocation)) {
        return;
    }
    if (!(check_dir_exists($clifilelocation, true))) {
        return;
    }
    if (!empty($cliprocessedlocation)) {
        if (!(check_dir_exists($cliprocessedlocation, true))) {
            return;
        }
    }
    // Deleting files that already over the expired day in processed folder , determined by the month selection from Admin user
    $csvfiledeletemonth  = get_config('local_movegroup', 'regularydelete'); // Admin user choose the month (if file is older than the month, will delete it)
    // --------Method1--------//
    if ($pdh = opendir($cliprocessedlocation)) {
        while (false !== ($pfilename = readdir($pdh))) {
            if (stripos($pfilename, '.csv') !== false) { // File name must contained .csv
                $processedcsvfilepath = "$cliprocessedlocation/$pfilename";
                $lastmodifieddate = date ("F d Y H:i:s.", filemtime($processedcsvfilepath));
                $lastmodifiedtimestamp = strtotime($lastmodifieddate);
                $checktoday = date('F d Y H:i:s');
                $checktodaytimestamp = strtotime($checktoday);
                $gettheresultmonths = round(($checktodaytimestamp - $lastmodifiedtimestamp) / 60 / 60 / 24 / 30); // Using current system timestamp minus file modified timestamp and transmit to month parameter
                if ($csvfiledeletemonth > 0) {
                    if ($gettheresultmonths >= $csvfiledeletemonth) { // If the month's parameter is bigger than user choice, means this file last modified day is expied
                        mtrace('movegroup: found processed folder'.'file-> '.$pfilename.' is over expired day'); // Moodle-show in cron logs
                        // Delete file
                        if (!empty($processedcsvfilepath)) {
                            mtrace('movegroup: processing delete file-> '.$pfilename); // Moodle-show in cron logs
                            @unlink($processedcsvfilepath);
                            mtrace('movegroup: expired files delete successfully!');
                        } else {
                            mtrace('movegroup: empty error! can not find the specific file in processed folder');
                        }
                    } else {
                        mtrace('movegroup: detected no expired files in the processed folder'); // Moodle-show in cron logs
                    }
                }
                if ($csvfiledeletemonth <= 0) {
                     mtrace('movegroup: Admin user did not select right month to delete expired files...');
                }
            }
        }
    }
    closedir($pdh);
    // --------Method1--------//
   /* // --------Method2--------//
    if($pdh = opendir($cliprocessedlocation)){
        while (false !== ($pfilename = readdir($pdh))){
            if (stripos($pfilename, '.csv') !== false) { //file name must contained .csv
                $processedcsvfilepath = "$cliprocessedlocation/$pfilename";
                
                $lastmodifieddate = date ("F d Y H:i:s.", filemtime($processedcsvfilepath));
                $lastmodifiedtimestamp = strtotime($lastmodifieddate);
                
                $checktoday = date('F d Y H:i:s');
                $checktodaytimestamp = strtotime($checktoday);
                
                $adjustyear = 0;
                $adjustmonth = 0;
                $adjustdays = 0;
                $adjusthours = 0;
                $adjustseconds = $csvfiledeletemonth;
                
                $minustodaytimestamp = strtotime('-'.$adjustyear.' year'.','.
                                                 '-'.$adjustmonth.' month'.','.
                                                 '-'.$adjustdays.' days'.','.
                                                 '-'.$adjusthours.' hours'.','.
                                                 '-'.$adjustseconds.' seconds'
                                                 , $checktodaytimestamp);
                if($lastmodifiedtimestamp <= $minustodaytimestamp){
                    //delete file
                    if(!empty($processedcsvfilepath)){
                        @unlink($processedcsvfilepath);
                        echo ('remove csv file successfully!!!!');
                    }
                    else{
                        echo ('remove csv file error!!!!');
                    }
                }
                if($lastmodifiedtimestamp > $minustodaytimestamp){
                    //do nothing
                }
            }
        }
    }
    closedir($pdh);
    // --------Method2--------//*/
    // Find files.
    if ($dh = opendir($clifilelocation)) { // Php-open a directory from $clifilelocation path
        $output = array(); // define an array
        while (false !== ($filename = readdir($dh))) { // Php-if there are files in the path will continue reading will pass the specific filename to $filename
            // We only want files ending in csv.
            // Php-search '.csv' string in $filename first appears location, will return number. ex:echo stripos("You love php, I love php too!","PHP"); return 9
            // Compare current file date is bigger than last enrolment date
            if (stripos($filename, '.csv') !== false) {
                // If separated current file date is bigger than the parameter $tempfiledate
                mtrace("movegroup: processing $filename"); // cron log
                ob_start(); // Open cache area
                local_movegroup_cron_process_file($filename, $clifilelocation); // This function did some stuff in cache
                $output[$filename] = ob_get_contents(); // Return current cache content and pass it to $output array
                ob_end_clean(); // This function won't output the cache content just delete it
                $source = "$clifilelocation/$filename";
                if (!empty($cliprocessedlocation)) {
                    $destination = "$cliprocessedlocation/$filename";
                    mtrace('movegroup: copy csv file into procressed folder...'); // Moodle-show in cron logs
                    mtrace('movegroup: csv files from--> ' . $source);
                    mtrace('movegroup: folder destination is--> ' . $destination);
                    @copy($source, $destination);
                    mtrace('movegroup: finish copy!'); // Moodle-show in cron logs
                }
                mtrace('movegroup: delete all csv files from--> '. $source); // Moodle-show in cron logs
                @unlink($source);
                mtrace('movegroup: finish delete!'); // Moodle-show in cron logs
            }
        }
        closedir($dh);
    }
    mtrace('movegroup: local_movegroup_cron() ended at '.date('H:i:s')); // Moodle-show in cron logs
}
/**
 * Processes a CSV file. Code has been taken and modified from admin/tool/uploaduser
 *
 * @param string $filename  CSV file
 * @param string $filedir  (optional) directory location of the CSV file
 * @return void
 */
function local_movegroup_cron_process_file($filename, $filedir='') {
    global $CFG, $DB;
    // List the specific user right
    list($STD_FIELDS, $PRF_FIELDS, $settings, $strings) = local_movegroup_cron_values();
    $iid = csv_import_reader::get_new_iid('uploaduser');
    $cir = new csv_import_reader($iid, 'uploaduser');

    // If read contents != true (read a file into a string)
    if (!($content = file_get_contents($filedir.'/'.$filename))) {
        return false;
    }
    $returnurl = new moodle_url('/admin/settings.php?section=local_movegroup_settings'); // /admin/tool/uploaduser/index.php
    $bulknurl  = new moodle_url('/admin/user/user_bulk.php');
    $today = time();
    $today = make_timestamp(date('Y', $today), date('m', $today), date('d', $today), 0, 0, 0);
    // Loading csv file and follow the setting clicsvencoding = utf8 and clicsvdelimiter = comma
    $readcount = $cir->load_csv_content($content, $settings->clicsvencoding, $settings->clicsvdelimiter);
    if ($readcount === false) { // When csv file can not read
        print_error('csvloaderror', '', $returnurl); // An error occurred while loading the CSV file: {$a}
    } else if ($readcount == 0) { // When csv file content is empty
        print_error('csvemptyfile', 'error', $returnurl); // Show the error on page content, get the string 'csvemptyfile' from /public_html/lang/en/error.php and set the website address to $returnurl
    }
    // Validation of csv columns
    $filecolumns = local_movegroup_validate_user_upload_columns($cir, $STD_FIELDS, $PRF_FIELDS, $returnurl);
    // An options of user uploaded 
    $optype = $settings->cliuutype;
    $updatetype        = isset($settings->cliuuupdatetype) ? $settings->cliuuupdatetype : 0;
    $createpasswords   = (!empty($settings->cliuupasswordnew) and $optype != UU_USER_UPDATE);
    $updatepasswords   = (!empty($settings->cliuupasswordold)  and $optype != UU_USER_ADDNEW and $optype != UU_USER_ADDINC and ($updatetype == UU_UPDATE_FILEOVERRIDE or $updatetype == UU_UPDATE_ALLOVERRIDE));
    $allowrenames      = (!empty($settings->cliuuallowrenames) and $optype != UU_USER_ADDNEW and $optype != UU_USER_ADDINC);
    $allowdeletes      = (!empty($settings->cliuuallowdeletes) and $optype != UU_USER_ADDNEW and $optype != UU_USER_ADDINC);
    $allowsuspends     = (!empty($settings->cliuuallowsuspends));
    $bulk              = $settings->cliuubulk;
    $noemailduplicates = $settings->cliuunoemailduplicates;
    //$standardusernames = $settings->cliuustandardusernames;
    //$resetpasswords    = isset($settings->cliuuforcepasswordchange) ? $settings->cliuuforcepasswordchange : UU_PWRESET_NONE;

    // verification moved to two places: after upload and into form2
    //$usersnew      = 0;
    //$usersupdated  = 0;
    //$usersuptodate = 0; //not printed yet anywhere
    //$userserrors   = 0;
    //$deletes       = 0;
    //$deleteerrors  = 0;
    //$renames       = 0;
    //$renameerrors  = 0;
    //$usersskipped  = 0;
    //$weakpasswords = 0;

    // caches
    $ccache         = array(); // Course cache - do not fetch all courses here, we  will not probably use them all anyway!
    //$cohorts        = array();
    //$rolecache      = uu_allowed_roles_cache(); // Roles lookup cache
    $manualcache    = array(); // Cache of used manual enrol plugins in each course
    $supportedauths = uu_supported_auths(); // Officially supported plugins that are enabled

    // We use only manual enrol plugin here, if it is disabled no enrol is done
    if (enrol_is_enabled('manual')) {
        $manual = enrol_get_plugin('manual');
    } else {
        $manual = NULL;
    }

    // Clear bulk selection
    if ($bulk) {
        $SESSION->bulk_users = array();
    }

    // Init csv import helper
    $cir->init();
    $linenum = 1; // Column header is first line

    // Init upload progress tracker
    $upt = new uu_progress_tracker();
    $upt->start(); // Start table

    while ($line = $cir->next()) {
        $upt->flush(); // Output cache area
        $linenum++;

        $upt->track('line', $linenum);

        $user = new stdClass();

        // Add fields to user object
        foreach ($line as $keynum => $value) {
            if (!isset($filecolumns[$keynum])) {
                // This should not happen
                continue;
            }
            $key = $filecolumns[$keynum];
            
            if (strpos($key, 'profile_field_') === 0) {
                // NOTE: bloody mega hack alert!!
                if (isset($USER->$key) and is_array($USER->$key)) {
                    // This must be some hacky field that is abusing arrays to store content and format
                    $user->$key = array();
                    $user->$key['text']   = $value;
                    $user->$key['format'] = FORMAT_MOODLE;
                } else {
                    $user->$key = $value;
                }
            } else {
                $user->$key = $value;
            }

            if (in_array($key, $upt->columns)) {
                // Default value in progress tracking table, can be changed later
                $upt->track($key, s($value), 'normal');
            }
        }

        if (!isset($user->username)) {
            // Prevent warnings below
            $user->username = '';
        }

        if (uu_progress_tracker) {
            $user->username = clean_param($user->username, PARAM_USERNAME);
        }
        
        // Make sure we really have username
        if (empty($user->username)) {
            $upt->track('status', get_string('missingfield', 'error', 'username'), 'error');
            $upt->track('username', $strings->error, 'error');
            $userserrors++;
            continue;
        } else if ($user->username === 'guest') {
            $upt->track('status', get_string('guestnoeditprofileother', 'error'), 'error');
            $userserrors++;
            continue;
        }

        if ($user->username !== clean_param($user->username, PARAM_USERNAME)) {
            $upt->track('status', get_string('invalidusername', 'error', 'username'), 'error');
            $upt->track('username', $strings->error, 'error');
            $userserrors++;
        }

        if (empty($user->mnethostid)) {
            $user->mnethostid = $CFG->mnet_localhost_id;
        }

        if ($existinguser = $DB->get_record('user', array('username' => $user->username, 'mnethostid' => $user->mnethostid))) {
            $upt->track('id', $existinguser->id, 'normal', false);
        }

        // Add default values for remaining fields
        $formdefaults = array();
        foreach ($STD_FIELDS as $field) {
            if (isset($user->$field)) {
                continue;
            }
            // All validation moved to form2
            if (isset($settings->{"cli$field"})) {
                // Process templates
                $user->$field = uu_process_template($settings->{"cli$field"}, $user);
                $formdefaults[$field] = true;
                if (in_array($field, $upt->columns)) {
                    $upt->track($field, s($user->$field), 'normal');
                }
            }
        }
        foreach ($PRF_FIELDS as $field) {
            if (isset($user->$field)) {
                continue;
            }
            if (isset($settings->{"cli$field"})) {
                // Process templates
                $user->$field = uu_process_template($settings->{"cli$field"}, $user);
                $formdefaults[$field] = true;
            }
        }
        if ($existinguser) {
            $user->id = $existinguser->id;

            $upt->track('username', html_writer::link(new moodle_url('/user/profile.php', array('id' => $existinguser->id)), s($existinguser->username)), 'normal', false);
            $upt->track('suspended', $strings->yesnooptions[$existinguser->suspended] , 'normal', false);
            $upt->track('auth', $existinguser->auth, 'normal', false);

            if (is_siteadmin($user->id)) {
                $upt->track('status', $strings->usernotupdatedadmin, 'error');
                $userserrors++;
                continue;
            }

            $existinguser->timemodified = time();
            // Do NOT mess with timecreated or firstaccess here!

            // Load existing profile data
            profile_load_data($existinguser);

            $doupdate = false;
            $dologout = false;

            if ($updatetype != UU_UPDATE_NOCHANGES and !$remoteuser) {
                if (!empty($user->auth) and $user->auth !== $existinguser->auth) {
                    $upt->track('auth', s($existinguser->auth).'-->'.s($user->auth), 'info', false);
                    $existinguser->auth = $user->auth;
                    if (!isset($supportedauths[$user->auth])) {
                        $upt->track('auth', $strings->userauthunsupported, 'warning');
                    }
                    $doupdate = true;
                    if ($existinguser->auth === 'nologin') {
                        $dologout = true;
                    }
                }
                $allcolumns = array_merge($STD_FIELDS, $PRF_FIELDS);
                foreach ($allcolumns as $column) {
                    if ($column === 'username' or $column === 'password' or $column === 'auth' or $column === 'suspended') {
                        // These can not be changed here
                        continue;
                    }
                    if (!property_exists($user, $column) or !property_exists($existinguser, $column)) {
                        // This should never happen
                        debugging("Could not find $column on the user objects", DEBUG_DEVELOPER);
                        continue;
                    }
                    if ($updatetype == UU_UPDATE_MISSING) {
                        if (!is_null($existinguser->$column) and $existinguser->$column !== '') {
                            continue;
                        }
                    } else if ($updatetype == UU_UPDATE_ALLOVERRIDE) {
                        // We override everything

                    } else if ($updatetype == UU_UPDATE_FILEOVERRIDE) {
                        if (!empty($formdefaults[$column])) {
                            // Do not override with form defaults
                            continue;
                        }
                    }
                    if ($existinguser->$column !== $user->$column) {
                        if ($column === 'email') {
                            if ($DB->record_exists('user', array('email' => $user->email))) {
                                if ($noemailduplicates) {
                                    $upt->track('email', $strings->emailduplicate, 'error');
                                    $upt->track('status', $strings->usernotupdated, 'error');
                                    $userserrors++;
                                    continue 2;
                                } else {
                                    $upt->track('email', $strings->emailduplicate, 'warning');
                                }
                            }
                            if (!validate_email($user->email)) {
                                $upt->track('email', get_string('invalidemail'), 'warning');
                            }
                        }

                        if ($column === 'lang') {
                            if (empty($user->lang)) {
                                // Do not change to not-set value.
                                continue;
                            } else if (clean_param($user->lang, PARAM_LANG) === '') {
                                $upt->track('status', get_string('cannotfindlang', 'error', $user->lang), 'warning');
                                continue;
                            }
                        }

                        if (in_array($column, $upt->columns)) {
                            $upt->track($column, s($existinguser->$column).'-->'.s($user->$column), 'info', false);
                        }
                        $existinguser->$column = $user->$column;
                        $doupdate = true;
                    }
                }
            }

            try {
                $auth = get_auth_plugin($existinguser->auth);
            } catch (Exception $e) {
                $upt->track('auth', get_string('userautherror', 'error', s($existinguser->auth)), 'error');
                $upt->track('status', $strings->usernotupdated, 'error');
                $userserrors++;
                continue;
            }
        }

        // Find course enrolments, groups, roles/types and enrol periods
        // This is again a special case, we always do this for any updated or created users
        foreach ($filecolumns as $column) {
            
            $shortname = $user->courseshortname;

           /* if (!preg_match('/^course\d+$/', $column)) {
                continue;
            }
            $i = substr($column, 6);
            var_dump($i);
            echo('</br>');
            if (empty($user->{'course'.$i})) {
                continue;
            }
            $shortname = $user->{'course'.$i};*/

            if (!array_key_exists($shortname, $ccache)) {
                if (!$course = $DB->get_record('course', array('shortname' => $shortname), 'id, shortname')) {
                    $upt->track('enrolments', get_string('unknowncourse', 'error', s($shortname)), 'error');
                    continue;
                }
                $ccache[$shortname] = $course;
                $ccache[$shortname]->groups = null;
            }
            $courseid      = $ccache[$shortname]->id;
            $coursecontext = context_course::instance($courseid);
            if (!isset($manualcache[$courseid])) {
                $manualcache[$courseid] = false;
                if ($manual) {
                    if ($instances = enrol_get_instances($courseid, false)) {
                        foreach ($instances as $instance) {
                            if ($instance->enrol === 'manual') {
                                $manualcache[$courseid] = $instance;
                                break;
                            }
                        }
                    }
                }
            }

            // Make sure two group fields is not empty
            if (!empty($user->from_group) && !empty($user->to_group)) {
                $upt->track("Group fields", "from_group & to_group field not empty");
                $fromgroupname = $user->from_group;
                $togroupname = $user->to_group;
                // Check two field's group of this spicific course is exist or not
                // Return from group id
                $isfromgroupnameexist = groups_get_group_by_name($courseid, $fromgroupname);
                // Return to group id
                $istogroupnameexist = groups_get_group_by_name($courseid, $togroupname);
                $isuserinfromgroupmember = groups_is_member($isfromgroupnameexist, $user->id);
                $isuserintogroupmember = groups_is_member($istogroupnameexist, $user->id);
                if ($isfromgroupnameexist == true && $istogroupnameexist == true) {
                    mtrace("</br> Enter from_group exist && to_group exist");
                    // If the user is a member of the group
                    if ($isuserinfromgroupmember == true) {
                        try {
                            // Remove user from group
                            if (groups_remove_member($isfromgroupnameexist, $user->id)) {
                                mtrace("</br> remove user from from_group successfully");
                                $upt->track('enrolments', "remove member from " . $fromgroupname . " successfully");
                            } else {
                                mtrace("</br> remove user from from_group unsuccessfully");
                                $upt->track('enrolments', "remove member from " . $fromgroupname . " unsuccessfully");
                            }
                        } catch (moodle_exception $e) {
                            mtrace("</br> remove user from from_group thrown exceptions");
                            $upt->track('enrolments', "remove member from " . $fromgroupname . " thrown exception");
                            continue;
                        }
                    } else{
                        // If the user isn't a member of the group
                        // Do nothing
                    }
                    
                    if ($isuserintogroupmember == true) {
                        // Do nothing because the user is already in the new group of this course
                        mtrace("</br> user is a member of to_group, won't add to it!!!!");
                    } else {
                        try {
                            if (groups_add_member($istogroupnameexist, $user->id)) {
                                mtrace("</br> add user to new to_group successfully");
                                $upt->track('enrolments', get_string('addedtogroup', '', s($togroupname)));
                            } else {
                                mtrace("</br> add user to new to_group unsuccessfully");
                                $upt->track('enrolments', get_string('addedtogroupnot', '', s($togroupname)), 'error');
                            }
                        } catch (moodle_exception $e) {
                            mtrace("</br> add user to new to_group thrown exception");
                            $upt->track('enrolments', get_string('addedtogroupnot', '', s($togroupname)), 'error');
                            continue;
                        }
                    }
                    mtrace("</br> Out from_group exist && to_group exist");
                }
                
                if ($isfromgroupnameexist == true && $istogroupnameexist == false) {
                    mtrace("</br> Enter from_group exist && to_group not exist");
                    if($isuserinfromgroupmember == true) { // If the user is a member of the group
                        try {
                            // Remove user from group
                            if (groups_remove_member($isfromgroupnameexist, $user->id)) {
                                mtrace("</br> remove user from from_group successfully");
                                $upt->track('enrolments', "remove member from " . $fromgroupname . " successfully");
                            } else {
                                mtrace("</br> remove user from from_group unsuccessfully");
                                $upt->track('enrolments', "remove member from " . $fromgroupname . " unsuccessfully");
                            }
                        } catch (moodle_exception $e) {
                            mtrace("</br> remove user from from_group thrown exceptions");
                            $upt->track('enrolments', "remove member from " . $fromgroupname . " thrown exception");
                            continue;
                        }
                    } else {
                        // If the user isn't a member of the group
                        // Do nothing
                    }
                    
                    // Create new group and add user inside the group
                    // Build group cache
                    if (is_null($ccache[$shortname]->groups)) {
                        $ccache[$shortname]->groups = array();
                        if ($groups = groups_get_all_groups($courseid)) {
                            foreach ($groups as $gid => $group) {
                                $ccache[$shortname]->groups[$gid] = new stdClass();
                                $ccache[$shortname]->groups[$gid]->id   = $gid;
                                $ccache[$shortname]->groups[$gid]->name = $group->name;
                                if (!is_numeric($group->name)) { // only non-numeric names are supported!!!
                                    $ccache[$shortname]->groups[$group->name] = new stdClass();
                                    $ccache[$shortname]->groups[$group->name]->id   = $gid;
                                    $ccache[$shortname]->groups[$group->name]->name = $group->name;
                                }
                            }
                        }
                    }
                    // Group exists?
                    if (!array_key_exists($togroupname, $ccache[$shortname]->groups)) {
                        // If group doesn't exist,  create it
                        $newgroupdata = new stdClass();
                        $newgroupdata->name = $togroupname;
                        $newgroupdata->courseid = $ccache[$shortname]->id;
                        $newgroupdata->description = '';
                        $gid = groups_create_group($newgroupdata);
                        if ($gid){
                            $ccache[$shortname]->groups[$togroupname] = new stdClass();
                            $ccache[$shortname]->groups[$togroupname]->id   = $gid;
                            $ccache[$shortname]->groups[$togroupname]->name = $newgroupdata->name;
                        } else {
                            $upt->track('enrolments', get_string('unknowngroup', 'error', s($togroupname)), 'error');
                            continue;
                        }
                    }
                    $gid   = $ccache[$shortname]->groups[$togroupname]->id;
                    $gname = $ccache[$shortname]->groups[$togroupname]->name;
    
                    try {
                        if (groups_add_member($gid, $user->id)) {
                            mtrace("</br> add user to new from_group successfully");
                            $upt->track('enrolments', get_string('addedtogroup', '', s($gname)));
                        } else {
                            mtrace("</br> add user to new from_group unsuccessfully");
                            $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                        }
                    } catch (moodle_exception $e) {
                        mtrace("</br> add user to new from_group thrown exception");
                        $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                        continue;
                    }
                    mtrace("</br> Out from_group exist && to_group not exist");
                }
                if ($isfromgroupnameexist == false && $istogroupnameexist == true) {
                    mtrace("</br> Enter from_group not exist && to_group exist");
                    if ($isuserintogroupmember == true) {
                        // Do nothing because user already in To group
                    } else { // Add the user to To group
                        try {
                            if (groups_add_member($istogroupnameexist, $user->id)) {
                                mtrace("</br> add user to new from_group successfully");
                                $upt->track('enrolments', get_string('addedtogroup', '', s($togroupname)));
                            } else {
                                mtrace("</br> add user to new from_group unsuccessfully");
                                $upt->track('enrolments', get_string('addedtogroupnot', '', s($togroupname)), 'error');
                            }
                        } catch (moodle_exception $e) {
                            mtrace("</br> add user to new from_group thrown exception");
                            $upt->track('enrolments', get_string('addedtogroupnot', '', s($togroupname)), 'error');
                            continue;
                        }
                    }
                    mtrace("</br> Out from_group not exist && to_group exist");
                }
                if ($isfromgroupnameexist == false && $istogroupnameexist == false) {
                    mtrace("</br> Enter from_group not exist && to_group not exist");
                    // Create new group and add user inside the group
                    // Build group cache
                    if (is_null($ccache[$shortname]->groups)) {
                        $ccache[$shortname]->groups = array();
                        if ($groups = groups_get_all_groups($courseid)) {
                            foreach ($groups as $gid => $group) {
                                $ccache[$shortname]->groups[$gid] = new stdClass();
                                $ccache[$shortname]->groups[$gid]->id   = $gid;
                                $ccache[$shortname]->groups[$gid]->name = $group->name;
                                if (!is_numeric($group->name)) { // only non-numeric names are supported!!!
                                    $ccache[$shortname]->groups[$group->name] = new stdClass();
                                    $ccache[$shortname]->groups[$group->name]->id   = $gid;
                                    $ccache[$shortname]->groups[$group->name]->name = $group->name;
                                }
                            }
                        }
                    }
                    // Group exists?
                    if (!array_key_exists($togroupname, $ccache[$shortname]->groups)) {
                        // If group doesn't exist, create it
                        $newgroupdata = new stdClass();
                        $newgroupdata->name = $togroupname;
                        $newgroupdata->courseid = $ccache[$shortname]->id;
                        $newgroupdata->description = '';
                        $gid = groups_create_group($newgroupdata);
                        if ($gid) {
                            $ccache[$shortname]->groups[$togroupname] = new stdClass();
                            $ccache[$shortname]->groups[$togroupname]->id   = $gid;
                            $ccache[$shortname]->groups[$togroupname]->name = $newgroupdata->name;
                        } else {
                            $upt->track('enrolments', get_string('unknowngroup', 'error', s($togroupname)), 'error');
                            continue;
                        }
                    }
                    $gid   = $ccache[$shortname]->groups[$togroupname]->id;
                    $gname = $ccache[$shortname]->groups[$togroupname]->name;
                    try {
                        if (groups_add_member($gid, $user->id)) {
                            mtrace("</br> add user to new from_group successfully");
                            $upt->track('enrolments', get_string('addedtogroup', '', s($gname)));
                        } else {
                            mtrace("</br> add user to new from_group unsuccessfully");
                            $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                        }
                    } catch (moodle_exception $e) {
                        mtrace("</br> add user to new from_group thrown exception");
                        $upt->track('enrolments', get_string('addedtogroupnot', '', s($gname)), 'error');
                        continue;
                    }
                    mtrace("</br> Out from_group not exist && to_group not exist");
                }
            }
            
            if (empty($user->from_group)) {
                $upt->track("Group fields", "from_group field is empty", 'error');
            }
            if (empty($user->to_group)) {
                $upt->track("Group fields", "to_group field is empty", 'error');
            }
        }
    }
    $upt->close(); // Close table
    $cir->close();
    $cir->cleanup(true);
}

/**
 * Set up various values needed by the upload user scripts. These have been taken directly
 * from admin/tool/uploaduser
 *
 * @return array
 */
function local_movegroup_cron_values() {
    global $DB;

    // Array of all valid fields for validation
    $STD_FIELDS = array('id', 'firstname', 'lastname', 'username', 'email',
            'city', 'country', 'lang', 'timezone', 'mailformat',
            'maildisplay', 'maildigest', 'htmleditor', 'autosubscribe',
            'institution', 'department', 'idnumber', 'skype',
            'msn', 'aim', 'yahoo', 'icq', 'phone1', 'phone2', 'address',
            'url', 'description', 'descriptionformat', 'password',
            'courseshortname', // Kentest30072018
            'from_group', // Kentest30072018
            'to_group', // Kentest30072018
            'auth',        // Watch out when changing auth type or using external auth plugins!
            'oldusername', // Use when renaming users - this is the original username
            'suspended',   // 1 means suspend user account, 0 means activate user account, nothing means keep as is for existing users
            'deleted',     // 1 means delete user
            'mnethostid',  // Can not be used for adding, updating or deleting of users - only for enrolments, groups, cohorts and suspending.
            );
    // Define an array
    $PRF_FIELDS = array();
    // Read data from table mdl_user_info_field and pass it to $prof_fields
    if ($prof_fields = $DB->get_records('user_info_field')) {
        foreach ($prof_fields as $prof_field) {
            // Select all the data from column shortname's content and combined to be a string profile_field_XXXX and pass it to the $PRF_FIELDS[] array
            $PRF_FIELDS[] = 'profile_field_'.$prof_field->shortname;
        }
    }
    // Clean $prof_fields to be empty
    unset($prof_fields);

    $settings = get_config('local_uploaduser');

    // Set some defaults. (check the local file public_html/local/uploaderuser/settings.php, if the parameter below has a empty or null value, will force set to right value)
    if (empty($settings->clicsvencoding)) $settings->clicsvencoding = 'utf8';
    if (empty($settings->clicsvdelimiter)) $settings->clicsvdelimiter = 'comma';
    if (empty($settings->cliuutype)) $settings->cliuutype = UU_USER_ADD_UPDATE;
    if (empty($settings->cliuubulk)) $settings->cliuubulk = UU_BULK_NONE;
    if (empty($settings->cliuunoemailduplicates)) $settings->cliuunoemailduplicates = 1;
    if (empty($settings->cliuustandardusernames)) $settings->cliuustandardusernames = 1;
    if (empty($settings->cliuupasswordnew)) $settings->cliuupasswordnew = 1;
    if (empty($settings->cliuupasswordold)) $settings->cliuupasswordold = 0;
    if (empty($settings->cliuuallowrenames)) $settings->cliuuallowrenames = 0;
    if (empty($settings->cliuuallowdeletes)) $settings->cliuuallowdeletes = 0;
    if (empty($settings->cliuuallowsuspends)) $settings->cliuuallowsuspends = 1;
    if (empty($settings->cliuuforcepasswordchange)) $settings->cliuuforcepasswordchange = UU_PWRESET_WEAK;

    // Set some strings
    // Pass a specific string to a parameter from different file folder
    $strings = new stdClass;
    $strings->userrenamed             = get_string('userrenamed', 'local_uploaduser'); // Parameter is userrenamed, string index is userrenamed, folder path is public_html/local/uploaderuser/lang/en/local_uploaduser.php
    $strings->usernotrenamedexists    = get_string('usernotrenamedexists', 'error');
    $strings->usernotrenamedmissing   = get_string('usernotrenamedmissing', 'error');
    $strings->usernotrenamedoff       = get_string('usernotrenamedoff', 'error');
    $strings->usernotrenamedadmin     = get_string('usernotrenamedadmin', 'error');
     
    $strings->userupdated             = get_string('useraccountupdated', 'local_uploaduser');
    $strings->usernotupdated          = get_string('usernotupdatederror', 'error');
    $strings->usernotupdatednotexists = get_string('usernotupdatednotexists', 'error');
    $strings->usernotupdatedadmin     = get_string('usernotupdatedadmin', 'error');
     
    $strings->useruptodate            = get_string('useraccountuptodate', 'local_uploaduser');
     
    $strings->useradded               = get_string('newuser');
    $strings->usernotadded            = get_string('usernotaddedregistered', 'error');
    $strings->usernotaddederror       = get_string('usernotaddederror', 'error');
     
    $strings->userdeleted             = get_string('userdeleted', 'local_uploaduser');
    $strings->usernotdeletederror     = get_string('usernotdeletederror', 'error');
    $strings->usernotdeletedmissing   = get_string('usernotdeletedmissing', 'error');
    $strings->usernotdeletedoff       = get_string('usernotdeletedoff', 'error');
    $strings->usernotdeletedadmin     = get_string('usernotdeletedadmin', 'error');
    
    $strings->cannotassignrole        = get_string('cannotassignrole', 'error');
    
    $strings->userauthunsupported     = get_string('userauthunsupported', 'error');
    $strings->emailduplicate          = get_string('useremailduplicate', 'error');
     
    $strings->invalidpasswordpolicy   = get_string('invalidpasswordpolicy', 'error');
    $strings->error                   = get_string('error');
    $strings->yes                     = get_string('yes');
    $strings->no                      = get_string('no');
    $strings->yesnooptions = array(0 => $strings->no, 1 => $strings->yes);
    return array($STD_FIELDS, $PRF_FIELDS, $settings, $strings);
}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts standard column names to lowercase.
 * @param csv_import_reader $cir
 * @param array $stdfields standard user fields
 * @param array $profilefields custom profile fields
 * @param moodle_url $returnurl return url in case of any error
 * @return array list of fields
 */
function local_movegroup_validate_user_upload_columns(csv_import_reader $cir, $stdfields, $profilefields, moodle_url $returnurl) {
    $columns = $cir->get_columns();
    if (empty($columns)) { // If detected column return true
        $cir->close();
        $cir->cleanup();
        print_error('cannotreadtmpfile', 'error', $returnurl); // Error reading temporary file
    }
    if (count($columns) < 2) { // If detected column elements number is less than 2
        $cir->close();
        $cir->cleanup();
        print_error('csvfewcolumns', 'error', $returnurl); // Not enough columns, please verify the delimiter setting
    }

    // Test columns
    $processed = array();

    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        $lcfield = core_text::strtolower($field);
        if (in_array($field, $stdfields) or in_array($lcfield, $stdfields)) {
            // standard fields are only lowercase
            $newfield = $lcfield;

        } else if (in_array($field, $profilefields)) {
            // Exact profile field name match - these are case sensitive
            $newfield = $field;

        } else if (in_array($lcfield, $profilefields)) {
            // hack: somebody wrote uppercase in csv file, but the system knows only lowercase profile field
            $newfield = $lcfield;

        } /*else if (preg_match('/^(cohort|course|group|type|role|enrolperiod|rtomclassid|rtombatch)\d+$/', $lcfield)) {
            // special fields for enrolments
            $newfield = $lcfield;

        } */
        // Kentest20180525
        /*else if (preg_match('/^(cohort|course|group|ngroup|type|role|enrolperiod|rtomclassid|rtombatch)\d+$/', $lcfield)) {
            // Special fields for enrolments
            $newfield = $lcfield;

        }*/
        // Kentest30072018
        else if (preg_match('/^(cohort|courseshortname|from_group|to_group|type|role|enrolperiod|rtomclassid|rtombatch)\d+$/', $lcfield)) {
            // Special fields for enrolments
            $newfield = $lcfield;
        }
        else {
            $cir->close();
            $cir->cleanup();
            print_error('invalidfieldname', 'error', $returnurl, $field);
        }
        if (in_array($newfield, $processed)) {
            $cir->close();
            $cir->cleanup();
            print_error('duplicatefieldname', 'error', $returnurl, $newfield);
        }
        $processed[$key] = $newfield;
    }
    return $processed;
}
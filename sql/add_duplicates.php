<?php
   /**********************************************************\
   | This script enters the relations of duplicate tasks into  |
   | the databse.                                              |
   \***********************************************************/
   
define('IN_FS', true);

require_once '../includes/functions.inc.php';
require_once '../includes/constants.inc.php';
require_once BASEDIR . '/includes/db.inc.php';

$db = new Database;
$db->dbOpenFast($conf['database']);

$check_sql = $db->Query('SELECT task_id, closure_comment, resolution_reason FROM {tasks}');

while ($row = $db->FetchRow($check_sql))
{
    if ($row['resolution_reason'] == 6) {
        preg_match("/\b(?:FS#|bug )(\d+)\b/", $row['closure_comment'], $dupe_of);
        if (count($dupe_of)) {
            $existing = $db->Query('SELECT * FROM {related} WHERE this_task = ? AND related_task = ? AND is_duplicate = 1',
                                    array($row['task_id'], $dupe_of[1]));
                               
            if ($db->CountRows($existing) == 0) {  
                $db->Query('INSERT INTO {related} (this_task, related_task, is_duplicate) VALUES(?,?,1)',
                            array($row['task_id'], $dupe_of[1]));
                echo $row['task_id'] . ' is a duplicate of ' . $dupe_of[1] . '.<br />';
            }
        }
    }
}

$check_sql = $db->Query('SELECT this_task, related_task FROM {related} WHERE is_duplicate = 0');
$deleted = array();

while ($row = $db->FetchRow($check_sql))
{
    $existing = $db->Query('SELECT related_id FROM {related} WHERE this_task = ? AND related_task = ? AND is_duplicate = 0',
                            array($row['related_task'], $row['this_task']));
                              
    if ($db->CountRows($existing) == 1 && !isset($deleted[$row['related_task'].'-'.$row['this_task']])) {
        $deleted[$row['this_task'].'-'.$row['related_task']] = true;
        $db->Query('DELETE FROM {related} WHERE related_id = ?', array($db->FetchOne($existing)));
    }
}

?>
<?php
// set a whole bunch of DEFAULT variables if they're not already set
//  this is a whole convoluted bunch of crap, but it works.

get_language_pack($lang, 'index');

$orderby = array($_GET['order'], $_GET['order2']);

foreach ( $orderby as $key => $val ) {
  switch ($orderby[$key]) {
    case "id": $orderby[$key] = 'task_id';
    break;
    case "proj": $orderby[$key] = 'attached_to_project';
    break;
    case "type": $orderby[$key] = 'task_type';
    break;
    case "date": $orderby[$key] = 'date_opened';
    break;
    case "sev": $orderby[$key] = 'task_severity';
    break;
    case "cat": $orderby[$key] = 'product_category';
    break;
    case "status": $orderby[$key] = 'item_status';
    break;
    case "due": $orderby[$key] = 'closedby_version';
    break;
    case "prog": $orderby[$key] = 'percent_complete';
    break;
    case "lastedit": $orderby[$key] = 'last_edited_time';
    break;
    case "pri": $orderby[$key] = 'task_priority';
    break;
    case "openedby": $orderby[$key] = 'opened_by';
    break;
    case "reportedin": $orderby[$key] = 'product_version';
    break;
    case "assignedto": $orderby[$key] = 'assigned_to';
    break;
    default: $orderby[$key] = 'task_severity';
    break;
  };
};

$sort = array($_GET['sort'], $_GET['sort2']);
foreach ( $sort as $key => $val ) {
  switch ($sort[$key]) {
    case "asc": $sort[$key] = "ASC";
    break;
    case "desc": $sort[$key] = "DESC";
    break;
    default: $sort[$key] = "DESC";
    break;
  };
};

$sortorder = "{$orderby[0]} {$sort[0]}, {$orderby[1]} {$sort[1]}"; 

// Check that what was submitted is a numerical value; most of them should be

// page we're on
if (is_numeric($_GET['pagenum'])) {
  $pagenum = $_GET['pagenum'];
} else {
  $pagenum = "0";
};
// number of results per page
if (is_numeric($_GET['perpage'])) {
  $perpage = $_GET['perpage'];
} else {
  $perpage = "20";
};

// the mysql query offset is a combination of the num results per page and the page num
$offset = $perpage * $pagenum;

$sql_params = array();
$where = array();

$where[] = 'project_is_active = ?';
$sql_params[] = '1';

// Set the default queries
if ($_GET['project'] != '0') {
  $where[]	= "attached_to_project = ?";
  $sql_params[] = $project_id;
};

// Check for special tasks to display
$dev = $_GET['dev'];

if ($_GET['tasks'] == 'assigned') {
    $dev = $_SESSION['userid'];
} elseif ($_GET['tasks'] == 'reported') {
    $where[] = 'opened_by = ?';
    $sql_params[] = $_SESSION['userid'];
};


// developer whos bugs to show
if (is_numeric($dev)) {
  $where[] = "assigned_to = ?";
  $sql_params[] = $dev;
} elseif ($dev == "notassigned") {
  $where[] = "assigned_to = '0'";
};


// The default task type
if (is_numeric($_GET['type'])) {
  $where[] = "task_type = ?";
  $sql_params[] = $_GET['type'];
};

// The default severity
if (is_numeric($_GET['sev'])) {
  $where[] = "task_severity = ?";
  $sql_params[] = $_GET['sev'];
};

// The default category
if (is_numeric($_GET['cat'])) {
  $temp_where = "(product_category = ?";
  $sql_params[] = $_GET['cat'];

  // Do some weird stuff to add the subcategories to the query
  $get_subs = $fs->dbQuery('SELECT category_id
                            FROM flyspray_list_category
                            WHERE parent_id = ?',
                            array($_GET['cat']));
  while ($row = $fs->dbFetchArray($get_subs)) {
    $temp_where = $temp_where . " OR product_category =?";
    $sql_params[] = $row['category_id'];
  };
  $where[] = $temp_where . ")";
};

// The default status
if ($_GET['status'] == "all") {
} elseif ($_GET['status'] == "closed") {
  $where[] = "is_closed = ?";
  $sql_params[] = "1";
} elseif (is_numeric($_GET['status'])) {
  $where[]	= "item_status = ?";
  $sql_params[] = $_GET['status'];
} else {
  $where[] = "is_closed != ?";
  $sql_params[] = '1';
};
// The default due in version
if (is_numeric($_GET['due'])) {
  $where[] = "closedby_version = ?";
  $sql_params[] = $_GET['due'];
};
// The default search string
if ($_GET['string']) {
  $string = $_GET['string'];
  $string = ereg_replace('\(', " ", $string);
  $string = ereg_replace('\)', " ", $string);
  $string = trim($string);

  $where[]	= "(t.item_summary LIKE ? OR t.detailed_desc LIKE ? OR t.task_id LIKE ?)";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
  $sql_params[] = "%$string%";
};

if ($_GET['project'] == '0') {
    $get = "&amp;project=0";
} else {
    $get = "&amp;project={$project_id}";
};
// for page numbering
$extraurl = $get . "&amp;tasks={$_GET['tasks']}&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;dev={$dev}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;due={$_GET['due']}&amp;string={$_GET['string']}&amp;perpage=$perpage";
// for 'sort by this column' links
$get = $extraurl . "&amp;pagenum=$pagenum";
$extraurl .= "&amp;order={$_GET['order']}&amp;sort={$_GET['sort']}";
$extraurl .= "&amp;order2={$_GET['order2']}&amp;sort2={$_GET['sort2']}";

?>

<!-- Query line -->
<map id="projectsearchform" name="projectsearchform">
<form action="index.php" method="get">
<p id="search">
<input type="hidden" name="tasks" value="<?php echo $_GET['tasks']; ?>" />
<input type="hidden" name="project" value="<?php echo $_GET['project'] == '0' ? '0' : $project_id;?>" />
  <label for="searchtext"><?php echo $index_text['searchthisproject'];?>:</label>
    <input id="searchtext" name="string" type="text" size="20"
    maxlength="100" value="<?php echo $_GET['string'];?>" accesskey="q" />

    <select name="type">
      <option value=""><?php echo $index_text['alltasktypes'];?></option>
      <?php
      $tasktype_list = $fs->dbQuery('SELECT tasktype_id, tasktype_name FROM flyspray_list_tasktype
                                       WHERE show_in_list = 1
                                       ORDER BY list_position');
      while ($row = $fs->dbFetchArray($tasktype_list)) {
        if ($_GET['type'] == $row['tasktype_id']) {
          echo "<option value=\"{$row['tasktype_id']}\" selected=\"selected\">{$row['tasktype_name']}</option>\n";
        } else {
          echo "<option value=\"{$row['tasktype_id']}\">{$row['tasktype_name']}</option>\n";
        };
      };
      ?>
    </select>

    <select name="sev">
      <option value=""><?php echo $index_text['allseverities'];?></option>
      <?php
      require("lang/$lang/severity.php");
      foreach($severity_list as $key => $val) {
        if ($_GET['sev'] == $key) {
          echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
    </select>

    <select name="due">
      <option value=""><?php echo $index_text['dueanyversion'];?></option>
      <?php
      $ver_list = $fs->dbQuery("SELECT version_id, version_name
                                  FROM flyspray_list_version
                                  WHERE project_id=? AND show_in_list=? AND version_tense=?
                                  ORDER BY list_position", array($project_id, '1', '3'));
      while ($row = $fs->dbFetchArray($ver_list)) {
        if ($_GET['due'] == $row['version_id']) {
          echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
        } else {
          echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
        };
      };
      ?>
    </select>

    <br />
    
    <select name="dev">
      <option value=""><?php echo $index_text['alldevelopers'];?></option>
      <option value="notassigned" <?php if ($dev == "notassigned") { echo "SELECTED";};?>><?php echo $index_text['notyetassigned'];?></option>
      <?php
      $fs->ListUsers($dev);
      ?>
    </select>

    <select name="cat">
      <option value=""><?php echo $index_text['allcategories'];?></option>
      <?php
      $cat_list = $fs->dbQuery('SELECT category_id, category_name
                                  FROM flyspray_list_category
                                  WHERE project_id=? AND show_in_list=? AND parent_id < ?
                                  ORDER BY list_position', array($project_id, '1', '1'));
      while ($row = $fs->dbFetchArray($cat_list)) {
        $category_name = stripslashes($row['category_name']);
        if ($_GET['cat'] == $row['category_id']) {
          echo "<option value=\"{$row['category_id']}\" selected=\"selected\">$category_name</option>\n";
        } else {
          echo "<option value=\"{$row['category_id']}\">$category_name</option>\n";
        };

        $subcat_list = $fs->dbQuery('SELECT category_id, category_name
                                  FROM flyspray_list_category
                                  WHERE project_id=? AND show_in_list=? AND parent_id = ?
                                  ORDER BY list_position', array($project_id, '1', $row['category_id']));
        while ($subrow = $fs->dbFetchArray($subcat_list)) {
          $subcategory_name = stripslashes($subrow['category_name']);
          if ($_GET['cat'] == $subrow['category_id']) {
            echo "<option value=\"{$subrow['category_id']}\" selected=\"selected\">&rarr;$subcategory_name</option>\n";
          } else {
            echo "<option value=\"{$subrow['category_id']}\">&rarr;$subcategory_name</option>\n";
          };
        };
      };
      ?>
    </select>

    <select name="status">
      <option value="all" <?php if ($_GET['status'] == "all") { echo "selected=\"selected\"";};?>><?php echo $index_text['allstatuses'];?></option>
      <option value="" <?php if ($_GET['status'] == "") { echo "selected=\"selected\"";};?>><?php echo $index_text['allopentasks'];?></option>
      <?php
      require("lang/$lang/status.php");
      foreach($status_list as $key => $val) {
        if ($_GET['status'] == $key) {
          echo "<option value=\"$key\" selected=\"selected\">$val</option>\n";
        } else {
          echo "<option value=\"$key\">$val</option>\n";
        };
      };
      ?>
	  <option value="closed" <?php if($_GET['status'] == "closed") { echo "SELECTED";};?>><?php echo $index_text['closed'];?></option>
    </select>

    <select name="perpage">
      <option value="10" <?php if ($perpage == "10") { echo "selected=\"selected\"";};?>>10</option>
      <option value="20" <?php if ($perpage == "20") { echo "selected=\"selected\"";};?>>20</option>
      <option value="30" <?php if ($perpage == "30") { echo "selected=\"selected\"";};?>>30</option>
      <option value="40" <?php if ($perpage == "40") { echo "selected=\"selected\"";};?>>40</option>
      <option value="50" <?php if ($perpage == "50") { echo "selected=\"selected\"";};?>>50</option>
      <option value="75" <?php if ($perpage == "75") { echo "selected=\"selected\"";};?>>75</option>
      <option value="100" <?php if ($perpage == "100") { echo "selected=\"selected\"";};?>>100</option>
    </select>

    <input class="mainbutton" type="submit" value="<?php echo $index_text['search'];?>" />
</p>
</form>
</map>

<?php
/**
 * Displays header cell for report list
 *
 * @param string $colname	The name of the column
 * @param string $orderkey	The actual key to use when ordering the list
 * @param string $image    An image to display instead of the column name
 */
function list_heading($colname, $orderkey, $image = '')
{
  global $project_prefs;
  global $index_text;
  global $get;
  
  if(ereg("$colname", $project_prefs['visible_columns']))
  { 
    if($orderkey)
    {
      if($_GET['order'] == "$orderkey")
      {
        $class = 'class="severity3"';

        $order2 = $_GET['order2'];
        $sort2 = $_GET['sort2'];
        
        if ($_GET['sort'] == 'desc')
        {
          $sort1 = "asc";
        }
        else
        {
          $sort1 = "desc";
        }
      }
      else
      {
        $order2 = $_GET['order'];
        $sort2 = $_GET['sort'];
      }

      $sort1 = ( $sort1 == 'asc' ? 'asc' : 'desc' );
      $sort2 = ( $sort2 == 'asc' ? 'asc' : 'desc' );
      
      echo "<th $class>";
      $title = $index_text['sortthiscolumn'];
      $link = "?order=$orderkey$get&amp;sort=$sort1&amp;order2=$order2&amp;sort2=$sort2";
      echo "<a title=\"$title\" href=\"$link\">";
      echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" />\n";
      
      // Sort indicator arrows
      if($_GET['order'] == $orderkey) {
        echo '&nbsp;&nbsp;<img src="themes/' . $project_prefs['theme_style'] . '/' . $_GET['sort'] . '.png" />';
      };
      
      echo "</a></th>";
    }
    else
    {
      echo "<th>";
      echo $image == '' ? $index_text[$colname] : "<img src=\"{$image}\" alt=\"{$index_text[$colname]}\" />";
      echo "</th>";
    }
  } 
}

/**
 * Displays data cell for report list
 *
 * @param string $colname	The name of the column
 * @param string $cellvalue	The value to display in the cell
 * @param integer $nowrap	Whether to force the cell contents not to wrap
 * @param string $url		A URL to wrap around the cell contents
 */
function list_cell($colname,$cellvalue,$nowrap=0,$url=0)
{
  global $project_prefs;

  if(ereg("$colname", $project_prefs['visible_columns']))
  {
    // We have a problem with these conversions applied to the progress cell
    if($colname != "progress")
    {
      $cellvalue = str_replace("&", "&amp;", $cellvalue);
      $cellvalue = str_replace("<", "&lt;", $cellvalue);
      $cellvalue = stripslashes($cellvalue);
    }
    
    // Check if we're meant to force this cell not to wrap
    if($nowrap)
    {
      $cellvalue = str_replace(" ", "&nbsp;", $cellvalue);
    }
    
    echo "<td class=\"project_$colname\">";
    if($url)
    {
      echo "<a href=\"$url\">$cellvalue</a>";
    }
    else
    {
      echo "$cellvalue";
    }
    echo "</td>\n";
  }
}

?>


<?php
// Check that the requested project is active
$getproject = $fs->dbFetchArray($fs->dbQuery('SELECT * FROM flyspray_projects WHERE project_id = ?', array($project_id)));
if ($getproject['project_is_active'] == 1) {
?>

<!--  Summary headings, followed by the query results -->
<table id="tasklist">
<thead>
  <tr>

  <?php
  list_heading('id','id');
  list_heading('project','proj');
  list_heading('tasktype','type');  
  list_heading('category','cat');
  list_heading('severity','sev');
  list_heading('priority','pri');
  list_heading('summary','');
  list_heading('dateopened','date');
  list_heading('status','status');
  list_heading('openedby','openedby');
  list_heading('assignedto','assignedto');
  list_heading('lastedit', 'lastedit');
  list_heading('reportedin','reportedin');
  list_heading('dueversion','due');
  list_heading('comments','', "themes/{$project_prefs['theme_style']}/comment.png");
  list_heading('attachments','', "themes/{$project_prefs['theme_style']}/attachment.png");
  list_heading('progress','prog');
  ?>

  </tr>
</thead>
<tfoot><tr><td colspan="20">
<?php
 
// SQL JOIN condition
$from = 'flyspray_tasks t';

if ($_GET['tasks'] == 'watched') {
    //join the notification table to get watched tasks
    $from .= ' RIGHT JOIN flyspray_notifications fsn ON t.task_id = fsn.task_id';
    $where[] = 'fsn.user_id = ?';
    $sql_params[] = $_SESSION['userid'];
};

// This SQL courtesy of Lance Conry http://www.rhinosw.com/
$from .= ' 

        LEFT JOIN flyspray_projects p ON t.attached_to_project = p.project_id
        LEFT JOIN flyspray_list_tasktype lt ON t.task_type = lt.tasktype_id
        LEFT JOIN flyspray_list_category lc ON t.product_category = lc.category_id
        LEFT JOIN flyspray_list_version lv ON t.product_version = lv.version_id
        LEFT JOIN flyspray_list_version lvc ON t.closedby_version = lvc.version_id
        LEFT JOIN flyspray_users u ON t.assigned_to = u.user_id
        LEFT JOIN flyspray_users uo ON t.opened_by = uo.user_id
        ';

//$where[] = 't.attached_to_project = flyspray_projects.project_id';
$where = join(' AND ', $where);
$get_total = $fs->dbQuery("SELECT * FROM $from
          WHERE $where
          ORDER BY $sortorder", $sql_params);

$total = $fs->dbCountRows($get_total);
print $fs->pagenums($pagenum, $perpage, "6", $total, $extraurl);


?>
</td></tr></tfoot>



<!--<tbody>-->
<?php
// Parts of his SQL courtesy of Lance Conry http://www.rhinosw.com/
$get_details = $fs->dbQuery("
SELECT
        t.*,
        p.project_title,
        p.project_is_active,
        lt.tasktype_name AS task_type,
        lc.category_name AS product_category,
        lv.version_name AS product_version,
        lvc.version_name AS closedby_version,
        u.real_name AS assigned_to,
        uo.real_name AS opened_by
FROM 
        $from
WHERE
        $where
ORDER BY
        $sortorder", $sql_params, $perpage, $offset);
       

  while ($task_details = $fs->dbFetchArray($get_details)) {

    // Set the status text to 'closed' if this task is closed
    if ($task_details['is_closed'] == "1")
    {
      $status = $index_text['closed'];
    } else {
      // Get the full status name
      $status_id = $task_details['item_status'];
      require("lang/$lang/status.php");
      $status = $status_list[$status_id];
    }
    
    // Get the full severity name
    $severity_id = $task_details['task_severity'];
    require("lang/$lang/severity.php");
    $severity = $severity_list[$severity_id];

    // Get the full priority name
    $priority_id = $task_details['task_priority'];
    require("lang/$lang/priority.php");
    $priority = $priority_list[$priority_id];
    
    // see if it's been assigned
    if (!$task_details['assigned_to']) {
      $assigned_to = $details_text['noone'];
    } else {
      $assigned_to = $task_details['assigned_to'];
    };

    // Convert the date_opened to a human-readable format
    $date_opened = $fs->formatDate($task_details['date_opened'], false);
        
    // Convert the last_edited_time to a human-readable format
    if ($task_details['last_edited_time'] > 0) {
      $last_edited_time = $fs->formatDate($task_details['last_edited_time'], false);
    } else {
      $last_edited_time = '';
    };
     
    // get the number of comments and attachments
    $getcomments = $fs->dbQuery("SELECT COUNT(*) AS num_comments FROM flyspray_comments WHERE task_id = ?", array($task_details['task_id']));
    list($comments) = $fs->dbFetchRow($getcomments);
    
    $getattachments = $fs->dbQuery("SELECT COUNT(*) AS num_attachments FROM flyspray_attachments WHERE task_id = ?", array($task_details['task_id']));
    list($attachments) = $fs->dbFetchRow($getattachments);    
    
    // Start displaying the cells for this row
    echo "<tr class=\"severity{$task_details['task_severity']}\"
    onclick='openTask(\"?do=details&amp;id={$task_details['task_id']}\")'
    onmouseover=\"this.className = 'severity{$task_details['task_severity']}_over';
    this.style.cursor = 'hand'\"
    onmouseout=\"this.className = 'severity{$task_details['task_severity']}';
    this.style.cursor = 'default'\">\n";

    list_cell("id",$task_details['task_id'],1,"?do=details&amp;id={$task_details['task_id']}");
    list_cell("project",$task_details['project_title'],1);
    list_cell("tasktype",$task_details['task_type'],1);
    list_cell("category",$task_details['product_category'],1);
    list_cell("severity",$severity,1);
    list_cell("priority",$priority,1);
    list_cell("summary",$task_details['item_summary'],0,"?do=details&amp;id={$task_details['task_id']}");
    list_cell("dateopened",$date_opened);
    list_cell("status",$status,1);
    list_cell("openedby",$task_details['opened_by'],0);
    list_cell("assigned",$task_details['assigned_to'],0);
    list_cell("lastedit",$last_edited_time);
    list_cell("reportedin",$task_details['product_version']);
    list_cell("dueversion",$task_details['closedby_version'],1);
    list_cell("comments",$comments);
    list_cell("attachments",$attachments);
    list_cell("progress","<img src=\"themes/{$project_prefs['theme_style']}/percent-{$task_details['percent_complete']}.png\" width=\"45\" height=\"8\" alt=\"{$task_details['percent_complete']}% {$index_text['complete']}\" title=\"{$task_details['percent_complete']}% {$index_text['complete']}\" />\n");
    
    // The end of this row
    echo "</tr>\n";
  };
  ?>
<!--</tbody>-->
</table>

<?php
// End of checking if the reqeusted project is active
};
?>

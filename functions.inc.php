<?php

class Flyspray {
    var $version = '0.9.5';

   function getGlobalPrefs() {
      $get_prefs = $this->dbQuery("SELECT pref_name, pref_value FROM flyspray_prefs");

      $global_prefs = array();

      while (list($pref, $value) = $this->dbFetchRow($get_prefs)) {
         $temp_array = array("$pref"  => "$value");
         $global_prefs = $global_prefs + $temp_array;
      }

      return $global_prefs;
   }

   function getProjectPrefs($project_id) {
      $get_prefs = $this->dbQuery("SELECT * FROM flyspray_projects WHERE project_id = ?", array($project_id));

      $project_prefs = $this->dbFetchArray($get_prefs);

      return $project_prefs;
   }

   function dbOpen($dbhost = '', $dbuser = '', $dbpass = '', $dbname = '', $dbtype = '') {

      $this->dbtype = $dbtype;

      $this->dblink = NewADOConnection($dbtype);
      $res = $this->dblink->Connect($dbhost, $dbuser, $dbpass, $dbname);
      $this->dblink->SetFetchMode(ADODB_FETCH_BOTH);

      return $res;

   }

   function dbClose() {
      $this->dblink->Close();
   }

   /* Replace undef values (treated as NULL in SQL database) with empty
   strings.
   @param arr	input array or false
   @return	SQL safe array (without undefined values)
   */
   function dbUndefToEmpty($arr) {
       if (is_array($arr)) {
	   $c = count($arr);

	   for($i=0; $i<$c; $i++)
	       if (!isset($arr[$i])) 
		   $arr[$i] = '';
       }
       return $arr;
   }

    /** Replace empty values with 0. Useful when inserting values from
    checkboxes.
    */
    function emptyToZero($arg) {
	return empty($arg) ? 0 : $arg;
    }

   function dbExec($sql, $inputarr=false, $numrows=-1, $offset=-1) {
      // replace undef values (treated as NULL in SQL database) with empty
      // strings
      $inputarr = $this->dbUndefToEmpty($inputarr);
       
      $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
      if (($numrows>=0) or ($offset>=0)) {
	  $result =  $this->dblink->SelectLimit($sql, $numrows, $offset, $inputarr);
      } else {
	  $result =  $this->dblink->Execute($sql, $inputarr);
      }
      if (!$result) {
	  if (function_exists("debug_backtrace")) {
	      echo "<pre>";
	      var_dump(debug_backtrace());
	      echo "<pre>";
	  }
	  
	  die (sprintf("Query {%s} with params {%s} Failed! (%s)", 
		    $sql, implode(', ', $inputarr), 
		    $this->dblink->ErrorMsg()));
      }
      return $result;
   }

   function dbCountRows($result) {
      $num_rows = $result->RecordCount();
   	return $num_rows;
   }

   function dbFetchRow(&$result) {
      $row = $result->FetchRow();
   	return $row;
   }

/* compatibility functions */
   function dbQuery($sql, $inputarr=false, $numrows=-1, $offset=-1) {
      $result = $this->dbExec($sql, $inputarr, $numrows, $offset);
      return $result;
   }

   function dbFetchArray(&$result) {
      $row = $this->dbFetchRow($result);
      return $row;
   }

 // Thanks to Mr Lance Conry for this query that saved me a lot of effort.
// Check him out at http://www.rhinosw.com/
function GetTaskDetails($task_id) {

   $flyspray_prefs = $this->GetGlobalPrefs();
   $lang = $flyspray_prefs['lang_code'];

	$get_details = $this->dbQuery("SELECT *,
						vr.version_name as reported_version_name,
						vd.version_name as due_in_version_name
						FROM flyspray_tasks t
						LEFT JOIN flyspray_projects p ON t.attached_to_project = p.project_id
						LEFT JOIN flyspray_list_category c ON t.product_category = c.category_id
						LEFT JOIN flyspray_list_os o ON t.operating_system = o.os_id
						LEFT JOIN flyspray_list_resolution r ON t.resolution_reason = r.resolution_id
						LEFT JOIN flyspray_list_tasktype tt ON t.task_type = tt.tasktype_id
						LEFT JOIN flyspray_list_version vr ON t.product_version = vr.version_id
						LEFT JOIN flyspray_list_version vd ON t.closedby_version = vd.version_id
   
						WHERE t.task_id = ?
						", array($task_id));
	$get_details = $this->dbFetchArray($get_details);
    if (empty($get_details))
           $get_details = array();

	$status_id = $get_details['item_status'];
    require("lang/$lang/status.php");
	$tmp_array = array("status_name" => $status_list[$status_id]);
	$get_details = $get_details + $tmp_array;

	$severity_id = $get_details['task_severity'];
    require("lang/$lang/severity.php");
	$tmp_array = array("severity_name" => $severity_list[$severity_id]);
	$get_details = $get_details + $tmp_array;

	return $get_details;
}


// Thank you to Mr Lance Conry for this awesome, FAST Jabber message function
// Check out his company at http://www.rhinosw.com/
function JabberMessage( $sHost, $sPort, $sUsername, $sPassword, $vTo, $sSubject, $sBody, $sClient='Flyspray' ) {

   if ($sHost != ''
       && $sPort != ''
       && $sUsername != ''
       && $sPassword != ''
       && !empty($vTo)
      ) {

   $sBody = str_replace('&amp;', '&', $sBody);

   $socket = fsockopen ( $sHost, $sPort, $errno, $errstr, 30 );
   if ( !$socket ) {
      return '$errstr (' . $errno . ')';
   } else {

   fputs($socket, '<?xml version="1.0" encoding="UTF-8"?><stream:stream to="' . $sHost . '" xmlns="jabber:client" xmlns:stream="http://etherx.jabber.org/streams">' );
   fgets( $socket, 1 );

   fputs( $socket, '<iq type="set" id="AUTH_01"><query xmlns="jabber:iq:auth"><username>' . $sUsername . '</username><password>' . $sPassword . '</password><resource>' . $sClient . '</resource></query></iq>' );
   fgets( $socket, 1 );

   if ( is_array( $vTo )) {
       foreach ($vTo as $sTo) {
           //fputs ( $socket, '<message to="' . $sTo . '" ><body>' . $sBody . '</body><subject>' . $sSubject . '</subject></message>' );
           fputs ( $socket, '<message to="' . $sTo . '" ><body><![CDATA[' . $sBody . ']]></body><subject><![CDATA[' . $sSubject . ']]></subject></message>' );
       }
    }
    else {
       fputs ( $socket, '<message to="' . $vTo . '" ><body>' . $sBody . '</body><subject>' . $sSubject . '</subject></message>' );
    }

   fclose ( $socket );
   }

   // End of checking that jabber is set up
   }
   
   //return TRUE;
}

   function SendEmail($to, $message) {

       if (empty($to))
	   return;

   $flyspray_prefs = $this->GetGlobalPrefs();

   $lang = $flyspray_prefs['lang_code'];
   require("lang/$lang/functions.inc.php");

   if (is_array($to)) {

     foreach($to as $address) {

       $subject = "{$functions_text['notifyfrom']} \"{$flyspray_prefs['project_title']}\"";
       $contenttype = "Content-Type: text/plain; charset=UTF-8";
       $from = "From: {$flyspray_prefs['project_title']} <{$flyspray_prefs['admin_email']}>";
       $useragent = "User-Agent: Mutt";

	   $message = str_replace('&amp;', '&', $message);
	   $message = "
========================================================
{$functions_text['autogenerated']}
========================================================
\n\n" . $message;

       $message = wordwrap($message, 72);

       mail ("$address", "$subject", $message,
              "$contenttype\r\n"
              ."$from\r\n"
              ."$useragent");
	 };

   } else {

       //$address = $to;
       $subject = "{$functions_text['notifyfrom']} \"{$flyspray_prefs['project_title']}\"";
       $contenttype = "Content-Type: text/plain; charset=UTF-8";
       $from = "From: {$flyspray_prefs['project_title']} <{$flyspray_prefs['admin_email']}>";
       $useragent = "User-Agent: Mutt";

	   $message = str_replace('&amp;', '&', $message);
	   $message = "
========================================================
{$functions_text['autogenerated']}
========================================================
\n\n" . $message;

       $message = wordwrap($message, 72);

       mail ("$to", "$subject", $message,
              "$contenttype\r\n"
              ."$from\r\n"
              ."$useragent");

	};
	//return TRUE;
   // End of mass mail function
   }

   // This function sends out basic messages at specified times.
   function SendBasicNotification($to, $message) {

	if (empty($to))
	    return;

       $flyspray_prefs = $this->GetGlobalPrefs();

       $lang = $flyspray_prefs['lang_code'];
       require("lang/$lang/functions.inc.php");

	   $get_user_details = $this->dbQuery("SELECT real_name, jabber_id, email_address, notify_type FROM flyspray_users WHERE user_id = ?", array($to));
	   list($real_name, $jabber_id, $email_address, $notify_type) = $this->dbFetchArray($get_user_details);

	   // if app preferences say to use jabber, or if the user can (and has) selected jabber
	   // and the jabber options are entered in the applications preferences
	   if (($flyspray_prefs['user_notify'] == '3')
	     OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '2')

		 && $flyspray_prefs['jabber_server'] != ''
	     && $flyspray_prefs['jabber_port'] != ''
	     && $flyspray_prefs['jabber_username'] != ''
	     && $flyspray_prefs['jabber_password'] != ''
	     ) {

	     $message = stripslashes($message);

		$this->JabberMessage(
		  				$flyspray_prefs['jabber_server'],
						$flyspray_prefs['jabber_port'],
						$flyspray_prefs['jabber_username'],
						$flyspray_prefs['jabber_password'],
						$jabber_id,
						"{$functions_text['notifyfrom']} {$flyspray_prefs['project_title']}",
						$message,
						"Flyspray"
						);
		//return TRUE;

	    // if app preferences say to use email, or if the user can (and has) selected email
		} elseif (($flyspray_prefs['user_notify'] == '2') OR ($flyspray_prefs['user_notify'] == '1' && $notify_type == '1')) {

			$to = "$real_name <$email_address>";
			$this->SendEmail($to, $message);
		};
	// End of basic notification function
   }


    // Detailed notification function - generates and passes arrays of recipients
    // These are the additional people who want to be notified of a task changing
    function SendDetailedNotification($task_id, $message) {

	$flyspray_prefs = $this->GetGlobalPrefs();

	$lang = $flyspray_prefs['lang_code'];
	require("lang/$lang/functions.inc.php");

	$jabber_users = array();
	$email_users = array();

	$get_users = $this->dbQuery("SELECT user_id FROM flyspray_notifications WHERE task_id = ?", array($task_id));

	while ($row = $this->dbFetchArray($get_users)) {

	    $get_details = $this->dbQuery("SELECT notify_type, jabber_id, email_address
		    FROM flyspray_users
		    WHERE user_id = ?",
		    array($row['user_id']));
	    while ($subrow = $this->dbFetchArray($get_details)) {

		if (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '1')
			OR ($flyspray_prefs['user_notify'] == '2')) {
		    array_push($email_users, $subrow['email_address']);
		} elseif (($flyspray_prefs['user_notify'] == '1' && $subrow['notify_type'] == '2')
			OR ($flyspray_prefs['user_notify'] == '3')) {
		    array_push($jabber_users, $subrow['jabber_id']);
		};
	    };
	};

	$message = stripslashes($message);

	// Pass the recipients and message onto the Jabber Message function
	$this->JabberMessage(
		$flyspray_prefs['jabber_server'],
		$flyspray_prefs['jabber_port'],
		$flyspray_prefs['jabber_username'],
		$flyspray_prefs['jabber_password'],
		$jabber_users,
		"{$functions_text['notifyfrom']} {$flyspray_prefs['project_title']}",
		$message,
		"Flyspray"
		);


	// Pass the recipients and message onto the mass email function
	$this->SendEmail($email_users, $message);

	//return TRUE;
	// End of detailed notification function
    }



   // This function generates a query of users for the "Assigned To" list
   function listUsers($current) {
     $flyspray_prefs = $this->getGlobalPrefs();

      $these_groups = explode(" ", $flyspray_prefs['assigned_groups']);
      while (list($key, $val) = each($these_groups)) {
	if (empty($val)) 
	  continue;
	$group_details = $this->dbFetchArray($this->dbQuery("SELECT group_name FROM flyspray_groups WHERE group_id = ?", array($val)));

        echo "<optgroup label=\"{$group_details['group_name']}\">\n";

        $user_query = $this->dbQuery("SELECT * FROM flyspray_users WHERE account_enabled = ? AND group_in = ?", array('1', $val));

        while ($row = $this->dbFetchArray($user_query)) {
          if ($current == $row['user_id']) {
            echo "<option value=\"{$row['user_id']}\" SELECTED>{$row['real_name']}</option>\n";
          } else {
            echo "<option value=\"{$row['user_id']}\">{$row['real_name']}</option>\n";
          };
        };

        echo "</optgroup>\n";
      };

  }


  // This provides funky page numbering
  // Thanks to Nathan Fritz for this.  http://www.netflint.net/
  function pagenums($pagenum, $perpage, $pagesper, $totalcount, $extraurl)
{
$flyspray_prefs = $this->GetGlobalPrefs();
$lang = $flyspray_prefs['lang_code'];
require("lang/$lang/functions.inc.php");

if (!($totalcount / $perpage <= 1)) {

    if ($pagenum - 1000 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1000) . $extraurl . "\">{$functions_text['back']} 1,000</a> - ";
    if ($pagenum - 100 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 100) . $extraurl . "\">{$functions_text['back']} 100</a> - ";
    if ($pagenum - 10 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 10) . $extraurl . "\">{$functions_text['back']} 10</a> - ";
    if ($pagenum - 1 >= 0) $output .= "<a href=\"?pagenum=" . ($pagenum - 1) . $extraurl . "\">{$functions_text['back']}</a> - ";
    $start = floor($pagenum - ($pagesper / 2)) + 1;
    if ($start <= 0) $start = 0;
    $finish = $pagenum + ceil($pagesper / 2);
    if ($finish > $totalcount / $perpage) $finish = floor($totalcount / $perpage);
    for ($pagelink = $start; $pagelink <= $finish;  $pagelink++)
    {
        if ($pagelink != $start) $output .= " - ";
        if ($pagelink == $pagenum) {
            $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\"><b>" . ($pagelink + 1) . "</b></a>";
        } else {
            $output .= "<a href=\"?pagenum=" . ($pagelink) . "$extraurl\">" . ($pagelink + 1) . "</a>";
        }
    }
    if ($pagenum + 1 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1) . $extraurl . "\">{$functions_text['forward']}</a> ";
    if ($pagenum + 10 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 10) . $extraurl . "\">{$functions_text['forward']} 10</a> ";
    if ($pagenum + 100 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 100) . $extraurl . "\">{$functions_text['forward']} 100</a> ";
    if ($pagenum + 1000 < $totalcount / $perpage) $output .= " - <a href=\"?pagenum=" . ($pagenum + 1000) . $extraurl . "\">{$functions_text['forward']} 1,000</a> ";
    return $output;
}
}
// End of Flyspray class
}

?>

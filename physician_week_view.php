<?php
 // $Id$
 // desc: physician's weekly calendar view
 // lic : GPL, v2

$page_name="physician_week_view.php";
include ("lib/freemed.php");
include ("lib/calendar-functions.php");

//----- Login/authenticate
freemed::connect ();

//----- Add to stack
$page_title = __("Physician Weekly View");
page_push();

//------HIPAA Logging
$user_to_log=$_SESSION['authdata']['user'];
if((LOGLEVEL<1)||LOG_HIPAA){syslog(LOG_INFO,"physician_week_view.php|user $user_to_log ");}	

// check if there is a valid date... if not, assign current date
if (!checkdate(substr($for_date, 5, 2), substr($for_date, 8, 2),
	substr($for_date, 0, 4))) $for_date = $cur_date;

// calculate previous and next dates for menubar
$prev_date = freemed_get_date_prev ($for_date);
for ($i=1; $i<=6; $i++)
	$prev_date = freemed_get_date_prev ($prev_date);
$next_date = freemed_get_date_next ($for_date);
for ($i=1; $i<=6; $i++)
	$next_date = freemed_get_date_next ($next_date);

//----- Set page title
$page_title = __("Physician Weekly View");

//----- Display previous/next bar
$display_buffer .= "
  <TABLE WIDTH=\"100%\" BGCOLOR=\"#000000\" VALIGN=\"TOP\"
   ALIGN=\"CENTER\" BORDER=0 CELLSPACING=0 CELLPADDING=2>
   <TR BGCOLOR=\"#000000\"><TD VALIGN=CENTER ALIGN=LEFT>
   <A HREF=\"$page_name?for_date=$prev_date&physician=$physician\"
    ><FONT COLOR=\"#ffffff\">&lt;</FONT></A>
   </TD><TD VALIGN=CENTER ALIGN=RIGHT>
   <A HREF=\"$page_name?for_date=$next_date&physician=$physician\"
    ><FONT COLOR=\"#ffffff\">&gt;</FONT></A>
   </TD></TR></TABLE>
   <BR>
";

// Check for us being a physician
$this_user = CreateObject('FreeMED.User');
if ($this_user->isPhysician() and ($physician < 1)) {
	$physician = $this_user->getPhysician();
}

// check if there is a physician specified, and if so, display their
// name, etc at the top...
if ($physician<=0) {
	$display_buffer .= "
     <CENTER>
      <B>".__("No Physician Selected")."</B>
     </CENTER>
     <BR>
	";
} else {
	$phyinfo  = freemed::get_link_rec ($physician, "physician");
	$phylname = $phyinfo["phylname"];
	$phyfname = $phyinfo["phyfname"];
	$phymname = $phyinfo["phymname"];
	$display_buffer .= "
     <CENTER>
      ".__("Physician").": $phylname, $phyfname $phymname
     </CENTER>
     <BR>
	";
}

//----- Actually display the calendar
fc_display_week_calendar ($for_date,
	"calphysician='".addslashes($physician)."'"
);

//----- End and display everything
template_display();
?>

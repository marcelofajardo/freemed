<?php
 // $Id$
 // note: scheduling module for freemed-project
 // lic : GPL, v2

$page_name = "book_appointment.php";
include ("lib/freemed.php");
include ("lib/API.php");
include ("lib/calendar-functions.php");

//----- Login/authenticate
freemed_open_db ();

//----- Check for current patient
if ($patient>0) {
	$this_patient = new Patient ($patient, ($type=="temp"));
} elseif ($SESSION["current_patient"]>0) {
	$this_patient = new Patient ($SESSION["current_patient"]);
	$type = "pat"; // kludge to keep real patient for this
}

if (strlen($selected_date)!=10) {
	$selected_date = $cur_date;
} // fix date if not correct

// set previous and next date variables...
$next = freemed_get_date_next ($selected_date);
$next_wk = $selected_date;
for ($i=1;$i<=7;$i++) $next_wk = freemed_get_date_next ($next_wk);
$prev = freemed_get_date_prev ($selected_date);
$prev_wk = $selected_date;
for ($i=1;$i<=7;$i++) $prev_wk = freemed_get_date_prev ($prev_wk);

switch ($action) {
 case "":
      // STAGE ONE:

      // BROWSE DATES ON THE CALENDAR TO DECIDE WHERE
      // AND WHAT DAY WE ARE LOOKING FOR...

	$page_title = _("Add Appointment");
  //fc_generate_calendar_mini($selected_date,
  // "$page_name?patient=$patient&room=$room&type=$type");

  //$display_buffer .= "
  //  <CENTER>
  //   <B><FONT FACE=\"Arial, Helvetica, Verdana\">
  //   $Current_Date_is ".fm_date_print($selected_date)."
  //   </FONT></B>
  //  </CENTER>
  //  <BR>
  //";

  if (date_in_the_past($selected_date))
    $display_buffer .= "
      <CENTER><I><FONT SIZE=-2
      >"._("this date occurs in the past")."</FONT></I></CENTER>
      <BR>
    ";

    $rm_name = freemed_get_link_field ($room, "room",
      "roomname");
    $rm_desc = freemed_get_link_field ($room, "room",
      "roomdescrip");

    if (strlen($rm_desc)<1) { $rm_desc="";               }
     else                   { $rm_desc="(".$rm_desc.")"; }

    if ($debug) $debug_var = "[$room]";

    $display_buffer .= freemed_patient_box ($this_patient)."
      <P>
      <CENTER>
    ";
    fc_generate_calendar_mini($selected_date,
     "$page_name?patient=$patient&room=$room&type=$type");
    $display_buffer .= " 
      <TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2 VALIGN=MIDDLE
       ALIGN=CENTER>
      ". (
       ($room > 0) ? "
      <TR>
      <TD ALIGN=RIGHT><B>"._("Room")." : </B></TD>
      <TD ALIGN=LEFT>$rm_name $rm_desc $debug_var</TD></TR>
        " : "" )."
      <TR>
      <TD ALIGN=RIGHT><B>"._("Date")." : </B></TD>
      <TD ALIGN=LEFT>".fm_date_print($selected_date)."</TD></TR>
      </TABLE>
      </CENTER>
    ";
    if (date_in_the_past($selected_date)) 
     $display_buffer .= "
      <BR><CENTER><I><FONT SIZE=-2>
      "._("this date occurs in the past")."</FONT></I></CENTER>
     ";
    $display_buffer .= "
      <P><CENTER>
      <FORM ACTION=\"$page_name\" METHOD=POST>
       <INPUT TYPE=HIDDEN NAME=\"action\"
        VALUE=\"\">
       <INPUT TYPE=HIDDEN NAME=\"patient\"
        VALUE=\"".prepare($patient)."\">
       <INPUT TYPE=HIDDEN NAME=\"selected_date\"
        VALUE=\"".prepare($selected_date)."\">
       <INPUT TYPE=HIDDEN NAME=\"type\"
        VALUE=\"".prepare($type)."\">
    ".freemed_display_selectbox (
      $sql->query ("SELECT room.roomname AS roomname, ".
	"facility.psrcity AS poscity,facility.psrstate AS posstate,".
	"facility.psrname AS posname,room.id AS id ".
	"FROM room,facility ".
	"WHERE room.roompos=facility.id ORDER BY roomname"),
      "#roomname# (#posname#/#poscity#,#posstate#)",
      "room"
    )."
       <INPUT TYPE=SUBMIT VALUE=\""._("Change Room")."\">
      </FORM></CENTER>
      <P>
    ";

    // now, find if it is "booked"
    if ($room > 0) { // only if it is specific
        // generate interference map
      fc_generate_interference_map ("calroom='$room'",
         $selected_date);
      if (fc_interference_map_count () < 1) {
        $display_buffer .= "
          <CENTER>
           <I>"._("The selected room is free all day.")."</I>
          </CENTER>
          <BR>
        ";
      }

      // display calendar here

      $display_buffer .= "
        <TABLE WIDTH=100% BORDER=1 CELLSPACING=0 CELLPADDING=3
         BGCOLOR=#777777><TR>
        <TD COLSPAN=2><CENTER>
         <FONT SIZE=-1
          COLOR=#ffffff><B>"._("TIME")."</B></FONT></CENTER></TD>
      ";

      $_alternate = freemed_bar_alternate_color ();
      for ($i=fc_starting_hour();$i<=fc_ending_hour();$i++) {
        if ($i > 11) { 
          $ampm = "pm"; 
          if ($i>12) $ampm_t = $i - 12;
            elseif ($i==12) $ampm_t=$i;
        } else { $ampm = "am"; $ampm_t = $i;}
        if (!fc_check_interference_map($i, "0", $selected_date, false) or
            (freemed_config_value("cal_ob")=="enable")) {
          $display_buffer .= "
            <TR BGCOLOR=\"".($_alternate =
	      freemed_bar_alternate_color ($_alternate))."\">
            <TD ALIGN=RIGHT VALIGN=TOP>
            <A HREF=\"$page_name?action=step2&patient=$patient&hour=$i".
            "&minute=00&room=$room&selected_date=$selected_date&type=$type\"
            >$ampm_t $ampm</A></TD><TD ALIGN=CENTER>
          ";
        } else { // if we _can't_ book here
          $interfere = fc_check_interference_map ($i, "0", $selected_date,
             false);
          $display_buffer .= "
            <TR BGCOLOR=\"".($_alternate =
	      freemed_bar_alternate_color ($_alternate))."\">
            <TD ALIGN=RIGHT VALIGN=TOP>
           ";
          if ($interfere) $display_buffer .= "<I>";
          $display_buffer .= "$ampm_t $ampm";
          if ($interfere) $display_buffer .= "</I>";
          $display_buffer .= "
            </TD><TD ALIGN=CENTER>
          ";
        } // end checking if booked

        for ($j=15;$j<=45;$j+=15) {
          if (!fc_check_interference_map($i, $j, $selected_date, false) or
              freemed_config_value("cal_ob")=="enable") {
            $display_buffer .= "
             <A HREF=\"$page_name?action=step2&patient=$patient&".
             "hour=$i&minute=$j&room=$room&selected_date=$selected_date&".
             "type=$type\"
             ><B>:$j</B></A>&nbsp;
            ";
          } else {
            $interfere = fc_check_interference_map($i, $j, $selected_date,
               false);
            if ($interfere) $display_buffer .= "<I>";
            $display_buffer .= "<B>:$j</B>";
            if ($interfere) $display_buffer .= "</I>";
            $display_buffer .= "&nbsp;\n";
          } // end checking for booked?
        } // end for minutes loop

        $display_buffer .= "
          </TD></TR>
        "; // end row
      } // end for loop (hours)
      $display_buffer .= "
        </TABLE>
      ";
    } // why is this here?

  break; 
 case "step2":

      // STAGE TWO:

      // ACTUALLY BOOKING SOMETHING... REQUIRES ROOM, HOUR,
      // PATIENT NUMBER, PHYSICIAN, ETC... THIS IS THE
      // FINAL FORM.

   $page_title = _("Add Appointment");
   $display_buffer .= freemed_patient_box ($this_patient);

   if (strlen($room)>0) {
     $rm_name = freemed_get_link_field ($room, "room",
       "roomname");
     $rm_desc = freemed_get_link_field ($room, "room",
       "roomdescrip");

     if (strlen($rm_desc)<1) $rm_desc="";
     else $rm_desc="(".$rm_desc.")";
   } else {
     $rm_name = _("NO PREFERENCE");
     $rm_desc = "";
   } // checking if room

   if ($hour > 11) { 
     $ampm = "pm"; 
     if ($hour>12) $ampm_t = $hour - 12;
       elseif ($hour==12) $ampm_t=12;
   } else { $ampm = "am"; $ampm_t = $hour;}
   
     // find default physician by room, if there is one
   if ($room!=0)
     if (freemed_get_link_field($room, "room", "roomdefphy")!=0)
       $physician = freemed_get_link_field($room, "room", "roomdefphy");

     // find the facility for it, with info
   $facility = freemed_get_link_field ($room, "room", "roompos");
   if ($facility > 0) {
     $fac_name = freemed_get_link_field ($facility, "facility", "psrname");
   } else {
     $fac_name = _("Default Facility");
   } // end checking for facility

   if ($debug) $debug_var = "[$room]";
   $display_buffer .= "
     <FORM ACTION=\"$page_name\">
     <INPUT TYPE=HIDDEN NAME=\"action\"   VALUE=\"add\">
     <INPUT TYPE=HIDDEN NAME=\"patient\"  VALUE=\"".prepare($patient)."\">
     <INPUT TYPE=HIDDEN NAME=\"room\"     VALUE=\"".prepare($room)."\">
     <INPUT TYPE=HIDDEN NAME=\"facility\" VALUE=\"".prepare($facility)."\">
     <INPUT TYPE=HIDDEN NAME=\"type\"     VALUE=\"".prepare($type)."\">
     <INPUT TYPE=HIDDEN NAME=\"selected_date\"
      VALUE=\"".prepare($selected_date)."\">
     <INPUT TYPE=HIDDEN NAME=\"hour\"     VALUE=\"".prepare($hour)."\">
     <INPUT TYPE=HIDDEN NAME=\"minute\"   VALUE=\"".prepare($minute)."\">

     <TABLE BORDER=0 CELLSPACING=2 CELLPADDING=2 VALIGN=MIDDLE
      ALIGN=MIDDLE>

     <TR>
     <TD ALIGN=RIGHT><B>"._("Facility")."</B>:</TD>
     <TD ALIGN=LEFT>$fac_name</TD></TR>
     <TR>
     <TD ALIGN=RIGHT><B>"._("Room")."</B>:</TD>
     <TD ALIGN=LEFT>$rm_name $rm_desc</TD></TR>
     <TR>
     <TD ALIGN=RIGHT><B>"._("Date")."</B>:</TD>
     <TD ALIGN=LEFT>".fm_date_print($selected_date)."</TD></TR>
     <TR>
     <TD ALIGN=RIGHT><B>"._("Time")."</B>:</TD>
     <TD ALIGN=LEFT>$ampm_t $minute $ampm</TD></TR>

     <TR>
     <TD ALIGN=RIGHT><B>"._("Duration")."</B>:</TD>
     <TD ALIGN=LEFT><SELECT NAME=\"duration\">
       <OPTION VALUE=\"15\" >0:15 
       <OPTION VALUE=\"30\" >0:30 
       <OPTION VALUE=\"45\" >0:45 
       <OPTION VALUE=\"60\" >1:00 
       <OPTION VALUE=\"75\" >1:15
       <OPTION VALUE=\"90\" >1:30
       <OPTION VALUE=\"105\">1:45
       <OPTION VALUE=\"120\">2:00
       <OPTION VALUE=\"180\">3:00
       <OPTION VALUE=\"480\">8:00
      </SELECT></TD></TR>

     <TR>
     <TD ALIGN=RIGHT><B>"._("Physician")."</B>:</TD>
     <TD ALIGN=LEFT>
   ".freemed_display_selectbox(
		$sql->query(
			"SELECT phyfname,phylname,id 
			FROM physician
			WHERE phyref='no'"),
		"#phylname#,#phyfname#",
		 "physician")."
      </TD></TR>

     <TR>
     <TD ALIGN=RIGHT><B>"._("Note")."</B>:</TD>
     <TD ALIGN=LEFT><INPUT TYPE=TEXT NAME=\"note\" VALUE=\"\"
      SIZE=40 MAXLENGTH=100></TD></TR>
     </TABLE>

     <BR>
     <CENTER>
      <INPUT TYPE=SUBMIT VALUE=\" "._("Commit Booking")." \">
     </CENTER>
     </FORM>
   ";
  break;
 case "add":
  $page_title = _("Add Appointment");
  $display_buffer .= "<CENTER>"._("Adding")." ... ";
	// TODO: migrate to insert_query wrapper
  $query = "INSERT INTO scheduler VALUES (
    '".addslashes($selected_date)."',
    '".addslashes($type)."',
    '".addslashes($hour)."',
    '".addslashes($minute)."',
    '".addslashes($duration)."',
    '".addslashes($facility)."',
    '".addslashes($room)."',     
    '".addslashes($physician)."',
    '".addslashes($patient)."',
    '".addslashes($cptcode)."',
    '".addslashes($status)."',
    '".addslashes($note)."',
    '',
    NULL )";
  $result = $sql->query ($query);

  if ($result) { $display_buffer .= _("done")."."; }
   else        { $display_buffer .= _("ERROR");    }

	/* FIXME: THIS HAS TO BE UNCOMMENTED

  $display_buffer .= "\n$selected_date, $fac_name, $room_nm";

  // get patient, room (lab) and selected_date info
  $dj_rm  = freemed_get_link_field ($room, "room", "roomname");
  $dj_mo  = substr ($selected_date, 5, 2);
  $dj_yr  = substr ($selected_date, 0, 4);
  $dj_day = substr ($selected_date, 8, 2);

  // TODO: change time back to am/pm format
  $dj_title = $dj_rm." ".$this_patient->ptlname." ".$hour.":".$minute." ".$note;
  // dj_text (javascript) popup window should have more information
  $dj_text  = $dj_rm." ".$this_patient->ptlname.", ".$this_patient->ptfname.
    " ".$hour.":".$minute." ".$note;
  $dj_id    = "01"; // dummied up, should be the actual user id
  $dj_sp    = "";

  $dj_query = "INSERT INTO calendar_messages ( ".
    "msg_id, msg_month, msg_day, msg_year, msg_title, msg_text, ".
    "msg_poster_id, msg_recurring, msg_active ) ".
    "VALUES ( ".
    "'NULL'
  $dj_query = $sql->insert_query (
	"calendar_messages",
	array (
		"msg_id"		=>	'NULL',
		"msg_month"		=>	$dj_mo,
		"msg_day"		=>	$dj_day,
		"msg_year"		=>	$dj_year,
		"msg_title"		=>	$dj_title,
		"msg_text"		=>	$dj_text,
		"msg_poster_id"	=>	$dj_id,
		"msg_recurring"	=>	$dj_sp,
		"msg_active"	=>	"1"
	)
  );

  $result = $sql->query ($dj_query);

  if (!$result) $display_buffer .= _("ERROR");

	END OF SECTION THAT HAS TO BE UNCOMMENTED */

  $display_buffer .= "
    </CENTER>
    <P>
    <CENTER>
  ";
  if ($type=="pat") {
	$refresh = "manage.php?id=".urlencode($patient);
    $display_buffer .= "
     <A HREF=\"manage.php?id=$patient\"
     >"._("Manage Patient")."</A>
     </CENTER>
    ";
  } else {
	$refresh = "call-in.php?action=display&id=".urlencode($patient);
    $display_buffer .= "
     <A HREF=\"call-in.php?action=display&id=$patient\"
     >"._("Manage Patient")."</A>
     </CENTER>
    ";
  } // end checking type

  break;
} // end master switch

template_display();

?>

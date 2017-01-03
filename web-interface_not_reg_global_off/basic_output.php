<?php
$predefpath="./testfiles/";
$extension="*.hlpsl";
$bindir="./bin/";
$tempdir="./tempdir/";

$hlpsl2if="hlpsl2if";
$ofmc="ofmc.sh";
$atse="cl-atse.sh";
$satmc="satmc";
$ta4sp="ta4sp";

$debug=false;

// Parse paths inside file config.ini
$config = parse_ini_file("config.ini");

$avispawebsetting = $config[AVISPA_WEB_SETTING];
$localwebinterfacepath = $config[AVISPA_WEB_LOCAL_PATH];
$remotewebinterfacepath = $config[AVISPA_WEB_REMOTE_PATH];
$remoteuser = $config[AVISPA_WEB_REMOTE_USER];
$remoteip = $config[AVISPA_WEB_REMOTE_IP];
$ssh_options= $config[SSH_OPTIONS];


// Detecting user's browser to define apropriate element sizes. 
// Each browser has its own way to arrange the interface elements on the page.
require_once('./include/browser.php');
$br = new Browser;
//echo $br->Name;
if (  (($br->Name == "Mozilla") && ($br->Version < "1.7")) 
    || ($br->Name == "MSIE") 
    || ($br->Name == "Konqueror") )
{
  $textarea_cols = 112; 
  $textarea_rows = 12; 
} 
else 
{
  $textarea_cols = 130;
  $textarea_rows = 14;
}

// Setting the location of the prelude file for SATMC
if ($avispawebsetting==remote)
  $prelude = $remotewebinterfacepath.$tempdir."prelude.if";
elseif ($avispawebsetting==local)
{
  if($localwebinterfacepath=="") 
    echo "AVISPA_WEB_LOCAL_PATH variable not properly set in config.ini<br>";
  else 
    $prelude=$localwebinterfacepath.$tempdir."prelude.if";
} 
else 
  echo "AVISPA_WEB_SETTING variable not properly set in config.ini<br>";

// Setting tool arguments
$ofmc_args="";
$atse_args="-col 10000 ";
$satmc_args="--solver=chaff --enc=gp-efa --mutex=0 --ct=true --oi=false --max=11 --prelude=".$prelude;

// Setting the location of the output dir for TA4SP
if ($avispawebsetting == remote)
  $ta4sp_args="--2AgentsOnly --level=0 --output=".$remotewebinterfacepath.$tempdir;
else
  $ta4sp_args="--2AgentsOnly --level=0 --output=".$localwebinterfacepath.$tempdir;


if($avispawebsetting == remote){
  $copy_hlpsl_remote = "scp -p".$ssh_options.
                                $localwebinterfacepath.$opened_file." ".
                                $remoteuser."@".$remoteip.":".$remotewebinterfacepath.$opened_file;

  $hlpsl2if_cmd = "ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                        $remotewebinterfacepath.$bindir.$hlpsl2if." --all ".
                        $remotewebinterfacepath.$opened_file." 2>&1";

  $copy_if_local = "scp  -p".$ssh_options.
                             $remoteuser."@".$remoteip.":".$remotewebinterfacepath.$opened_file.".if ".
                             $localwebinterfacepath.$opened_file.".if";

  $remove_if_remote = "ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                        "rm ".$remotewebinterfacepath.$opened_file.".if";

  $remove_hlpsl_remote = "ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                        "rm ".$remotewebinterfacepath.$opened_file;

  $ofmc_cmd = "(ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                     $remotewebinterfacepath.$bindir.$ofmc." ".
                     $remotewebinterfacepath.$opened_file.".if ".$ofmc_args.") > ".
                     $localwebinterfacepath.$opened_file.".ofmc.atk";

  $atse_cmd = "(ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                     $remotewebinterfacepath.$bindir.$atse." ".
                     $atse_args.$remotewebinterfacepath.$opened_file.".if) > ".
                     $localwebinterfacepath.$opened_file.".atse.atk";

  $satmc_cmd = "(ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                      $remotewebinterfacepath.$bindir.$satmc." ".
                      $remotewebinterfacepath.$opened_file.".if ".$satmc_args.") > ".
                      $localwebinterfacepath.$opened_file.".satmc.atk";

  $ta4sp_cmd = "(ssh".$ssh_options.$remoteuser."@".$remoteip." ".
                      $remotewebinterfacepath.$bindir.$ta4sp." ".
                      $remotewebinterfacepath.$opened_file.".if ".$ta4sp_args.") > ".
                      $localwebinterfacepath.$opened_file.".ta4sp.atk";

}
elseif ($avispawebsetting==local)
{

  $hlpsl2if_cmd = $bindir.$hlpsl2if." --all ".$opened_file." 2>&1";
  $ofmc_cmd = $bindir.$ofmc." ".$localwebinterfacepath.$opened_file.".if ".$ofmc_args."> ".$localwebinterfacepath.$opened_file.".ofmc.atk";
  $atse_cmd = $bindir.$atse." ".$localwebinterfacepath.$opened_file.".if ".$atse_args."> ".$localwebinterfacepath.$opened_file.".atse.atk";
  $satmc_cmd = $bindir.$satmc." ".$localwebinterfacepath.$opened_file.".if ".$satmc_args." > ".$localwebinterfacepath.$opened_file.".satmc.atk";
  $ta4sp_cmd = $bindir.$ta4sp." ".$localwebinterfacepath.$opened_file.".if ".$ta4sp_args." > ".$localwebinterfacepath.$opened_file.".ta4sp.atk";
}

// Return the summary from a tool output file
function Summary($atk_file)
{

  // Open the output file
  $file = fopen ($atk_file, "r");
  if (!$file) {
    echo "Can not open ".$atk_file." file.\n";
    exit;
  }

  // Search for the SUMMARY keyword inside the file
  $found = false;
  while (!feof($file) && (!$found))
  {
    $line = trim(fgets ($file,1024));
    if ($line == "SUMMARY")
	  $found = true;
  }

  // If SUMMARY was found return the result
  if ($found)
  {
    do
	{
      $line = trim(fgets ($file,1024));
	} while ($line=="");
    return $line;
  } else {
    //echo "Section SUMMARY not found in this file!\n";
	return "Error";
  }
}
?>

<html>
<head>
<title>AVISPA Web Tool</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>

<body bgcolor="#FFFFFF" text="#000000">
<table width="1000" border="0" align="center" cellspacing="0" cellpadding="0">
<tr>
<td valign="top">

  <form name="forminput" method="post" action="basic.php">
     <input type="hidden" name="opened_file" value="<?=$opened_file?>">
     <table width="100%" border="2" align="center" bordercolor="#00008C" cellpadding="3" cellspacing="0" bgcolor="#8E8FC0">

     <!-- Top Bar -->
     <tr>
     <td align="center" valign="middle" colspan=5 bgcolor="#00008C">
	<img src="images/topbar_basic.gif" border="0" usemap="#Map2">
        <map name="Map2">
	 <area shape="rect" coords="554,37,665,68" href="basic.php">
         <area shape="rect" coords="672,37,785,68" href="expert.php">
      </map>
     </td>
     </tr>

     <!-- Output -->
     <tr>
     <td valign="top" align="center" height="230" width="100%" colspan="5">
     <font size="2">
     <img align="left" src="images/output.gif">

     <br><br>

     <textarea name="textoutputarea" rows="<?=$textarea_rows?>" cols="<?=$textarea_cols?>" wrap="off" readonly
     ><?php

     // Call the tools on the workfile

     if ($avispawebsetting == remote)
     {
       // Copy HLPSL file to remote machine 
       if($debug) echo "COPY_HLPSL_REMOTE: ".$copy_hlpsl_remote."\n";
       exec($copy_hlpsl_remote);
            
       // Execute HLPSL2IF remotly
       if($debug) echo "HLPSL2IF: ".$hlpsl2if_cmd."\n";
       $hlpsl2if_output = shell_exec($hlpsl2if_cmd);

       // For some unknown reason the message "Could not create directory '//.ssh'"
       // is been shown at the beginnig of the output. The code below will separate 
       // the first line from the others in the hlpsl_output and the final value for
       // hlpsl_output will not contain the first line   
       $hlpsl2if_array = explode("\n", $hlpsl2if_output, 2);
       $hlpsl2if_output = $hlpsl2if_array[1];
            
       // Copy remote IF file to local machine
       if($debug) echo "COPY_IF_LOCAL: ".$copy_if_local."\n";
       exec($copy_if_local); 
     }
     else
     {
       if ($debug) echo "HLPSL2IF: ".$hlpsl2if_cmd."\n";       
       $hlpsl2if_output = shell_exec($hlpsl2if_cmd);
     }

     $translation_ok = file_exists($opened_file.".if");

     // If the translation was sucessuful call the the analysis tools,
     // otherwise print the error message from the HLPSL2IF
     if ($translation_ok) 
     {
       if ($debug) echo "OFMC: ".$ofmc_cmd."\n";
       exec($ofmc_cmd);
       if ($debug) echo "CL-ATSE: ".$atse_cmd."\n";
       exec($atse_cmd);
       if ($debug) echo "SATMC: ".$satmc_cmd."\n";
       exec($satmc_cmd);
       if ($debug) echo "TA4SP: ".$ta4sp_cmd."\n";
       exec($ta4sp_cmd);

       $ofmc_summary  = Summary($opened_file.".ofmc.atk");
       $atse_summary  = Summary($opened_file.".atse.atk");
       $satmc_summary = Summary($opened_file.".satmc.atk");
       $ta4sp_summary = Summary($opened_file.".ta4sp.atk");

       echo "AVISPA Tool Summary\n\n";
       echo "OFMC    : ".$ofmc_summary."\n";
       echo "CL-AtSe : ".$atse_summary."\n";
       echo "SATMC   : ".$satmc_summary."\n";
       echo "TA4SP   : ".$ta4sp_summary."\n\n";
       echo "Refer to individual tools output for details";

       if($avispawebsetting==remote)
       {
         // Remove remote HLPSL file 
         if($debug) echo "REMOVE_HLPSL_REMOTE: ".$remove_hlpsl_remote."\n";
         exec($remove_hlpsl_remote);

         // Remove remote IF file 
         if($debug) echo "REMOVE_IF_REMOTE: ".$remove_if_remote."\n";
         exec($remove_if_remote);
       }
     } else {
       //Show output of the HLPSL2IF Translator
       echo "AVISPA Web Tool\n";
       echo "===============\n";
       echo "\n";
       echo "The HLPSL2IF translator is reporting the following error:\n\n";
       echo $hlpsl2if_output;

       if($avispawebsetting==remote)
       {
         // Remove remote HLPSL file 
         if($debug) echo "REMOVE_HLPSL_REMOTE: ".$remove_hlpsl_remote."\n";
         exec($remove_hlpsl_remote);
       }
     }
     ?>
     </textarea>
     </font>
     <br><br>
     
     <!-- TextArea Buttons -->
     <a href=# onclick="window.open('showfile.php?file=<? echo $opened_file ?>','view_hlspl','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0'); return(false)"><img src="images/view_hlpsl.gif" border="0"></a>
     
     <?php
        // Deciding if IF View Button should be enabled or disabled 
        if ($translation_ok)
	  echo "<a href=# onclick=\"window.open('showfile.php?file=".$opened_file.".if','view_if','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0');return(false)\"><img src=\"images/view_if.gif\" border=\"0\"></a>\n"; 
	else
	  echo "<img src=\"images/view_if_d.gif\" border=\"0\">";
     ?>
     
    </td>
    </tr>
    
    <!-- Tools Result -->
    <tr>
    <td>
      <table width="100%" border="0" align="center" bordercolor="#00008C" cellpadding="3" cellspacing="0" bgcolor="#8E8FC0">
      <tr>

      <!-- OFMC -->
      <td valign="center" align="center" width="15%">
      <fieldset>
        <legend>
	<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="darkblue">
	<b>OFMC</b>
	</font>
	</legend>
	<br>
	<?php 
	  // Deciding if OFMC Result Button should be enabled or disabled
          if (file_exists($opened_file.".ofmc.atk"))
	    echo "<a href=# onclick=\"window.open('showfile.php?file=".$opened_file.".ofmc.atk&tool=ofmc','ofmc','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0');return(false)\"><img src=\"images/view_result.gif\" border=\"0\"></a>\n";
	  else
	    echo "<img src=\"images/view_result_d.gif\" border=\"0\">\n";
	?>
        <br>
	<?php
	  if ($ofmc_summary=="UNSAFE")
	  {
	    echo "<a href=# onclick=\"window.open('showfile.php?image=".$opened_file.".ofmc.atk&tool=ofmc','ofmc_msc','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_msc.gif\" border=\"0\"></a>\n";
	    echo "<br>\n";
	    echo "<a href=# onclick=\"window.open('showfile.php?ps=".$opened_file.".ofmc.atk&tool=ofmc','ofmc_ps','width=500,height=300,left=262,top=234,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_ps.gif\" border=\"0\"></a>\n";
	  } else {
	    echo "<img src=\"images/view_msc_d.gif\" border=\"0\">\n";
	    echo "<br>\n";
	    echo "<img src=\"images/view_ps_d.gif\" border=\"0\">\n";
	  }
	?>
	<br><br>
        </fieldset>
      </td>

      <!-- CL-AtSe -->
      <td valign="center" align="center" width="15%">
      <fieldset>
        <legend>
	<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="darkblue">
	<b>CL-AtSe</b>
	</font>
	</legend>
	<br>
	<?php 
	  // Deciding if ATSE Result Button should be enabled or disabled
          if (file_exists($opened_file.".atse.atk"))
	    echo "<a href=# onclick=\"window.open('showfile.php?file=".$opened_file.".atse.atk&tool=atse','atse','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0');return(false)\"><img src=\"images/view_result.gif\" border=\"0\"></a>\n";
	  else
	    echo "<img src=\"images/view_result_d.gif\" border=\"0\">\n";
	?>
        <br>
	<?php
	  if ($atse_summary=="UNSAFE")
	  {
	    echo "<a href=# onclick=\"window.open('showfile.php?image=".$opened_file.".atse.atk&tool=atse','atse_msc','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_msc.gif\" border=\"0\"></a>\n";
	    echo "<br>\n";
	    echo "<a href=# onclick=\"window.open('showfile.php?ps=".$opened_file.".atse.atk&tool=atse','atse_ps','width=500,height=300,left=262,top=234,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_ps.gif\" border=\"0\"></a>\n";
	  } else {
	    echo "<img src=\"images/view_msc_d.gif\" border=\"0\">\n";
	    echo "<br>\n";
	    echo "<img src=\"images/view_ps_d.gif\" border=\"0\">\n";
	  }
	?>
	<br><br>
      </fieldset>
      </td>

      <!-- Help Center -->
      <td valign="center" align="center" width="40%" height="192">
      <font face="Verdana, Arial, Helvetica, sans-serif" size="3" color="blue">
      <b>
      <?php
        if ($translation_ok)
	{
          echo "View detailed output or<br>\n";
          echo "Return to file selection<br>\n";
	} else {
	  echo "Go back and correct<br>\n";
	  echo "your specification file<br>\n";
	}
      ?>
      </b>
      </font>
      <?php
        // If there was no compilation error show the File Selection Button
        if ($translation_ok)
	{
	  echo "<br><br>\n";
          echo "<a href=\"basic.php\"><img src=\"images/file_selection.gif\" border=\"0\"></a>\n";
	}
      ?>
      </td>

      <!-- SATMC -->
      <td valign="center" align="center" width="15%">
      <fieldset>
        <legend>
	<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="darkblue">
	<b>SATMC</b>
	</font>
        </legend>
	<br>
	<?php 
	  // Deciding if SATMC Result Button should be enabled or disabled
          if (file_exists($opened_file.".satmc.atk"))
	    echo "<a href=# onclick=\"window.open('showfile.php?file=".$opened_file.".satmc.atk&tool=satmc','satmc','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0');return(false)\"><img src=\"images/view_result.gif\" border=\"0\"></a>\n";
	  else
	    echo "<img src=\"images/view_result_d.gif\" border=\"0\">\n";
	?>
        <br>
        <?php
	  if ($satmc_summary=="UNSAFE")
	  {
            echo "<a href=# onclick=\"window.open('showfile.php?image=".$opened_file.".satmc.atk&tool=satmc','satmc_msc','width=1000,height=600,left=12,top=84,top=100,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_msc.gif\" border=\"0\"></a>\n";
	    echo "<br>\n";
	    echo "<a href=# onclick=\"window.open('showfile.php?ps=".$opened_file.".satmc.atk&tool=satmc','satmc_ps','width=500,height=300,left=262,top=234,top=100,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_ps.gif\" border=\"0\"></a>\n";
	  } else {
	    echo "<img src=\"images/view_msc_d.gif\" border=\"0\">\n";
	    echo "<br>\n";
	    echo "<img src=\"images/view_ps_d.gif\" border=\"0\">\n";
	  }
	?>
	<br><br>
        </fieldset>
      </td>

      <!-- TA4SP -->
      <td valign="center" align="center" width="15%">
      <fieldset>
        <legend>
	<font face="Verdana, Arial, Helvetica, sans-serif" size="2" color="darkblue">
	<b>TA4SP</b>
	</font>
	</legend>
	<br>
	<?php 
	  // Deciding if TA4SP Result Button should be enabled or disabled
          if (file_exists($opened_file.".ta4sp.atk"))
	    echo "<a href=# onclick=\"window.open('showfile.php?file=".$opened_file.".ta4sp.atk&tool=ta4sp','ta4sp','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=0,resizable=0');return(false)\"><img src=\"images/view_result.gif\" border=\"0\"></a>\n";
	  else
	    echo "<img src=\"images/view_result_d.gif\" border=\"0\">\n";
	?>
        <br>
	<?php
	  if ($ta4sp_summary=="UNSAFE")
	  {
	    echo "<a href=# onclick=\"window.open('showfile.php?image=".$opened_file.".ta4sp.atk&tool=ta4sp','ta4sp_msc','width=1000,height=600,left=12,top=84,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_msc.gif\" border=\"0\"></a>\n";
	    echo "<br>\n";
	    echo "<a href=# onclick=\"window.open('showfile.php?ps=".$opened_file.".ta4sp.atk&tool=ta4sp','ta4sp_ps','width=500,height=300,left=262,top=234,menubar=0,status=0,location=0,toolbar=0,scrollbars=1,resizable=0');return(false)\"><img src=\"images/view_ps.gif\" border=\"0\"></a>\n";
	  } else {
	    echo "<img src=\"images/view_msc_d.gif\" border=\"0\">\n";
	    echo "<br>\n";
	    echo "<img src=\"images/view_ps_d.gif\" border=\"0\">\n";
	  }
	?>
	<br><br>
      </fieldset>
      </td>

      </tr>
      </table>
    </td>
    </tr>

    <!-- Bottom Bar -->
    <tr>
    <td align="center" valign="top" colspan=5 bgcolor="#00008C">
    <img src="images/bottombar.gif">
    </td>
    </tr>
    </table>
  </form>

</td>
</tr>
</table>

</body>
</html>

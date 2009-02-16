<?
#==============================================================================
# pvptools configuration file                  (c) 2007-2009 by Itzchak Rehberg
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

error_reporting(E_ERROR); # disable all but errors to be reported

#==========================================================[ Configuration ]===
#---------------------------------[ setup your phpVideoPro installation(s) ]---
$instance["demo"]->dir = '/var/www/phpvideo'; // path to the base directory
$instance["demo"]->adminID = 1;               // ID of the 'admin' user
// If you have only one instance, you may not want to name it on each script
// call - so you can define it here. If you have multiple installations, comment
// it out to be able to use the scripts on all of them - or set it to the one
// you want the scripts to work on
$myinstance = "demo";

#-------------------------------[ Settings for the IMDB ID updater scripts ]---
$instance["demo"]->imdbid  = TRUE; // scan for imdb ids?
$instance["demo"]->rating  = TRUE; // update ratings?

$stopafter   = 200; // process no more than 200 entries at once
$ignore_cat  = "cat_videoclip"; // list of cat_id to ignore - like VideoClips, comma-separated
$skip_id     = array();
$skip_to     = 1; // start at the beginning or other given index
$stop_at     = 100;

// Update Movie ID in DB, when...
$write_yt     = TRUE; // 100% match on title and year
$write_aka_yt = TRUE; // 100% match on title and year for AKA
$write_yf     = FALSE; // 100% match on year, take 1st entry
$write_yd     = FALSE; // 100% match on year and director

// IMDB name ID updater:
$report_nochange = TRUE;
$compare_mode = "equal"; // how to compare: equal|binary|binary_i|soundex|metaphone

// Update Name ID in DB, when...
$update_name  = FALSE; // same name, different IMDB ID - this COULD be a different person, but also an alias

#-------------------------------------------------[ Backup Script specials ]---
$instance["demo"]->cleandb = TRUE; // delete existing movies before restore?

#=========================================================[ Initialization ]===
// Do not change anything below this line - here starts the code :)
$script = array_shift($argv);
if (empty($myinstance)) {
  $inst   = array_shift($argv);
  $pinst  = "<instance> ";
} else {
  $inst   = $myinstance;
  $pinst  = "";
}

#-----------------------------------------------[ check specified instance ]---
 if (empty($instance[$inst]->dir)) { // no such installation
   die("Sorry - but we have no instance with the name '".$argv[1]."'.\n");
 } else {
   $cleaninst = $instance[$inst];
 }
 if (file_exists($cleaninst->dir)) chdir($cleaninst->dir);
 else die ("Sorry - but the given directory does not exist: '".$cleaninst->dir."'.\n");

#---------------------------------------------------------[ initialize API ]---
 $pvp->auth->user_id = $cleaninst->adminID;
 $pvpinstall = 1;
 if (file_exists("inc/includes.inc")) {
   include ("inc/config.inc");
   include ("inc/config_internal.inc");
   include ("inc/class.preferences.inc");
   include ("inc/class.common.inc");
   $pvp->preferences = new preferences;
   $pvp->common = new common;
 } else {
   die ("Sorry - could not find this installations API files.\n");
 }

 function lang($str,$m1="",$m2="",$m3="") {
   return $str;
 }
 function debug ($level,$msg) {
   return TRUE;
 }

?>
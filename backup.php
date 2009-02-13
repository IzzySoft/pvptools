#!/usr/bin/php
<?
#==============================================================================
# phpVideoPro Backup CLI                            (c) 2009 by Itzchak Rehberg
# Pass the name of the installation as first argument
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

error_reporting(E_ERROR); # disable all but errors to be reported

#==========================================================[ Configuration ]===
 $instance["demo"]->dir = '/var/www/phpvideo';
 $instance["demo"]->adminID = 1;
 $instance["demo"]->cleandb = TRUE;

#=========================================================[ Initialization ]===
function syntax() {
 echo "Syntax:\n  backup.php <instance> backup <type>\n"
    . "or:\n  backup.php <instance> restore <file>\n"
    . "where:\n  <instance> is a phpVideoPro installation configured in the script\n"
    . " <file> is the backup file to restore from'\n"
    . " <type> is either 'movie', 'sysconf' or 'cats'\n\n";
 exit;
}

#----------------------------------------------------[ evaluate parameters ]---
 if (empty($argv[3])) {
   syntax();
 } else {
   $modus = $argv[2];
   $type  = $argv[3];
 }
#-----------------------------------------------[ check specified instance ]---
 if (empty($instance[$argv[1]]->dir)) { // no such installation
   die("Sorry - but we have no instance with the name '".$argv[1]."'.\n");
 } else {
   $cleaninst = $instance[$argv[1]];
   // echo "Updating PVP Installation '".$argv[1]."':\n";
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
   include ("inc/class.xfer.inc");
   $pvp->preferences = new preferences;
 } else {
   die ("Sorry - could not find this installations API files.\n");
 }

 function lang($str,$m1="",$m2="",$m3="") {
   return $str;
 }
 function debug ($level,$msg) {
   return TRUE;
 }

#==========================================================[ Check Process ]===
$xfer = new xfer("export");
$xfer->compressionOn();
if ($modus=="backup") {
#-----------------------------------------------------------------[ Backup ]---
# movie, sysconf, cats
  // $stamp = date('ymd'); // to generate a unique filename
  switch ($type) {
    case "movie" :
      $xfer->fileExport("Movie");
      exit;
      break;
    case "sysconf"  : $xfer->fileExport("SysConf"); exit; break;
    case "cats"     : $xfer->fileExport("Cats"); exit; break;
    default         : syntax(); break;
  }


} elseif ($modus=="restore") {
#----------------------------------------------------------------[ Restore ]---
  if (empty($from_owner)) $from_owner = "";
  if (empty($to_owner))   $to_owner = "";
  $cleandb = TRUE;
  $save_result = $xfer->fileImport($type,$pvp->backup_dir,!$cleaninst->cleandb,$from_owner,$to_owner);


} else {
#---------------------------------------------------------------[ Invalid! ]---
  syntax();
}

#---------------------------------------------------------------[ All Done ]---
 // echo "Done - updated $upd_id IMDB IDs and $upd_rat ratings in your database.\n";
?>
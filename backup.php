#!/usr/bin/php
<?
#==============================================================================
# phpVideoPro Backup CLI                            (c) 2009 by Itzchak Rehberg
# Pass the name of the installation as first argument
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

#=========================================================[ Initialization ]===
require_once("pvptools.config.php");

function syntax() {
 echo "Syntax:\n  backup.php $pinst backup <type>\n"
    . "or:\n  backup.php $pinst restore <file>\n"
    . "where:\n";
 if (!empty($pinst)) echo "  $pinst is a phpVideoPro installation configured in the script\n";
 echo " <file> is the backup file to restore from'\n"
    . " <type> is either 'movie', 'sysconf' or 'cats'\n\n";
 exit;
}

#----------------------------------------------------[ evaluate parameters ]---
$modus  = array_shift($argv);
$type   = array_shift($argv);
if (empty($type)) syntax();

#---------------------------------------------------------[ initialize API ]---
include ("inc/class.xfer.inc");

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
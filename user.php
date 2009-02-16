#!/usr/bin/php
<?
#==============================================================================
# phpVideoPro User Management CLI                   (c) 2009 by Itzchak Rehberg
# Pass the name of the installation as first argument
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

require_once(dirname(__FILE__)."/pvptools.config.php");

#=========================================================[ Initialization ]===
function syntax() {
 GLOBAL $script;
 echo "Syntax:\n  $script $pinst add <login> <password> <permissions>\n"
    . "  $script $pinst remove <login> [cascade|keep]\n"
    . "  $script $pinst perms <login> <permissions>\n"
    . "  $script $pinst pass <login> <password>\n"
    . "  $script $pinst show <login>\n"
    . "  $script $pinst list\n"
    . "where\n";
 if (!empty($pinst)) echo "  <instance> is a phpVideoPro installation configured in the script\n";
 echo "  <login> is the login of the user to process\n"
    . "  <password> the password for the user\n"
    . "  <permissions> are the permissions the specified user should have\n\n";
 exit;
}

#----------------------------------------------------[ evaluate parameters ]---
 $modus  = array_shift($argv);
 if (empty($modus)) syntax();

#==========================================================[ Check Process ]===
switch ($modus) {
  case "remove" :
#--------------------------------------------------[ remove specified user ]---
    $login   = array_shift($argv);
    $objects = array_shift($argv);
    if (empty($login)) syntax();
    $users = $db->get_users();
    $uc = count($users);
    for ($i=0;$i<$uc;++$i) {
      if (strtolower($login)==strtolower($users[$i]->login)) {
        switch(strtolower($objects)) {
          case "cascade": $db->user_media_delete($users[$i]->id); break;
          case "keep"   : $db->user_media_xfer($users[$i]->id,$cleaninst->adminID); break;
          default       : break;
        }
        $rc = $db->del_user($users[$i]->id);
        if ($rc) die("User '$login' deleted.\n\n");
        else {
          echo "Failed to delete user '$login' - probably there are still movies attached?\n"
             . "Try with the additional parameter 'cascade' if you want to drop those as well.\n"
             . "Try with the additional parameter 'keep' to have them owned by the admin.\n\n";
        }
        break(2);
      }
    }
    echo "Sorry - could not find a user with the login '$login'.\n";
    break;
  case "add"    :
#---------------------------------------------------------[ add a new user ]---
    $login = array_shift($argv);
    $passw = array_shift($argv);
    $perms = array_shift($argv);
    if (empty($perms)) {
      echo "\nYou must specify permissions. To remove all, use '.'.\n"
         . "Otherwise, the permission string consists of the uppercase letters for the\n"
         . "permissions to set: Admin, Browse, aDd, Update, deLete. Permissions not\n"
         . "mentioned will be set to FALSE.\n\n";
         exit;
    } elseif (empty($passw)) {
      die("You must specify a password.\n\n");
    } elseif (empty($login)) syntax();
    $user->login = $login;
    $user->pwd   = md5($passw);
    if (strpos($perms,'a')!==FALSE) $user->admin  = 1; else $user->admin  = 0;
    if (strpos($perms,'b')!==FALSE) $user->browse = 1; else $user->browse = 0;
    if (strpos($perms,'d')!==FALSE) $user->add    = 1; else $user->add    = 0;
    if (strpos($perms,'u')!==FALSE) $user->upd    = 1; else $user->upd    = 0;
    if (strpos($perms,'l')!==FALSE) $user->del    = 1; else $user->del    = 0;
    $rc = $db->add_user($user);
    if ($rc) die("User '$login' created.\n\n");
    else die ("Failed to create user '$login'!\n\n");
    break;
  case "perms"  :
#----------------------------------------[ change perms for specified user ]---
    $login = array_shift($argv);
    $perms = array_shift($argv);
    if (empty($perms)) {
      echo "\nYou must specify permissions. To remove all, use '.'.\n"
         . "Otherwise, the permission string consists of the uppercase letters for the\n"
         . "permissions to set: Admin, Browse, aDd, Update, deLete. Permissions not\n"
         . "mentioned will be set to FALSE.\n\n";
         exit;
    }
    $users = $db->get_users();
    $uc = count($users);
    for ($i=0;$i<$uc;++$i) {
      if (strtolower($login)==strtolower($users[$i]->login)) {
        $perms = strtolower($perms);
        if (strpos($perms,'a')!==FALSE) $users[$i]->admin  = 1; else $users[$i]->admin  = 0;
        if (strpos($perms,'b')!==FALSE) $users[$i]->browse = 1; else $users[$i]->browse = 0;
        if (strpos($perms,'d')!==FALSE) $users[$i]->add    = 1; else $users[$i]->add    = 0;
        if (strpos($perms,'u')!==FALSE) $users[$i]->upd    = 1; else $users[$i]->upd    = 0;
        if (strpos($perms,'l')!==FALSE) $users[$i]->del    = 1; else $users[$i]->del    = 0;
        $rc = $db->set_user($users[$i]);
        if ($rc) die("User successfully updated.\n\n");
        else die("User update failed, sorry.\n\n");
        break(2);
      }
    }
    echo "Sorry - could not find a user with the login '$login'.\n";
    break;
  case "pass"   :
#---------------------------------------[ change passwd for specified user ]---
    $login = array_shift($argv);
    $passw = array_shift($argv);
    if (empty($passw)) die("You must specify a password.\n\n");
    $users = $db->get_users();
    $uc = count($users);
    for ($i=0;$i<$uc;++$i) {
      if (strtolower($login)==strtolower($users[$i]->login)) {
        $users[$i]->pwd = md5($passw);
        $rc = $db->set_user($users[$i]);
        if ($rc) die("User successfully updated.\n\n");
        else die("User update failed, sorry.\n\n");
        break(2);
      }
    }
    echo "Sorry - could not find a user with the login '$login'.\n";
    break;
  case "show"   :
#----------------------------------------[ show details for specified user ]---
    $login = array_shift($argv);
    $users = $db->get_users();
    $uc = count($users);
    for ($i=0;$i<$uc;++$i) {
      if (strtolower($login)==strtolower($users[$i]->login)) {
        printf('%12s',"Login"); echo ": ".$users[$i]->login."\n";
        printf('%12s',"Comment"); echo ": ".$users[$i]->comment."\n";
        $perms = "";
        if ($users[$i]->admin)  $perms .= ",Admin";
        if ($users[$i]->browse) $perms .= ",Browse";
        if ($users[$i]->add)    $perms .= ",aDd";
        if ($users[$i]->upd)    $perms .= ",Update";
        if ($users[$i]->del)    $perms .= ",deLete";
        if (strlen($perms)>0) $perms = substr($perms,1);
        printf('%12s',"Permissions"); echo ": $perms\n";
        break(2);
      }
    }
    echo "Sorry - could not find a user with the login '$login'.\n";
    break;
  case "list"   :
#-------------------------------------------------------------[ list users ]---
    $users = $db->get_users();
    $uc = count($users);
    echo "Configured users:\n";
    for ($i=0;$i<$uc;++$i) {
      echo " - " . $users[$i]->login . "\n";
    }
    break;
#----------------------------------------------------------[ invalid modus ]---
  default       : echo "\nSorry, but '$modus' is not a valid option.\n\n"; syntax(); break;
}

#---------------------------------------------------------------[ All Done ]---
?>
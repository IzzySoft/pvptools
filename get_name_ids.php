#!/usr/bin/php
<?
#==============================================================================
# phpVideoPro IMDB Updater for staff IDs            (c) 2009 by Itzchak Rehberg
# Pass the name of the installation as first argument
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

#=========================================================[ Initialization ]===
require_once("pvptools.config.php");
$incpath = explode(":", ini_get('include_path'));
$IMDBfound = FALSE;
foreach($incpath as $trypath) {
  if (file_exists("$trypath/imdb.class.php")) {
    echo "- Including IMDB class\n";
    if (file_exists("$trypath/imdb_person.class.php")) {
      include_once("$trypath/imdb.class.php");
      include_once("$trypath/imdb_person.class.php");
      $IMDBfound = TRUE;
    }
    break;
  }
}
if (!$IMDBfound) die("IMDB classes not found, aborting.\n");

$imdbper = new imdb_person('0000007');
$imdbmov = new imdb('0119177');
$imdbmov->imdbsite = "akas.imdb.com";

#==========================================================[ Check Process ]===
$upd_id = 0; // counter

#-------------------------------------------------[ Helper: String Compare ]---
function samestr($str1,$str2) {
  if (in_array($GLOBALS["compare_mode"],array("soundex","metaphone"))) {
    $str1 = str_replace("ß","ss",$str1);
    $str2 = str_replace("ß","ss",$str2);
  }
  switch($GLOBALS["compare_mode"]) {
    case "binary"    : return (strcmp(trim($str1),trim($str2))==0); // case sensitive identical should consider locale - but does not ("ß"!="ss", "e"!="é")
    case "binary_i"  : return (strcasecmp(trim($str1),trim($str2))==0); // case insensitive identical - does not work ("ß"!="ss", "e"!="é")
    case "metaphone" : return (metaphone($str1)==metaphone($str2)); // improved soundex compare (more identical, see example below) - "e"="é", but "ss"!="ß"
    case "soundex"   : return (soundex($str1)==soundex($str2)); // like metaphone, but faster -- but "Hilbert"="Heilbronn" (urgs)
    case "equal"     : 
    default          : return ($str1==$str2);          // absolute identical
  }
}

#-----------------------------------------------------[ Helper: Update IDs ]---
function imdbCheck(&$iactors,&$actor,$role,$table) {
  GLOBAL $upd_id, $pfound, $db, $movie;
  foreach($iactors as $iactor) { // walk the IMDB data set to find matches
    $pfound = FALSE;
    if (empty($actor['imdb_id'])) {
      if (samestr($iactor['name'],$actor['fullname'])) { // got her/him obviously
        $db->query("UPDATE pvp_${table} SET imdb_id='".$iactor['imdb']."' WHERE id=".$actor['id']);
        echo "  + Updated $role '".$actor['fullname']."' (ID ".$actor['id'].") with IMDB ID '".$iactor['imdb']."'\n";
        ++$upd_id;
        $pfound = TRUE;
        break;
      }
    } else {
      if (samestr($iactor['imdb'],$actor['imdb_id'])) { // already OK
        if ($report_nochange) echo "  - $role '".$actor['fullname']."' is already stored with the correct ID (".$iactor['imdb'].")\n";
        $pfound = TRUE;
        break;
      }
      if (samestr($iactor['name'],$actor['fullname'])) { // same name, different id - shit happens!
        $pfound = TRUE;
        if (!$update_name) break;
        echo "  ! $role '".$actor['fullname']."' is known at IMDB by ID '".$iactor['imdb']."', but we have '".$actor['imdb_id'].":'\n";
        // here we have to create a new DB entry for the given person, and update the movie record accordingly:
        $aid = $db->insert_person($role,"SELECT id FROM pvp_${table}","INSERT INTO pvp_${table}",$actor['name'],$actor['firstname'],$iactor['imdb']);
        if ($aid) {
          echo "   - Created new record with ID ${aid}\n";
          if ($role=="actor") {
            $db->query("SELECT actor1_id,actor2_id,actor3_id,actor4_id,actor5_id FROM pvp_video WHERE id=".$movie['id']);
            $db->next_record();
            for ($i=1;$i<6;++$i) {
              $xid = $db->f("actor${i}_id");
              if ($xid==$actor['id']) {
                $db->query("UPDATE pvp_video SET actor${i}_id=$aid WHERE id=".$movie['id']);
                echo "   + Updated record of movie id '".$movie['id']."' for actor $i (".$actor['id'].") to actor ID '$aid'\n";
                break;
              }
            }
          } else {
            $db->query("UPDATE pvp_video SET ${role}_id=$aid WHERE id=".$movie['id']);
            echo "   + Updated record of movie id '".$movie['id']."' for $role (".$actor['id'].") to $role ID '$aid'\n";
          }
        }
      }
    }
  }
}

#------------------------------------------------[ Gather the movies first ]---
 if (!empty($ignore_cat)) {
   $skip_cat = "";
   $db->query("SELECT id FROM pvp_cat WHERE name IN ('$ignore_cat')");
   while ($db->next_record()) {
     $skip_cat .= ",".$db->f('id');
   }
 }
 // we need the movies imdb id to verify that we get the right person
 $query = "SELECT id FROM pvp_video WHERE imdb_id IS NOT NULL";
 if (isset($skip_cat)) $query .= " AND cat1_id NOT IN ('".substr($skip_cat,1)."')";
 $db->query($query);
 while ($db->next_record()) {
   $mid[] = $db->f('id');
 }
 $midc = count($mid);
#-----------------------------------------------[ Go for the staff members ]---
 if ($midc) {
   echo "- Try to update staff members for $midc movies...\n";
   for ($i=$skip_to;$i<$midc;++$i) { // walk the movie array
     if ($i-$skip_to>$stopafter) break;
     if ($i==$stop_at) break;
     if (in_array($i,$skip_id)) continue;
     $movie = $db->get_movie($mid[$i]);
     echo " - [$i] Processing movie ID '".$mid[$i]."' (".$movie["title"].")\n";
     $imdbmov->setid($movie["imdb_id"]);
     $iactors = $imdbmov->cast(); $iac = count($iactors);
     if ($iac==0) { // not found in IMDB - ooops? - or no actors found there
       echo "  ! Sorry, no actors recorded for this movie at the IMDB site\n";
       continue;
     }
     for ($k=1;$k<6;++$k) { // actors
       $idn = "actor${k}_id";
       $actor = $db->get_actor($movie[$idn]);
       if (empty($actor['fullname'])) continue; // not set
       imdbCheck($iactors,$actor,"actor","actors");
       if (!$pfound) echo "  ! actor '".$actor['fullname']."' (ID ".$actor['id'].") was not found in connection with this movie at IMDB.com - not updated.\n";
     } // end internal actor walk (for $k)
     if (!empty($movie['director_id'])) { // director
       $actor = $db->get_director($movie['director_id']);
       $iactors = $imdbmov->director();
       imdbCheck($iactors,$actor,"director","directors");
       if (!$pfound) echo "  ! director '".$actor['fullname']."' (ID ".$actor['id'].") was not found in connection with this movie at IMDB.com - not updated.\n";
     }
     if (!empty($movie['music_id'])) { // music
       $actor = $db->get_music($movie['music_id']);
       $iactors = $imdbmov->composer();
       imdbCheck($iactors,$actor,"componist","music");
       if (!$pfound) echo "  ! componist '".$actor['fullname']."' (ID ".$actor['id'].") was not found in connection with this movie at IMDB.com - not updated.\n";
     }
   } // end movie array walk (for $i)
 } else {
   echo "- No IMDB IDs to update.\n";
 }

#---------------------------------------------------------------[ All Done ]---
 echo "Done - updated $upd_id IMDB IDs of crew members in your database.\n";
?>
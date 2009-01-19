#!/usr/bin/php
<?
#==============================================================================
# phpVideoPro IMDB Updater                     (c) 2007-2009 by Itzchak Rehberg
# Pass the name of the installation as first argument
# -----------------------------------------------------------------------------
# $Id$
#==============================================================================

error_reporting(E_ERROR); # disable all but errors to be reported

#==========================================================[ Configuration ]===
 $instance["demo"]->dir = '/var/www/phpvideo';
 $instance["demo"]->adminID = 1;
 $instance["demo"]->imdbid  = TRUE; // scan for imdb ids?
 $instance["demo"]->rating  = TRUE; // update ratings?

 $stopafter   = 200; // process no more than 200 entries at once
 $ignore_cat  = "cat_videoclip"; // list of cat_id to ignore - like VideoClips, comma-separated
 $skip_id     = array();
 $skip_to     = 1; // start at the beginning or other given index
 $stop_at     = 100;

 # DB, when...
 $write_yt     = TRUE; // 100% match on title and year
 $write_aka_yt = TRUE; // 100% match on title and year for AKA
 $write_yf     = FALSE; // 100% match on year, take 1st entry
 $write_yd     = FALSE; // 100% match on year and director

#=========================================================[ Initialization ]===
 if (empty($instance[$argv[1]]->dir)) { // no such installation
   die("Sorry - but we have no instance with the name '".$argv[1]."'.\n");
 } else {
   $cleaninst = $instance[$argv[1]];
   echo "Updating PVP Installation '".$argv[1]."':\n";
 }
 if (file_exists($cleaninst->dir)) chdir($cleaninst->dir);
 else die ("Sorry - but the given directory does not exist: '".$cleaninst->dir."'.\n");
 $pvp->auth->user_id = $cleaninst->adminID;
 $pvpinstall = 1;
 if (file_exists("inc/includes.inc")) {
   include ("inc/config.inc");
   include ("inc/config_internal.inc");
   include ("inc/class.preferences.inc");
   $pvp->preferences = new preferences;
   $incpath = explode(":", ini_get('include_path'));
   $IMDBfound = FALSE;
   foreach($incpath as $trypath) {
     if (file_exists("$trypath/imdb.class.php")) {
       echo "Including IMDB class\n";
       include_once("$trypath/imdb.class.php");
       $IMDBfound = TRUE;
       break;
     }
   }
   if (!$IMDBfound) die("IMDB classes not found, aborting.\n");
 } else {
   die ("Sorry - could not find this installations API files.\n");
 }

 function lang($str,$m1="",$m2="",$m3="") {
   return $str;
 }
 function debug ($level,$msg) {
   return TRUE;
 }

 $search = new imdbsearch();
 $imdbmov = new imdb('0119177');
 $imdbmov->imdbsite = "uk.imdb.com";

#==========================================================[ Check Process ]===
#---------------------------------------------------------[ Update IMDB ID ]---
 $upd_id = 0;
 if ($cleaninst->imdbid) {
   $db->query("SELECT id FROM pvp_cat WHERE name IN ('$ignore_cat')");
   while ($db->next_record()) {
     $skip_cat .= ",".$db->f('id');
   }
   $query = "SELECT id FROM pvp_video WHERE imdb_id IS NULL";
   if ($skip_cat) $query .= " AND cat1_id NOT IN ('".substr($skip_cat,1)."')";
   $db->query($query);
   while ($db->next_record()) {
     $mid[] = $db->f('id');
   }
   $midc = count($mid);
   if ($midc) {
     echo "- Try to update IMDB ID for $midc movies...\n";
     for ($i=$skip_to;$i<$midc;++$i) {
       if ($i-$skip_to>$stopafter) break;
       if ($i==$stop_at) break;
       if (in_array($i,$skip_id)) continue;
       $movie = $db->get_movie($mid[$i]);
       $search->setsearchname($movie["title"]);
       $results = $search->results();
       foreach ($results as $res) {
         if ($movie["year"]==$res->year()) {
           if ($movie["title"]==$res->title()) {
             echo "* [$i] MATCH for '".$movie["title"]."' (".$movie["year"]."): ".$res->title()." (".$res->year().")\n";
             if ($write_yt) {
               $db->query("UPDATE pvp_video SET imdb_id='".$res->imdbid()."' WHERE id=".$mid[$i]);
               ++$upd_id;
             }
             break;
           } else { // check AKAs
             $imdbid = $res->imdbid();
             $imdbmov->setid($imdbid);
             $akas   = $imdbmov->alsoknow();
             $akac   = count($akas);
             echo "* [$i] Possible match for '".$movie["title"]."' (".$movie["year"]."): ".$res->title()." (".$res->year()."), IMDB ID '".$res->imdbid()."' ($akac AKAs)\n";
             if (is_array($akas)) foreach ( $akas as $ak) {
               if ($ak["title"]==$movie["title"]) {
                 echo "* [$i] MATCH with AKA: ".$ak["title"]." (".$ak["year"].")\n";
                 if ($write_aka_yt) {
                   $db->query("UPDATE pvp_video SET imdb_id='$imdbid' WHERE id=".$mid[$i]);
                   ++$upd_id;
                   continue 3;
                 }
               }
             }
             if ($write_yf) {
               $db->query("UPDATE pvp_video SET imdb_id='".$res->imdbid()."' WHERE id=".$mid[$i]);
               ++$upd_id;
               continue 2;
             }
             // check for director
             unset($imdbmov); // re-initialize - otherwise breaks after the first check whyever
             $imdbmov = new imdb($imdbid);
             $imdbmov->imdbsite = "uk.imdb.com";
             $dir = $imdbmov->director(); // check for director
             for ($k=0;$k<count($dir);++$k) {
               echo "~ [$i] DirectorCheck: '".$dir[$k]["name"]."'/'".$movie["director"]."'\n";
               if ($movie["director"]==$dir[$k]["name"]) {
                 echo "* [$i] MATCH with director (".$dir[$k]["name"]."): '".$movie["title"]."' (".$movie["year"]."): ".$res->title()." (".$res->year()."), IMDB ID '".$res->imdbid()."'\n";
                 if ($write_yd) {
                   $db->query("UPDATE pvp_video SET imdb_id='".$res->imdbid()."' WHERE id=".$mid[$i]);
                   ++$upd_id;
                 }
                 continue 3;
               }
             }
           }
         }
       }
     }
   } else {
     echo "- No IMDB IDs to update.\n";
   }
 }

#----------------------------------------------------[ Update movie rating ]---
 $upd_rat = 0;
 unset($mid); $midc = 0;
 if ($cleaninst->rating) {
   $db->query("SELECT id,imdb_id FROM pvp_video WHERE rating IS NULL AND imdb_id IS NOT NULL AND imdb_id!=''");
   while ($db->next_record()) {
     $m->id = $db->f('id');
     $m->imdbid = $db->f('imdb_id');
     $mid[] = $m;
   }
   $midc = count($mid);
   if ($midc) {
     echo "- Try to update rating for $midc movies...\n";
     for ($i=0;$i<$midc;++$i) {
       if ($i>$stopafter) break;
       $imdbmov->setid($mid[$i]->imdbid);
       $rating = $imdbmov->rating();
       if (!empty($rating)) {
         echo "* [$i] Rating for '".$imdbmov->title()."' (".$mid[$i]->imdbid.") is ".$rating." - Updating DB.\n";
         $db->query("UPDATE pvp_video SET rating='$rating' WHERE id=".$mid[$i]->id);
         ++$upd_rat;
       }
     }
   } else {
     echo "- No ratings to update.\n";
   }
 }

#---------------------------------------------------------------[ All Done ]---
 echo "Done - updated $upd_id IMDB IDs and $upd_rat ratings in your database.\n";
?>
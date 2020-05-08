#!/usr/bin/php
<?php
#==============================================================================
# phpVideoPro IMDB Updater                     (c) 2007-2020 by Itzchak Rehberg
# Pass the name of the installation as first argument unless explicitly set
# in pvptools.config.php
#==============================================================================

#=========================================================[ Initialization ]===
require_once(dirname(__FILE__)."/pvptools.config.php");

if ( !empty($imdb_api_path) && file_exists("${imdb_api_path}/bootstrap.php") ) {
  require_once("${imdb_api_path}/bootstrap.php");
} else {
  die("IMDB classes not found, aborting.\n");
}

$imdb_lang = $pvp->preferences->get("imdb_lang");
$iconfig = new \Imdb\Config();
if ( !empty($imdb_lang) ) $iconfig->language = $imdb_lang;
$search = new \Imdb\TitleSearch($iconfig);
$imdbmov = new \Imdb\Title('0119177',$iconfig);

if ( !property_exists($pvp,'auth') ) {
  $pvp->auth = new stdClass();
}
$pvp->auth->user_id = $instance[$inst]->adminID;

#==========================================================[ Check Process ]===
echo "Updating PVP Installation '$inst':\n";
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
       $results = $search->search($movie["title"]);
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
             unset($imdbmov);
             $imdbmov = new \Imdb\Title($imdbid,$iconfig);
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
             $imdbmov = new \Imdb\Title($imdbid,$iconfig);
             $dir = $imdbmov->director(); // check for director
             for ($k=0;$k<count($dir);++$k) {
               echo "~ [$i] DirectorCheck: '".$dir[$k]["name"]."'/'".$movie["director"]."'\n";
               if ($movie["director"]==$dir[$k]["name"]) {
                 echo "* [$i] MATCH with year and director (".$dir[$k]["name"]."): '".$movie["title"]."' (".$movie["year"]."): ".$res->title()." (".$res->year()."), IMDB ID '".$res->imdbid()."'\n";
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
     $m = new stdClass();
     $m->id = $db->f('id');
     $m->imdbid = $db->f('imdb_id');
     $mid[] = $m;
   }
   $midc = count($mid);
   if ($midc) {
     echo "- Try to update rating for $midc movies...\n";
     for ($i=0;$i<$midc;++$i) {
       if ($i>$stopafter) break;
       unset($imdbmov);
       $imdbmov = new \Imdb\Title($mid[$i]->imdbid,$iconfig);
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
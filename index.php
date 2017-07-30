<!DOCTYPE HTML>
<?php

$configpath = 'config/config.php';
$debugpath = 'ignore/config.php';
if (file_exists($configpath)){
  include_once($configpath);
}
else {
  if (file_exists($debugpath)){
    include_once($debugpath);
  }
  else {
    echo '<br><center>There was an error reading from the configuration file.<br>Did you rename config.php.dist to config.php and make the necessary changes?</center>';
    exit();
  }
}

$map = $_GET["map"];
$cache = $_GET["cache"]; //added to version control for cache busting static files for debugging (js/css/images/etc)
if (!$cache){$cache="";}

if (!$map){
  $map = "Azeroth";
}

$map_json = $map;
$json = 'json/maps.json';
if (file_exists($json)){
  $json = file_get_contents($json);
  $json = json_decode($json, true);
  foreach ($json["map"] as $name => $cont)
  {
    if ($cont["parent"]){
      $cont["name"] = $cont["parent"];
    }
    if ($map == $cont["name"]){
      $map_x_size = $cont["x_size"];
      $map_y_size = $cont["y_size"];
      $map_x_pos = $cont["x_pos"];
      $map_y_pos = $cont["y_pos"];
      $map_image = '<div class="map" id="'.strtolower($cont["name"]).'">';
    }
  }
  $cont = $json["map"];
}

$cachebust = $version->hash . $cache;
?>

<head>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha256-hwg4gsxgFZhOsEEamdOYGBf13FyQuiTwlAQgxVSNgt4=" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script type="text/javascript" src="javascripts/jquery-mousewheel-3.1.13/jquery.mousewheel.min.js"></script>
<script type="text/javascript" src="javascripts/jquery-mousewheel-3.1.13/jquery.mousewheel.min.js"></script>
<script type="text/javascript" src="javascripts/playermapper.min.js?v=<?php echo $cachebust; ?>"></script>
<link rel="stylesheet" type='text/css' href="css/playermapper.min.css?v=<?php echo $cachebust; ?>">
<link rel="stylesheet" type='text/css' href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Muli:200">
<?php echo '<script>function mapResetPos(){ $(".map").css({"top" : "'.$map_y_pos.'px", "left" : "'.$map_x_pos.'px"});}</script>';
?>
<style>
body{background:url("images/swatch_<?php echo strtolower($map); ?>.jpg"); color:white; font-family:Muli; overflow:hidden;}
#northrend{top:<?php echo $map_y_pos; ?>px; left:<?php echo $map_x_pos; ?>px; width:<?php echo $map_x_size; ?>px; height:<?php echo $map_y_size; ?>px; position:absolute; background:url("images/<?php echo $config->expansion; ?>/northrend.jpg?v=<?php echo $cachebust; ?>") no-repeat; background-position:0px 0px; background-size:100% 100%;}
#azeroth{top:<?php echo $map_y_pos; ?>px; left:<?php echo $map_x_pos; ?>px; width:<?php echo $map_x_size; ?>px; height:<?php echo $map_y_size; ?>px; position:absolute; background:url("images/<?php echo $config->expansion; ?>/azeroth.jpg?v=<?php echo $cachebust; ?>") no-repeat; background-position:0px 0px; background-size:100% 100%;}
#outland{top:<?php echo $map_y_pos; ?>px; left:<?php echo $map_x_pos; ?>px; width:<?php echo $map_x_size; ?>px; height:<?php echo $map_y_size; ?>px; position:absolute; background:url("images/<?php echo $config->expansion; ?>/outland.jpg?v=<?php echo $cachebust; ?>") no-repeat; background-position:0px 0px; background-size:100% 100%;}
</style>
</head>
<body>
<?php

echo $map_image;

if (($config->expansion >= 3) && ($map == "Azeroth")){
  echo '<div id="dk_zone"></div>';
}

//zone boundaries and identification
$zone_json = $map;
if ($map == "Azeroth"){
  $zone_json = $config->expansion . '/Azeroth';
}
$json = 'json/'.strtolower($zone_json).'.json';
if (file_exists($json)){
  $json = file_get_contents($json);
  $json = json_decode($json, true);
  echo '
<!-- SVG Polygons -->
';
  echo '<svg id="zone_matrix" style="width:'.$map_x_size.'px; height:'.$map_y_size.'px">';
  foreach ($json[0]["zone"] as $name => $zone) {
    if ($zone["polygon"]){
      echo '<defs><filter id="blur" x="0" y="0"><feGaussianBlur in="SourceGraphic" stdDeviation="2" /></filter></defs>';
      echo '<polygon class="zone-bind" id="zone_'.$zone["id"].'" filter="url(#blur)" onmouseover="zoneIdentity(\''.addslashes($map . " - " . $zone["name"]).'\')" style="fill:'.$zone["color"].'" points="'.$zone["polygon"].'" />';
    }
  }
  echo '</svg>';
  echo '
<!-- End of SVG Polygons -->
';
}

//All footprints are inside this div.
//The end of this div must be after the footprints, or the character matrix can not be adjusted from the map.
echo '<div id="char_matrix">';

$ap_gps = "";
if ($config->live_track){$ap_gps = "_gps";}

/*
$realm_dropdown = '<select class="field_dropdown">';
for ($d=0; $d<$n_realms; $d++){
  $realm_dropdown .= '<option>'.$realm_db[$d]->realm_name.'</option>';
}
$realm_dropdown .= '</select>';
*/

function footprint($char, $realm, $x, $y)
{
  global $config, $race, $class;
  $special_class = "";
  if ($char[$realm]["wrath_zone"]){
    $special_class = " dk";
  }
  echo '<div class="fp'.$special_class.'" id="'.strtolower($char[$realm]["name"].'_'.$char[$realm]["realm_name"]).'" style="left:'.$x.'px; top:'.$y.'px;"><i class="fa fa-map-marker '.$race[$char[$realm]["race"]][1].'"></i>';
  if ($config->show_player_details){
    echo '<div class="fp_details"><b>'.$char[$realm]["name"].'</b> ['.$char[$realm]["realm_name"].']</br>'.$char[$realm]["level"].' '.$race[$char[$realm]["race"]][0].' '.$class[$char[$realm]["class"]][0].'</div>';
  }
  echo '</div>';
}

$p_total = $p_map = 0;
for ($realm=0; $realm<$n_realms; $realm++)
{
  $table[$realm] = $DB[$realm]->query('SELECT name, race, gender, class, level, position_x, position_y, map, zone, instance_id from '.$realm_db[$realm]->table . $ap_gps.' WHERE name != ""');
  while($char[$realm] = $table[$realm]->fetch_assoc())
  {
    $p_total++;
    $char[$realm]["realm_name"] = $realm_db[$realm]->realm_name;

    if (($char[$realm]["map"] == 530) && ($char[$realm]["instance_id"] == 0))
    {
      if (($char[$realm]["zone"] == 4080) || //Isle of Quel'Danas
         ($char[$realm]["zone"] == 3487) || //Silvermoon City
         ($char[$realm]["zone"] == 3430) || //Eversong Woods
         ($char[$realm]["zone"] == 3433)) //Ghostlands
         {
           $char[$realm]["map"] = 0; //add footprint to Eastern Kingdoms
           $char[$realm]["tbc_zone"] = 1;
         }
      if (($char[$realm]["zone"] == 3524) || //Azuremyst Isle
         ($char[$realm]["zone"] == 3557) || //Exodar
         ($char[$realm]["zone"] == 3525)) //Bloodmyst Isle
         {
           $char[$realm]["map"] = 1; //add footprint to Kalimdor
           $char[$realm]["tbc_zone"] = 1;
         }
    }
    else if ($char[$realm]["map"] == 609) //DK Starting area
    {
      $char[$realm]["map"] = 0; //add footprint to Eastern Kingdoms
      $char[$realm]["wrath_zone"] = 1;
    }

    if (($map == "Outland") && ($char[$realm]["map"] == 530))
    {
      $cur_x = $char[$realm]["position_x"] - 1325;
      $cur_y = $char[$realm]["position_y"] - 7895;
      $x_pos = ceil($cur_x * 0.081882);
      $y_pos = ceil($cur_y * 0.081882);
      $char_x = 182 - $y_pos;
      $char_y = 319 - $x_pos;
      $p_map++;
      footprint($char, $realm, $char_x, $char_y);
    }
    else if (($map == "Northrend") && ($char[$realm]["map"] == 571))
    {
      $cur_x = $char[$realm]["position_x"] - 1465;
      $cur_y = $char[$realm]["position_y"] - 7855;
      $x_pos = ceil($cur_x * 0.048382);
      $y_pos = ceil($cur_y * 0.048382);
      $char_x = 155 - $y_pos;
      $char_y = 538 - $x_pos;
      $p_map++;
      footprint($char, $realm, $char_x, $char_y);
    }
    else
    {
      if ($map == "Azeroth")
      {
        if ($char[$realm]["map"] == 1) //Kalimdor
        {
          $cur_x = $char[$realm]["position_x"] - 1465;
          $cur_y = $char[$realm]["position_y"] - 8155;
          $x_pos = ceil($cur_x * 0.031162);
          $y_pos = ceil($cur_y * 0.030682);
          if ($char[$realm]["tbc_zone"]){
            $char_x = -562 - $y_pos;
            $char_y = 82 - $x_pos;
          }
          else {
            $char_x = 12 - $y_pos;
            $char_y = 396 - $x_pos;
          }
          $p_map++;
          footprint($char, $realm, $char_x, $char_y);
        }
        else if ($char[$realm]["map"] == 0) //Eastern Kingdoms
        {
          $cur_x = $char[$realm]["position_x"] - 1465;
          $cur_y = $char[$realm]["position_y"] - 8155;
          $x_pos = ceil($cur_x * 0.031162);
          $y_pos = ceil($cur_y * 0.029882);
          if ($char[$realm]["tbc_zone"]){
            $char_x = 996 - $y_pos;
            $char_y = 381 - $x_pos;
          }
          else if ($char[$realm]["wrath_zone"]){
            $char_x = 1078 - $y_pos;
            $char_y = 315 - $x_pos;
          }
          else {
            //$char_x = 820 - $y_pos;
            $char_x = 1070 - $y_pos;
            $char_y = 315 - $x_pos;
          }
          $p_map++;
          footprint($char, $realm, $char_x, $char_y);
        }
      }
    }

  }
}

echo '</div>'; //The back map
echo '</div>'; //The div char_matrix has all footprints attached

echo '<div id="nav_menu">';

$ugly_url="?map=";
if ($config->rewrite_module){
  $ugly_url="";
}

if ($map == "Outland"){
  echo '<div id="nav_right" class="nav_button_flash" onclick="location.href=\''.$ugly_url.'Azeroth\';"><i class="fa fa-chevron-up"></i><br>Azeroth</div>';
  echo '<style>#mm_outland img{border:1px solid #8d8d8d;}</style>';
}
else if ($map == "Northrend"){
  echo '<div id="nav_bot" class="nav_button_flash" onclick="location.href=\''.$ugly_url.'Azeroth\';">Azeroth<br><i class="fa fa-chevron-down"></i></div>';
  echo '<style>#mm_northrend img{border:1px solid #8d8d8d;}</style>';
}
else {
  echo '<div id="nav_top" class="nav_button_flash" onclick="location.href=\''.$ugly_url.'Northrend\';"><i class="fa fa-chevron-up"></i><br>Northrend</div>';
  echo '<div id="nav_left" class="nav_button_flash" onclick="location.href=\''.$ugly_url.'Outland\';"><i class="fa fa-chevron-up"></i><br>Outland</div>';
  echo '<style>#mm_azeroth img{border:1px solid #8d8d8d;}</style>';
}

echo '<center>
<div id="nav_title">'.$map.'</div>
<br>
<table>
<tr>
<td><td><div class="nav_button" onclick="mapShift(1)"><i class="fa fa-chevron-up"></i></div><td>
<tr>
<td><div class="nav_button" onclick="mapShift(3)"><i class="fa fa-chevron-left"></i></div>
<td><div class="nav_button" onclick="mapShift(0)"><i class="fa fa-arrows-alt"></i></div>
<td><div class="nav_button" onclick="mapShift(4)"><i class="fa fa-chevron-right"></i></div>
<tr>
<td><td><div class="nav_button" onclick="mapShift(2)"><i class="fa fa-chevron-down"></i></div><td>
</table>
<div class="nav_div"></div>
<table>
<td><div class="nav_button" onclick="mapZoom(2)"><i class="fa fa-search-plus"></i></div>
<td><div class="nav_button" onclick="mapZoom(0)" id="zoom_val" style="width:60px; font-size:11pt">100%</div>
<td><div class="nav_button" onclick="mapZoom(1)"><i class="fa fa-search-minus"></i></div>
</table>
</center>
<br>
<label><input type="checkbox" onclick="showCharMatrix()" checked />Show Players</label>
<br>
<label><input type="checkbox" onclick="showMapMatrix()" checked />Show Map</label>
<br>
<label><input type="checkbox" onclick="showMapZones()" />Show Zones</label>';
if (($config->expansion >= 3) && ($map == "Azeroth")){
  echo '<br>
  <label><input type="checkbox" onclick="showDKZone()" checked />Show DK Start Area</label>';
}
if (!$config->show_all_realms){
  echo '<br><br>';
  echo 'Realm:<br>';
  echo $realm_dropdown;
}
echo '</div>';

echo '<div id="minimap">
<div id="minimap_title">'.$map.'</div>
<table cellpadding="0" cellspacing="0">
<tr>
  <td>
  <td id="mm_northrend" onmouseover="zoneIdentity(\'Northrend\')" onclick="location.href=\''.$ugly_url.'Northrend\';"><img src="images/'.$config->expansion.'/minimap/northrend.png?v='.$cachebust.'">
  <td>
<tr>
  <td id="mm_outland" onmouseover="zoneIdentity(\'Outland\')" onclick="location.href=\''.$ugly_url.'Outland\';"><img src="images/'.$config->expansion.'/minimap/outland.png?v='.$cachebust.'">
  <td id="mm_azeroth" onmouseover="zoneIdentity(\'Azeroth\')" onclick="location.href=\''.$ugly_url.'Azeroth\';"><img src="images/'.$config->expansion.'/minimap/azeroth.png?v='.$cachebust.'">
  <td>
<tr>
  <td>
  <td>
  <td>
</table>
<div id="minimap_details"><div style="float:left">'.$map.': '.$p_map.'</div><div style="float:right; margin-right:15px;">Realm(s): '.$p_total.'</div></div>
</div>';

if (!$realm_db[0]->realm_name){
  echo '<br><center><i class="fa fa-warning"></i> There was an error reading from config/config.php</center>';
  exit();
}

echo '<a id="version" target="_blank" href="'.$version->site.'">'.$version->hash.'</a>';
?>

<div id="search"><div id="search_cancel" onclick='search("cancel")'><i class="fa fa-close"></i></div><div id="search_btn" onclick='search("click")'><i class="fa fa-search"></i></div><input id="search_in" onkeydown="search(event)" placeholder="Search..." /></div>
</body>

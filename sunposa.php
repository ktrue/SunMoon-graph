<?php
error_reporting(E_ALL);
ini_set('display_errors','1');
ini_set('user_agent','Mozilla 5.0 (sunpos.php - saratoga-weather.org)');
#Script updated on 21 April 2009
#original script written by Breitling (http://meteo.aerolugo.com/)
#Thanks to input from Forum-members we added sunpath for 21 June and 21 December
#http://www.weather-watch.com/smf/index.php/topic,39197.0.html
#changing colors: By Jim McMurry from  Juneau County Weather (http://www.jcweather.us/)
#changed the way the calculation is done(21 April 2009)
#it should be ok now within two arc-minutes
#Added sunrise/sunset/length of day: Chuck McGill from Westford Weather (http://www.westfordweather.net)
#
#Changed moon image generation to use ajax-images/moon/w/NH-moon gif's.  
#Requires /Solaris/moonphase.php (2015 Sept. 11) (Removed.. Saratoga-weather.org)
#
# reworked by Saratoga-weather.org for PHP 8+
# Version 2.00 - 03-Oct-2022 - initial fixup
# Version 2.01 - 09-Oct-2022 - added debugging to show diagnostics on failed sun image fetch
# Version 2.02 - 10-Oct-2022 - added check for allow_url_fopen=true in debugging
# Version 3.00 - 18-Aug-2024 - added code from get-USNO-sunmoon.php to replace need for clientrawextra.txt
# Version 3.01 - 19-Aug-2024 - code and comments cleanup
# Version 3.02 - 21-Aug-2024 - add context to sun image fetch from NASA
#
# NOTE: requires jpgraph 4.4.0+ for operation with PHP 8+
#
$Version = 'sunposa.php Version 3.02 - 21-Aug-2024';
// allow viewing of generated source

if ( isset($_REQUEST['sce']) && strtolower($_REQUEST['sce']) == 'view' ) {
//--self downloader --
   $filenameReal = __FILE__;
   $download_size = filesize($filenameReal);
   header('Pragma: public');
   header('Cache-Control: private');
   header('Cache-Control: no-cache, must-revalidate');
   header("Content-type: text/plain");
   header("Accept-Ranges: bytes");
   header("Content-Length: $download_size");
   header('Connection: close');
   
   readfile($filenameReal);
   exit;
}
//Start of script
###############################################################
#Settings                                                     #
###############################################################
#
$jploc = './jpgraph-4.4.2-src-only/';  // relative location of jpgraph library

/*  GLOBAL VARIABLES */
$lat = 37.2715;              //overridden by $SITE['latitude']
//Note! longitude is west negative
$lon = -122.02274;           //overridden by $SITE['longitude'];
$tz = "America/Los_Angeles"; //overridden by $SITE['tz']
$cacheFileDir = './cache/';        //overridden by $SITE['cacheFileDir']
//
$moonImagePath = './moonimg/NH-moon'; //moon images NH-moon - Norhern Hemisphere
#$moonImagePath = './moonimg/SH-moon'; //moon images SH-moon - Southern Hemisphere

$dtstring   = "M j Y g:ia";         // format for the date & time
$dateMDY    = true;  // =true for mm/dd/yyyy, =false for dd/mm/yyyy format
#
# you likely do not have to configure the following:
$daycolor   = 'lightskyblue';
$ctlcolor   = 'skyblue:0.6';           // Civil Twilight
$ntlcolor   = 'skyblue:0.6';           // Nautical Twilight
$atlcolor   = 'midnightblue:0.9';           // Astronomical Twilight
$nightcolor = 'midnightblue:0.7';
$dawncolor  = 'lightskyblue:0.4';
$zenith = 90.83333;
#
# optional uncomment to use Weather-Display clientrawextra.txt for moon instead of<br />
#  the internal calculations
#
# $crextrafile = "./clientrawextra.txt"; // set location of WD clientrawextra file
#
# optional if proxy used - uncomment to use. Leave commented if no proxy server needed
# $myProxy = 'proxyip:port';
#
###############################################################
#End of settings                                              #
###############################################################

if(file_exists("Settings.php")) { include_once("Settings.php"); }

global $SITE,$Debug;
$Debug = "";

if (isset($SITE['latitude'])) 	{$lat = $SITE['latitude'];}
if (isset($SITE['longitude'])) 	{$lon = $SITE['longitude'];}
if (isset($SITE['tz'])) 	{$tz = $SITE['tz'];}
if (isset($SITE['cacheFileDir'])) {$cacheFileDir = $SITE['cacheFileDir']; }

set_tz( $tz );

if(isset($crextrafile) and file_exists($crextrafile)) {
  # Use WD for moon data
  $clientrawextra = explode(' ',file_get_contents($crextrafile));
} else {
  # Use internal calculations
  $USNOdata = get_sunmoon($dateMDY,$lat,$lon,$tz);

  if(isset($USNOdata['sunrise']))                 {$sunrise =     $USNOdata['sunrise']; }  
  if(isset($USNOdata['sunrisedate']))             {$sunrisedate = $USNOdata['sunrisedate']; }  
  if(isset($USNOdata['sunset']))                  {$sunset =      $USNOdata['sunset']; }  
  if(isset($USNOdata['sunsetdate']))              {$sunsetdate =  $USNOdata['sunsetdate']; }  
  if(isset($USNOdata['moonrise']))                {$moonrise =    $USNOdata['moonrise']; }  
  if(isset($USNOdata['moonrisedate']))            {$moonrisedate= $USNOdata['moonrisedate']; }  
  if(isset($USNOdata['moonset']))                 {$moonset =     $USNOdata['moonset']; }  
  if(isset($USNOdata['moonsetdate']))             {$moonsetdate = $USNOdata['moonsetdate']; }  
  if(isset($USNOdata['moonphase']))               {$moonphasename = $USNOdata['moonphase']; }  
  if(isset($USNOdata['illumination']))            {$moonillum = $USNOdata['illumination']; }  
  if(isset($USNOdata['hoursofpossibledaylight'])) {$hoursofpossibledaylight = $USNOdata['hoursofpossibledaylight'];}
  $moondata = new calcMoonPhase();
  $moonage = round($moondata->age() , 1); 
}


if(isset($_REQUEST['debug']) and $_REQUEST['debug'] = 'y') {
	header('Content-type: text/plain');
	print "------------------------------------------------------------------\n";
	print "$Version\n";
	print "..debug=y debugging output for sunposa.php.  PHP version ".phpversion()."\n";
	print "  php.ini setting 'allow_url_fopen = ";
	if(ini_get('allow_url_fopen') == true) {
		print "true;'  (Note: this enables fetch of sun image).";
	} else {
		print "false;'  WARNING: this must =true to fetch the sun image.";
	}
	print "\n";
  
	$toCheck = array('imagecreatefrompng','imagecreatefromjpeg','imagecreatefromgif',
									 'imagettfbbox','imagettftext',
                   'gregoriantojd',
                  'curl_init','curl_setopt','curl_exec','curl_error','curl_getinfo','curl_close');

	print "\n  Status of needed built-in PHP functions:\n";

	foreach ($toCheck as $n => $chkName) {
		print "  function '$chkName()' ";
		if(function_exists($chkName)) {
			print " is available\n";
		} else {
			print " is NOT available, but required\n";
		}
  }
  print "\n  Settings used:";
	print "  lat='$lat', lon='$lon', tz='$tz', cacheFileDir='$cacheFileDir'\n";
	print "  jpgraph location='$jploc'\n";
  if (isset($crextrafile)) {
    print "  Using '$crextrafile' for moon data.\n";
    if(!file_exists($crextrafile)){
      print "--Warning: '$crextrafile' not found!\n";
    } else {
      print "  '$crextrafile' last updated ".date("Y-m-d h:i:s",filemtime($crextrafile))."\n";
    }
  } else {
    print "  Using internal calculations for moon data.\n";
  }
	print "  moon image cache '".$cacheFileDir.'jpmoon.png'." ";
	if(file_exists($cacheFileDir.'jpmoon.png')) {
		print "exists. Updated ".date("Y-m-d h:i:s",filemtime($cacheFileDir.'jpmoon.png'));
	} else {
		print "does not exist.";
	}
	print "\n";
	print "  sun  image cache '".$cacheFileDir.'jpsun.png'." ";
	if(file_exists($cacheFileDir.'jpsun.png')) {
		print "exists.  Updated ".date("Y-m-d h:i:s",filemtime($cacheFileDir.'jpsun.png'));
	} else {
		print "does not exist.";
	}
	print "\n";
	if(function_exists("gd_info")){
   $GDinfo = gd_info();
	 print "\n  GD Library is available:\n";
	 print var_export($GDinfo,true)."\n";
	} else {
		print"-- Warning: GD library is required but does not seem to be enabled\n";
	}
	
	exit(0);
}
		

include ($jploc."src/jpgraph.php");
include ($jploc."src/jpgraph_scatter.php");
include ($jploc."src/jpgraph_line.php");
include ($jploc."src/jpgraph_date.php");

// Get a current moon once per day
if (!file_exists($cacheFileDir."jpmoon.png") or 
    (date("md") != date("md",filemtime($cacheFileDir."jpmoon.png"))) or
		isset($_REQUEST['force']) ) {
  if(isset($clientrawextra[561])){
	  $age = $clientrawextra[561]; # USE WD moonage value
  } else {
    $age = round($moonage);
  }
	#$age -= (($age>14)?(($age>21)?2:1):0);
	if ($age>28) { $age=28; }
	$moonImage = $moonImagePath.(($age<10&&!strpos($age,'0'))?'0'.$age:$age).'.gif';
	saveImage($moonImage,
	    $cacheFileDir."jpmoon.png",50,50);
} 
if (!file_exists($cacheFileDir."jpsun.png") or 
    filemtime($cacheFileDir."jpsun.png") <= time() - 3600 or
		isset($_REQUEST['force']) ) {

	maketransparent("https://umbra.nascom.nasa.gov/images/latest_solisHe_thumbnail.gif",
	    $cacheFileDir."jpsun.png",50,50);
}


//calculations start here

$dst = date("I");
$tdiff = ((date("Z"))/3600);
$year = date("Y");
$mo = date("n");
$day = date("j");
$ahour = (date("G") + (date("i")/60));
//constants for sun
$dsj2k = (367*$year)-floor(7*($year+floor(($mo+9)/12))/4)+(floor(275*$mo/9)+$day-730531.5+(-$tdiff)/24);
//$dsj2k = (367*$year)-floor(7*($year+floor(($mo+9)/12))/4)+(floor(275*$mo/9)+$day-730531.5+(-$tz-$dst)/24);
$csj2k = ($dsj2k/36525);
$lst = fmod((280.46061837 + (360.98564736629*($dsj2k+ $lon))),360);
$epsilon = (23.439 - (0.0000004 * $dsj2k));
$eff = (180/pi());
$tee = pow(tan(deg2rad($epsilon/2)),2);

for ($qq = 0; $qq <= 25; $qq += 1) {
if ($qq == 25) {
    $hour = $ahour;
} else  {
    $hour = ($qq);
}
$gtime[] = $hour;

//sunstuff
$FNday = (367*$year)-floor(7*($year+floor(($mo+9)/12))/4)+(floor(275*$mo/9)+$day-730531.5+(-$tdiff)/24)+($hour/24);
$lsun = fmod((280.461 + (0.9856474 * $FNday)), 360);
$gee = fmod((357.528 + (0.9856003 * $FNday)), 360);
$lambda = (($lsun+1.915 * sin(deg2rad($gee))) + (0.02 * sin(deg2rad(2 *$gee))));
$rasun = $lambda - $eff*$tee*sin(deg2rad(2*$lambda)) + $eff/2 * pow($tee,2) * sin(deg2rad(4*$lambda));
$decsun = rad2deg(asin(sin(deg2rad($epsilon))*sin(deg2rad($lambda))));
$hasun = fmod((280.46061837 + (360.98564736629*$FNday) + ($lon-$rasun)), 360);
$salt = (rad2deg(asin(sin(deg2rad($decsun))* sin(deg2rad($lat))+cos(deg2rad($decsun))*cos(deg2rad($lat))*cos(deg2rad($hasun)))));

$sunpos[] = $salt;

//Sun azimuth

$coslat = cos(deg2rad($lat));
$sinlat = sin(deg2rad($lat));
$decsunr = deg2rad($decsun);
$rasunr = deg2rad($rasun);
$ddat = ($decsun + (20.0383 * cos($rasunr))/36*($FNday/36525));
$cosddat = cos(deg2rad($decsun + (20.0383 * cos($rasunr))/36*($FNday/36525)));
$sinddat = sin(deg2rad($decsun + (20.0383 * cos($rasunr))/36*($FNday/36525)));
$sinHA = sin(deg2rad($hasun));
$sinalt = sin(deg2rad($salt));

$suny = (-1 * $coslat * $cosddat * $sinHA);
$sunx = ($sinddat - $sinlat * $sinalt);
$sunA1 = atan($suny/$sunx);

//$sunazr
if ($sunx < 0) {
    $sunazr = (pi() + $sunA1);
} elseif ($suny < 0) {
    $sunazr = ((2*pi()) + $sunA1);
} else  {
    $sunazr = ($sunA1);
}

$sunazd = rad2deg($sunazr);

$sunazi[] = $sunazd;

//moonstuff
$mtee = ($FNday/36525);
$mlambda = fmod(218.32 + 481267.883 * $mtee,360)
 + 6.29 * sin(deg2rad(fmod(134.9 + 477198.85 * $mtee,360)))
 - 1.27 * sin(deg2rad(fmod(259.2 - 413335.38 * $mtee,360)))
 + 0.66 * sin(deg2rad(fmod(235.7 + 890534.23 * $mtee,360)))
 + 0.21 * sin(deg2rad(fmod(269.9 + 954397.7 * $mtee, 360)))
 - 0.19 * sin(deg2rad(fmod(357.5 + 35999.05 * $mtee, 360)))
 - 0.11 * sin(deg2rad(fmod(186.6 + 966404.05 * $mtee, 360)));
$mbeta = ((5.13 * sin(deg2rad(fmod(93.3 + (483202.03 * $mtee), 360))))
 + (0.28 * sin(deg2rad(fmod(228.2 + (960400.87 * $mtee), 360))))
 - (0.28 * sin(deg2rad(fmod(318.3 + (6003.18 * $mtee), 360))))
 - (0.17 * sin(deg2rad(fmod(217.6 - (407332.2 * $mtee), 360))))); 

$mpi = 0.9508
+ (0.0518 * cos(deg2rad(fmod(134.9 + 477198.85 * $mtee, 360))))
+ (0.0095 * cos(deg2rad(fmod(259.2 - 413335.38 * $mtee, 360))))
+ (0.0078 * cos(deg2rad(fmod(235.7 + 890534.23 * $mtee, 360))))
+ (0.0028 * cos(deg2rad(fmod(269.9 + 954397.7 * $mtee, 360))));

$mr = 1/(sin(deg2rad($mpi)));
$ml = cos(deg2rad($mbeta))*cos(deg2rad($mlambda));
$mm = 0.9175 * cos(deg2rad($mbeta))*sin(deg2rad($mlambda)) - (0.3978 * sin(deg2rad($mbeta)));
$mn = 0.3978 * cos(deg2rad($mbeta))*sin(deg2rad($mlambda)) + (0.9175 * sin(deg2rad($mbeta)));
$mA = rad2deg(atan($mm/$ml));

//$ramoon
if ($ml < 0) {
    $ramoon = ($mA + 180);
} elseif ($mm < 0) {
    $ramoon = ($mA + 360);
} else  {
    $ramoon = ($mA);
}

$decmoon = rad2deg(asin($mn));
$mx = $mr*cos(deg2rad($decmoon))*cos(deg2rad($ramoon)) - cos(deg2rad($lat))*cos(deg2rad(fmod(280.46061837 + 360.98564736629*$FNday+ $lon, 360)));
$my = $mr*cos(deg2rad($decmoon))*sin(deg2rad($ramoon)) - cos(deg2rad($lat))*sin(deg2rad(fmod(280.46061837 + 360.98564736629*$FNday+ $lon, 360)));
$mz = $mr*sin(deg2rad($decmoon))-sin(deg2rad($lat));
$mr1 = sqrt(($mx*$mx)+($my*$my)+($mz*$mz));
$mA1 = rad2deg(atan($my/$mx));
//$ramoon1
if ($mx < 0) {
    $ramoon1 = ($mA1 + 180);
} elseif ($my < 0) {
    $ramoon1 = ($mA1 + 360);
} else  {
    $ramoon1 = ($mA1);
}

$decmoon1 = rad2deg(asin($mz/$mr1));
$hamoon1 = fmod(280.46061837 + 360.98564736629*$FNday+ $lon-$ramoon1,360);
$malt = rad2deg(asin(sin(deg2rad($decmoon1))*sin(deg2rad($lat))+cos(deg2rad($decmoon1))*cos(deg2rad($lat))*cos(deg2rad($hamoon1))));
$moonpos[] = $malt;

/*###########################################################################################################################
https://eclipse.gsfc.nasa.gov/LEvis/LEaltitude.html
In our current example the azimuth of the Moon is then:
 
   Azimuth of Moon:  A = ArcTan [-(Cos d Sin h)/(Sin d Cos f - Cos d Cos h Sin f)]
                       = ArcTan [-(Cos(19.8) Sin(-9))/(Sin(19.8) Cos(38.9) - Cos(19.8) Cos(-9) Sin(38.9))]
                       = ArcTan [-(0.941 * -0.156) / ((0.339 * 0.778) - (0.941 * 0.988 * 0.628))]
                       = ArcTan [-(-0.147) / ((0.264) - (0.584))]
                       = ArcTan [ +0.147 / (-0.320)]
                       = ArcTan [ -0.459 ]
                       = -24.7�
*/ ########################################################################################################################
//$azmoon = rad2deg(atan(-(cos(deg2rad($decmoon))*sin(deg2rad($hamoon1)))/(sin(deg2rad($decmoon))*cos(deg2rad($lat)) - cos(deg2rad($decmoon))*cos(deg2rad($hamoon1))*sin(deg2rad($lat)))));
$azm = (atan(-(cos(deg2rad($decmoon))*sin(deg2rad($hamoon1)))));
$denom = (sin(deg2rad($decmoon))*cos(deg2rad($lat)) - cos(deg2rad($decmoon))*cos(deg2rad($hamoon1))*sin(deg2rad($lat)));
//echo "<br/>";
if($denom <= 0){$azmoon = rad2deg(atan(($azm / $denom))) + 180;}
else if ($denom >= 0 ){$azmoon = rad2deg(atan(($azm / ($denom))));}
if ($azmoon < 0){$azmoon = $azmoon + 360;}
//$azmoon = fmod($azmoon,360);
//$moonazi[] = $azmoon;
//$azmoon = rad2deg(atan(-(cos(deg2rad($decmoon))*sin(deg2rad($hamoon1)))/(sin(deg2rad($decmoon))*cos(deg2rad($lat)) - cos(deg2rad($decmoon))*cos(deg2rad($hamoon1))*sin(deg2rad($lat)))));
//$azmoon = $azmoon+180;
//if ($azmoon < 0){$azmoon = $azmoon+360;}//else if($azmoon>=0 && $azmoon <=90){$azmoon = $azmoon+180;}//else{$azmoon = ($azmoon);}

$moonazi[] = $azmoon;
//$azm = (atan(-(cos(deg2rad($decmoon))*sin(deg2rad($hamoon1)))));
//$denom = (sin(deg2rad($decmoon))*cos(deg2rad($lat)) - cos(deg2rad($decmoon))*cos(deg2rad($hamoon1))*sin(deg2rad($lat)));
}
/*
echo "<pre>";
echo "gtime ";
print_r ($gtime);
echo "sunpos ";
print_r ($sunpos);
echo "sunazi ";
print_r ($sunazi);
echo "moonpos ";
print_r ($moonpos);
echo "moonazi ";
print_r ($moonazi);

##########################################################################################################################
Since the denominator in ArcTan [ +0.147 / (-0.320)] is negative, we must add 180� to the final answer:

                     A = -24.7� + 180�
                     A = 155.3�
folowing this I have a correct azimuth HALF the time, don't know how to handle the other half
*/
for ($i=0;$i<25;$i++){
	if ($moonazi[$i] <= 0.0){$moonazi[$i] = $moonazi[$i]+180.0;}
	else if ($moonazi[$i] >= 0.0){$moonazi[$i] = $moonazi[$i];}
}
##########################################################################################################################
for ($i=0;$i<=24;$i++){$tm11[$i]=$sunazi[$i];}
$saz[0] = $sunazi[25];
for ($i=0;$i<=24;$i++){$tm21[$i]=$moonazi[$i];}
$maz[0] = round($moonazi[25]);
$he5[0] = round($moonpos[25]);
for ($i=0;$i<=24;$i++){$he61[$i]=$moonpos[$i];}
$he7[0] = round($sunpos[25]);
for ($i=0;$i<=24;$i++){$he81[$i]=$sunpos[$i];}
#
##END FUNCTIONS###############################################################################
#
/* CREATE SUN PATH */

if (version_compare(PHP_VERSION, '5.2.1', '>')) {                             
	$sun_info = date_sun_info(strtotime(date("Y/n/j",time())), $lat, $lon);
} else {                                                                      
	$sun_info = date_sun_info(strtotime(date("Y/n/j",time())), $lon, $lat);  // A bug in the earlier versions.
}

$day=$sun_info["sunset"]-$sun_info["sunrise"];

######################################################################
$hours = $day / 3600; // 3600 seconds in an hour
#
$minutes = ($hours - floor($hours)) * 60;
#
$final_minutes = round($minutes);
if ($final_minutes <= 9){$final_minutes = "0" . $final_minutes;}
$dl = $final_hours = floor($hours) . ":" . $final_minutes;
######################################################################

$sr = date("h:i a",$sun_info["sunrise"]);
$zen = date("h:i a",$sun_info["transit"]);
$ss = date("h:i a",$sun_info["sunset"]);

$b = array();
$a = ($sun_info["transit"]-36000);
for ($i=0;$i<=25;$i++){
  $a=$a+3600;
  $b[] = $a;
}
foreach ($b as $v) {
  $pos = sun_pos($v,$lat,$lon);
  $az[] = $pos[0];
  $he[] = $pos[1];
}

//Hi position
$sun_info = date_sun_info(strtotime("21 June".date("Y",time())), $lat,$lon);
$b = array();
$a = ($sun_info["transit"]-36000);
for ($i=0;$i<=49;$i++){
  $a=$a+1800;
  $b[] = $a;
}
foreach ($b as $v) {
  $pos = sun_pos($v,$lat,$lon);
  $az0[] = $pos[0];
  $he0[] = $pos[1];
}

// Autumnal Eq
$sun_info = date_sun_info(strtotime("21 September".date("Y",time())),$lat,$lon);
$b = array();
  $a = ($sun_info["transit"]-36000);
for ($i=0;$i<=25;$i++){
  $a=$a+3600;
  $b[] = $a;
}
foreach ($b as $v) {
  $pos = sun_pos($v,$lat,$lon);
  $az3[] = $pos[0];
  $he3[] = $pos[1];
}

//Low position
$sun_info = date_sun_info(strtotime("21 December".date("Y",time())), $lat,$lon);
$b = array();
$a = ($sun_info["transit"]-32400);
for ($i=0;$i<=25;$i++){
  $a=$a+3600;
  $b[] = $a;
}
foreach ($b as $v) {
  $pos = sun_pos($v,$lat,$lon);
  $az1[] = $pos[0];
  $he1[] = $pos[1];
}

if(isset($clientrawextra[560]) and isset($clientrawextra[558])) {
  $mrise  = $clientrawextra[558];
  $millum = $clientrawextra[560];
} else {
  $mrise = $moonrise;
  $millum = $moonillum;
}

/* CREATE ACTUAL SUN POSITION */
$time = time();

// For testing using https://aa.usno.navy.mil/data/docs/AltAz.php & PHP function date_sun_info() results - (for Jim 90� 4' & 43� 48' & 5 hrs W)
/*
$testHr = 19;
$testMin = 25;
$time = mktime($testHr,$testMin,0,date('m'),date('j'),date('Y'));
*/

$pos = sun_pos($time, $lat, $lon);
$az2[0] = $pos[0];
$he2[0] = $pos[1];

// Create the graph. These two calls are always required
$graph  = new Graph(500,300,"auto",1);
$graph->clearTheme();
$graph->SetMargin(25,20,25,55);
$graph->SetFrame(true,'black',0);
$graph->SetMarginColor('black');

$graph->SetBackgroundGradient('blue',$daycolor,GRAD_HOR,BGRAD_PLOT);                        
$suncolor = "yellow";  
								
if ($az2[0] == 180) { 
	$skyword = 'Solar Noon';
} else if ($az2[0] == 360) { 
	$skyword = 'Solar Midnight';
$graph->SetBackgroundGradient('black',$nightcolor,GRAD_HOR,BGRAD_PLOT);    
	
} else if ($he2[0] <= 3 && $he2[0] > -1) {     // Seems to work out about right
	$suncolor = "darkorange";
	$graph->SetBackgroundGradient('brown',$dawncolor,GRAD_HOR,BGRAD_PLOT); 
	if ($he2[0] >= 0) {                        // Not sunrise or sunset
		if ($az2[0] > 180) {
			$skyword = 'Dusk';
		} else {
			$skyword = 'Dawn';
		}		
	} 
} else if ($he2[0] == -1) {
	$suncolor = "darkorange";
	$graph->SetBackgroundGradient('brown',$dawncolor,GRAD_HOR,BGRAD_PLOT); 
	if ($az2[0] > 180) {
		$skyword = 'Sunset';
	} else {
		$skyword = 'Sunrise';
	}
} else if ($he2[0] > 0) {
	if ($az2[0] > 180) {
		$skyword = 'Afternoon';
	} else {
		$skyword = 'Morning';
	}
} else if ($he2[0] >= -6) {          // Civil Twilight
	$graph->SetBackgroundGradient('darkorange',$ctlcolor,GRAD_HOR,BGRAD_PLOT);  
	$skyword = 'Civil Twilight';
} else if ($he2[0] >= -12) {         // Nautical Twilight
	$graph->SetBackgroundGradient('black',$ntlcolor,GRAD_HOR,BGRAD_PLOT);
	$skyword = 'Nautical Twilight';
} else if ($he2[0] >= -18) {         // Astronomical Twilight
	$graph->SetBackgroundGradient('black',$atlcolor,GRAD_HOR,BGRAD_PLOT);
	$skyword = 'Astronomical Twilight';
} else {                             // Must be night
	$graph->SetBackgroundGradient('black',$nightcolor,GRAD_HOR,BGRAD_PLOT);	
	$skyword = 'Nighttime';
}

$graph->SetMarginColor("black");
$fecha = date($dtstring, time()) . " local time";
$graph->SetClipping();
$graph->SetScale( "linlin",0,90,40,320);
$graph ->xgrid->Show( true,true);
$graph ->xgrid->SetColor('#99999');
$graph ->ygrid->Show( true,true);
$graph ->ygrid->SetColor('#99999');
$graph->setTickDensity(TICKD_NORMAL,TICKD_DENSE);
$graph->xaxis->SetPos("min");
$graph->legend->Hide();
$graph->xaxis->title->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->xaxis->title->SetColor("orange");
$graph->xaxis->title->Set("Sun AZ: $az2[0]�                                       Zenith: $zen                                         Sun EL: $he2[0]�");
$graph->xaxis->SetTitleMargin(10);
$graph->yaxis->SetFont(FF_ARIAL,FS_BOLD,7);
$graph->yaxis->SetColor('white');
$graph->xaxis->SetFont(FF_ARIAL,FS_BOLD,7);
$graph->xaxis->SetColor('white');
$graph->footer->left->SetFont(FF_ARIAL,FS_BOLD,9);
$graph->footer->left->Set("Sunrise: $sr");
$graph->footer->left->SetColor("darkorange");
$graph->footer->center->SetFont(FF_ARIAL,FS_BOLD,9);
$graph->footer->center->Set("Sunlight: $dl hrs");
$graph->footer->center->SetColor("darkorange");
$graph->footer->right->SetFont(FF_ARIAL,FS_BOLD,9);
$graph->footer->right->Set("Sunset: $ss");
$graph->footer->right->SetColor("darkorange");
	
// Create the line plot
	$plot = new LinePlot($he, $az);
	$plot->SetColor("orange");
	$plot->SetWeight(3);
	$plot->SetStyle('dashed');

// Create the line plot
	$plot0 = new LinePlot($he0, $az0);
	$plot0->SetColor("green");
	$plot0->SetWeight(1);

// Create the line plot
	$plot1 = new LinePlot($he1, $az1);
	$plot1->SetColor("magenta");
	$plot1->SetWeight(1);

// Create the line plot
	$plot2 = new LinePlot($he3, $az3);
	$plot2->SetColor("cyan");
	$plot2->SetWeight(1);

// Create the line plot for Moon path
	$plot3 = new LinePlot($he61,$tm21);
	$plot3->SetColor("white");
	$plot3->SetWeight(1);
	$plot3->SetStyle('dashed');

// Top Title	
$graph->title->Set('Sun/Moon position: '.$fecha.' - '.$skyword);      
$graph->title->SetFont(FF_ARIAL,FS_BOLD,9);
$graph->title->SetColor('orange','black','white');

// Create the scatter plot
if ($he2[0] == -1) $he2[0]++;             // Push the sun back up on the horizon when at -1 which is sunrise/set
$sp1 = new ScatterPlot($he2,$az2);
$sp1->mark->SetType(MARK_IMG,$cacheFileDir.'jpsun.png',0.8);
//	$sp1->mark->SetType(MARK_FILLEDCIRCLE);
$sp1->mark->SetWidth(8);
$sp1->SetImpuls();
$sp1->mark->SetFillColor($suncolor);
$sp1->mark->SetColor($suncolor);
$sp2 = new ScatterPlot($he5,$maz);
$sp2->mark->SetType(MARK_IMG,$cacheFileDir.'jpmoon.png',0.6);	

// Add plots to graph
$graph->Add($plot0);
$graph->Add($plot1);
$graph->Add($plot2);
$graph->Add($plot3); //Moon
$graph->Add($plot);
if($he2[0]>-1) {$graph->Add($sp1);}
$graph->Add($sp2);

// Add labels for equinox peaks	                
$txt =new Text("Jun 21");
$txt->SetFont(FF_ARIAL,FS_BOLD,7);
#$txt->SetPos( 237,40);
$txt->SetScalePos( 170,max($he0)+5);
$txt->SetColor( "green");
$graph->AddText($txt);	

$txt1 =new Text("Dec 21");
$txt1->SetFont(FF_ARIAL,FS_BOLD,7);
#$txt1->SetPos( 237,173);
$txt1->SetScalePos( 170,max($he1)+5);
$txt1->SetColor( "magenta");
$graph->AddText($txt1);	

$txt2 =new Text("Equinox");
$txt2->SetFont(FF_ARIAL,FS_BOLD,7);
#$txt2->SetPos( 233,96);
$txt2->SetScalePos( 170,max($he3)+5);
$txt2->SetColor( "cyan");
$graph->AddText($txt2);

$txt3 =new Text("Moon EL: $he5[0]�");
$txt3->SetFont(FF_ARIAL,FS_BOLD,8);
$txt3->SetPos(408,276);
$txt3->SetColor( "cyan");
$graph->AddText($txt3);	
$txt4 =new Text("Moonrise: $mrise");
$txt4->SetFont(FF_ARIAL,FS_BOLD,8);
$txt4->SetPos(15,276);
$txt4->SetColor( "cyan");
$graph->AddText($txt4);	
$txt5 =new Text("Illumination: $millum");
$txt5->SetFont(FF_ARIAL,FS_BOLD,8);
$txt5->SetPos(208,276);
$txt5->SetColor( "cyan");
$graph->AddText($txt5);	

// Save the graph
$graph->Stroke();

#------------ functions -------------------
function saveImage($oldfile,$newfile,$width,$height)
// Save Image and re-size
{
	$info = getimagesize($oldfile);
	$im = imagecreatefromgif($oldfile);
	$img = imagecreatetruecolor($width,$height);
	imagecopyresized($img,$im,0,0,0,0,$width,$height,$info[0],$info[1]);
	imageinterlace($im);
	imagepng($im,$newfile);
	imagedestroy($img);
}

function maketransparent($oldfile,$newfile,$width,$height)
// Turn black background transparent and re-size
{
  global $myProxy,$cacheFileDir;
  $tfileName = $cacheFileDir.'tempImg.gif';
  $tfile = get_file_contents($oldfile);
  if(!$tfile){
    header('Content-type: text/plain,charset=ISO-8859-1');
    print "..Oops .. unable to fetch '$oldfile' .. exiting.";
    exit(0);
  }
  file_put_contents($tfileName,$tfile);
	$info = getimagesize($tfileName);
	if(!is_array($info)) {
		if(!headers_sent() ) {header('Content-type: text/plain'); }
		print "-- error fetching image size from $oldfile.  Unable to continue.\n";
		exit(1);
	}
	$im = imagecreatefromgif($tfileName);
	if($im == false) {
		if(!headers_sent() ) {header('Content-type: text/plain'); }
		print "-- error fetching image file $oldfile for processing.  Unable to continue.\n";
		exit(1);
	}
	
	$img = imagecreatetruecolor($width,$height);
	$trans = imagecolorallocate($img, 0x00, 0x00, 0x00);
	imagecolortransparent($img,$trans);
	imagecopyresized($img,$im,0,0,0,0,$width,$height,$info[0],$info[1]);
	imagetruecolortopalette($img, true, 256);
	imageinterlace($img);
	imagepng($img,$newfile);
	imagedestroy($img);
}

// FUNCTION - get data using cURL
function get_file_contents($url)    {
  global $myProxy,$Version;                                                          // set global
  $data = '';

  $ch = curl_init();                                                      // initialize a cURL session
  curl_setopt($ch, CURLOPT_URL, $url);                                    // connect to provided URL
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);                            // don't verify peer certificate
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 ('.$Version.')');
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);                            // 3 sec connection timeout
  curl_setopt($ch, CURLOPT_TIMEOUT, 10);                                   // 2 sec data timeout
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                         // return the data transfer
//    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:58.0) Gecko/20100101 Firefox/58.0');
  $curlHeaders =array(
      "Cache-control: no-cache",
      "Pragma: akamai-x-cache-on, akamai-x-get-request-id"
    );
  curl_setopt($ch, CURLOPT_HTTPHEADER,$curlHeaders); // request  format
  if(isset($myProxy)) {
    curl_setopt($ch, CURLOPT_PROXY, "$myProxy");
  }
  $data = curl_exec($ch); 
  $cInfo = curl_getinfo($ch);
  // execute session
  if(curl_error($ch) <> '' or $cInfo['http_code'] !== 200) {                                             // IF there is an error
   header('Content-type: text/plain,charset=ISO-8859-1');
   print "$Version\n";
   print "-- curl error: RC='".$cInfo['http_code']."' ". curl_error($ch) ." \n";                   //  display error notice
   print "   URL: '$url' \n";
   return('');
  }

  curl_close($ch);                                                        // close the cURL session
  
  return $data;                                                           // return data
}// end get_file_contents


function set_tz ($TZ){
	if (phpversion() >= "5.1.0") {
			date_default_timezone_set($TZ);
	} else {
			putenv("TZ=" . $TZ);
	}
}
function sun_pos($UnixTimestamp, $latitude, $longitude) {
	$year = date("Y",$UnixTimestamp);
	$month = date("n",$UnixTimestamp);
	$day = date("j",$UnixTimestamp);
	$UT = gmdate("H",$UnixTimestamp)+gmdate("i",$UnixTimestamp)/60+gmdate("s",$UnixTimestamp)/3600;
	$d = round(367 * $year - (7 * ($year + (($month + 9)/12)))/4 + (275 * $month)/9 + $day - 730530);
	$w = 282.9404 + 0.0000470935 * $d;
	$e = 0.016709 - 0.000000001151 * $d;
	$M = 356.0470 + 0.9856002585 * $d;
	$M = fmod($M , 360);
	if ($M < 0){$M = $M+360;}
	$L = $w + $M;
	$L = fmod($L , 360);
	$oblecl = 23.4393 - 0.0000003563 * $d ;
	$E = rad2deg(deg2rad($M) + (180/M_PI) * deg2rad($e) * sin(deg2rad($M)) * (1 + $e * cos(deg2rad($M))));
	$x = cos(deg2rad($E)) - $e;
	$y = sin(deg2rad($E)) * sqrt(1 - $e*$e);
	$r = sqrt($x*$x + $y*$y);
	$v = rad2deg(atan2( deg2rad($y), deg2rad($x)) );
	$lon = $v + $w;
	$lon = fmod($lon, 360);
	$x_rect = $r * cos(deg2rad($lon));
	$y_rect = $r * sin(deg2rad($lon));
	$z_rect = 0.0;
	$z = 0.0;
	$x_equat = $x_rect;
	$y_equat = $y_rect * cos(deg2rad($oblecl)) + $z * sin(deg2rad($oblecl));
	$z_equat = $y_rect * sin(deg2rad($oblecl)) + $z * cos(deg2rad($oblecl));
	$r    = sqrt( $x_equat*$x_equat + $y_equat*$y_equat + $z_equat*$z_equat );
	$RA   = rad2deg(atan2( deg2rad($y_equat), deg2rad($x_equat) ));
	$Decl = rad2deg(atan2( deg2rad($z_equat), deg2rad(sqrt( $x_equat*$x_equat + $y_equat*$y_equat ))) );
	$GMST0 = $L/15 + 12;
	$SIDTIME = $GMST0 + $UT + $longitude/15;
	$HA = $SIDTIME*15 - $RA;
	$x = cos(deg2rad($HA)) * cos(deg2rad($Decl));
	$y = sin(deg2rad($HA)) * cos(deg2rad($Decl));
	$z = sin(deg2rad($Decl));
	$xhor = $x * cos(deg2rad(90) - deg2rad($latitude)) - $z * sin(deg2rad(90) - deg2rad($latitude));
	$yhor = $y;
	$zhor = $x * sin(deg2rad(90) - deg2rad($latitude)) + $z * cos(deg2rad(90) - deg2rad($latitude));
	$azimuth  = round(rad2deg(atan2($yhor, $xhor)) + 180);
	$altitude = round(rad2deg(asin($zhor )));
	$pos = array($azimuth, $altitude);
	return $pos;
}

function getTimestamp ($val) {
	list ($hour,$min) = preg_split("/:/",$val);
	$ttl = ($min * 60) + ($hour * 60 * 60);
	return($ttl);
}


function out_time ( $seconds , $mode = false) {
	$uday = (3600 * 24);
	$uhr = 3600;
	$umin = 60;
	
	if ($seconds < 0 ) {
			$neg = "-" ;
			$seconds = $seconds * -1 ;
			//echo "Seconds = " . $seconds . "<br/>";
			
	} else {
			$neg = "" ;
	}
	
	// Calculate days
	$dd = intval($seconds / $uday );
	$mmremain = ($seconds - ($dd * $uday));
	
	// Calculate hours
	$hh = intval($mmremain / $uhr);    
	$ssremain = ($mmremain - ($hh * $uhr));  
	
	// Calculate minutes
	$mm = intval($ssremain / $umin);
	$ss = ($ssremain - ($mm * $umin));
	
	// If day or days
	if ($dd == 1) { $days = 'day';  }
	if ($dd > 1 ) { $days = "days"; }
	
	if (!$mode) {
			// String for if there are days
		if ( $dd > 0 ) {
				$out_string = sprintf("%d %s %02d hrs %02d mins", 
						$dd, $days, $hh, $mm);
		}
		
		// String if there are hours
		if ( $dd == 0 && $hh > 0 ) {
				$out_string = $neg . sprintf("%d hrs %02d mins", 
						$hh, $mm);
		}
		
		// String if there are minutes
		if ( $dd == 0 && $hh == 0 && $mm > 0 ) {
				$out_string = $neg . sprintf("%d mins", 
						$mm);
		}
		
		// Only output Seconds
		if ( $hh == 0 && $mm == 0 ) {
				$out_string = $neg . sprintf("%d secs", $ss) ;
		}
	} else {
			// String for if there are days
		if ( $dd > 0 ) {
				$out_string = $neg . sprintf("%d:%s %02d:%02d", 
						$dd, $days, $hh, $mm);
		}
		
		// String if there are hours
		if ( $dd == 0 && $hh > 0 ) {
				$out_string = $neg . sprintf("%d:%02d", 
						$hh, $mm);
		}
		
		// String if there are minutes
		if ( $dd == 0 && $hh == 0 && $mm > 0 ) {
				$out_string = $neg . sprintf("00:%02d", 
						$mm);
		}
		
		// Only output Seconds
		if ( $hh == 0 && $mm == 0 ) {
				$out_string = $neg . sprintf("%d secs", $ss) ;
		}       
			
	}
	
	// Return value
	return ($out_string);
}

function time24to12 ($val) {
	if (strlen($val) == 5 ) {
			$val .= ":00";
	}
	
	return( date("g:i", strtotime($val)) );
} 

# ------------------------------------------------------
#
# Astronomical functions from get-USNO-sunmoon.php
# ------------------------------------------------------

function get_sunmoon($useMDY,$myLat,$myLong,$ourTZ) {
      // create an instance of the class, and use the current time
  global $Debug;
  $Data = array();
  $timeFormat = $useMDY ?"g:ia m/d/Y":"H:i d/m/Y";
  $moon = new calcMoonPhase();
  $age = round($moon->age() , 1);
  $stage = $moon->phase() < 0.5 ? 'waxing' : 'waning';
  $Data['illumination'] = round($moon->illumination() * 100.0) . '%';
  $new_moon = gmdate($timeFormat, (integer)$moon->new_moon());
  $next_new_moon = gmdate($timeFormat, (integer)$moon->next_new_moon());
  $first_quarter = gmdate($timeFormat, (integer)$moon->first_quarter());
  $next_first_quarter = gmdate($timeFormat, (integer)$moon->next_first_quarter());
  $full_moon = gmdate($timeFormat, (integer)$moon->full_moon());
  $next_full_moon = gmdate($timeFormat, (integer)$moon->next_full_moon());
  $last_quarter = gmdate($timeFormat, (integer)$moon->last_quarter());
  $next_last_quarter = gmdate($timeFormat, (integer)$moon->next_last_quarter());
  $Data['moonphase'] = $moon->phase_name();
  list($Y, $M, $D) = explode(' ', date("Y n j"));
  $moonrs = calcMoon::calculateMoonTimes($M, $D, $Y, $myLat, $myLong);
  list($Data['moonrise'], $Data['moonrisedate']) = explode(" ", date($timeFormat, $moonrs->moonrise));
  list($Data['moonset'], $Data['moonsetdate']) = explode(" ", date($timeFormat, $moonrs->moonset));
  $SINFO = date_sun_info(time() , $myLat, $myLong);

  // print_r($SINFO);

  /*
  Array
  (
    [sunrise] => 1480345321
    [sunset] => 1480380672
    [transit] => 1480362996
    [civil_twilight_begin] => 1480343611
    [civil_twilight_end] => 1480382381
    [nautical_twilight_begin] => 1480341680
    [nautical_twilight_end] => 1480384313
    [astronomical_twilight_begin] => 1480339797
    [astronomical_twilight_end] => 1480386196
  )
  */
  list($Data['beginciviltwilight'], $Data['beginciviltwilightdate']) = 
    explode(" ", date($timeFormat, $SINFO['civil_twilight_begin']));
  list($Data['sunrise'], $Data['sunrisedate']) = 
    explode(" ", date($timeFormat, $SINFO['sunrise']));
  list($Data['suntransit'], $Data['suntransitdate']) = 
    explode(" ", date($timeFormat, $SINFO['transit']));
  list($Data['sunset'], $Data['sunsetdate']) = 
    explode(" ", date($timeFormat, $SINFO['sunset']));
  list($Data['endciviltwilight'], $Data['endciviltwilightdate']) = 
    explode(" ", date($timeFormat, $SINFO['civil_twilight_end']));
  $Data['hoursofpossibledaylight'] = gmdate('H:i', $SINFO['sunset'] - $SINFO['sunrise']);
  $moonTimes = new calcMoonRiSet($myLat, $myLong, $ourTZ);
  $moonTimes->setDate(date("Y") , date("m") , date("d"));
  $moonTransit = $moonTimes->transit["timestamp"];
  $Debug.= "<!-- moonTransit " . print_r($moonTransit, true) . " -->\n";
  if ($moonTransit > 99999) {
    list($Data['moontransit'], $Data['moontransitdate']) = 
      explode(" ", date($timeFormat, $moonTransit));
  }

  $Data['databy'] = 'calculated';

  return($Data);

}

// ------------------------------------------------------------------
// Classes for moon calculations
// ------------------------------------------------------------------

/**
 * Moon phase calculation class
 * Adapted for PHP from Moontool for Windows (http://www.fourmilab.ch/moontoolw/)
 * by Samir Shah (http://rayofsolaris.net)
 * License: MIT
 * Adapted by K. True - Saratoga-weather.org 15-Aug-2017
 *
 */
class calcMoonPhase

{
  private $timestamp;
  private $phase;
  private $illum;
  private $age;
  private $dist;
  private $angdia;
  private $sundist;
  private $sunangdia;
  private $synmonth;
  private $quarters = null;
  function __construct($pdate = null)
  {
    if (is_null($pdate)) $pdate = time();
    /*  Astronomical constants  */
    $epoch = 2444238.5; // 1980 January 0.0
    /*  Constants defining the Sun's apparent orbit  */
    $elonge = 278.833540; // Ecliptic longitude of the Sun at epoch 1980.0
    $elongp = 282.596403; // Ecliptic longitude of the Sun at perigee
    $eccent = 0.016718; // Eccentricity of Earth's orbit
    $sunsmax = 1.495985e8; // Semi-major axis of Earth's orbit, km
    $sunangsiz = 0.533128; // Sun's angular size, degrees, at semi-major axis distance
    /*  Elements of the Moon's orbit, epoch 1980.0  */
    $mmlong = 64.975464; // Moon's mean longitude at the epoch
    $mmlongp = 349.383063; // Mean longitude of the perigee at the epoch
    $mlnode = 151.950429; // Mean longitude of the node at the epoch
    $minc = 5.145396; // Inclination of the Moon's orbit
    $mecc = 0.054900; // Eccentricity of the Moon's orbit
    $mangsiz = 0.5181; // Moon's angular size at distance a from Earth
    $msmax = 384401; // Semi-major axis of Moon's orbit in km
    $mparallax = 0.9507; // Parallax at distance a from Earth
    $synmonth = 29.53058868; // Synodic month (new Moon to new Moon)
    $this->synmonth = $synmonth;
    $lunatbase = 2423436.0; // Base date for E. W. Brown's numbered series of lunations (1923 January 16)
    /*  Properties of the Earth  */

    // $earthrad = 6378.16;				// Radius of Earth in kilometres
    // $PI = 3.14159265358979323846;	// Assume not near black hole

    $this->timestamp = $pdate;

    // pdate is coming in as a UNIX timstamp, so convert it to Julian

    $pdate = $pdate / 86400 + 2440587.5;
    /* Calculation of the Sun's position */
    $Day = $pdate - $epoch; // Date within epoch
    $N = $this->fixangle((360 / 365.2422) * $Day); // Mean anomaly of the Sun
    $M = $this->fixangle($N + $elonge - $elongp); // Convert from perigee co-ordinates to epoch 1980.0
    $Ec = $this->kepler($M, $eccent); // Solve equation of Kepler
    $Ec = sqrt((1 + $eccent) / (1 - $eccent)) * tan($Ec / 2);
    $Ec = 2 * rad2deg(atan($Ec)); // True anomaly
    $Lambdasun = $this->fixangle($Ec + $elongp); // Sun's geocentric ecliptic longitude
    $F = ((1 + $eccent * cos(deg2rad($Ec))) / (1 - $eccent * $eccent)); // Orbital distance factor
    $SunDist = $sunsmax / $F; // Distance to Sun in km
    $SunAng = $F * $sunangsiz; // Sun's angular size in degrees
    /* Calculation of the Moon's position */
    $ml = $this->fixangle(13.1763966 * $Day + $mmlong); // Moon's mean longitude
    $MM = $this->fixangle($ml - 0.1114041 * $Day - $mmlongp); // Moon's mean anomaly
    $MN = $this->fixangle($mlnode - 0.0529539 * $Day); // Moon's ascending node mean longitude
    $Ev = 1.2739 * sin(deg2rad(2 * ($ml - $Lambdasun) - $MM)); // Evection
    $Ae = 0.1858 * sin(deg2rad($M)); // Annual equation
    $A3 = 0.37 * sin(deg2rad($M)); // Correction term
    $MmP = $MM + $Ev - $Ae - $A3; // Corrected anomaly
    $mEc = 6.2886 * sin(deg2rad($MmP)); // Correction for the equation of the centre
    $A4 = 0.214 * sin(deg2rad(2 * $MmP)); // Another correction term
    $lP = $ml + $Ev + $mEc - $Ae + $A4; // Corrected longitude
    $V = 0.6583 * sin(deg2rad(2 * ($lP - $Lambdasun))); // Variation
    $lPP = $lP + $V; // True longitude
    $NP = $MN - 0.16 * sin(deg2rad($M)); // Corrected longitude of the node
    $y = sin(deg2rad($lPP - $NP)) * cos(deg2rad($minc)); // Y inclination coordinate
    $x = cos(deg2rad($lPP - $NP)); // X inclination coordinate
    $Lambdamoon = rad2deg(atan2($y, $x)) + $NP; // Ecliptic longitude
    $BetaM = rad2deg(asin(sin(deg2rad($lPP - $NP)) * sin(deg2rad($minc)))); // Ecliptic latitude
    /* Calculation of the phase of the Moon */
    $MoonAge = $lPP - $Lambdasun; // Age of the Moon in degrees
    $MoonPhase = (1 - cos(deg2rad($MoonAge))) / 2; // Phase of the Moon

    // Distance of moon from the centre of the Earth

    $MoonDist = ($msmax * (1 - $mecc * $mecc)) / (1 + $mecc * cos(deg2rad($MmP + $mEc)));
    $MoonDFrac = $MoonDist / $msmax;
    $MoonAng = $mangsiz / $MoonDFrac; // Moon's angular diameter

    // $MoonPar = $mparallax / $MoonDFrac;							// Moon's parallax
    // store results

    $this->phase = $this->fixangle($MoonAge) / 360; // Phase (0 to 1)
    $this->illum = $MoonPhase; // Illuminated fraction (0 to 1)
    $this->age = $synmonth * $this->phase; // Age of moon (days)
    $this->dist = $MoonDist; // Distance (kilometres)
    $this->angdia = $MoonAng; // Angular diameter (degrees)
    $this->sundist = $SunDist; // Distance to Sun (kilometres)
    $this->sunangdia = $SunAng; // Sun's angular diameter (degrees)
  }

  private function fixangle($a)
  {
    return ($a - 360 * floor($a / 360));
  }

  //  KEPLER  --   Solve the equation of Kepler.

  private function kepler($m, $ecc)
  {
    $epsilon = 0.000001; // 1E-6
    $e = $m = deg2rad($m);
    do {
      $delta = $e - $ecc * sin($e) - $m;
      $e-= $delta / (1 - $ecc * cos($e));
    }

    while (abs($delta) > $epsilon);
    return $e;
  }

  /*  Calculates  time  of  the mean new Moon for a given
  base date.  This argument K to this function is the
  precomputed synodic month index, given by:
  K = (year - 1900) * 12.3685
  where year is expressed as a year and fractional year.
  */
  private function meanphase($sdate, $k)
  {

    // Time in Julian centuries from 1900 January 0.5

    $t = ($sdate - 2415020.0) / 36525;
    $t2 = $t * $t;
    $t3 = $t2 * $t;
    $nt1 = 2415020.75933 
		  + $this->synmonth * $k 
			+ 0.0001178 * $t2 - 0.000000155 * $t3 
			+ 0.00033 * sin(deg2rad(166.56 + 132.87 * $t - 0.009173 * $t2));
    return $nt1;
  }

  /*  Given a K value used to determine the mean phase of
  the new moon, and a phase selector (0.0, 0.25, 0.5,
  0.75), obtain the true, corrected phase time.
  */
  private function truephase($k, $phase)
  {
    $apcor = false;
    $k+= $phase; // Add phase to new moon time
    $t = $k / 1236.85; // Time in Julian centuries from 1900 January 0.5
    $t2 = $t * $t; // Square for frequent use
    $t3 = $t2 * $t; // Cube for frequent use
    $pt = 2415020.75933 // Mean time of phase
     + $this->synmonth * $k 
		 + 0.0001178 * $t2 
		 - 0.000000155 * $t3 
		 + 0.00033 * sin(deg2rad(166.56 + 132.87 * $t - 0.009173 * $t2));
    $m = 359.2242 + 29.10535608 * $k - 0.0000333 * $t2 - 0.00000347 * $t3; // Sun's mean anomaly
    $mprime = 306.0253 + 385.81691806 * $k + 0.0107306 * $t2 + 0.00001236 * $t3; // Moon's mean anomaly
    $f = 21.2964 + 390.67050646 * $k - 0.0016528 * $t2 - 0.00000239 * $t3; // Moon's argument of latitude
    if ($phase < 0.01 || abs($phase - 0.5) < 0.01) {

      // Corrections for New and Full Moon

      $pt+= (0.1734 - 0.000393 * $t) * sin(deg2rad($m)) 
			  + 0.0021 * sin(deg2rad(2 * $m)) 
				- 0.4068 * sin(deg2rad($mprime)) 
				+ 0.0161 * sin(deg2rad(2 * $mprime)) 
				- 0.0004 * sin(deg2rad(3 * $mprime)) 
				+ 0.0104 * sin(deg2rad(2 * $f)) 
				- 0.0051 * sin(deg2rad($m + $mprime)) 
				- 0.0074 * sin(deg2rad($m - $mprime)) 
				+ 0.0004 * sin(deg2rad(2 * $f + $m)) 
				- 0.0004 * sin(deg2rad(2 * $f - $m)) 
				- 0.0006 * sin(deg2rad(2 * $f + $mprime)) 
				+ 0.0010 * sin(deg2rad(2 * $f - $mprime)) 
				+ 0.0005 * sin(deg2rad($m + 2 * $mprime));
      $apcor = true;
    }
    else
    if (abs($phase - 0.25) < 0.01 || abs($phase - 0.75) < 0.01) {
      $pt+= (0.1721 - 0.0004 * $t) * sin(deg2rad($m)) + 0.0021 * sin(deg2rad(2 * $m)) 
			  - 0.6280 * sin(deg2rad($mprime)) 
				+ 0.0089 * sin(deg2rad(2 * $mprime)) 
				- 0.0004 * sin(deg2rad(3 * $mprime)) 
				+ 0.0079 * sin(deg2rad(2 * $f)) 
				- 0.0119 * sin(deg2rad($m + $mprime)) 
				- 0.0047 * sin(deg2rad($m - $mprime)) 
				+ 0.0003 * sin(deg2rad(2 * $f + $m)) 
				- 0.0004 * sin(deg2rad(2 * $f - $m)) 
				- 0.0006 * sin(deg2rad(2 * $f + $mprime)) 
				+ 0.0021 * sin(deg2rad(2 * $f - $mprime)) 
				+ 0.0003 * sin(deg2rad($m + 2 * $mprime)) 
				+ 0.0004 * sin(deg2rad($m - 2 * $mprime)) 
				- 0.0003 * sin(deg2rad(2 * $m + $mprime));
      if ($phase < 0.5) // First quarter correction
      $pt+= 0.0028 - 0.0004 * cos(deg2rad($m)) + 0.0003 * cos(deg2rad($mprime));
      else

      // Last quarter correction

      $pt+= - 0.0028 + 0.0004 * cos(deg2rad($m)) - 0.0003 * cos(deg2rad($mprime));
      $apcor = true;
    }

    if (!$apcor) { // function was called with an invalid phase selector
      return false;
		}
    return $pt;
  }

  /* 	Find time of phases of the moon which surround the current date.
  Five phases are found, starting and
  ending with the new moons which bound the  current lunation.
  */
  private function phasehunt()
  {
    $sdate = $this->utctojulian($this->timestamp);
    $adate = $sdate - 45;
    $ats = $this->timestamp - 86400 * 45;
    $yy = (int)gmdate('Y', $ats);
    $mm = (int)gmdate('n', $ats);
    $k1 = floor(($yy + (($mm - 1) * (1 / 12)) - 1900) * 12.3685);
    $adate = $nt1 = $this->meanphase($adate, $k1);
    while (true) {
      $adate+= $this->synmonth;
      $k2 = $k1 + 1;
      $nt2 = $this->meanphase($adate, $k2);

      // if nt2 is close to sdate, then mean phase isn't good enough, we have to be more accurate

      if (abs($nt2 - $sdate) < 0.75) $nt2 = $this->truephase($k2, 0.0);
      if ($nt1 <= $sdate && $nt2 > $sdate) break;

      $nt1 = $nt2;
      $k1 = $k2;
    }

    // results in Julian dates

    $data = array(
      $this->truephase($k1, 0.0) ,
      $this->truephase($k1, 0.25) ,
      $this->truephase($k1, 0.5) ,
      $this->truephase($k1, 0.75) ,
      $this->truephase($k2, 0.0) ,
      $this->truephase($k2, 0.25) ,
      $this->truephase($k2, 0.5) ,
      $this->truephase($k2, 0.75)
    );
    $this->quarters = array();
    foreach($data as $v) $this->quarters[] = ($v - 2440587.5) * 86400; // convert to UNIX time
  }

  /*  Convert UNIX timestamp to astronomical Julian time (i.e. Julian date plus day fraction).  */
  private function utctojulian($ts)
  {
    return $ts / 86400 + 2440587.5;
  }

  private function get_phase($n)
  {
    if (is_null($this->quarters)) $this->phasehunt();
    return $this->quarters[$n];
  }

  /* Public functions for accessing results */
  function phase()
  {
    return $this->phase;
  }

  function illumination()
  {
    return $this->illum;
  }

  function age()
  {
    return $this->age;
  }

  function distance()
  {
    return $this->dist;
  }

  function diameter()
  {
    return $this->angdia;
  }

  function sundistance()
  {
    return $this->sundist;
  }

  function sundiameter()
  {
    return $this->sunangdia;
  }

  function new_moon()
  {
    return $this->get_phase(0);
  }

  function first_quarter()
  {
    return $this->get_phase(1);
  }

  function full_moon()
  {
    return $this->get_phase(2);
  }

  function last_quarter()
  {
    return $this->get_phase(3);
  }

  function next_new_moon()
  {
    return $this->get_phase(4);
  }

  function next_first_quarter()
  {
    return $this->get_phase(5);
  }

  function next_full_moon()
  {
    return $this->get_phase(6);
  }

  function next_last_quarter()
  {
    return $this->get_phase(7);
  }

  function phase_name()
  { // Ken's take on Phase Name calculation
    $ph = round($this->phase * 100);
    if ($ph <= 1 || $ph >= 98) {
      return ('New Moon');
    }
    elseif ($ph > 1 && $ph < 24) {
      return ('Waxing Crescent');
    }
    elseif ($ph >= 24 && $ph <= 27) {
      return ('First Quarter');
    }
    elseif ($ph > 27 && $ph < 49) {
      return ('Waxing Gibbous');
    }
    elseif ($ph >= 49 && $ph <= 52) {
      return ('Full Moon');
    }
    elseif ($ph > 52 && $ph < 74) {
      return ('Waning Gibbous');
    }
    elseif ($ph >= 74 && $ph <= 77) {
      return ('Last Quarter');
    }
    elseif ($ph > 77 && $ph < 98) {
      return ('Waning Crescent');
    }
  }
} // end class MoonPhase
/******************************************************************************
* The following is a PHP implementation of the JavaScript code found at:      *
* http://bodmas.org/astronomy/riset.html                                      *
*                                                                             *
* Original maths and code written by Keith Burnett <bodmas.org>               *
* PHP port written by Matt "dxprog" Hackmann <dxprog.com>                     *
*                                                                             *
* This program is free software: you can redistribute it and/or modify        *
* it under the terms of the GNU General Public License as published by        *
* the Free Software Foundation, either version 3 of the License, or           *
* (at your option) any later version.                                         *
*                                                                             *
* This program is distributed in the hope that it will be useful,             *
* but WITHOUT ANY WARRANTY; without even the implied warranty of              *
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               *
* GNU General Public License for more details.                                *
*                                                                             *
* You should have received a copy of the GNU General Public License           *
* along with this program.  If not, see <http://www.gnu.org/licenses/>.       *
*                                                                             *
* Adapted by K. True - Saratoga-weather.org 15-Aug-2017                       *
******************************************************************************/
class calcMoon

{
  /**
   * Calculates the moon rise/set for a given location and day of year
   */
  public static

  function calculateMoonTimes($month, $day, $year, $lat, $lon)
  {
    $utrise = $utset = 0;

    //		$timezone = (int)($lon / 15);

    $date = self::modifiedJulianDate($month, $day, $year);

    //		$date -= $timezone / 24;

    $timezone = date('Z') / 3600; // KT
    $date-= $timezone / 24;
    $latRad = deg2rad($lat);
    $sinho = 0.0023271056;
    $sglat = sin($latRad);
    $cglat = cos($latRad);
    $rise = false;
    $set = false;
    $above = false;
    $hour = 1;
    $ym = self::sinAlt($date, $hour - 1, $lon, $cglat, $sglat) - $sinho;
    $above = $ym > 0;
    while ($hour < 25 && (false == $set || false == $rise)) {
      $yz = self::sinAlt($date, $hour, $lon, $cglat, $sglat) - $sinho;
      $yp = self::sinAlt($date, $hour + 1, $lon, $cglat, $sglat) - $sinho;
      $quadout = self::quad($ym, $yz, $yp);
      $nz = $quadout[0];
      $z1 = $quadout[1];
      $z2 = $quadout[2];
      $xe = $quadout[3];
      $ye = $quadout[4];
      if ($nz == 1) {
        if ($ym < 0) {
          $utrise = $hour + $z1;
          $rise = true;
        }
        else {
          $utset = $hour + $z1;
          $set = true;
        }
      }

      if ($nz == 2) {
        if ($ye < 0) {
          $utrise = $hour + $z2;
          $utset = $hour + $z1;
        }
        else {
          $utrise = $hour + $z1;
          $utset = $hour + $z2;
        }
      }

      $ym = $yp;
      $hour+= 2.0;
    }

    // Convert to unix timestamps and return as an object

    $retVal = new stdClass();
    $utrise = self::convertTime($utrise);
    $utset = self::convertTime($utset);
    $retVal->moonrise = $rise ? mktime($utrise['hrs'], $utrise['min'], 0, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
    $retVal->moonset = $set ? mktime($utset['hrs'], $utset['min'], 0, $month, $day, $year) : mktime(0, 0, 0, $month, $day + 1, $year);
    return $retVal;
  }

  /**
   *	finds the parabola throuh the three points (-1,ym), (0,yz), (1, yp)
   *  and returns the coordinates of the max/min (if any) xe, ye
   *  the values of x where the parabola crosses zero (roots of the self::quadratic)
   *  and the number of roots (0, 1 or 2) within the interval [-1, 1]
   *
   *	well, this routine is producing sensible answers
   *
   *  results passed as array [nz, z1, z2, xe, ye]
   */
  private static function quad($ym, $yz, $yp)
  {
    $nz = $z1 = $z2 = 0;
    $a = 0.5 * ($ym + $yp) - $yz;
    $b = 0.5 * ($yp - $ym);
    $c = $yz;
    $xe = - $b / (2 * $a);
    $ye = ($a * $xe + $b) * $xe + $c;
    $dis = $b * $b - 4 * $a * $c;
    if ($dis > 0) {
      $dx = 0.5 * sqrt($dis) / abs($a);
      $z1 = $xe - $dx;
      $z2 = $xe + $dx;
      $nz = abs($z1) < 1 ? $nz + 1 : $nz;
      $nz = abs($z2) < 1 ? $nz + 1 : $nz;
      $z1 = $z1 < - 1 ? $z2 : $z1;
    }

    return array(
      $nz,
      $z1,
      $z2,
      $xe,
      $ye
    );
  }

  /**
   *	this rather mickey mouse function takes a lot of
   *  arguments and then returns the sine of the altitude of the moon
   */
  private static function sinAlt($mjd, $hour, $glon, $cglat, $sglat)
  {
    $mjd+= $hour / 24;
    $t = ($mjd - 51544.5) / 36525;
    $objpos = self::minimoon($t);
    $ra = $objpos[1];
    $dec = $objpos[0];
    $decRad = deg2rad($dec);
    $tau = 15 * (self::lmst($mjd, $glon) - $ra);
    return $sglat * sin($decRad) + $cglat * cos($decRad) * cos(deg2rad($tau));
  }

  /**
   *	returns an angle in degrees in the range 0 to 360
   */
  private static function degRange($x)
  {
    $b = $x / 360;
    $a = 360 * ($b - (int)$b);
    $retVal = $a < 0 ? $a + 360 : $a;
    return $retVal;
  }

  private static function lmst($mjd, $glon)
  {
    $d = $mjd - 51544.5;
    $t = $d / 36525;
    $lst = self::degRange(280.46061839 
		  + 360.98564736629 * $d 
		  + 0.000387933 * $t * $t 
		  - $t * $t * $t / 38710000);
    return $lst / 15 + $glon / 15;
  }

  /**
   * takes t and returns the geocentric ra and dec in an array mooneq
   * claimed good to 5' (angle) in ra and 1' in dec
   * tallies with another approximate method and with ICE for a couple of dates
   */
  private static function minimoon($t)
  {
    $p2 = 6.283185307;
    $arc = 206264.8062;
    $coseps = 0.91748;
    $sineps = 0.39778;
    $lo = self::frac(0.606433 + 1336.855225 * $t);
    $l = $p2 * self::frac(0.374897 + 1325.552410 * $t);
    $l2 = $l * 2;
    $ls = $p2 * self::frac(0.993133 + 99.997361 * $t);
    $d = $p2 * self::frac(0.827361 + 1236.853086 * $t);
    $d2 = $d * 2;
    $f = $p2 * self::frac(0.259086 + 1342.227825 * $t);
    $f2 = $f * 2;
    $sinls = sin($ls);
    $sinf2 = sin($f2);
    $dl = 22640 * sin($l);
    $dl+= - 4586 * sin($l - $d2);
    $dl+= 2370 * sin($d2);
    $dl+= 769 * sin($l2);
    $dl+= - 668 * $sinls;
    $dl+= - 412 * $sinf2;
    $dl+= - 212 * sin($l2 - $d2);
    $dl+= - 206 * sin($l + $ls - $d2);
    $dl+= 192 * sin($l + $d2);
    $dl+= - 165 * sin($ls - $d2);
    $dl+= - 125 * sin($d);
    $dl+= - 110 * sin($l + $ls);
    $dl+= 148 * sin($l - $ls);
    $dl+= - 55 * sin($f2 - $d2);
    $s = $f + ($dl + 412 * $sinf2 + 541 * $sinls) / $arc;
    $h = $f - $d2;
    $n = - 526 * sin($h);
    $n+= 44 * sin($l + $h);
    $n+= - 31 * sin(-$l + $h);
    $n+= - 23 * sin($ls + $h);
    $n+= 11 * sin(-$ls + $h);
    $n+= - 25 * sin(-$l2 + $f);
    $n+= 21 * sin(-$l + $f);
    $L_moon = $p2 * self::frac($lo + $dl / 1296000);
    $B_moon = (18520.0 * sin($s) + $n) / $arc;
    $cb = cos($B_moon);
    $x = $cb * cos($L_moon);
    $v = $cb * sin($L_moon);
    $w = sin($B_moon);
    $y = $coseps * $v - $sineps * $w;
    $z = $sineps * $v + $coseps * $w;
    $rho = sqrt(1 - $z * $z);
    $dec = (360 / $p2) * atan($z / $rho);
    $ra = (48 / $p2) * atan($y / ($x + $rho));
    $ra = $ra < 0 ? $ra + 24 : $ra;
    return array(
      $dec,
      $ra
    );
  }

  /**
   *	returns the self::fractional part of x as used in self::minimoon and minisun
   */
  private static function frac($x)
  {
    $x-= (int)$x;
    return $x < 0 ? $x + 1 : $x;
  }

  /**
   * Takes the day, month, year and hours in the day and returns the
   * modified julian day number defined as mjd = jd - 2400000.5
   * checked OK for Greg era dates - 26th Dec 02
   */
  private static function modifiedJulianDate($month, $day, $year)
  {
    if ($month <= 2) {
      $month+= 12;
      $year--;
    }

    $a = 10000 * $year + 100 * $month + $day;
    $b = 0;
    if ($a <= 15821004.1) {
      $b = - 2 * (int)(($year + 4716) / 4) - 1179;
    }
    else {
      $b = (int)($year / 400) - (int)($year / 100) + (int)($year / 4);
    }

    $a = 365 * $year - 679004;
    return $a + $b + (int)(30.6001 * ($month + 1)) + $day;
  }

  /**
   * Converts an hours decimal to hours and minutes
   */
  private static function convertTime($hours)
  {
    $hrs = (int)($hours * 60 + 0.5) / 60.0;
    $h = (int)($hrs);
    $m = (int)(60 * ($hrs - $h) + 0.5);
    return array(
      'hrs' => $h,
      'min' => $m
    );
  }
} // end class Moon
/******************************************************************************
* The following is courtesy of Jachym https://meteotemplate.com               *
* Note: the moon rise/set is NOT quite as accurate as that                    *
* provided by the prior calcMoon class above, so is only used for moon        *
* transit time                                                                *
*                                                                             *
*******************************************************************************
*/
/* ############ MOON FUNCTIONS #################### */

// Moon rise/set


class calcMoonRiSet

{
  const RADEG = 57.29577951308232;
  const DEGRAD = 0.01745329251994;
  const ARC = 206264.8;
  const SIN_EPS = 0.39768; // sin+cos obliquity ecliptic (23d26m)
  const COS_EPS = 0.91752;
  const PREC = 18; // precision
  private $_sinEarthLatitude, $_cosEarthLatitude;
  private $_data = array();
	public $earthLatitude,$earthLongitude,$earthTimezone;
	public $tdiff,$transit,$rise,$set,$rise2,$set2;
  public

  function __construct($earthLatitude = false, $earthLongitude = false, $earthTimezone = false)
  {
    if ($earthLatitude === false) $this->earthLatitude = ini_get('date.default_latitude');
    else $this->earthLatitude = $earthLatitude;
    if ($earthLongitude === false) $this->earthLongitude = ini_get('date.default_longitude');
    else $this->earthLongitude = $earthLongitude;
    if ($earthTimezone === false) $this->earthTimezone = ini_get('date.timezone');
    else $this->earthTimezone = $earthTimezone;

    // set current day

    $this->setDate(date("Y", time()) , date("n", time()) , date("j", time()));
  }

  // set day

  public function setDate($year, $month, $day)
  {
    if ($year < 1583 or $year > 2500) return (false);
    $old_timezone = date_default_timezone_get();
    date_default_timezone_set($this->earthTimezone);

    // calculation day's table, begin+end time

    $t = $tb = mktime(0, 0, 0, $month, $day, $year);
    $te = mktime(24, 0, 0, $month, $day, $year);
    $this->tdiff = ($te - $tb) / self::PREC;
    $this->_sinEarthLatitude = $this->dsin($this->earthLatitude);
    $this->_cosEarthLatitude = $this->dcos($this->earthLatitude);
    $i = 0;
    while ($i <= self::PREC) {
      $this->_data[$i]["timestamp"] = $t;
      $jd = $this->getJulianDate($t);
      $LST = $this->getLST($jd); // Local Sidereal Time
      $this->_data[$i]["LST"] = $LST;
      list($RA, $de) = $this->miniMoon(($jd - 2451545.0) / 36525.0);
      $this->_data[$i]["RA"] = $RA;
      $HA = $LST - $RA; // hour angle
      if ($HA > 12) $HA-= 24;
      $this->_data[$i]["HA"] = $HA;
      $this->_data[$i]["sAlt"] = 
			  $this->_sinEarthLatitude * $this->dsin($de) 
				+ $this->_cosEarthLatitude * $this->dcos($de) * $this->dcos(15 * $this->_data[$i]["HA"]); // sinus Altitude
      $t+= $this->tdiff;
      $i++;
    }

    // Moon transit

    list($this->transit["timestamp"], $this->transit["hhmm"], $this->transit["hh:mm"]) = $this->getTransit("HA");

    // Moon's rise and set

    list($this->rise["timestamp"], 
		  $this->rise["hhmm"], 
		  $this->rise["hh:mm"], 
		  $this->set["timestamp"], 
			$this->set["hhmm"], 
			$this->set["hh:mm"], 
			$this->rise2["timestamp"], 
			$this->rise2["hhmm"], 
			$this->rise2["hh:mm"], 
			$this->set2["timestamp"], 
			$this->set2["hhmm"], 
			$this->set2["hh:mm"]) = $this->getRiSet("sAlt", $this->dsin(0.125));
    date_default_timezone_set($old_timezone);
    return (true);
  }

  // PRIVATE //////////////////////////////////////////////////////////////////////////////////////////////////////////////////

  private function getTransit($o)
  {
    $fT = false;
    for ($i = 1; $i < self::PREC && !$fT; $i+= 2) {
      if (($this->_data[$i - 1][$o] < 0 && $this->_data[$i][$o] >= 0) || 
			    ($this->_data[$i][$o] <= 0 && $this->_data[$i + 1][$o] > 0)) {
        list($nz, $z1, $z2, $xe, $ye) = 
				  $this->quad($this->_data[$i - 1][$o], $this->_data[$i][$o], $this->_data[$i + 1][$o]);
        $fT = true;
        $oTran = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
      }
    }

    if ($fT) list($transit["timestamp"], $transit["hhmm"], $transit["hh:mm"]) = $this->formatTime($oTran);
    else {
      $transit["timestamp"] = false;
      $transit["hhmm"] = "    ";
      $transit["hh:mm"] = "     ";
    }

    return array(
      $transit["timestamp"],
      $transit["hhmm"],
      $transit["hh:mm"]
    );
  }

  private function getRiSet($o, $alt)
  {
    $fRise = $fSet = false;
    $fAbove = false;
    $oRise2 = $oSet2 = false;
    if ($this->_data[0][$o] > $alt) $fAbove = true;
    for ($i = 1; $i < self::PREC; $i+= 2) {
      list($nz, $z1, $z2, $xe, $ye) = 
			  $this->quad($this->_data[$i - 1][$o] 
				  - $alt, $this->_data[$i][$o] 
					- $alt, $this->_data[$i + 1][$o] 
					- $alt);
      if ($nz == 1) {
        if ($this->_data[$i - 1][$o] < $alt) {
          if ($fRise === true) $oRise2 = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
          else {
            $oRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
            $fRise = true;
          }
        }
        else {
          if ($fSet === true) $oSet2 = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
          else {
            $oSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
            $fSet = true;
          }
        }
      }
      elseif ($nz == 2) {
        if ($ye < 0.0) {
          $oRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z2;
          $oSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
        }
        else {
          $oRise = $this->_data[$i]["timestamp"] + $this->tdiff * $z1;
          $oSet = $this->_data[$i]["timestamp"] + $this->tdiff * $z2;
        }

        $fRise = $fSet = true;
      }
    }

    // output

    if ($fRise === true || $fSet === true) {
      if ($fRise === true) {
        list($rise["timestamp"], $rise["hhmm"], $rise["hh:mm"]) = $this->formatTime($oRise);
        list($rise2["timestamp"], $rise2["hhmm"], $rise2["hh:mm"]) = $this->formatTime($oRise2);
      }
      else {
        $rise["timestamp"] = false;
        $rise["hhmm"] = "    ";
        $rise["hh:mm"] = "     ";
      }

      if ($fSet === true) {
        list($set["timestamp"], $set["hhmm"], $set["hh:mm"]) = $this->formatTime($oSet);
        list($set2["timestamp"], $set2["hhmm"], $set2["hh:mm"]) = $this->formatTime($oSet2);
      }
      else {
        $set["timestamp"] = true;
        $set["hhmm"] = "    ";
        $set["hh:mm"] = "     ";
      }
    }
    else {
      if ($fAbove === true) { // continuously above horizon
        $rise["timestamp"] = $set["timestamp"] = true;
        $rise["hhmm"] = $set["hhmm"] = "****";
        $rise["hh:mm"] = $set["hh:mm"] = "**:**";
      }
      else { // continuously below horizon
        $rise["timestamp"] = $set["timestamp"] = false;
        $rise["hhmm"] = $set["hhmm"] = "----";
        $rise["hh:mm"] = $set["hh:mm"] = "--:--";
      }
    }

    // return

    if ($oRise2 !== false) {
			return array(
				$rise["timestamp"],
				$rise["hhmm"],
				$rise["hh:mm"],
				$set["timestamp"],
				$set["hhmm"],
				$set["hh:mm"],
				$rise2["timestamp"],
				$rise2["hhmm"],
				$rise2["hh:mm"],
				false,
				false,
				false
      );
		}
    elseif ($oSet2 !== false) {
			return array(
				$rise["timestamp"],
				$rise["hhmm"],
				$rise["hh:mm"],
				$set["timestamp"],
				$set["hhmm"],
				$set["hh:mm"],
				false,
				false,
				false,
				$set2["timestamp"],
				$set2["hhmm"],
				$set2["hh:mm"]
			);
		}
    else {
			return array(
				$rise["timestamp"],
				$rise["hhmm"],
				$rise["hh:mm"],
				$set["timestamp"],
				$set["hhmm"],
				$set["hh:mm"],
				false,
				false,
				false,
				false,
				false,
				false
			);
		}
  }

  // Low precision formulae for planetary position, Flandern & Pulkkinen
  // returns ra and dec of Moon to 5 arc min (ra) and 1 arc min (dec)
  // for a few centuries either side of J2000.0
  // Predicts rise and set times to within minutes for about 500 years

  private function miniMoon($T)
  {
    $l0 = $this->frac(0.606433 + 1336.855225 * $T);
    $l = 2 * M_PI * $this->frac(0.374897 + 1325.552410 * $T);
    $ls = 2 * M_PI * $this->frac(0.993133 + 99.997361 * $T);
    $d = 2 * M_PI * $this->frac(0.827361 + 1236.853086 * $T);
    $f = 2 * M_PI * $this->frac(0.259086 + 1342.227825 * $T);

    // perturbation

    $dl = 22640 * sin($l);
    $dl+= - 4586 * sin($l - 2 * $d);
    $dl+= + 2370 * sin(2 * $d);
    $dl+= + 769 * sin(2 * $l);
    $dl+= - 668 * sin($ls);
    $dl+= - 412 * sin(2 * $f);
    $dl+= - 212 * sin(2 * $l - 2 * $d);
    $dl+= - 206 * sin($l + $ls - 2 * $d);
    $dl+= + 192 * sin($l + 2 * $d);
    $dl+= - 165 * sin($ls - 2 * $d);
    $dl+= - 125 * sin($d);
    $dl+= - 110 * sin($l + $ls);
    $dl+= + 148 * sin($l - $ls);
    $dl+= - 55 * sin(2 * $f - 2 * $d);
    $s = $f + ($dl + 412 * sin(2 * $f) + 541 * sin($ls)) / self::ARC;
    $h = $f - 2 * $d;
    $n = - 526 * sin($h);
    $n+= + 44 * sin($l + $h);
    $n+= - 31 * sin(-$l + $h);
    $n+= - 23 * sin($ls + $h);
    $n+= + 11 * sin(-$ls + $h);
    $n+= - 25 * sin(-2 * $l + $f);
    $n+= + 21 * sin(-$l + $f);
    $l_moon = 2 * M_PI * $this->frac($l0 + $dl / 1296000);
    $b_moon = (18520.0 * sin($s) + $n) / self::ARC;

    // convert to equatorial coords using a fixed ecliptic

    $cb = cos($b_moon);
    $x = $cb * cos($l_moon);
    $v = $cb * sin($l_moon);
    $w = sin($b_moon);
    $y = self::COS_EPS * $v - self::SIN_EPS * $w;
    $z = self::SIN_EPS * $v + self::COS_EPS * $w;
    $rho = sqrt(1.0 - $z * $z);
    $de = (180 / M_PI) * atan($z / $rho);
    $RA = (24 / M_PI) * atan($y / ($x + $rho));
    if ($RA < 0) $RA+= 24;
    return (array(
      $RA,
      $de
    ));
  }

  // finds the parabola through the three points (-1,ym), (0,yz), (1, yp) and returns
  // the coordinates of the values of x where the parabola crosses zero (roots of the quadratic)
  // and the number of roots (0, 1 or 2) within the interval [-1, 1]

  private function quad($ym, $yz, $yp)
  {
    $z1 = $z2 = 0;
    $nz = 0;
    $a = 0.5 * ($ym + $yp) - $yz;
    $b = 0.5 * ($yp - $ym);
    $c = $yz;
    $xe = - $b / (2 * $a);
    $ye = ($a * $xe + $b) * $xe + $c;
    $dis = $b * $b - 4.0 * $a * $c;
    if ($dis > 0) {
      $dx = 0.5 * sqrt($dis) / abs($a);
      $z1 = $xe - $dx;
      $z2 = $xe + $dx;
      if (abs($z1) <= 1.0) $nz+= 1;
      if (abs($z2) <= 1.0) $nz+= 1;
      if ($z1 < - 1.0) $z1 = $z2;
    }

    return (array(
      $nz,
      $z1,
      $z2,
      $xe,
      $ye
    ));
  }

  private function getJulianDate($t)
  {

    // return $t/86400 + 2440587.5; // only for 64bit && year > 1582

    $jd = gregoriantojd(gmdate("n", $t) , gmdate("j", $t) , gmdate("Y", $t)) - 0.5;
    $jd+= gmdate("H", $t) / 24 + gmdate("i", $t) / 1440 + gmdate("s", $t) / 86400;
    return ($jd);
  }

  // returns the local sidereal time (degree)

  private function getLST($jd)
  {
    $mjd = $jd - 2451545.0;
    $lst = $this->range(280.46061837 + 360.98564736629 * $mjd);
    return ($lst + $this->earthLongitude) / 15;
  }

  // round time, return array(timestamp, "hhmm", "hh:mm")

  private function formatTime($t)
  {
    $t0 = 60 * (int)($t / 60 + 0.5);
    if (date("j", (integer)$t) == date("j", $t0)) $t = $t0;
    return array(
      $t,
      date("Hi", $t) ,
      date("H:i", $t)
    );
  }

  private function frac($x)
  {
    return ($x - floor($x));
  }

  private function range($x)
  {
    return ($x - 360.0 * (Floor($x / 360.0)));
  }

  private function dsin($x)
  {
    return (sin($x * self::DEGRAD));
  }

  private function dcos($x)
  {
    return (cos($x * self::DEGRAD));
  }
} // end class MoonRiSet

# --- end of functions from get-USNO-sunmoon.php

# leave closing PHP tag off for image script
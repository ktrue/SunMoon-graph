<?php
# ------------------------------------------------------------------------
#
# Program:  check-sunmoon-data.php
# 
# Purpose:  Compare sunposa.php internal calculations for azimuth,elevation 
#           with azimuth,elevation data from the USNO for one date.
#
# Author:  Ken True - webmaster@saratoga-weather.org
#
# Version 1.00 - 26-Aug-2024 - initial release
#
# ------------------------------------------------------------------------

$Version ="check-sunmoon-data.php V1.00 - 26-Aug-2024";
header('Content-type: text/plain,charset=ISO-8859-1');
print "$Version\n";

if(file_exists("usno-sunmoon-data.php")) {
  include_once("usno-sunmoon-data.php");
} else {
  print "-- 'usno-sunmoon-data.php' not found.\n";
  print "  Run get-usno-data.php in this directory to create the file.\n";
  print ".. Done.\n";
  exit(0);
}
if(file_exists('calc-sunmoon-data.php')) {
  include_once('calc-sunmoon-data.php');
} else {
  print "-- 'calc-sunmoon-data.php' not found.\n";
  print "  Set \$doLog = true; in sunposa.php and\n";
  print "  Run sunposa.php in this directory to create the file.\n";
  print ".. Done.\n";
  exit(0);
}

if($calcMeta['date'] !== $dataMeta['date']) {
  print "-- Oops. sunposa date '".$calcMeta['date']."' is different than USNO date '".$dataMeta['date']."'\n";
  print "-- unable to run comparison.\n";
  exit(0);
}
if($calcMeta['lat'] !== $dataMeta['lat']) {
  print "-- Oops. sunposa lat '".$calcMeta['lat']."' is different than USNO lat '".$dataMeta['lat']."'\n";
  print "-- unable to run comparison.\n";
  exit(0);
}
if($calcMeta['lon'] !== $dataMeta['lon']) {
  print "-- Oops. sunposa lon '".$calcMeta['lon']."' is different than USNO lon '".$dataMeta['lon']."'\n";
  print "-- unable to run comparison.\n";
  exit(0);
}
if($calcMeta['tz'] !== $dataMeta['tz']) {
  print "-- Oops. sunposa tz '".$calcMeta['tz']."' is different than USNO tz '".$dataMeta['tz']."'\n";
  print "-- unable to run comparison.\n";
  exit(0);
}

$pFormat = '%-7s %-20s %-20s';
print "..using sunposa.php/USNO data for local times at\n  lat='".$dataMeta['lat']."' lon='".$dataMeta['lon']."' tz='".$dataMeta['tz']."'.\n\n";
print "Note: only USNO times coresponding to sunposa.php generated times\n";
print "      are displayed in the following tables\n\n";

print "----- Sun data compare -----\n";
print "Data values are azimuth,elevation\n\n";
printf($pFormat,'Date:',$dataMeta['date'],$calcMeta['date']);
print "\n";
printf($pFormat,"Time","USNO data","Calc data");
print "\n";
printf($pFormat,"-----","--------------","--------------");
print "\n";

foreach ($calcSun as $time => $val) {
  if(!isset($dataSun[$time])) {
    continue;
  }
  #print $time.": ".$dataSun[$time]."\t".$val."\n";
  printf($pFormat,$time,$dataSun[$time],$val) ;
  print "\n";
}

print "\n\n----- Moon data compare -----\n";
print "Data values are azimuth,elevation[,illumination if available]\n\n";
printf($pFormat,'Date:',$dataMeta['date'],$calcMeta['date']);
print "\n";
printf($pFormat,"Time","USNO data","Calc data");
print "\n";
printf($pFormat,"-----","--------------","--------------");
print "\n";
foreach ($calcMoon as $time => $val) {
  if(!isset($dataMoon[$time])) {
    continue;
  }
  printf($pFormat,$time,$dataMoon[$time],$val) ;
  print "\n";
  #print $time.": ".$dataMoon[$time]."\t".$val."\n";
}


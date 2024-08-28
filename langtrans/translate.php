<?php
header('Content-type: text/plain,charset=ISO-8859-1');
# a quick and dirty use of Google Translate API to convert an English phrase 
# Author: Ken True - webmaster@saratoga-weather.org
/*
This does require Google Translate be installed with:

composer require google/cloud-translate

Doc: https://github.com/googleapis/google-cloud-php-translate

To use on your site, you'll need a Google API key
*/
$VersionInfo = "V1.00 - 28-Aug-2024";

date_default_timezone_set('America/Los_Angeles');
header("Content-type: text/plain");
print "translate.php - V1.00 - 28-Aug-2024\n";

#-------------------------------------------------------------
# Settings:
# Google Translate Key
$key = '-specify-key-here-';

# Includes the autoloader for libraries installed with composer
# change for your installation
require 'vendor/autoload.php';
#require 'c:/xampp/vendor/autoload.php';

# our language v.s. ISO691 language name table
# uncomment the one(s) to regenerate -- recommend select no more than 5 at a time

$ISOLang = array ( // ISO 639-1 2-character language abbreviations from country domain 
#  'af' => 'af',
#  'bg' => 'bg',
#  'ct' => 'ca',
#  'cs' => 'cs',
#  'dk' => 'da',
#  'nl' => 'nl',
#  'fi' => 'fi',
#  'fr' => 'fr',
#  'de' => 'de',
#  'el' => 'el',
#  'it' => 'it',
#  'he' => 'he',
#  'hu' => 'hu',
#  'no' => 'no',
#  'pl' => 'pl',
#  'pt' => 'pt',
#  'ro' => 'ro',
#  'es' => 'es',
#  'se' => 'sv',
#  'si' => 'sl',
#	 'sk' => 'sk',
#	 'sr' => 'sr',
);

# End of Settings
#-------------------------------------------------------------

#---------------------
$inCharset = 'UTF-8';
$outCharset = 'ISO-8859-1'; // for most of the translations


# Imports the Google Cloud client library
use Google\Cloud\Translate\V2\TranslateClient;

if(strpos($key,'specify') !== false) {
  print "-- Error: Google API key not set\n";
  exit(0);
}

# 
$toTranslate = array( # English
    # 'to lookup'  =>  'replacement text'
    'Solar Noon' => 'used in Title',
    'Solar Midnight' => 'used in Title',
    'Dusk' => 'used in Title',
    'Dawn' => 'used in Title',
    'Sunset' => 'used in Title and Legend',
    'Sunrise' => 'used in Title and Legend',
    'Sunlight' => 'used in Legend',
    'hours' => 'Abbreviated \'hours\' used in Legend|hrs',
    'Afternoon' => 'used in Title',
    'Morning' => 'used in Title',
    'Civil Twilight' => 'used in Title',
    'Nautical Twilight' => 'used in Title',
    'Astronomical Twilight' => 'used in Title',
    'Twilight' => 'spare',
    'Nighttime' => 'used in Title',
    'local time' => 'used in Title',
    'Sun azimuth' => 'Abbreviated \'Sun Azimuth\' in Legend|Sun AZ',
    'Zenith' => 'Zenith',                 # used in Legend
    'Azimuth' => 'spare',
    'Sun' => 'spare',
    'Moon' => 'spare',
    'Sun elevation' => 'Abbreviated \'Sun Elevation\' in Legend|Sun EL',
    'Moon elevation' => 'Abbreviated \'Moon Elevation\' in Legend|Moon EL',
    'Moonrise' => 'used in Legend',
    'Illumination' => '\'Moon Illumination percentage\' in Legend',
    'Sun/Moon position' => 'used in Title',
    'Summer Solstice' => 'used in graph for Summer Solstice date legend|Jun 21',
    'Winter Solstice' => 'used in graph for Winter Solstice date legend|Dec 21',
    'Equinox' => 'used in graph for Spring/Autumn Equinox legend',
    );

# output character sets to use

$langCharset = array( // for languages that DON'T use ISO-8859-1 (latin) set
 'bg' => 'ISO-8859-5',
 'el' => 'ISO-8859-7',
 'he' => 'UTF-8',
 'cs' => 'ISO-8859-2', 
 'hu' => 'ISO-8859-2',
 'ro' => 'ISO-8859-2',
 'pl' => 'ISO-8859-2',
 'si' => 'ISO-8859-2',
 'sk' => 'ISO-8859-2',
 'sr' => 'ISO-8859-2',
);

$googleLang = 'en';

# Instantiates a client
global $translate;
$translate = new TranslateClient( array(
   'key' => $key,
  ) );

#------------- process --------------

foreach ($ISOLang as $toLang => $googleLang) {
	if(isset($langCharset[$toLang])) {
	  $outCharset = $langCharset[$toLang];
  } else {
		$outCharset = 'ISO-8859-1';
	}
  $tranArray = array();
  $outFile = 'lang-'.$toLang.'.txt';
  print "..Outfile '$outFile' used for output.\n";

  print "..Our lang '$toLang', googleLang '$googleLang' used\n";
  print "..Converting '$inCharset' to '$outCharset' used\n";
  $tranArray[$toLang]['charset'] = $outCharset;

  foreach ($toTranslate as $English => $rec) {
		list($Comment,$realKey) = explode('|',trim($rec).'|||');
    if(strlen($realKey)>0) {
      $key = $realKey;
      } else {
      $key = $English;
    }
		#if(substr($type,0,1) == '#') {continue;}
		#if($type == 'langlookup') {
			$TRANSLATED = doTranslate($English,$googleLang,$outCharset);
      print "$toLang:  '$English' = '$TRANSLATED', '$Comment' '$realKey'\n";
			$tranArray[$toLang][$key] = "$TRANSLATED|$Comment";
		#} else {
		#	print trim($rec)."\r\n";
		#	$output .= trim($rec)."\r\n";
		#}
	
	}
  $tranArray['version'] = "Version $VersionInfo";
  $tranArray['generated'] = date('r');
	file_put_contents($outFile,"<?php\n\$trans_$toLang =".var_export($tranArray,true).";\n");
  sleep(1); # wait a bit before next translation
}
# ---------------

function doTranslate($text,$target,$outCharset) {
global $translate;

$translation = $translate->translate($text, array(
    'target' => $target
) );
	if(!isset($translation['text'])) {
    print "-- error: '$text' not translated. \$translation = ".var_export($translation,true)."\n";
		return $text;
	} 
	$UTFtext = $translation['text'];
	$RTNtext = @iconv('UTF-8',$outCharset.'//TRANSLIT',$UTFtext);
	
	if($RTNtext !== false) {
	  return $RTNtext;
	} else {
    print "-- error: '$text' not translated. \$translation = ".var_export($translation,true)."\n";
    if(isset($translation['text'])) {
      print ".. returned UTF-8 text instead of $outCharset text.\n";
      return($translation['text']);
     } else {
		  return $text;
    }
	}
}

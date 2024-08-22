# SunMoon-graph
Create PNG image of Sun/Moon azimuth/elevation rise/set times using a PHP script with internal moon and sun calculations.

This ***sunposa.php*** script is an update and repackaging of the original script which used Weather-Display *clientrawextra.txt* data for moon information.
It is now independent of weather software used and is fully standalone.

**NOTE:** V3.02 of the script expects latitudes to be Northern Hemisphere from +25 to +70 degrees North. Values outside that range produces quirky plots at this time

Version 3.02 now uses curl to download the sun image from NASA instead of file_get_contents() function and now supports using an optional proxy server for the access.
## Contents
+  **./jpgraph-4.4.2-src-only/** A source only for [JPGraph 4.4.2](https://jpgraph.net/download/) is included (under [QPL-1.0 license](http://www.opensource.org/licenses/qtpl.php)) for convenience.  The script can be changed to use your existing JPGraph installation if desired.
+  **./cache/** storage for jpsun.png and jpmoon.png created by the script
+  **./moonimg/** collection of Northern and Southern hemisphere moon images for each day in the lunar cycle
+  **sunposa.php** the script to generate the image .png

# Settings
Internal settings in the script are shown below and are active if used outside a Saratoga website template.  Inside a Saratoga wx...php page, needed configurations are gathered from your *Settings.php* file and no additional configuration is needed.
```php
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

$dtstring   = "M j Y g:ia";         // format for the date & time in title
$dateMDY    = true;  // =true for mm/dd/yyyy, =false for dd/mm/yyyy format overridden by $SITE['WDdateMDY']
$timeOnlyFormat = 'g:ia';     //='H:i' or ='g:ia' overridden by $SITE['timeOnlyFormat']
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
```

## Usage
To use the file in a page, simply invoke it using `<img src="sunposa.php" alt="sun/moon graph"/>`

## Debugging
The script includes some helpful debugging code to check for needed PHP settings/functions.
Invoke with `https://your.website.com/sunposa.php?debug=y` and output similar to this will be shown.

```
------------------------------------------------------------------
sunposa.php Version 3.01 - 19-Aug-2024
..debug=y debugging output for sunposa.php.  PHP version 8.2.0
  php.ini setting 'allow_url_fopen = true;'  (Note: this enables fetch of sun image).
  Status of needed built-in PHP functions:
  function 'imagecreatefrompng'  is available
  function 'imagecreatefromjpeg'  is available
  function 'imagecreatefromgif'  is available
  function 'imagettfbbox'  is available
  function 'imagettftext'  is available
  function 'gregoriantojd'  is available
  lat='37.2715', lon='-122.02274', tz='America/Los_Angeles', cacheFileDir='./cache/'
  jpgraph location='./jpgraph-4.4.2-src-only/'
  moon image cache './cache/jpmoon.png exists. Updated 2024-08-19 06:31:45
  sun  image cache './cache/jpsun.png exists.  Updated 2024-08-19 08:28:18
  GD Library is available:
array (
  'GD Version' => 'bundled (2.1.0 compatible)',
  'FreeType Support' => true,
  'FreeType Linkage' => 'with freetype',
  'GIF Read Support' => true,
  'GIF Create Support' => true,
  'JPEG Support' => true,
  'PNG Support' => true,
  'WBMP Support' => true,
  'XPM Support' => true,
  'XBM Support' => true,
  'WebP Support' => true,
  'BMP Support' => true,
  'AVIF Support' => true,
  'TGA Read Support' => true,
  'JIS-mapped Japanese Font Support' => false,
)
```
Note that PHP gregoriantojd() function is required, along with the GD library with TTF functions enabled.

## Sample output at various times

![sunposa-0](https://github.com/user-attachments/assets/1b534200-22bf-48f1-baa1-613c9521a9dc)

![sunposa-1](https://github.com/user-attachments/assets/2ce76ec7-72e0-4b4c-9a60-bfbf88da6b8b)

![sunposa-3](https://github.com/user-attachments/assets/c05b9f09-99d5-4283-bbe9-d90ec5cd32c6)

![sunposa-4](https://github.com/user-attachments/assets/4f8e337f-2d49-465a-af9b-6b6c2c62f319)

![sunposa-5](https://github.com/user-attachments/assets/44f9ebc6-9639-4702-8739-f1b032db4841)

![sunposa-6](https://github.com/user-attachments/assets/6fbbcab4-3e23-46a4-9d65-21b1acf75f0d)

![sunposa-8](https://github.com/user-attachments/assets/9813be36-0ac9-41cf-a072-eb6f8df923eb)











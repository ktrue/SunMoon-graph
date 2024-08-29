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
+  **sunposa-lang.php** (new V3.50) the language lookup for legends
+  **wxastronomy.php** replacement page in Saratoga template to display the graphic
+  **./langtrans/** directory for raw language translations in native character sets (see below to update your own **sunposa-lang.php** )

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

$dateMDY    = false;  // =true for mm/dd/yyyy, =false for dd/mm/yyyy format overridden by $SITE['WDdateMDY']
$timeOnlyFormat = 'H:i';     // ='H:i' or ='g:ia' overridden by $SITE['timeOnlyFormat']
$dtstring   = "M j Y";  // ='M j Y ' for Mon d yyyy format for the date & time in title
#
# you likely do not have to configure the following:
$daycolor   = 'lightskyblue';
$ctlcolor   = 'skyblue:0.6';           // Civil Twilight
$ntlcolor   = 'skyblue:0.6';           // Nautical Twilight
$atlcolor   = 'midnightblue:0.9';           // Astronomical Twilight
$nightcolor = 'midnightblue:0.7';
$dawncolor  = 'lightskyblue:0.4';
$zenith = 90.83333;
# customize default languages
$lang = 'en';  # Default language for legends (see set_legends() function for configuration)
# test:
#$lat=55.8983; $lon=-3.2077; $tz='Europe/London';
#
# optional uncomment to use Weather-Display clientrawextra.txt for moon instead of<br />
#  the internal calculations
#
# $crextrafile = "./clientrawextra.txt"; // set location of WD clientrawextra file
#
# optional if proxy used - uncomment to use. Leave commented if no proxy server needed
# $myProxy = 'proxyip:port';
#
# optional uncomment to enable export of sun/moon data to ./calc-sunmoon-data.php for debugging
# and comparison with USNO using get-usno-data=>usno-sunmoon-data.php and check-sunmoon-data.php
$doLog = true;
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
sunposa.php Version 3.50 - 28-Aug-2024
..debug=y debugging output for sunposa.php.  PHP version 8.1.29

  Status of needed built-in PHP functions:
  function 'imagecreatefrompng()'  is available
  function 'imagecreatefromgif()'  is available
  function 'imagecreatetruecolor()'  is available
  function 'imagecolortransparent()'  is available
  function 'imagettfbbox()'  is available
  function 'imagettftext()'  is available
  function 'gregoriantojd()'  is available
  function 'curl_init()'  is available
  function 'curl_setopt()'  is available
  function 'curl_exec()'  is available
  function 'curl_error()'  is available
  function 'curl_getinfo()'  is available
  function 'curl_close()'  is available

  Settings used:  lat='37.27153397', lon='-122.02274323', tz='America/Los_Angeles', cacheFileDir='./cache/'
  jpgraph location='./jpgraph-4.4.2-src-only/'
  Using internal calculations for moon data.
  moon image cache './cache/jpmoon.png exists. Updated 2024-08-28 09:00:03
  sun  image NASA  './cache/tempImg.gif exists.  Updated 2024-08-28 16:27:51
  sun  image cache './cache/jpsun.png exists.  Updated 2024-08-28 16:27:51

  GD Library is available and has these capabilities:
    GD Version: bundled (2.1.0 compatible)
    FreeType Support: true
    FreeType Linkage: with freetype
    GIF Read Support: true
    GIF Create Support: true
    JPEG Support: true
    PNG Support: true
    WBMP Support: true
    XPM Support: true
    XBM Support: true
    WebP Support: true
    BMP Support: true
    AVIF Support: false
    TGA Read Support: true
    JIS-mapped Japanese Font Support: false```

Note that PHP gregoriantojd() function is required, along with the GD library with TTF functions enabled.
```
## Changing the language translations

In the **./langtrans/** directory are the individual language files.  Please see the **README-langtrans.txt** file
in that directory for instructions on how to change the language presented.  It's a bit tricky to do, but following the instructions there should result in success. Good luck...

## Checking the computed data v.s. USNO

Two utility programs are included to do a comparison of the azimuth,elevation values that are computed in **sunposa.php** versus the US Naval Observatory data for the same date.

In **sunposa.php** the '$doLog = true;` allows creation of **calc-sunmoon-data.php**, one of the files needed by the utility.

Run **get-usno-data.php** to create **usno-sunmoon-data.php** in the same directory.

Then you can run **check-sunmoon-data.php** to compare the results.  The output to your browser looks like:

## Sample output with various languages

![sunmoon-sample-en](https://github.com/user-attachments/assets/c4cdf96f-9adb-4595-9c2a-be925f0e2d97)

![sunmoon-sample-es](https://github.com/user-attachments/assets/a8e17218-85b1-40e7-815c-79d166412237)

![sunmoon-sample-fr](https://github.com/user-attachments/assets/330a546c-ce02-45f5-91f4-b5b197e1efea)

![sunmoon-sample-el](https://github.com/user-attachments/assets/747000b9-c20d-44b1-9790-f6e52ba0fc86)

![sunmoon-sample-pl](https://github.com/user-attachments/assets/4d995dd6-bf10-4227-8986-d7276fdc2277)






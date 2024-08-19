# SunMoon-graph
Create PNG image of Sun/Moon azimuth/elevation rise/set times using a PHP script with internal moon and sun calculations.

This ***sunposa.php*** script is an update and repackaging of the original script which used Weather-Display *clientrawextra.txt* data for moon information.
It is now independent of weather software used and is fully standalone.

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
###############################################################
#End of settings                                              #
###############################################################
```

## Usage
To use the file in a page, simply invoke it using `<img src="sunposa.php" alt="sun/moon graph"/>`

## Sample output at various times

![sunposa-0](https://github.com/user-attachments/assets/1b534200-22bf-48f1-baa1-613c9521a9dc)

![sunposa-1](https://github.com/user-attachments/assets/2ce76ec7-72e0-4b4c-9a60-bfbf88da6b8b)

![sunposa-3](https://github.com/user-attachments/assets/c05b9f09-99d5-4283-bbe9-d90ec5cd32c6)

![sunposa-4](https://github.com/user-attachments/assets/4f8e337f-2d49-465a-af9b-6b6c2c62f319)

![sunposa-5](https://github.com/user-attachments/assets/44f9ebc6-9639-4702-8739-f1b032db4841)

![sunposa-6](https://github.com/user-attachments/assets/6fbbcab4-3e23-46a4-9d65-21b1acf75f0d)

![sunposa-8](https://github.com/user-attachments/assets/9813be36-0ac9-41cf-a072-eb6f8df923eb)











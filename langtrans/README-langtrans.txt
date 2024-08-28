README-langtrans.txt Version 1.00 - 28-Aug-2024

The ./langtrans/ directory contains individual lang-LL.txt files for each language supported.
The initial lang-LL.txt files were generated using Google Translate V1 API to translate
the English legends to the target language and character set compatible with the Saratoga
template set.

Most translations use ISO-8859-1 (Western European).  Some translations use other
character sets.  Each file has a 'charset' => '...', entry indicating which encoding
should be used.

DO NOT EDIT sunposa-lang.php directly .. doing so may screw up other translations.

To make changes in a translation:

1) Open the langtrans/lang-LL.txt file with Notepad++ and (important!) set the Encoding to match the encoding of the file
   that is indicated by 'charset' => 'ISO-8859-n' in the file.
   (Windows Notepad won't work well for non-ISO-8859-1 languages and UTF-8 editors may make the legends undisplayable)
   
   Get Notepad++ for free from https://notepad-plus-plus.org/downloads/

   In Notepad++ the following names for character sets are used:
   ISO-8859-1 is Western European
   ISO-8859-2 is Eastern European (used for 'cs','hu','ro','pl','si','sk')
   ISO-8859-5 is Cyrillic (used for 'bg'
   ISO-8859-7 is Greek (used for 'el'
   
2) Make your changes as needed, and save the file.
3) run langtrans/gen-sunposa-lang.php script in your browser
4) copy langtrans/sunposa-lang.php to ../sunposa-lang.php

If you make updates to the lang-LL.txt file(s), please send them as .zip
to me at webmaster at saratoga-weather.org so I can update the distribution.

To create a new language translation:
1) copy langtrans/lang-en.txt to langtrans/lang-LL.txt (LL= 2 character ISO language code)
2) edit the new lang-LL.txt file in Notepad++ and set the appropriate coding as shown above.
3) change :

$trans_en =array (
  'en' => 
  array (
    'charset' => 'ISO-8859-1',
to

$trans_LL =array (
  'LL' => 
  array (
    'charset' => 'ISO-8859-N',

where LL= 2 character ISO language code and N is the ISO language set used.

4) make your changes in the appropriate array entries like:

change:

    'Solar Noon' => 'Solar Noon|used in Title',

to

    'Solar Noon' => 'new phrase for Solar Noon|used in Title',

5) save the new file and run the langtrans/gen-sunposa-lang.php script to implement.
   copy langtrans/sunposa-lang.php to ../sunposa-lang.php


Thanks!

K. True - 28-Aug-2024

<?php
/**
 * Example that use SubtitleManager in console
 *
 * @author César Rodríguez <kesarr@gmail.com>
 * @example php console.php <filepath>
 */

require_once 'SubtitlesManager.php';

if (empty($argv[1])) {
    echo 'error! you must supply a file';
    return 1;
} 
// if (!is_file($argv[1])) {
//     echo 'error! file ' . $argv[1] . ' doesnt exist';
//     return 1;
// }

$config = parse_ini_file(__DIR__ . '/config.ini');
$manager = new OpenSubtitles\SubtitlesManager();
$sub = $manager->getSubtitleUrls($argv[1], true);
if (!empty($sub)) {
	print_r($sub);
     $manager->downloadSubtitle($sub['fre']);
//      $manager->downloadSubtitle($sub['eng'], 'eng.'.$argv[1]);
} else {
    echo 'error! impossible to find the subtitle';
}


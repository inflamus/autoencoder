<?php

define('CPASBIEN_URL', 'http://www.cpasbien.cm');


if(!empty($_GET['torrent']))
{
	// /telechargement/adieu-au-langage-french-bluray-720p-2014.torrent
	if(preg_match('/(\/telechargement\/.+\.torrent)/', file_get_contents($_GET['torrent']), $match))
		exit(header('Location: '.CPASBIEN_URL . $match[1]));
	else
		exit('couldnt find torrent in page.');
}

$search = !empty($_GET['search']) ? preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($_GET['search'])) : '720p';
$page = !empty($_GET['page']) ? (int) $_GET['page'] : 1;
$pagesuiv = $page +1;

// Html : 

print '<html><body>';
print '<form method=get><input type=text placeholder=Search name=search value="'.$search.'" /> <button type=submit name=sub value=Find>Find</button>';
print "<button type=submit name=page value=$pagesuiv>Page $pagesuiv</button></form>";
print '<br /><br />';

$url = CPASBIEN_URL . '/recherche/' . $search . '/page-'.$page;

preg_match_all('/<a href="(.+)" title=".+" class="titre">(.+)<\/a><div class="poid">(.+)<\/div><div class="up"><span class="seed_ok">([0-9]+)<\/span><\/div><div class="down">([0-9]+)<\/div>/U', 
	file_get_contents($url),
	$matches);
	
foreach($matches[0] as $k => $useless)
{
	print '<p><a href="index.php?torrent='.urlencode($matches[1][$k]).'">'.$matches[2][$k].'</a>';
	print ' - '.$matches[3][$k].' - Seed : '.$matches[4][$k].' - Leechs : '.$matches[5][$k].'</p>';
}


print '</body></html>';
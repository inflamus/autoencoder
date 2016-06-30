#!/usr/bin/php
<?php

require('inc/FreeMobile.php');
require('inc/CliColors.php');
require('inc/Notify.php');
$Color = new Colors();
if(!empty($argv[1]))
{
	try{
		FreeMobile::__New()->Send($argv[1]);
	}catch(Exception $e)
	{
		print "Error : ".$e->getMessage();
		Notify::Send('MP3 Encoder', $argv[1]);
	}
	exit();
}

// Console Mode :
print "---------------------------------------------\n";
print "--------------".$Color->getColoredString(" FreeMobile SMS ", 'b_white', 'red')."---------------\n";
print "---------------------------------------------\n";
print $Color->getColoredString("Entrez votre message.", 'u_white')."\n";

try{
	if(!empty($input = trim(fgets(STDIN))))
		if(FreeMobile::__New()->Send($input))
			print "\nEnvoyé.";
}catch(Exception $e)
{
	print "Une erreur est survenue : ".$Color->getColoredString($e->getMessage(), 'b_red');
}
exit();
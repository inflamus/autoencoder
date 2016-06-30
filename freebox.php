#!/usr/bin/php
<?php

require_once('inc/FreeboxApi.php');
require_once('inc/CliColors.php');
$Color = new Colors();

if(!empty($argv[1]))
{
	try{
		$Freebox = new FreeboxAPI();
		$Freebox = $Freebox->Downloads();
		try{
			for($i=1; $i<$argc;$i++)
				$Freebox->Add($argv[$i]);
		}catch(Exception $ee)
		{
			print $Color->getColoredString($ee->getMessage(), 'b_red');
		}
		print $Color->getColoredString("Téléchargements ajoutés.", 'green');
	}catch(Exception $e)
	{
		print "Error : \n".$Color->getColoredString($e->getMessage(), 'b_red');
	}
}
else
{
	// Console Mode :
	print "---------------------------------------------\n";
	print "----------------".$Color->getColoredString(' Freebox API ', 'b_white', 'red')."----------------\n";
	print "---------------------------------------------\n";
	print "Une ligne par téléchargement, ou plusieurs séparés par une virugle.\n";
	print "If you skip, you'll see the active downloads.\n";
	try{
		$Freebox = new FreeboxAPI();
		$Freebox = $Freebox->Downloads();
		try{
			if(!empty($input = trim(fgets(STDIN))))
				foreach(explode(",", $input) as $l)
					if(!empty($l))
						exit($Freebox->Add($l));
		}catch(Exception $e)
		{
			print $Color->getColoredString($e->getMessage(), 'b_red');
		}
// 		print "\nEnvoyé.";
	}catch(Exception $e)
	{
		print "Une erreur est survenue : ".$Color->getColoredString($e->getMessage(), 'b_red');
	}
	
	$Status = array(
		'done' => 'green',
		'seeding' => 'b_green',
		'downloading' => 'b_yellow',
		'stopped' => 'white',
		'checking' => 'b_cyan',
		'error' => 'b_red',
		'starting' => 'yellow',
		'stopping' => 'b_white',
		'queued' => 'cyan',
		'retry' => 'blue',
		'extracting' => 'magenta',
		'repairing' => 'b_magenta',
	);
	
	$continue = true;
	while($continue)
	{
	
	print "\n------------ ".$Color->getColoredString('Liste des Downloads', 'u_white')." ------------\n";
	foreach($Freebox->getList() as $id => $DL)
	print "[$id]\t".$Color->getColoredString($DL->Progress()."%\t".$DL->name, $Status[$DL->status])."\n";
	print "More Infos ? type ID or (".$Color->getColoredString('R', 'u_white').")efresh : ";
	if(strtolower($id = trim(fgets(STDIN))) == 'refresh')
	{
		print "Refresh isnt working yet.";
		continue;
	}
	if(empty($id))	break;
	$id = (int)$id;
	if(!$DL = $Freebox->Get($id))
	{
		print "Error : ".$Color->getColoredString('Wrong ID', 'b_red')."\n";
		sleep(1);
		continue;
	}
	print $Color->getColoredString("[$id]", 'inverted_white')."\t".$Color->getColoredString($DL->name, $Status[$DL->status])."\n";
	print 	"\t\tStatus : ".$Color->getColoredString($DL->status, $Status[$DL->status])."\n";
	print 	"\t\tProgress : ".$Color->getColoredString($DL->Progress().'%', $Status[$DL->status]).($DL->status == 'downloading' ? " (".$DL->Downloaded()." / ".$DL->Size().")" : '')."\n";
	print 	"\t\tSpeed : ".$Color->getColoredString($DL->DownloadSpeed(), $Status['downloading'])." | ".$Color->getColoredString($DL->UploadSpeed(), $Status['seeding'])."\n";
	print 	"\t\tTaille : ".$DL->Size()."\n";
	print 	"\t\tRatio : ".$DL->Ratio()." / ".$DL->StopRatio()." (Uploaded : ".$DL->Uploaded().")\n";
	print 	"\t\tETA : ".$Color->getColoredString($DL->ETA(), 'cyan')."\n";
	print 	"\t\tPriority : {$DL->io_priority}\n";
	print 	"\t\tDownload Directory : ".$DL->Directory()."\n";
	print 	"\t\tCreation date : ".$DL->CreationDate()."\n";
	print 	"\t\tError : {$DL->error}\n";
	print "What to do ? (".$Color->getColoredString('P', 'u_white').")ause ? (".$Color->getColoredString('R', 'u_white').")esume ? (".$Color->getColoredString('D', 'u_white').")elete ? Delete and (".$Color->getColoredString('E', 'u_white').")rase ? change priority to (".$Color->getColoredString('H', 'u_white').")igh, (".$Color->getColoredString('N', 'u_white').")ormal, (".$Color->getColoredString('L', 'u_white').")ow ? or Choose (".$Color->getColoredString('A', 'u_white').")nother DL ID.";
	switch(strtolower(trim(fgets(STDIN))))
	{
		default:
			print "Nothing to do. Exiting.\n";
			$continue = false;
		break;
		case 'a': print "Choosing another.\n";	break;
		case 'p': $DL->Pause();			break;
		case 'r': $DL->Resume();		break;
		case 'd': $DL->Delete();		break;
		case 'e': $DL->Delete(true);		break;
		case 'h': $DL->HighPriority();		break;
		case 'n': $DL->NormalPriority();	break;
		case 'l': $DL->LowPriority();		break;
	}
	
	}
}
exit();

?>
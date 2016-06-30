<?php

#define('AACENCODER', 'nero'); //neroaacenc (Nero)
#define('AACENCODER', 'nero2pass'); //neroaacenc (Nero) -2pass with wav temp file
#define('AACENCODER', 'ct'); //coding technologies (winamp) enc_aacplus
define('SRT_STATE_SUBNUMBER', 0);
define('SRT_STATE_TIME',      1);
define('SRT_STATE_TEXT',      2);
define('SRT_STATE_BLANK',     3);
//print_r($_POST);
//exit();
chdir('/home/romein/Downloads/');
$arr = array();
$subs = array();
if(isset($_POST['file']))
	foreach($_POST['file'] as $f)
	{
	$s = array();
		exec('mediainfo --output=XML "'.$f.'"', $s);
		$x = simplexml_load_string(implode('', $s));
		//print_r($x);

		$title = (isset($x->File->track[0]->Movie_name) ? 
			(string)$x->File->track[0]->Movie_name : 
			format_name($x->File->track[0]->Complete_name));
	/*		$duree = format_date($x->File->track[0]->Duration) ;
*/
		$movie = new ffmpeg_movie($f);

		//$title = ($movie->getTitle()  ? $movie->getTitle() : $f);
		$duration = round($movie->getDuration());

		$thumb = md5($f).'.jpg';
		if(!file_exists($thumb))
			@exec('ffmpegthumbnailer -i "'.$f.'" -o "'.$thumb.'" -s 0');
		
	$subs += glob(dirname($f).'/*.srt');
		$audio = array();
		foreach($x->File->track as $t)
		{
			if(in_array('Audio', current($t->attributes())))
				$audio[] = array('lang' => (isset($t->Language) ? (string)$t->Language : 'Undefined'), 'Channels' => (int) $t->Channel_s_, 'Codec' => (string)$t->Format);
			if(in_array('Video', current($t->attributes())))
				$video = array('AR' => (($t->Display_aspect_ratio=='16:9' || $t->Display_aspect_ratio=='4:3') ? (string) $t->Display_aspect_ratio : (float)$t->Display_aspect_ratio), 'Width' => (int) str_replace(' ', '', $t->Width), 'Height' => (int)$t->Height); 
		}		

		//print $title.$duration;
		// affiches
		// http://api.allocine.fr/rest/v3/search?partner=YW5kcm9pZC12M3M&filter=movie,theater,person,news,tvseries&count=5&page=1&q=avatar&format=json
		$arr[] = array('thumb' => $thumb, 'duration' =>$duration, 'title' => $title, 'file' => $f, 'audio' => $audio, 'video' => $video);
		
	}

if(isset($_POST['f']))
	foreach($_POST['f'] as $f)
	{
		$out = "#!/bin/sh\n# ".$f['file']."\n#".$f['title']."\n";
		
		if(substr($f['basedir'], -1, 1) != '/')
			$f['basedir'] .= '/';
		// Video filters 			
		$vfilter = array();
		if(!empty($f['cropheight']))
			$vfilter[] = 'crop='.$f['cropwidth'].':'.$f['cropheight'].':'.$f['cropx'].':'.$f['cropy'];
		if(!empty($f['HQDN3D']))
			$vfilter[] = 'hqdn3d='.$f['HQDN3D'];
		//more filters
			$vfilter[] = 'scale='.$f['Width'].':'.$f['Height'];
		
		
		$audio = array();
		$subs = array();
		$s = 0;
		//changed $out to exec, to dump directly posters while internet connection is active for sure
		exec("wget \"".$f['poster']."\" -O ".md5($f['file']).'.poster.jpg'."\n");
		$poster = ($f['poster'] != '' && file_exists(md5($f['file']).'.poster.jpg') && filesize(md5($f['file']).'.poster.jpg') != 0) ? 
			true :
			false;
		foreach($f as $k=>$v)
		{
			if(preg_match('/audio-([\w]+)-([\w]+)/i', $k, $matches))
			{
				$audio[$matches[2]]['lang'] = format_lang($matches[2]);
				$audio[$matches[2]][$matches[1]] = $v;
				$audio[$matches[2]]['outfile'] = md5($f['file']).'.'.$matches[2].'.m4a';
			}
			if(preg_match('/subs[0-9]+-([\w]+)/i', $k, $matches))
			{
				$subs[++$s]['lang'] = $matches[1];
				$subs[$s]['file'] = $v;
			}
		}

		switch($f['encodemethod'])
		{
			default:
			case 'bitrate':
				foreach($audio as $a)
					if((int)$a['br'] != 0)
						switch($f['aud-encode'])
						{
							case 'ct':
								$out .= 'ffmpeg -i "'.$f['file'].'" -filter:a "volume=1.1" -map '.getaudiobylang($f['file'], $a['lang']).' -vn -ac '.($c = ($a['br']<82 ? '2' : '6')).' -c:a pcm_s16le -f u16le - | wine enc_aacplus - "'.$a['outfile'].'" '.($a['br'] > 256 ? '--lc' : '--he').(($c == 2 && $a['br']<=56) ? ' --ps' : '').' --br '.$a['br'].'000 --rawpcm 48000 '.$c.' 16'."\n";
							break;
							default:
						 	case 'nero':
								$out .= 'ffmpeg -i "'.$f['file'].'" -map '.getaudiobylang($f['file'], $a['lang']).' -vn -ac '
								.$c = ($a['br'] < 82 ? '2' : '6').($a['codec']=='DTS' ? ' -filter:a "volume=0.88"' : ''). ' -c:a pcm_s16le -f wav - | neroAacEnc -if - -of "'.$a['outfile'].'" -br '
								.$a['br'].'000 -2passperiod 0 -ignorelength'."\n";
							break;
							case 'nero2pass':
								$out .= 'ffmpeg -i "'.$f['file'].'" -map '.getaudiobylang($f['file'], $a['lang']).' -vn -ac '
								.$c = ($a['br'] < 82 ? '2' : '6').($a['codec']=='DTS' ? ' -filter:a "volume=0.88"' : ''). ' -c:a pcm_s16le -f wav "'.$a['outfile'].'.wav"'."\n".
								'neroAacEnc -if "'.$a['outfile'].'.wav" -of "'.$a['outfile'].'" -br '
								.$a['br'].'000 -2pass'."\n".
								'rm "'.$a['outfile'].'.wav"'."\n";
							break;
						}
						
				$out .= 'ffmpeg -i "'.$f['file'].'" '.(!empty($vfilter) ? '-filter:v "'.implode(',', $vfilter).'" ' : '').'-aspect '.$f['AR'].' -an -sn -c:v libx264 -bf 16 -pass 1 -b:v '.$f['videobitrate'].'k -f matroska -y /dev/null'."\n";
				$out .= 'ffmpeg -i "'.$f['file'].'" '.(!empty($vfilter) ? '-filter:v "'.implode(',', $vfilter).'" ' : '').(!empty($f['NR']) ? '-nr '.$f['NR'].' ' : '').'-aspect '.$f['AR'].' -an -direct-pred 3 -sn -rc-lookahead 55 -c:v libx264 -bf 16 -pass 2 -b:v '.$f['videobitrate'].'k -subq 9 -me_method umh -me_range 20 -refs 5 -trellis 1 -y "'.md5($f['file']).'.video.mkv"'."\n";

			break;
			case 'quality':
				switch($f['audio-engine'])
				{
					default:
					case 'fdk':
						$out .= 'ffmpeg -i "'.$f['file'].'" ';
						$maps = array();
						$curr_i = 0;
							foreach($audio as $a)
							{
								$g=(getaudiobylang($f['file'], $a['lang']));
								//$ci = '0:'.$curr_i++;
								$ci = $curr_i++;
								$out .= '-map '.$g.' -c:a libfdk_aac -ac:'.$ci.' '.$a['qch'].' '.($a['aq'] < 0.32 ? 
									(($a['aq'] < 0.25 && $a['qch'] == 2) ? '-profile:a:'.substr($ci, -1).' aac_he_v2 ' : '-profile:a:'.substr($ci, -1).' aac_he ')
										.'-b:a:'.$ci.' '.$a['aq']*32*5*$a['qch'].'k' : 
									'-vbr:'.$ci.' '.round($a['aq']*5, 2)).' '; 
								$maps[] = $g;
							}
						$out .= (!empty($vfilter) ? '-filter:v "'.implode(',', $vfilter).'" ' : '').(!empty($f['NR']) ? '-nr '.$f['NR'].' ' : '').'-aspect '.$f['AR'].' -direct-pred 3 -rc-lookahead 90 -me_range 20 -sn -c:v libx264 -bf 16 -crf '.$f['videocrf'].' -subq 9 -me_method umh -refs 5 -trellis 1 -map '.getvideotrack($f['file']).' ';

						$out .='-y "'.md5($f['file']).'.video.mkv"'."\n";
					break;
					case 'nero':
						foreach($audio as $a)
							if((float)$a['aq'] != 0.0)
								$out .= 'ffmpeg -i "'.$f['file'].'" -map '.getaudiobylang($f['file'], $a['lang']).' -vn -ac '.$a['qch'].' -c:a pcm_s16le -f wav - | neroAacEnc -if - -of "'.$a['outfile'].'" -ignorelength -q '.$a['aq']."\n";
						$out .= 'ffmpeg -i "'.$f['file'].'" '.(!empty($vfilter) ? '-filter:v "'.implode(',', $vfilter).'" ' : '').(!empty($f['NR']) ? '-nr '.$f['NR'].' ' : '').'-aspect '.$f['AR'].' -an -direct-pred 3 -rc-lookahead 90 -me_range 20 -sn -c:v libx264 -bf 16 -crf '.$f['videocrf'].' -subq 9 -me_method umh -refs 5 -trellis 1 -y "'.md5($f['file']).'.video.mkv"'."\n";
					break;
				}
			break;
			case 'serie':
				$out .= 'ffmpeg -i "'.$f['file'].'" ';
				$out .= (!empty($vfilter) ? '-filter:v "'.implode(',', $vfilter).'" ' : '').(!empty($f['NR']) ? '-nr '.$f['NR'].' ' : '').'-aspect '.$f['AR'].' -direct-pred 3 -rc-lookahead 90 -me_range 20 -sn -c:v libx264 -bf 16 -crf '.$f['videocrf'].' -subq 9 -me_method umh -refs 5 -trellis 1 -c:a libfdk_aac '.($f['audio-aq'] < 80 ? ($f['audio-aq'] < 52 ? '-profile:a aac_he_v2 ' : '-profile:a aac_he ').'-b:a '.$f['audio-aq'].'k' : '-vbr '.round($f['audio-aq'] / 64 , 2)).' -ac 2 -metadata title="'.$f['title'].'" -y "'.md5($f['file']).'.video.mkv"'."\n";
				$out .=	'mkvmerge -o "'.$f['basedir'].trim(preg_replace(array('/:/', '/\([0-9]+\)/'), array('-', ''), $f['title'])).'.mkv" --title "'.$f['title'].'" "'.md5($f['file']).'.video.mkv"';			
				foreach($subs as $s)
				{
					strip_HI($s['file']);
					$out .= ' --language 0:'.$s['lang'].' "'.$s['file'].'"';
				}
			break;
		}
		
		if(in_array($f['encodemethod'], array('quality', 'bitrate')))
		{
			$out .=	'mkvmerge -o "'.$f['basedir'].trim(preg_replace(array('/:/', '/\([0-9]+\)/'), array('-', ''), $f['title'])).'.mkv" --title "'.$f['title'].'" '.($poster ? '--attachment-description cover --attach-file '.md5($f['file']).'.poster.jpg ' : '').'-A -D "'.$f['file'].'" "'.md5($f['file']).'.video.mkv"';
			if($f['audio-engine'] != 'fdk')
				foreach($audio as $a)
					$out .= ' --language 0:'.$a['lang'].' "'.$a['outfile'].'"';
			if(!empty($subs))
				foreach($subs as $s)
				{
					strip_HI($s['file']);
					$out .= ' --language 0:'.$s['lang'].' "'.$s['file'].'"';
				}

		}
		$out .= "\nmv ".md5($f['file']).'.sh '.md5($f['file']).'.end';
		file_put_contents(md5($f['file']).'.sh', $out);
		unlink(md5($f['file']).'.jpg');
	}



function strip_HI($file)
{
$lines   = file($file);

$subs    = array();
$state   = SRT_STATE_SUBNUMBER;
$subNum  = 0;
$subText = '';
$subTime = '';

foreach($lines as $line) {
    switch($state) {
        case SRT_STATE_SUBNUMBER:
            $subNum = trim($line);
            $state  = SRT_STATE_TIME;
            break;

        case SRT_STATE_TIME:
            $subTime = trim($line);
            $state   = SRT_STATE_TEXT;
            break;

        case SRT_STATE_TEXT:
            if (trim($line) == '') {
                $sub = new stdClass;
                $sub->number = $subNum;
                list($sub->startTime, $sub->stopTime) = explode(' --> ', $subTime);
                $sub->text   = $subText;
                $subText     = '';
                $state       = SRT_STATE_SUBNUMBER;

                $subs[]      = $sub;
            } else {
                $subText .= $line;
            }
            break;
    }
}


// Strip Hearing impaired texts
$i = 1;
$text = '';
foreach($subs as $s)
{
$string = $s->text;
	$string = preg_replace('/\[.+\]/', '', $string); // Strip Hearing Impaired
	$string = preg_replace('/\(.+\)?/', '', $string); // Strip HI
	$string = preg_replace('/[A-ZÉ ]* ?: +/', '', $string);  // Strip Names of actors
	$string = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $string); // strip empty lines
	if(!preg_match('/[\S]/', $string))
		continue;
	$text .= $i++ ."\n";
	$text .= $s->startTime." --> ".$s->stopTime."\n";
	$text .= trim($string); // strip empty lines
	$text .= "\n\n"; 
}

return file_put_contents($file, $text);
}

function format_name($s)
{
	//rreplaces
	$s = basename($s, '.mkv');
	$s = str_replace('.', ' ', $s);
	$s = preg_replace(
		array(
				'/HDTV/i'	,
				'/x264/i'	,
				'/720p/'	,
				'/1080p/'	,
				'/FRENCH/i'	,
				'/MULTI/i'	,
				'/BluRay/i'	,
				'/-DIMENSION/'	,
				'/-IMMERSE/'	,
				'/-LOL/'	,
				'/-ULSHD/i'	,
				'/-CARPEDIEM/'	,
				'/-LOST/i'	,
				'/-NERDHD/i'	,
				'/-ROUGH/i'	,
				'/-SEIGHT/i'	,
				'/-TMB/'	,
				'/-TBL/'	,
				'/Link to /'	,
				'/AC3/'		,
				'/DTS/'		,
				'/DTS-HDMA/'	,
				'/DTS-HD MA/'	,
				'/VFF/'		,
				'/[0-9]{4}/'	,
				'/LiMiTED/i'	,
				'/UNRATED/i'	,
				'/PROPER/i'		,
			), 
		'',
		$s);
	$s = preg_replace('/^(.+)s0?([1-9]{1,2})e([0-9]{1,2})/i', '$1 $2x$3 - ', $s);
	$s = preg_replace('/ +/', ' ', $s);
return $s;
}

function getaudiobylang($file, $lang)
{
	$s = array();
	exec('ffmpeg -i "'.$file.'" 2>&1', $s);
	if($lang=='und')
		preg_match('/Stream \#(0:[0-9]+): Audio/', implode('',$s), $matches);
	else
		preg_match('/Stream \#(0:[0-9]+)\('.$lang.'\): Audio/', implode('',$s), $matches);
	return $matches[1]; 
}

function getvideotrack($file)
{
	$s = array();
	exec('ffmpeg -i "'.$file.'" 2>&1', $s);
		preg_match('/Stream \#(0:[0-9]+)(\([a-z]+\))?: Video/', implode('',$s), $matches);
	return $matches[1]; 
}

function format_lang($lang)
{
	switch($lang)
	{
		default:
		case 'Undefined':
		case 'Und':
			return 'und';
		break;
		case 'German':
			return 'ger';
		break;
		case 'French':
			return 'fre';
		break;
		case 'English':
			return 'eng';
		break;
		case 'Zulu':
			return 'zul';
		break;
		case 'Japanese':
			return 'jpn';
		break;
		case 'Korean':
			return 'kor';
		break;
		case 'Chinese':
			return 'chi';
		break;
		case 'Spanish':
			return 'spa';
		break;
	}
}
 
function format_ar($w, $h)
{
	switch(round($w/$h, 2))
	{
		default:
		
	}
}

function format_date($s)
{
	preg_match('/(([0-9]+)h )?([0-9]{1,2})mn/', $s, $matches);
	return 3600*(int)$matches[2] + 60*(int)$matches[3]; //return uin seconds
}

$subs = glob('*/*.srt');

header('content-type: text/html;charset=utf-8');
?>

<!doctype html>
<html>
<head>
<style type=text/css>

img.active{
	margin: 0 8px;
		-webkit-box-shadow: 0 8px 6px -6px black;
	   -moz-box-shadow: 0 8px 6px -6px black;
	        box-shadow: 0 8px 6px -6px black;
}
div{

}
</style>
<link rel=stylesheet type=text/css href=/jquery.Jcrop.min.css>
<link rel=stylesheet type=text/css href=/css/ui-lightness/jquery-ui-1.9.1.custom.min.css>
 <style>
#feedback { font-size: 1.4em; }
#selectable .ui-selecting { background: #FECA40; }
#selectable .ui-selected { background: #F39814; color: white; }
#selectable { list-style-type: none; margin: 0; padding: 0; width: 60%; }
#selectable li { margin: 3px; padding: 0.4em; height: 14px; }
</style>
</head>

<body>

<form method=post>
<ul>
<?php 
chdir('/home/romein/Downloads');
$i=0;
foreach(glob('./Downloads_*/*.mkv') as $v)
{
	print '<li><input type=checkbox id="'.++$i.'" name=file[] value="'.$v.'" /><label for='.$i.'> '.$v.'</label></li>';
}
?>
</ul>
<input value=Selectioner type=submit />
</form>
<script type=text/javascript src=/jquery.min.js></script>
<script type=text/javascript src=/jquery.Jcrop.min.js></script>
<script type=text/javascript src=/jquery-ui.min.js></script>
<script type=text/javascript src=CryptoJS.js></script>
<script type=text/javascript>
	var data = JSON.parse('<?php echo addslashes(json_encode($arr)); ?>');
	var subs = JSON.parse('<?php echo addslashes(json_encode($subs)); ?>');
$(document).ready(function(){
	$(data).each(function(){
		new M(this.title, this.duration, this.thumb, this.file, this.audio, this.video);	
	
	});
});

function M (title, duration, thumb,  file, audiotracks, video)
{
	this.title = title;
	this.duration = duration;
	this.thumb = thumb;
	
	var that = this;
	$('body').append(
	$('<form class=foo>').append(
		$('<fieldset>').append(
			$('<legend>').append(
				this.inputtitle = $('<input>', {'type':'text', 'name':'title'}).val(title).on('blur', null, that, function(e)
				{
					console.log(e.data);
					e.data.allocine($(this).val());
				})
			)
		).append(
			'Output Basedir : <input type=text name=basedir value="./Downloads_Verbatim/Films/"><br>'
		).append(
			this.inputposter = $('<input type=hidden name=poster>')
		).append(
			this.inputsubs = $('<div class=inputsubs></div>')
		).append(
			this.inputfile = $('<input type=hidden name=file value="'+file+'">')
		).append(
			this.inputvideobitrate = $('<input type=hidden name=videobitrate value=0>')
		).append(
			this.encodemethod = $('<input type=hidden class=encodemethod name=encodemethod value=bitrate>')
		)
		.append(
			$('<div>').append(
				this.divsearchresult = $('<div>', {'class' : 'searchresult'})
			).append(
				$('<div id=tabs style="margin: 10px;">').append(
					$('<ul>').append(
						$('<li>').append('<a href=#tabs-1>ABR Method</a>')
					).append(
						$('<li>').append('<a href=#tabs-2>VBR Method</a>')
					)
					.append(
						$('<li>').append('<a href=#tabs-3>Simplified Serie Encode</a>')
					)
				).append(
					this.divbitrate = $('<div>', {'id':'tabs-1', 'class' : 'bitrate'})
				).append(
					this.divquality = $('<div>', {'id':'tabs-2', 'class' : 'quality'})
				).append(
					this.divserie	= $('<div>', {'id':'tabs-3', 'class' : 'serie'	})
				).tabs({activate:function(event, ui){ console.log(ui.newPanel); that.encodemethod.val($(ui.newPanel).hasClass('bitrate') ? 'bitrate' : $(ui.newPanel).hasClass('quality') ? 'quality' : 'serie')}})
			).append(
				$('<a>', {'href':'#', text: 'Show Available Subtitles *.srt'}).click(function(){$('div.subs').toggle('blind')})
			).append(
				this.divsubs = $('<div>', {'class' : 'subs'}).hide()
			).append(
				this.divthumb = $('<div>', {'class' : 'thumb'})
			)	
		)
	)
	);
	this.allocine(title);
	this.bitrate(duration, audiotracks);
	this._thumb(thumb, video);
	this._subs(this);
	return;
}

M.prototype = 
{

	send: function()
	{

	},
	
	_subs : function(t)
	{
		list = $('<ol id=selectable>');
		$(subs).each(function(){
			$('<li class="ui-widget-content">'+this+'</li>').appendTo(list)
		});
		this.divsubs.append(list.selectable({
			stop: function()
			{
				var result = t.inputsubs.empty();
				$( ".ui-selected", this ).each(function() {
					var index = $( "#selectable li" ).index( this );
					if(lng=prompt('Language for : '+$(this).text()+"\nEg : eng, fre, chi, jpn, ita, und", 'eng'))
						result.append('<input type=hidden name=subs'+index+'-'+lng+' value="'+$(this).text()+'">')
					
				});
			},
			}));
	},
	
	_thumb: function(thumb, video)
	{
		this.divthumb.append($('<img src=./'+thumb+'>').click(function(){$(this).Jcrop(
			{
				onChange: showCoords,
				onSelect: showCoords,
				setSelect: [0, 0, $(this).width(), $(this).height()]
			})
		})).append('<input type=hidden id=cropx name=cropx> <input type=hidden id=cropy name=cropy> <input type=hidden id=cropwidth name=cropwidth> <input type=hidden name=cropheight id=cropheight></p>').append($('<button type=button>Crop-Update</button>').click(function(){
			AR = Math.round(($('#cropwidth').val()/$('#cropheight').val())*100)/100;
			$('#sAR').text(AR);
			$('#AR').val(AR);
			$('#sizeheight').val(Math.ceil(976/AR));
		}));
		this.divthumb.append('<p>AR : <span id=sAR>'+video.AR+'</span><input type=hidden id=AR name=AR value="'+video.AR+'"> Width : 976 <input type=hidden name=Width value="976"> Height: <input id=sizeheight type=text name=Height value="'+((video.AR != '16:9') ? parse_multiple_16(Math.round(976/parseFloat(video.AR))) : '544')+'"></p>');
		
		this.divthumb.append('<p>HQDN3D <input type=text placeholder=4:3:4:3 name=HQDN3D></p>');
		this.divthumb.append('<p>NoiseReduction <input type=text placeholder=1000 name=NR></p>');
		return true;
	},
	
	bitrate: function(duration, audiotracks)
	{
	that = this;
		this.divbitrate.add(this.divquality).append('<p>Durée : '+Math.floor(duration/3600)+'h '+Math.round((duration%3600)/60)+'mn </p>');
			this.divbitrate.append('<p>Taille : <input type=text name=size>MB</p>');
			this.divbitrate.append('<p>Video : <span class=videobitrate></span>kbps</p>');
			this.divbitrate.append('<p>Audio Encoding : <select name=aud-encode><option value="ct"> enc_aacplus (CT)</option>'+
								'<option selected value=nero> neroAacEnc -br (Nero)</option>'+
								'<option value=nero2pass> neroAacEnc -2pass -br (Nero)</option></select></p>');
								
			this.divquality.append($('<p>Video CRF : <input type=text readonly value=22.5 name=videocrf></p>').append($('<div>', {'width':'500px'}).slider({value:100-22.5, min:100-25, max:100-16, step: 0.1, slide: function(event, ui){$(this).prev().val(Math.round((100-ui.value)*10)/10);}}))).append(				$('<p>').append('Audio Engine : <select name=audio-engine><option value=fdk selected>FDK_AAC</option><option value=nero>Nero</option></select>'));
		
			this.divserie.append($('<p>Video CRF : <input type=text readonly value=22.5 name=s_videocrf></p>').append($('<div>', {'width':'500px'}).slider({value:100-22.5, min:100-25, max:100-16, step: 0.1, slide: function(event, ui){$(this).prev().val(Math.round((100-ui.value)*10)/10);}})));
		
		$(audiotracks).each(function()
		{
			that.divbitrate.append($('<p>').append('Audio - '+this.lang+' '+this.Channels+'ch '+this.Codec)
						.append(' <input type=hidden name=audio-codec-'+this.lang+' value='+this.Codec+'><input size=4 type=text name="audio-br-'+this.lang+'" value='+(this.Channels==2 ? '64' : '192')+'>kbps'));
						/*.append(' <input type=radio name="audio-'+this.lang+'-mode" id="audio-'+this.lang+'-mode-q" value=Q> <label for="audio-'+this.lang+'-mode-q">Quality 0..1</label> <input type=radio name="audio-'+this.lang+'-mode" id="audio-'+this.lang+'-mode-abr" value=abr> <label for="audio-'+this.lang+'-mode-abr">ABR kbps</label>')
						.append(' <input type=text size=4 name="audio-'+this.lang+'-br" id="audio-'+this.lang+'-br"> kbps'+
								' q=<input size=4 type=text name="audio-'+this.lang+'-q" id="audio-'+this.lang+'-q">')
						.append($('<div>', {'width':'500px'}).slider({
							range: 'min',
							value: 50,
							min: 30,
							max: 100,
							slide: function(event, ui){
								$(this).prev().val(ui.value/100).prev().val(Math.round(ui.value*8));
								},
							
						})));*/
						
			that.divquality.append(

				$('<p>').append('Audio - '+this.lang+' <input type=text name=audio-qch-'+this.lang+' size=1 value='+this.Channels+'>ch '+this.Codec)
						.append(' <input type=hidden name=audio-codec-'+this.lang+' value='+this.Codec+'> Q=<input readonly size=4 type=text name="audio-aq-'+this.lang+'" value='+(this.Channels==2 ? '0.4' : '0.3')+'>')
						.append($('<div>', {'width':'500px'}).slider({
							min:0,
							max:1,
							step: 0.01,
							value: 0.3,
							slide: function(event,ui){
								$(this).prev().val(ui.value);
								switch($('select[name=audio-engine] option:selected').val())
								{
									case 'fdk':
										show = '2ch~'+Math.round(64*ui.value*5)+'kbps 5.1ch~'+Math.round(32*6*ui.value*5)+'kbps';
									break;
									case 'nero':
										//show = '2ch~'+Math.round(((360/6)*2*ui.value)*0.5)+'kbps 5.1ch~'+Math.round((360*ui.value)*0.5);
										show = 'Q='+ui.value;
									break;
								}
								$('p#estimatedbirate').remove();
								$(this).after('<p id=estimatedbirate>Estimated bitrate : '+show+'</p>');
							}})
						)
			);
			that.divserie.append(
				$('<p>').append('Audio - '+this.lang+' <input type=text name=audio-sch-'+this.lang+' size=1 readonly value=2>ch '+this.Codec)
						.append(' <input type=hidden name=audio-codec-'+this.lang+' value='+this.Codec+'> BR=<input readonly size=4 type=text name="audio-aq" value=128>kbps FDK_AAC')
						.append($('<div>', {'width':'500px'}).slider({
							min:20,
							max:320,
							step: 1,
							value: 128,
							slide: function(event,ui){
								$(this).prev().val(ui.value);
							}})
						)
			);
			
		});
		this.calcBitrate(duration);

	},
	
	calcBitrate: function(duration)
	{
	/*
		(audio+audio+video)*duration = taille
		video = taille/duration-audio-audio;
	*/
	that=this;
		this.divbitrate.find('[name=size]').add(this.divbitrate.find('[name^=audio-br]')).change(function(){
			audiobitrate=0
			that.divbitrate.find('input[name^=audio-br]').each(function(){
				audiobitrate += parseInt($(this).val());
			});

				videobitrate = processvalues5(duration, audiobitrate, that.divbitrate.find('[name=size]').val());
				that.divbitrate.find('.videobitrate').text(videobitrate);
				that.inputvideobitrate.val(videobitrate);
		});
	},

	allocine: function(title)
	{
		t=this;
		$.get('./allocine.php',{
			
				//'partner' 	: 'YW5kcm9pZC12M3M',
				//'partner'	: '100043982026',
				//'filter'	: 'movie',
				//'count'		: '10',
				'q'			: encodeURI(title),
				//'format'	: 'json',
				'act'		: 'search',
				}
			,
			function(data)
			{
				$(data.feed.movie).each(function()
				{
					$.get('allocine.php',
						{
							//'partner' 	: '100043982026',
							'code'		: this.code,
							//'format'	: 'json',
							'act'		: 'movie',
						},
						function(datam)
						{
							if(datam.movie.poster)
								t.fillPosters(datam, t);
						},
						'json'
						);
				});
			},
			'json'
		);
		return;
	},
	
	fillPosters: function(datam, t)
	{
		this.divsearchresult.append(
			$('<img width=150 src='+datam.movie.poster.href+' >').css('cursor', 'pointer').click(function()
			{
				year = (datam.movie.productionYear ? ' ('+datam.movie.productionYear+')' : '');
				t.inputtitle.val(datam.movie.title ? datam.movie.title+year : datam.movie.originalTitle+year);
				t.inputposter.val(datam.movie.poster.href);
				$(this).parent().find('img.active').removeClass('active');
				$(this).addClass('active');
			})
		);
		return true;
	},

};

function processvalues5(secs,bitrate,totalmegs)
{
totalsecs= secs;
audiototal5= totalsecs*bitrate/8*1048/1000;
leftover= totalmegs*1048 -audiototal5;
result=Math.floor(leftover/totalsecs*8);
if (result<=0) {
result="Not Possible...";
}
return result;
}

function showCoords(c)
{
	c.x = Math.round(c.x/2)*2;
	c.y = Math.round(c.y/2)*2;
	c.w = Math.round(c.w/2)*2;
	c.h = Math.round(c.h/2)*2;
	$('#cropx').val(c.x );
	$('#cropy').val(c.y );
	//$('#x2').val(c.x2);
	//$('#y2').val(c.y2);
	$('#cropwidth').val(c.w );
	$('#cropheight').val(c.h );
}

function parse_multiple_16(nb)
{
	p = parseInt(nb/16);
	//console.log(p);
	//console.log(nb);
	//console.log(p*16);
	r =
		(nb - (p*16) > (((p+1)*16) - nb) ?
		(p+1)*16 :
		p*16);
	//console.log(r);
	return r;
}

function send()
{
form = $('<form method=post>');
i=0
	$('form.foo').each(function(){
	++i;
		$(this).find('input').each(function(){
			form.append($('<input>', {'type':'hidden', 'name': 'f['+i+']['+$(this).attr('name')+']', 'value':$(this).val()}));
		});
		$(this).find('option:selected').each(function(){
			form.append($('<input>', {'type':'hidden', 'name': 'f['+i+']['+$(this).parent().attr('name')+']', 'value':$(this).val()}));
		});
	});
console.log(form);
form.appendTo('body').submit();
}


/**
 * Creator: Benjamin Moreau (Anrolosia)
 * Date: 20/04/13
 * Time: 18:23
 *
 * You need to use this file for encryption : http://ma-filmotheque.fr/CryptoJS.js
 *
 * Example :
 * getUrl( 'search', {
 *          q: "avatar",
 *          count: 10,
 *          page: 1
 * })
 *
 */

var apiBaseUrl = "http://api.allocine.fr/rest/v3/";
var apiSecretKey = "29d185d98c984a359e6e6f26a0474269";

var extend = function (defaultObject, additionalObject) {
    return jQuery.extend(true, {}, defaultObject || {}, additionalObject || {});
};

var globalPresets = {
    partner: "100043982026",
    format: "json"
};

var preset = function (params) {
    return extend(globalPresets, params);
};

var routePresets = {
    "appprofile": preset(),
    "movielist": preset({ profile: "large" }),
    "movie": preset({ profile: "large" }),
    "tvserieslist": preset({ filter: "top", order: "viewcount" }),
    "tvseries": preset({ profile: "large" }),
    "tvseriesbroadcastlist": preset({ profile: "large" }),
    "tvseriesoriginalbroadcastlist": preset(),
    "season": preset({ profile: "large" }),
    "seasonlist": preset({ profile: "small" }),
    "news": preset({ profile: "large" }),
    "newslist": preset({ profile: "large" }),
    "media": preset({ mediafmt: "mp4" }),
    "feature": preset({ profile: "large" }),
    "featurelist": preset({ profile: "large" }),
    "theater": preset(),
    "theaterlist": preset(),
    "showtimelist": preset(),
    "picturelist": preset({ profile: "large" }),
    "videolist": preset({ mediafmt: "mp4" }),
    "carousel": preset(),
    "search": preset({ "filter": "movie,tvseries,theater,news,video" }),
    "reviewlist": preset(),
    "termlist": preset()
};

var getUrl = function (route, params) {
    if (!routePresets[route]) {
        throw new Error("route \"" + route + "\" unknown.");
    }
    return buildUri(apiBaseUrl + route, params, routePresets[route]);
};

var pad = function pad (num) {
    var s = num + "";
    return (s.length < 2) ? "0" + s : s;
};

var my_date = function( date ) {
    return '' + date.getFullYear().toString() + pad(date.getMonth()+1) + pad(date.getDate());
};

var buildUri = function (baseUri, params, defaultParams) {
    var tokens = [];

    var finalParams = extend(defaultParams, params);

    for (p in finalParams) {
        tokens.push({ name: p, value: finalParams[p] });
    }

    if (tokens.length > 0) {

        tokens.sort(function (a, b) {
            if (a.name < b.name)
                return -1;
            if (a.name > b.name)
                return 1;
            return 0;
        });

        for (var i = 0; i < tokens.length; i++) {
            tokens[i] = tokens[i].name + "=" + encodeURIComponent(tokens[i].value);
        }

        if (apiSecretKey) {
            var sed = encodeURIComponent(my_date(new Date()));
            var sig = encodeURIComponent(CryptoJS.SHA1(apiSecretKey + tokens.join("&") + "&sed=" + sed).toString(CryptoJS.enc.Base64));
            return baseUri + "?" + tokens.join("&") + "&sed=" + sed + "&sig=" + sig;
        }
        else {
            return baseUri + "?" + tokens.join("&");
        }
    }
    else {
        return baseUri;
    }
};
</script>

<button onclick=send();>Envoyer</button>

</body>
</html>

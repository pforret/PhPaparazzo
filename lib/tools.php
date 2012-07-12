<?php

function trace($text,$fatal=false){
	global $debug;
	$timeformat="H:i:s";
	
	if($fatal){
		// stop the program
		if($debug){
			// show line
			$time=date($timeformat);
			echo "--- ERROR AT $time ---\n";
			echo "--- $text ---\n";
			echo "--- STOPPING! ---\n";
			exit();
		} else {
			// do nothing
			exit();
		}
	} else {
		if($debug){
			// show line
			$time=date($timeformat);
			echo ". $time: $text\n";
		} else {
			// do nothing
		}
	}

}

function progress($segment,$text){
	$timeformat="H:i:s";
	$time=date($timeformat);
	printf("> %8s %-10s: %s\n",$time,$segment,$text);
}

function listfiles($folder,$ext="",$recursive=false){
	
	$start=time();
	$selection=Array();
	if(is_dir($folder)){
		$cfiles=scandir($folder);
		if($cfiles){
			foreach($cfiles as $cfile){
				$cfull="$folder/$cfile";
				$selected=true;
				if(!is_file($cfull)) $selected=false;
				if($ext AND strpos($cfile,$ext)<1) $selected=false;
				//trace("listfiles: [$cfull] : $selected");
				if($selected)	$selection[]=$cfull;
			}
		} else {
			trace("WARNING: no files found in [$folder]");
		}
	} else {
		trace("WARNING: folder [$folder] does not exist");
	}
	$stop=time();
	$secs=$stop-$start;
	trace("listfiles: found $ext files in [$folder]: " . count($selection) . " (in $secs sec)");
	return $selection;
}

function listfiles_dos($folder,$ext="",$recursive=false){

	$start=time();
	$selection=Array();
	if(is_dir($folder)){
		$rec="";
		if($recursive) $rec="/s ";
		$cmd="dir /b $rec \"$folder\\*$ext\"";
		$stdout=Array();
		exec($cmd,$stdout);
		foreach($stdout as $file){
			if(basename($file)==$file)
				$selection[]=$folder."\\".$file;
			else
				$selection[]=$file;
		}
	} else {
		trace("WARNING: folder [$folder] does not exist",true);
	}
	sort($selection);
	$stop=time();
	$secs=$stop-$start;
	//trace("listfiles_dos: found $ext files in [$folder]: " . count($selection). " (in $secs sec)");
	return $selection;
}

function listfolders($folder,$filter=false){
	$selection=Array();
	if(is_dir($folder)){
		$cfiles=scandir($folder);
		if($cfiles){
			foreach($cfiles as $cfile){
				$cfull="$folder/$cfile";
				$selected=false;
				if(is_dir($cfull)) $selected=true;
				if($cfile == ".")  $selected=false;
				if($cfile == "..")  $selected=false;
				if($filter AND !contains($cfile,$filter))	$selected=false;
				if($selected)	$selection[]=$cfull;
			}
		} else {
			trace("WARNING: no subfolders found in [$folder]");
		}
	} else {
		trace("WARNING: folder [$folder] does not exist");
	}
	trace("listfolders: found folders in [$folder]: " . count($selection));
	return $selection;
}


function leftstr($text,$chars){
	if($chars<strlen($text)){
		return substr($text,0,$chars);
	} else {
		return $text;
	}
}

function rightstr($text,$chars){
	$tlen=strlen($text);
	if($chars<$tlen){
		return substr($text,$tlen-$chars,$chars);
	} else {
		return $text;
	}
}

function getfromhtml($html,$tag,$include=true){
	$start=strpos($html,"<" . $tag);
	$stop=strpos($html,"</" . $tag . ">",$start);
	if($start && $stop){
		$text=substr($html,$start,$stop+strlen("</" . $tag . ">")-$start);
		if($include)
			return $text;
		else {
			$text=substr($text,strpos($text,">")+1);
			$text=substr($text,0,strpos($text,"</" . $tag . ">"));
			return $text;
			}
		}
	else
		return "(not found)";
}

function quickmatch($text,$search){

	$lensrc=strlen($text);
	$nb=preg_match($search , $text, $matches);
	if($nb){
		$value=$matches[1];
		$lenval=strlen($value);
		//trace("quickmatch: found [$search]: [$value] in $lensrc chars");
		return $value;
	}
	return false;
}

function textmatch($text,$start,$stop,$include=true){
	$match="";
	$p_start=strpos($text,$start);
	if($p_start){
		if($include){
			$text=substr($text,$p_start);
		} else {
			$text=substr($text,$p_start+strlen($start));
		}
		trace("textmatch: found start pattern at char $p_start - length is now " . strlen($text));
		$p_stop=strpos($text,$stop);
		if($p_stop){
			if($include){
				$text=substr($text,0,$p_stop+strlen($stop));
			} else {
				$text=substr($text,0,$p_stop);
			}
			trace("textmatch: found end pattern at char $p_stop - length is now " . strlen($text));
			$match=$text;
		} else {
			trace("textmatch: did not find end pattern [$stop]");
		}
	} else {
		trace("textmatch: did not find start pattern [$start]");
	}
	trace("textmatch: found " . strlen($match) . " chars");
	return $match;
}

function getfromxml($xml,$tag){
	$search="@<$tag>(.*)</$tag>@i";
	$nb=preg_match($search , $xml, $matches);
	trace("Looking for $search in string of ". strlen($xml) . " bytes: $nb matches");
	if($nb){

		$value=$matches[1];
		$value=str_replace("<![CDATA[","",$value);
		$value=str_replace("]]>","",$value);
		return $value;
	}
	return "(not found)";
}

function contains($haystack,$needle){
	$pos=strpos($haystack,$needle);
	if($pos>0) return true; // in haystack
	if($pos===false) return false; // not in
	if($pos===0) return true; // starts with
	return false; //normally never
}

function txt_removespecialchars($input){
	$return=utf8_decode($input);
	$return=strtolower($input);
	$return=str_replace(	Array("â","à","ä","ã"),	"a",$return);
	$return=str_replace(	Array("ç"),				"c",$return);
	$return=str_replace(	Array("é","è","ë","ê"),	"e",$return);
	$return=str_replace(	Array("î","ï","í"),		"i",$return);
	$return=str_replace(	Array("ñ"),				"n",$return);
	$return=str_replace(	Array("ô","ö","ò","ó"),	"o",$return);
	$return=str_replace(	Array("œ"),				"oe",$return);
	$return=str_replace(	Array("ü","ù","ú"),		"u",$return);

	trace("txt_removespecialchars: output = " . txt_shortentext($return,100));
	return $return;
}

function txt_makecanonical($input,$aremove=false,$maxlen=255){

	if(!$aremove){
		$aremove=Array("the","to","for","and","in");
	}
	$areplace=Array();
	foreach($aremove as $keyword){
		$areplace[]=txt_removespecialchars(" $keyword ");
	}

	$return=txt_removespecialchars($input);
	$return=str_replace($areplace," "," " . $return . " ");
	$return=str_replace(Array("-","_","'","/",".",",",";",":","!","?","(",")","°","~"),' ',$return);
	$return2=str_replace(' ','',$return);
	if(strlen($return2)>$maxlen){
		$wordend=strpos($return,' ',$maxlen);
		$return3=substr($return,0,$wordend);
		if(!$return3){
			$return3=substr($return,0,$maxlen);
		}

		$return2=str_replace(' ','',$return3);
	}
	trace("txt_makecanonical: output = [$return2] ( " . strlen($return2) . " chars)");
	return($return2);
}

function txt_shortentext($text,$max,$etc="..."){
	if(strlen($text)<=$max) return $text;
	$text=substr($text,0,$max-strlen($etc)).$etc;
	//trace("txt_shortentext: result= [$text]");
	return $text;
}

function txt_removehtml($html,$br="\n"){
	$result=$html;
	$tempbr="~$$";
	$result=preg_replace("/<!--.*-->/"," ",$result);
	$result=str_replace("/>","/> ",$result);
	$result=str_replace(Array("</div>","</p>","<br>","<br />","<br/>"),$tempbr,$html);
	$result=preg_replace("#<[^>]*>#","",$result);
	$result=preg_replace("#\s\s*#"," ",$result);
	$result=str_replace($tempbr,$br,$result);
	trace("txt_removehtml: output = " . txt_shortentext($result,100));
	return $result;
}

function pretty_filesize($bytes,$unit="B"){
	switch(true){
		case $bytes < 1000:
			return "$bytes B";
			break;
		case $bytes < 1000 * 1000:
			return number_format($bytes/1000,1) . " K$unit";
			break;
		case $bytes < 1000 * 1000 * 1000:
			return number_format($bytes/1000000,1) . " M$unit";
			break;
		case $bytes < 1000 * 1000 * 1000 * 1000:
			return number_format($bytes/1000000000,1) . " G$unit";
			break;
		case $bytes < 1000 * 1000 * 1000 * 1000 * 1000:
			return number_format($bytes/1000000000000,1) . " T$unit";
			break;
		default:
			return $bytes;
			break;
	}
}

function pretty_fileage($secs){
	switch(true){
		case $secs < 60:
			return "$secs sec";
			break;
		case $secs < 60 * 60 :
			return number_format($secs/60,1) . " min";
			break;
		case $secs < 60 * 60 * 24:
			return number_format($secs/(60*60),1) . " hrs";
			break;
		case $secs < 60 * 60 * 24 * 7:
			return number_format($secs/(60*60*24),1) . " day";
			break;
		case $secs < 60 * 60 * 24 * 30:
			return number_format($secs/(60*60*24*7),1) . " wks";
			break;
		case $secs < 60 * 60 * 24 * 365:
			return number_format($secs/(60*60*24*30),1) . " mon";
			break;
		case $secs < 60 * 60 * 24 * 365 * 10:
			return number_format($secs/(60*60*24*365),1) . " yrs";
			break;
		default:
			return number_format($secs/(60*60*24*365),1) . " yrs";
			break;
	}
}

function cmdline($cmd,$folder=false){
	$stdout=Array();
	$return=false;
	if($folder){
		$line="pushd \"$folder\" && $cmd  2>&1";
	} else {
		$line="$cmd  2>&1";
	}
	$return=exec($line,$stdout);
	trace("cmdline: [" . txt_shortentext($line,50) . "] resulted in " . count($stdout) . " lines stdout");
	return $stdout;
}

?>
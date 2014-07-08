#!/usr/bin/php -q
<?php
// Config section

$default_ua = "Mozilla/5.0 (Windows NT 6.1; WOW64; rv:14.0) Gecko/20100101 Firefox/14.0.1";
$ch = false;

$parsers = array();
include "parsers/moebooru.php";


// Prereq section - we need cURL extension
if(!function_exists("curl_init")){
	nodeps();
}
$self = $argv[0];

array_shift($argv);

$urls = array();		// URL list
$options = array(
	'U'=>$default_ua,
	'o'=>'.'
);		// Option list

$args_disabled = false;
$scheduled_for_args = array();


foreach($argv as $arg){
	if($arg == "") continue;
	if(count($scheduled_for_args))
		$options[array_shift($scheduled_for_args)] = $arg;
	else if($arg{0} != '-' || $args_disabled) $urls[] = $arg;
	else {
		if(strlen($arg)==1) continue;
		$opts = str_split(substr($arg, 1));
		foreach($opts as $opt){
			switch($opt){
				case '-':
					$args_disabled = true;
					break;
				case 'c':
				case 'u':
				case 'p':
				case 'o':
				case 'U':
					$scheduled_for_args[] = $opt;
					break;
				case 'a':
				case 'w':
					$options[$opt]= true;
					break;
				case 'h':
					help();
				default:
					echo "Unknown option: $opt\n";
					die();
			
			}
		}
	}
}
if(count($scheduled_for_args)){
	foreach($scheduled_for_args as $opt){
		echo "Error: $opt requires an argument\n";
	}
	die();
}
if(count($urls)<1)
	help();

foreach($urls as $url){
	$url = fix($url);
	$parser = choose_parser($url);
	$parser->scrape($url, $options);
}
curl_close($ch);


function url2hr($url2){
	return $url2['proto']."://".$url2['site'].$url2['location'];
}
function choose_parser($url){
	echo "Fetching frontpage: ";
	global $parsers;
	$site = $url['site'];
	$proto = $url['proto'];
	$frontpage = curl_get($proto."://".$site);
	$parser = null;
	foreach($parsers as $parser){
		if($parser->ismine_frontpage($frontpage)) break;
	}
	if($parser== null || !$parser->ismine_frontpage($frontpage)){
		echo "No parser :(\n";
		return null;
	}
	echo "Chose ".$parser->name."\n";
	return $parser;
}
function fix($url){
	$newrl = array();
	if(!preg_match('/^((https?)\:\/\/)?([^\/]+)(\/.*)?$/',$url, $match)){
		badurl($url);
	}
	$proto = $match[2];
	if($proto == "") $proto = "http";
	$newrl['proto'] = $proto;

	$site = $match[3];
	$newrl['site'] = $site;

	$loc = "/";
	if(isset($match[4])) $loc = $match[4];
	$newrl['location'] = $loc;
	return $newrl;
}
function badurl($url){
	echo "Give me something that can possibly exist as a webpage, will ya, mr $url? :)\n";
	die();
}
function nodeps(){
	echo "Error: You need the php curl extension available and working to make this script work. Please go hang yourself or install it.\n".
		"\tDebian style: (sudo) apt-get install php5-curl.\n";
	die();
}
function help(){
	global $self;
	global $default_ua;
	echo "Usage: {$self} [--] url1 [url2] [url...]\n";
	echo "	[] - optional\n";
	echo "	Options are optional... </Captain Obvious>\n";
	echo "		--	= rest of the arguments are urls\n";
	echo "		-a	= axel format output (pipe to shell)\n";
	echo "		-c arg	= cookies file to generate with curl (for cookie auth -- REQUIRED for cookie auth)\n";
	echo "		-o arg  = output files to this directory. Defaults to current directory\n";
	echo "		-p arg	= password for login\n";
	echo "		-u arg	= username for login\n";
	echo "		-U arg	= User agent to hide behind - default is:\n";
	echo "		-w	= wget format (pipe to shell) -- REQUIRED for cookie auth\n";
	echo "				$default_ua\n";
	echo "		-h	= Show this fancy and helpful (and sexy) message\n";
	die();
} 

function curl_get($url){
	global $options;
	global $ch;
	if(!$ch) $ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
//	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1);
//	curl_setopt($ch, CURLOPT_USERAGENT, $options['U']);
	$data = curl_exec($ch);
	return $data;
}
function add($url,$referrer=null, $comment=null){
	echo $url."\n";
}

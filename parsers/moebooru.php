<?php
class moebooru {
	var $name = "moebooru";


	var $folder = "";
	function cache_store($url,$file){
		$md = md5($url);
		$folder = $this->folder."/cache/";
		@mkdir($folder,0777,true);
		file_put_contents($folder.$md, $file);
	}
	function cache_get($url){
		$md = md5($url);
		$folder = $this->folder."/cache/";
		if($file=@file_get_contents($folder.$md)){
			return $file;
		}
		return false;
	}
	function ismine_frontpage($data){
		if(preg_match('/Moebooru/', $data)) return true;
		if(preg_match('/Danbooru/', $data)) return true;
		return false;
	}
	function singleindex($url){
		echo url2hr($url).": ";
		$data = curl_get(url2hr($url));
		if(!preg_match('/<ul id="post-list-posts">(.*?)<\/ul>/', str_replace(array("\r","\n"), " ", $data), $m)){
			echo "Cannot find the images inside the index html!\n";
			return false;
		}
		if(!preg_match_all('/href="(\/.*?)"/', $m[1], $m)){
			echo "Cannot isolate links from the index chunk of the html\n";
			return false;
		}
		echo count($m[1])." entries\n";
		$i=1;
		foreach($m[1] as $loc){
			$url['location'] = $loc;
			echo "\t\t$i/".count($m[1]).": ";
			$this->singleimage($url, $i);
			$i++;
		}
	}
	function singleimage($url, $num){

		$link = $this->cache_get(url2hr($url));
		if(!$link){
			$data = curl_get(url2hr($url));
			if(preg_match('/<a class="original-file-unchanged" href="([^"]+)"/', $data, $m)){

			} else if(preg_match('/<a class="original-file-changed" href="([^"]+)" id="highres">Download larger version /', $data, $m)){

			} else {
				die("Cannot get download link for ".url2hr($url)."\n");
			}
			$newurl = $m[1];
			$this->cache_store(url2hr($url),$newurl);
		} else {
			$newurl = $link;
		}
		if(!preg_match('/([a-f0-9]{32})\/([^\/]+?)\.([a-zA-Z0-9]+)$/', $newurl, $m)){
			die("Cannot extract image info from $newurl\n");
		}
		$md5 = $m[1];
		$type = strtolower($m[3]);
		$filename = $this->folder."/".$md5.".".$type;

		$skip = file_exists($filename) ? true : false;

		if(!$skip){
			echo "$filename ";
			$data = curl_get($newurl);
			echo "RAM: ".((strlen(serialize($GLOBALS))+strlen($data))/1024)."k ";
			file_put_contents($filename,$data);
			echo "done\n";
		} else {
			echo "$filename skip\n";
		}
	}
	function scrape($url, $options){
		// $this->folder is the base directory that we write our files to
		$this->folder = $options['o']."/".$url['site'];
		echo "Choosing folder: $this->folder\n";
		@mkdir($this->folder,0777,true);

		// subloc contains anything before ? in [0], anything after in [1]
		$subloc = explode("?", $url['location'], 2);
		// folders are an array of the path on moebooru, most cases [0] = "" and [1] = "post"
		$folders = explode("/", $subloc[0]);

		if(count($folders)<2){
			die("Unsupported page..\n");
		}

		// remove the leading empty cell
		array_shift($folders);

		// "post" is a type of page we can scrape
		if($folders[0] == "post"){
			// In this case, the URL is a single image
			if(isset($folders[1]) && $folders[1] == "show"){
				$this->singleimage($url);
				return;
			}

			// this scrapes the index
			echo url2hr($url).": ";
			$data = curl_get(url2hr($url));
			// Indexes without the pagination div are only one page long
			if(!preg_match('/<div class="pagination">(.*?)<\/div>/', $data, $m)){
				echo "1 page\n";
				$this->singleindex($url);
				return;
			}
			$pagination = $m[1];

			// Match all the pages in the pagination
			if(!preg_match_all('/href="([^"]*?)">([0-9]+)<\/a>/', $pagination, $m))
				die("Can't understand this page..\n");

			$first = array_shift($m[1]);
			$page = array_shift($m[2]);

			if(($num=strpos($first, "page=$page"))==null) die("Can't understand this page..\n");
			$pages = intval(array_pop($m[2]));
			$part1 = substr($first,0,6);
			$part2 = substr($first,6+strlen("page=".$page));

			echo "done, ".$pages." pages\n";
			for($i=1;$i<=$pages;$i++){
				$url['location'] = $part1."page=$i".$part2;
				echo "\t$i/$pages: ";
				$this->singleindex($url);
			}
		} else die("Unsupported page..\n");
	}
};
$parsers[] = new moebooru;

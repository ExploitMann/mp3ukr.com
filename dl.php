<?
$ch = curl_init();
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_SSL_VERIFYHOST => false,
	CURLOPT_SSL_VERIFYPEER => false,
	CURLOPT_FOLLOWLOCATION => true
]);

function getAll($page) {
	global $ch;
	
	curl_setopt($ch, CURLOPT_URL, "https://mp3ukr.com/ua/page/{$page}/");
	$res = curl_exec($ch);
	
	if($errno = curl_errno($ch)) {
		return $errno;
	}
	
	$tracks = [];
	preg_match_all('/<li><a href="(.*\.html)">(.*) <span>([0-9\:]+)<\/span><\/a><\/li>/', $res, $tracks);
	
	$result = [];
	foreach($tracks[2] as $id => $name) {
		$result[$name] = $tracks[1][$id];
	}
	
	return $result;
}

function dlTrack($url) {
	global $ch;
	
	curl_setopt($ch, CURLOPT_URL, $url);
	$res = curl_exec($ch);
	
	if($errno = curl_errno($ch)) {
		return $errno;
	}
	
	$data = [];
	preg_match('/"(https\:\/\/mp3ukr.*\.mp3)"/', $res, $data);
	
	return count($data) > 0 ? $data[1] : false;
}

function cleanName($name) {
   return preg_replace('/[^A-Za-zА-Яа-яіїєІЇЄ0-9\- \.\(\)\[\]&\*=\+\,]/siu', '', $name);
}

$tracks = [];
$maxPage = 14;
for($i = 1; $i <= $maxPage; $i++) {
	echo "Getting tracks from {$i} page... ";
	if($currPage = getAll($i)) {
		echo "OK!\n";
		$tracks = array_merge($tracks, getAll($i));
	} else {
		echo "FAILED: ". var_dump($currPage) ."\n" ;
	}
}

echo "All count tracks = " . count($tracks) . "\n";

$linksFile = 'links.txt';
$fp = fopen($linksFile, 'a+');
foreach($tracks as $name => $url) {
	$name = cleanName(htmlspecialchars_decode($name));
	echo "GET link for \"{$name}\"... ";
	if($mp3 = dlTrack($url)) {
		echo "OK!\n";
		fwrite($fp, "{$mp3}\n");
		fwrite($fp, "\tout={$name}.mp3\n");
	} else {
		echo "FAIL!\n";
	}
}

fclose($fp);

curl_close($ch);
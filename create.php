<?php

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);

	include 'known.php';
	include 'tenc.php';
	$configs = include('configs.php');
	$twAPIkey =  $configs->twAPIkey;
	$twAPIsecret = $configs->twAPIsecret;
	$twUserKey  = $configs->twUserKey;
	$twUserSecret = $configs->twUserSecret;
	$results = file_get_contents('text.txt');
	$wp_comments = eval("return " . $results . ";");

	function post_to_url($url, $data) {
		   $fields = '';
		   foreach($data as $key => $value) { 
		      $fields .= $key . '=' . $value . '&'; 
		   }
		   rtrim($fields, '&');

		   $post = curl_init();

		   curl_setopt($post, CURLOPT_URL, $url);
		   curl_setopt($post, CURLOPT_POST, count($data));
		   curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
		   curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);

		   $result = curl_exec($post);

		   curl_close($post);
		   return $result;
	}



	function ping_micro_blog($url) {
		$fields = '';
		// foreach($data as $key => $value) { 
		// 	$fields .= $key . '=' . $value . '&'; 
		// }
		$fields .= "url" . '=' . "http://".$url."/rss.php" . '&'; 
		rtrim($fields, '&');

		$post = curl_init();

		curl_setopt($post, CURLOPT_URL, "http://micro.blog/ping");
		// curl_setopt($post, CURLOPT_POST, count($data));
		curl_setopt($post, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($post, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($post, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));

		$result = curl_exec($post);

		curl_close($post);
		return $result;
	}
	
	if(isset($_POST['submit'])) {

		$text = $_POST['something'];
		$pass = $_POST['nothing'];

		if ($pass == $configs->password){
			reset($wp_comments);
			
			$errors = array_filter($wp_comments);
			if (empty($errors)) {
				$wp_comments = array();
			}
			
			if ($results == FALSE){
				$itemID = 1;	
			}
			else{
				$item = array_values($wp_comments)[0];
				$itemID = intval($item['comment_ID']);
				$itemID++;	
			}
			$comment_id = strval($itemID);

			date_default_timezone_set( $configs->timezone);
			$date = date('Y-m-d H:i:s', time());

			// Length setting part starts
			
			if (strlen($text) > 256){
				$ADNURL = 'http://'.$configs->siteUrl.'#'.$comment_id;
				$ADNtext = substr($text, 0, 190);
				$ADNtext = $ADNtext.'... '.$ADNURL;
			}
			else{
				$ADNtext = $text;
			}

			if (strlen($text) > 140){
				$TwURL = 'http://'. $configs->siteUrl.'#'.$comment_id;
				$Twtext = substr($text, 0, 75);
				$Twtext = $Twtext.'... '.$TwURL;
			}
			else{
				$Twtext = $text;
			}
			
			// Length setting part over
			
			// Known part starts

			$Knowntext = str_replace("\&quot;", "\"", $text);

			$result = statusKnown($configs->knownUser, $configs->knownAPIkey, $configs->knownTwName, $configs->knownSite, $Knowntext);

			// Known part over
			
			// 10Centuries PART STARTS

			$TenCtext = str_replace("\'", "'", $text);
			$TenCtext = str_replace("\&quot;", "\"", $TenCtext);
			$TenCtext = urlencode($TenCtext);
			$tencToken = $configs->tenCauthtoken;
			$data = array(
				"content" => $TenCtext,
			);

			$the_result_10c = post_to_tenC('https://api.10centuries.org/content', $tencToken, $data);
			$the_Array_10c = json_decode($the_result_10c, true);

			// Sets up a variable which contains a link to the 10C blurb
			$tenclink = "https://" . $arrayName['data']['0']['urls']['full_url'];
			echo $tenclink;

			// 10Centuries PART OVER
			
			// Twitter part starts

			require_once('codebird.php');
			\Codebird\Codebird::setConsumerKey($twAPIkey, $twAPIsecret);
			$cb = \Codebird\Codebird::getInstance();
			$cb->setToken($twUserKey, $twUserSecret);
			
			$params = array(
			  'status' => $Twtext
			);
			$reply = $cb->statuses_update($params);
			$array_twit = json_decode($reply,true);

			// Gives the twitter name if needed
			$twScreen = $array_twit->user->screen_name;

			// Sets up a variable which provides a link to the posted tweet 
			$twitlink = "https://twitter.com/" . $array_twit->user->screen_name . "/status/" . $array_twit->id_str;
			echo $twitlink; 
			// Twitter part Over
			
			// ping microblog

			ping_micro_blog($configs->siteUrl);

			// ping microblog over


			//This is the actual _POST_ element. This needs to move to the _END_ of the active part of the process.
			$postarray = array(
				'comment_author' => $configs->userName,
				'comment_date' => $date,
				'comment_content' => $text,
				'comment_ID' => $comment_id
			);

			array_unshift($wp_comments, $postarray);
			$result = var_export($wp_comments, true); 
			file_put_contents('text.txt', $result);
		}
	}
?>
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
<script>
	function textareaLengthCheck() {
		tex = document.getElementById('something');
	    var length = tex.value.length;
	    // var charactersLeft = 500 - length;
	    var count = document.getElementById('count');
	    count.innerHTML = length + " chars.";
	}
</script>
<form method="post" action="" class="form-horizontal">
    <textarea onkeyup="textareaLengthCheck()" rows="7" cols="50" id="something" name="something" value="<?= isset($_POST['something']) ? htmlspecialchars($_POST['something']) : '' ?>" ></textarea><br />
    <textarea rows="1" cols="20" id="nothing" name="nothing" value="<?= isset($_POST['nothing']) ? htmlspecialchars($_POST['nothing']) : '' ?>" ></textarea><br />
    <input type="submit" class="btn" name="submit" />
    <p id="count"></p>
</form>

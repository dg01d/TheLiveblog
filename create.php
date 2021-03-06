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

		if (hash('sha256', $pass) === $configs->password) {
			reset($wp_comments);
			
			$knownlink = '';
			$twitlink = '';
			$mastodonlink = '';
			$tenclink = '';
			$pnutlink = '';

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
				// $TwURL = 'http://'. $configs->siteUrl.'#'.$comment_id;
				// $Twtext = substr($text, 0, 75);
				// $Twtext = $Twtext.'... '.$TwURL;
				$Twtext = "";
				// $TwArray = explode(' ', $text, ceil(strlen($text)/140));
				$TwArray = str_split($text, 140);
				// echo ceil(strlen($text)/140);
				// echo "<pre>";
				// print_r($TwArray);
				// echo "</pre>";
			}
			else{
				$Twtext = $text;
			}
			
			// Length setting part over
			
			// Known part starts
			if ($configs->postKnown) {
				$Knowntext = str_replace("\&quot;", "\"", $text);

				$result = statusKnown($configs->knownUser, $configs->knownAPIkey, $configs->knownTwName, $configs->knownSite, $Knowntext);
				$knownRes = json_decode($result, true);
				$knownlink = str_replace("?_t=json", "", $knownRes['location']);
				echo $knownlink . " ";
			}
			// Known part over

			// pnut.io part starts

			if ($configs->postPnut) {
				$pnutText = str_replace("\'", "'", $text);
				$pnutText = str_replace("\&quot;", "\"", $pnutText);
				$pnutText = urlencode($pnutText);
				$pnutToken = "Bearer " . $configs->pnutauthtoken;
				$data = array(
					"text" => $pnutText,
				);

				$the_result_pnut = post_to_api('https://api.pnut.io/v0/posts', $pnutToken, $data);
				$the_array_pnut = json_decode($the_result_pnut, true);
				$pnutlink = "https://posts.pnut.io/" . $the_array_pnut['data']['id'];
				echo $pnutlink;
				echo "<br>";
			}

			// pnut.io part ends
			
			// 10Centuries PART STARTS
			if ($configs->postTenc) {
				$TenCtext = str_replace("\'", "'", $text);
				$TenCtext = str_replace("\&quot;", "\"", $TenCtext);
				$TenCtext = urlencode($TenCtext);
				$tencToken = $configs->tenCauthtoken;
				$data = array(
					"content" => $TenCtext,
				);


				$the_result_10c = post_to_api('https://api.10centuries.org/content', $tencToken, $data);
				$the_Array_10c = json_decode($the_result_10c, true);

				// Sets up a variable which contains a link to the 10C blurb
				$tenclink = "https://" . $the_Array_10c['data']['0']['urls']['full_url'];
				echo $tenclink;
				echo "<br>";
			}

			// 10Centuries PART OVER
			
			// Mastodon PART STARTS
			if ($configs->postMastodon) {
				$MastodonText = str_replace("\'", "'", $text);
				$MastodonText = str_replace("\&quot;", "\"", $MastodonText);
				$MastodonText = urlencode($MastodonText);
				$mastodonToken = "bearer " . $configs->mastodonToken;
				$mastodonUrl = "https://" . $configs->mastodonInstance . "/api/v1/statuses";
				$data = array(
					"status" => $MastodonText,
				);

				$result_mastodon = post_to_api($mastodonUrl, $mastodonToken, $data);
				$array_mastodon = json_decode($result_mastodon, true);

				// Sets up a variable linking to the toot
				$mastodonlink = $array_mastodon['url'];
				echo $mastodonlink . " ";
			}
			// Mastodon ENDS

			// Twitter part starts
			if ($configs->postTwitter) {
				require_once('codebird.php');
				\Codebird\Codebird::setConsumerKey($twAPIkey, $twAPIsecret);
				$cb = \Codebird\Codebird::getInstance();
				$cb->setToken($twUserKey, $twUserSecret);
				

				if (!empty($Twtext)) {
					$params = array(
						'status' => $Twtext
					);
					$reply = $cb->statuses_update($params);
					// Gives the twitter name if needed
					$twScreen = $reply->user->screen_name;
					$twid = $reply->id_str;
					// Sets up a variable which provides a link to the posted tweet 
					$twitlink = "https://twitter.com/" . $twScreen . "/status/" . $twid;
					echo $twitlink; 
				}
				else {
					$twitlink = "";
					$replytwitid = "";
					foreach ($TwArray as $key => $singletweet) {
						if ($key == 0){
							$params = array(
								'status' => $singletweet
							);
							$reply = $cb->statuses_update($params);
							// Gives the twitter name if needed
							$twScreen = $reply->user->screen_name;
							$twid = $reply->id_str;
							// Sets up a variable which provides a link to the posted tweet 
							$twitlink = "https://twitter.com/" . $twScreen . "/status/" . $twid;
							$replytwitid = $reply->id_str;
						}
						else {
							$params = array(
								'status' => $singletweet,
								'in_reply_to_status_id' => $replytwitid
							);
							$reply = $cb->statuses_update($params);
							$replytwitid = $reply->id_str;	
						}
					}
				echo $twitlink;
				}
			}
			
			// Twitter part Over
			
			// ping microblog
			if ($configs->pingMicro) {
				ping_micro_blog($configs->siteUrl);
			}
			// ping microblog over


			//This is the actual _POST_ element. This needs to move to the _END_ of the active part of the process.
			$postarray = array(
				'comment_author' => $configs->userName,
				'comment_date' => $date,
				'comment_content' => $text,
				'comment_ID' => $comment_id,
				'known' => $knownlink,
				'blurb' => $tenclink,
				'pnost' => $pnutlink,
				'toot' => $mastodonlink,
				'tweet' => $twitlink
			);

			array_unshift($wp_comments, $postarray);
			$result = var_export($wp_comments, true); 
			file_put_contents('text.txt', $result);
			}
		}
?>
<html>
<head>
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
	<script src="https://code.jquery.com/jquery-3.1.1.slim.min.js" integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n" crossorigin="anonymous"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js" integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb" crossorigin="anonymous"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js" integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn" crossorigin="anonymous"></script>
<script>
	function textareaLengthCheck() {
		tex = document.getElementById('something');
	    var length = tex.value.length;
	    // var charactersLeft = 500 - length;
	    var count = document.getElementById('count');
	    count.innerHTML = length + " chars.";
	}
</script>
</head>
<body>
<div class="container-fluid h-100" id="root">
   <div class="row h-100">
     <div class="col-md-1 fixed py-1"></div>
     
     <div class="col fluid py-1">

		<form method="post" action="">
			<div class="form-group row">
				<label for="something">Enter your Words</label>
				<textarea onkeyup="textareaLengthCheck()" rows="7" class="form-control" id="something" name="something" value="<?= isset($_POST['something']) ? htmlspecialchars($_POST['something']) : '' ?>" ></textarea>
			</div>
			<div class="form-group row">
				<div class="col-2">
			    	<h6 id="count"></h6>
			    </div>
			</div>
			<div class="form-group row">
				<label for="nothing">Passphrase</label>
				<input class="form-control" id="nothing" name="nothing" type="password" value="<?= isset($_POST['nothing']) ? htmlspecialchars($_POST['nothing']) : '' ?>" >
			</div>
			<div class="form-group row">
				<div class="col-2">
					<button type="submit" class="btn btn-primary" name="submit">Post It!</button>
			    </div>
		    </div>
		</form>

	</div>
    <div class="col-md-1 fixed py-1"></div>
  </div>
</div>
</body>
</html>



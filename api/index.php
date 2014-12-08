<?php
	error_reporting(0);
	mysql_connect('', '', '');
	mysql_select_db('');
	header('Access-Control-Allow-Credentials: true');
	if(isset($_SERVER['HTTP_ORIGIN']))
		header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
	else
		header('Access-Control-Allow-Origin: *');
	session_start();

	function do_void(){
		echo 'Boring fine print? Not going to read it? I know that feeling. But I\'m such freak and I readed it. And I saw THINGS, I tell you. THINGS, man. And you know what? <a href = "http://54.174.198.214">I Know The Things</a> now. Wanna hang out?';
	}

	function is_logged(){
		if(empty($_SESSION['user_logged']))
			return false;
		return is_numeric($_SESSION['user_logged']);
	}

	function do_login_face(){
		$unid = mt_rand(11111, 99999);
		echo '<div id = "iktt_login_'.$unid.'">
		<table border = "0" cellspacing = "0" cellpadding = "2">
		<tr><td><b>Login:</b></td><td><input type = "text" id = "my_login_'.$unid.'" style = "width:100%"></td></tr>
		<tr><td><b>Password:</b></td><td><input type = "password" id = "my_password_'.$unid.'" style = "width:100%"></td></tr>
		<tr><td colspan = "2"><center><button onClick = "iktt_do_login_step(document.getElementById(\'iktt_login_'.$unid.'\').parentNode, '.$unid.')" style = "width:100%">Log in!</button></center></td></tr>
		</table>
		</div><br>
		No account? Hate Facebook, Twitter and such? <a href = "javascript:void(0)" onClick = "iktt_do_register(this.parentNode)">Click here</a> to register.';
	}

	function do_register_face(){
		$unid = mt_rand(11111, 99999);
		echo '<div id = "iktt_register_'.$unid.'">
		<table border = "0" cellspacing = "0" cellpadding = "2">
		<tr><td><b>Login:</b></td><td><input type = "text" id = "my_login_'.$unid.'" style = "width:100%"></td></tr>
		<tr><td><b>Password:</b></td><td><input type = "password" id = "my_password_'.$unid.'" style = "width:100%"></td></tr>
		<tr><td><b>Repeat password:</b></td><td><input type = "password" id = "my_password_rep_'.$unid.'" style = "width:100%"></td></tr>
		<tr><td><b>E-Mail:</b></td><td><input type = "text" id = "my_mail_'.$unid.'" placeholder = "Optional" style = "width:100%"></td></tr>
		<tr><td colspan = "2"><center><button onClick = "iktt_do_register_step(document.getElementById(\'iktt_register_'.$unid.'\').parentNode, '.$unid.')" style = "width:100%">Register!</button></center></td></tr>
		</table>
		</div><br>
		Misclick? Already got account? <a href = "javascript:void(0)" onClick = "iktt_do_login(this.parentNode)">Click here</a> to log in.';
	}

	function do_login($login, $pass){
		$Q = mysql_query('SELECT id FROM iktt_users WHERE login = "'.$login.'" AND pass = "'.md5(sha1($pass)).'" LIMIT 1');
		$Q = mysql_fetch_array($Q);
		$_SESSION['user_logged'] = $Q['id'];
		$_SESSION['user_login'] = strip_tags($login);
	}

	function do_register($login, $pass, $mail){
		$pass = md5(sha1($pass));
		mysql_query("INSERT INTO iktt_users VALUES ('', '$mail', '$login', '$pass')");
		$Q = mysql_query('SELECT id FROM iktt_users WHERE login = "'.$login.'" AND pass = "'.$pass.'" LIMIT 1');
		$Q = mysql_fetch_array($Q);
		$_SESSION['user_logged'] = $Q['id'];
		$_SESSION['user_login'] = strip_tags($login);
	}

	function do_login_step(){
		$login = addslashes($_GET['login']);
		$pass = addslashes($_GET['pass']);
		$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_users WHERE login = "'.$login.'" AND pass = "'.md5(sha1($pass)).'" LIMIT 1');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] == 1){
			do_login($login, $pass);
			echo 'Logged in! Nice to see you, '.$login.'. <a href = "javascript:void(0)" onClick = "iktt_bind()">Click here</a> to continue.';
		}
		else {
			echo '<b>Error:</b> wrong login or password.<br>';
			do_login_face();
		}
	}

	function do_register_step(){
		$login = addslashes(strip_tags($_GET['login']));
		$pass = addslashes($_GET['pass']);
		$mail = addslashes(strip_tags($_GET['mail']));
		$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_users WHERE login = "'.$login.'"');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] != 0){
			echo '<b>Error:</b> this login is already taken.<br>';
			do_register_face();
		}
		else {
			do_register($login, $pass, $mail);
			echo 'Welcome aboard, '.$login.'! <a href = "javascript:void(0)" onClick = "iktt_bind()">Click here</a> to continue.';
		}
	}

	function do_info(){
		if(!isset($_GET['docid'])){
			echo '<b>Error:</b> no docid specified.';
			exit(0);
		}
		$at_attachstats = isset($_GET['attachstats']);
		$at_attachuserinfo = isset($_GET['attachuserinfo']);
		$at_onlylocal = isset($_GET['onlylocal']);
		$at_showtrophy = isset($_GET['showtrophy']);
		$at_showtrophyname = isset($_GET['showtrophyname']);
		$at_allowlogin = isset($_GET['allowlogin']);
		$at_attachpanel = isset($_GET['attachpanel']);
		$gl_docid = addslashes($_GET['docid']);
		if($at_showtrophyname)
			$at_showtrophy = false;
		if($at_allowlogin)
			$at_attachuserinfo = false;
		if($at_attachuserinfo){
			if(is_logged()){
				echo 'Hello, '.$_SESSION['user_login'].'.';
				if(!$at_onlylocal){
					$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND result = true');
					$Q = mysql_fetch_array($Q);
					echo ' You acquired <b>'.$Q['ch'].'</b> trophies so far.';
				}
				$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND qid = "'.$gl_docid.'"');
				$Q = mysql_fetch_array($Q);
				if($Q['ch'] == 0)
					echo ' You never tried to acquire trophy for this document.';
				else
					echo ' You tried to acquire trophy for this document '.($Q['ch'] == 1 ? 'once' : $Q['ch'].' times').'.';
				if($Q['ch'] != 0){
					$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND qid = "'.$gl_docid.'" AND result = true');
					$Q = mysql_fetch_array($Q);
					if($Q['ch'] > 0)
						echo ' You already acquired this trophy.';
					else
						echo ' You don\'t have this trophy yet.';
				}
			}
		}
		if($at_showtrophyname){
			$Q = mysql_query('SELECT trophy_name FROM iktt_docs WHERE id = "'.$gl_docid.'"');
			$Q = mysql_fetch_array($Q);
			echo ' You can acquire <b>'.strip_tags(stripslashes($Q['trophy_name'])).'</b> here. ';
		}
		if($at_showtrophy){
			$Q = mysql_query('SELECT trophy_name, trophy_icon FROM iktt_docs WHERE id = "'.$gl_docid.'"');
			$Q = mysql_fetch_array($Q);
			echo '<br>You can acquire this trophy here:<br><img src = "'.strip_tags(stripslashes($Q['trophy_icon'])).'" border = "0"><br><b>'.strip_tags(stripslashes($Q['trophy_name'])).'</b><br>';
		}
		if($at_attachstats){
			$Q = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.$gl_docid.'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] == 0)
				echo ' Noone tried to acquire trophy for this document yet.';
			else if($Q['ch'] == 1)
				echo ' One person tried to acquire trophy for this document.';
			else
				echo ' '.$Q['ch'].' peoples tried to acquire this trophy.';
			if($Q['ch'] != 0){
				$Q = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.$gl_docid.'" AND result = true');
				$Q = mysql_fetch_array($Q);
				if($Q['ch'] == 0)
					echo ' Noone acquired this trophy.';
				else if($Q['ch'] == 1)
					echo ' One person acquired this trophy.';
				else
					echo ' '.$Q['ch'].' peoples acquired this trophy.';
			}
		}
		if($at_attachpanel && is_logged() && $at_attachuserinfo){
			echo ' <a href = "http://54.174.198.214/user.php?s=trophies">Click here</a> to see your trophies.';
		}
		if(!is_logged() && ($at_attachuserinfo || $at_allowlogin)){
			echo ' You are not logged in. <a href = "javascript:void(0)" onClick = "iktt_do_login(this.parentNode)">Click here</a> to log in or create account.';
		}
		if($at_attachpanel && !is_logged()){
			echo ' You don\'t know what\'s it? <a href = "http://54.174.198.214/user.php">Click here</a> to find out.';
		}
	}

	function do_quest(){
		if(!is_logged()){
			echo ' You are not logged in. <a href = "javascript:void(0)" onClick = "iktt_do_login(this.parentNode)">Click here</a> to log in or create account.';
			exit(0);
		}
		if(empty($_GET['docid']))
			exit(0);
		$docid = addslashes($_GET['docid']);
		$time = time();
		$answers = array();
		$counter = 0;
		while(true){
			if(isset($_GET['ans_'.$counter]))
				$answers[] = $_GET['ans_'.$counter];
			else
				break;
			$counter++;
		}
		$Q = mysql_query('SELECT minimum_win, questions_array, trophy_name, trophy_icon FROM iktt_docs WHERE id = "'.$docid.'"');
		$Q = mysql_fetch_array($Q);
		$prepare = unserialize($Q['questions_array']);
		$good = 0;
		foreach($answers as $K => $V){
			if($V == $prepare[$K][1])
				$good++;
		}
		echo 'You answered <b>'.$good.'</b> questions correctly out of <b>'.count($prepare).'</b>.';
		if($good >= $Q['minimum_win']){
			mysql_query('INSERT INTO iktt_attempts VALUES ("", "'.addslashes($_SESSION['user_logged']).'", "'.$docid.'", "'.$time.'", true)');
			echo '<br>Congratulations, you acquired <b>'.$Q['trophy_name'].'</b>!<br>
			<img border = "0" src = "'.$Q['trophy_icon'].'">';
		}
		else {
			mysql_query('INSERT INTO iktt_attempts VALUES ("", "'.addslashes($_SESSION['user_logged']).'", "'.$docid.'", "'.$time.'", false)');
			echo '<br>Unfortunately you didn\'t acquire trophy.<br>Want to <a href = "javascript:void(0)" onClick = "iktt_bind()">try again</a>?';
		}
	}

	function do_main(){
		if(!isset($_GET['docid'])){
			echo '<b>Error:</b> no docid specified.';
			exit(0);
		}
		$at_attachstats = isset($_GET['attachstats']);
		$at_onlylocal = isset($_GET['onlylocal']);
		$at_showtrophy = isset($_GET['showtrophy']);
		$at_showtrophyname = isset($_GET['showtrophyname']);
		$at_attachpanel = isset($_GET['attachpanel']);
		$gl_docid = addslashes($_GET['docid']);
		$H = mysql_query('SELECT * FROM iktt_docs WHERE id = "'.addslashes($gl_docid).'"');
		$H = mysql_fetch_array($H);
		?>
<div id = "iktt_mainq_1">
<?php
	$is_won = false;
	if(is_logged()){
		$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND qid = "'.$gl_docid.'" AND result = true');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] != 0)
			$is_won = true;
	}
	if($at_showtrophyname){
		if(!$is_won)
			echo 'You are going to try to acquire <b>'.strip_tags(stripslashes($H['trophy_name'])).'</b>.<br>';
		else
			echo 'You already have <b>'.strip_tags(stripslashes($H['trophy_name'])).'</b>.<br>';
	}
	if($at_showtrophy){
		if(!$is_won)
			echo '<br>You are going to try to acquire:<br><img src = "'.strip_tags(stripslashes($H['trophy_icon'])).'" border = "0"><br><b>'.strip_tags(stripslashes($H['trophy_name'])).'</b><br>';
		else
			echo '<br>You already have:<br><img src = "'.strip_tags(stripslashes($H['trophy_icon'])).'" border = "0"><br><b>'.strip_tags(stripslashes($H['trophy_name'])).'</b><br>';
	}
	if($at_attachstats){
		$Q = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.$gl_docid.'"');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] == 0)
			echo 'Noone tried to acquire trophy for this document yet.';
		else if($Q['ch'] == 1)
			echo 'One person tried to acquire trophy for this document.';
		else
			echo $Q['ch'].' peoples tried to acquire this trophy.';
		if($Q['ch'] != 0){
			$Q = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.$gl_docid.'" AND result = true');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] == 0)
				echo ' Noone acquired this trophy.';
			else if($Q['ch'] == 1)
				echo ' One person acquired this trophy.';
			else
				echo ' '.$Q['ch'].' peoples acquired this trophy.';
		}
		echo '<br>';
	}
	echo '<br>';
	if(!is_logged()){
		echo '<a href = "javascript:void(0)" onClick = "iktt_do_login(this.parentNode.parentNode)">Log in</a> or <a href = "javascript:void(0)" onClick = "iktt_do_register(this.parentNode.parentNode)">create account</a> (within like five seconds).';
	}
	else {
		if($is_won)
			echo '<b>You Know The Things!</b>';
		else
			echo '<a href = "javascript:void(0)" onClick = "iktt_mainq_next()">Acquire trophy!</a>';
	}
	echo '</div>';
	$prepare = unserialize($H['questions_array']);
	$current_panel = 2;
	foreach($prepare as $K => $X){
		echo '<div id = "iktt_mainq_'.$current_panel.'" style = "display:none">
		<b>'.stripslashes($X[0]).'</b><br>';
		foreach($X[2] as $sK => $xS){
			echo '<input type = "radio" name = "iktt_answer_'.$K.'" value = "'.$sK.'"> '.stripslashes($xS).'<br>';
		}
		echo '<br><a href = "javascript:void(0)" onClick = "iktt_mainq_next()">Next question</a>
		</div>';
		$current_panel++;
	}
?>
<div id = "iktt_mainq_<?php echo $current_panel; ?>" style = "display:none">
	<b>We're done. Can you feel this beauty?</b><br>
	<img border = "0" src = "<?php echo $H['trophy_icon']; ?>"><br><br>
	<a href = "javascript:void(0)" onClick = "iktt_mainq_final(<?php echo $H['id']; ?>)">Calculate results!</a>
</div>
<?php
	}

	if(!isset($_GET['act']))
		exit(0);
	switch($_GET['act']){
		case 'void':		do_void();		break;
		case 'info':		do_info();		break;
		case 'dologin':		do_login_face();	break;
		case 'dologinstep':	do_login_step();	break;
		case 'doregister':	do_register_face();	break;
		case 'doregisterstep':	do_register_step();	break;
		case 'main':		do_main();		break;
		case 'doquest':		do_quest();		break;
		default:		do_void();		break;
	}
	exit(0);
?>

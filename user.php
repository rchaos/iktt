<?php
	mysql_connect('', '', '');
	mysql_select_db('');
	session_start();

	function is_logged(){
		if(empty($_SESSION['user_logged']))
			return false;
		return is_numeric($_SESSION['user_logged']);
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

	if(!empty($_POST['a_login'])){
		$login = addslashes($_POST['a_login']);
		$pass = addslashes($_POST['a_pass']);
		$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_users WHERE login = "'.$login.'" AND pass = "'.md5(sha1($pass)).'" LIMIT 1');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] == 1){
			do_login($login, $pass);
			header('Location: user.php');
		}
		else {
			header('Location: user.php?err='.base64_encode('Wrong username or password.'));
		}		
	}

	if(!empty($_POST['b_login'])){
		$login = addslashes($_POST['b_login']);
		$pass = addslashes($_POST['b_pass']);
		if($_POST['b_pass'] != $_POST['b_passs']){
			header('Location: user.php?err='.base64_encode('Passwords doesn\'t match.'));
		}
		else {
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_users WHERE login = "'.$login.'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] != 0){
				header('Location: user.php?err='.base64_encode('Login already taken.'));
			}
			else {
				do_register($login, $pass, $mail);
				header('Location: user.php');
			}
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD -//W3C//DTD HTML 4.01//EN//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>I Know The Things</title>
<link rel = "stylesheet" type = "text/css" href = "style.css">
</head>
<body>
<div id="container">
<div id="header">
<h1>I Know The Things</h1>
<h3>We're all mad here.</h3>
</div>
<div id="main_content">
<?php
	if(!empty($_GET['err']))
		echo strip_tags(base64_decode($_GET['err'])).'<br>';
	if(empty($_GET['s'])){
		echo '<h3>User\'s page.</h3><br>I have no time to write content here, unfortunately. It\'s pretty much mockup.<br>';
		if(!is_logged()){
			echo '<a href = "user.php?s=login">Login</a> | <a href = "user.php?s=register">Register</a>';
		}
		else {
			echo '<a href = "user.php?s=trophies">Trophies</a><br><a href = "user.php?s=alltrophies">Available trophies</a>.';
		}
	}
	if($_GET['s'] == 'login'){
		echo '<form method = "POST">
		<table border = "0" cellspacing = "0" cellpadding = "2">
		<tr><td><b>Login:</b></td><td><input name = "a_login" type = "text" style = "width:100%"></td></tr>
		<tr><td><b>Password:</b></td><td><input name = "a_pass" type = "password" style = "width:100%"></td></tr>
		<tr><td colspan = "2"><center><input type = "submit" value = "Log in!" style = "width:100%"></center></td></tr>
		</table>
		</form>';
	}
	if($_GET['s'] == 'register'){
		echo '<form method = "POST">
		<table border = "0" cellspacing = "0" cellpadding = "2">
		<tr><td><b>Login:</b></td><td><input name = "b_login" type = "text" style = "width:100%"></td></tr>
		<tr><td><b>Password:</b></td><td><input name = "b_pass" type = "password" style = "width:100%"></td></tr>
		<tr><td><b>Repeat password:</b></td><td><input name = "b_passs" type = "password" style = "width:100%"></td></tr>
		<tr><td colspan = "2"><center><input type = "submit" value = "Log in!" style = "width:100%"></center></td></tr>
		</table>
		</form>';
	}

	if($_GET['s'] == 'trophies'){
		echo '<h2>Your trophies</h2>';
		$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND result = true');
		$Q = mysql_fetch_array($Q);
		if($Q['ch'] < 1)
			echo 'You don\'t have any trophies. Check <a href = "user.php?s=alltrophies">available trophies</a> if you want one.';
		else {
			$R = mysql_query('SELECT DISTINCT(qid) FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND result = true ORDER BY id ASC');
			while($Q = mysql_fetch_array($R)){
				$H = mysql_query('SELECT * FROM iktt_docs WHERE id = "'.$Q['qid'].'"');
				$H = mysql_fetch_array($H);
				echo '<b>'.$H['trophy_name'].'</b><br><img border = "0" src = "'.$H['trophy_icon'].'"><br>Document: <a href = "'.$H['doc_url'].'">'.$Q['doc_url'].'</a><br><br>';
			}
		}
	}

	if($_GET['s'] == 'alltrophies'){
		echo '<h2>All trophies</h2>';
		$R = mysql_query('SELECT * FROM iktt_docs ORDER BY id DESC');
		while($Q = mysql_fetch_array($R)){
			echo '<b>'.$Q['trophy_name'].'</b><br><img border = "0" src = "'.$Q['trophy_icon'].'"><br>Document: <a href = "'.$Q['doc_url'].'">'.$Q['doc_url'].'</a><br>';
			$H = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE uid = "'.addslashes($_SESSION['user_logged']).'" AND result = true AND qid = "'.$Q['id'].'"');
			$H = mysql_fetch_array($H);
			if($H['ch'] != 0)
				echo '<b>Already acquired!</b>';
			echo '<br><br>';
			}
		}

?>
</div>
</div>	
</body>
</html>

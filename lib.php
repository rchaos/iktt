<?php
	mysql_connect('', '', '');
	mysql_select_db('');
	session_start();

	$errmsg = '';

	class c_bind {
		public function bind_content(){
			global $interface, $account, $quest;
			if(!isset($_GET['s']))
				$interface->show_main();
			else {
				switch($_GET['s']){
					case 'implementation':	$interface->show_implementation();		break;
					case 'example':		$interface->show_about();			break;
					case 'login':		$interface->show_login();			break;
					case 'register':	$interface->show_register();			break;
					case 'aboutproject':	$interface->show_about();			break;
					case 'admin_logged':	$interface->show_admin_main();			break;
					case 'admin_addq':	$interface->show_admin_addq();			break;
					case 'admin_quests':	$interface->show_admin_quests();		break;
					case 'generator':	$interface->show_generator();			break;
					case 'quest_details':	$quest->details($_GET['q']);			break;
					case 'remove_quest':	$quest->remove($_GET['q']);			break;
					default:		$interface->show_main();
				}
			}
		}

		public function bind_post(){
			global $account, $quest;
			switch($_POST['action']){
				case 'f1':
					$account->login_admin($_POST['f1_login'], $_POST['f1_pass']); break;
				case 'f2':
					$account->register_admin($_POST['f2_login'], $_POST['f2_pass'], $_POST['f2_passr'], $_POST['f2_mail']); break;
				case 'create_doc':
				case 'edit_doc':
					$quest->save(); break;
			}
			if(isset($_GET['s'])){
				switch($_GET['s']){
					case 'admin_logout':	$account->do_logout();		break;
				}
			}
		}
	}

	class c_quest {
		public function save(){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			if($_POST['action'] == 'edit_doc')
				return -1;
			$name = addslashes(strip_tags($_POST['name']));
			$domain = addslashes(strip_tags($_POST['domain']));
			$url = addslashes(strip_tags($_POST['url']));
			$trophy = addslashes(strip_tags($_POST['trophy']));
			$trophyicon = addslashes(strip_tags($_POST['trophyicon']));
			$trophymin = addslashes(strip_tags($_POST['trophymin']));
			if(!is_numeric($trophymin) || $trophymin > count($_POST['tquestion']) || empty($name) || empty($trophy) || empty($trophyicon) || empty($trophymin) || $trophymin < 1)
				return -1;
			$questions = array();
			foreach($_POST['tquestion'] as $K => $X){
				$subans = array();
				foreach($X['ans'] as $K => $ans){
					$subans[$K] = addslashes($ans);
				}
				$questions[] = array(strip_tags($X['question']), strip_tags($X['answer']), $subans);
			}
			$questions = addslashes(serialize($questions));
			$uid = addslashes($_SESSION['logged_as']);
			mysql_query("INSERT INTO iktt_docs VALUES ('', '$uid', '$name', '$domain', '$url', '$questions', '$trophy', '$trophyicon', 0, 0, 0, 0, 0, '$trophymin')");
			header('Location: index.php?s=admin_quests');
		}

		public function remove($id){
			if($id == 6 || $id == 5){
				echo 'UNREMOVABLE!';
				exit(0);
			}
			global $account, $interface;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			$Q = mysql_query('SELECT uid FROM iktt_docs WHERE id = "'.addslashes($id).'"');
			$Q = mysql_fetch_array($Q);
			if($Q['uid'] != $_SESSION['logged_as'])
				exit(0);
			mysql_query('DELETE FROM iktt_docs WHERE id = "'.addslashes($id).'"');
			$interface->show_admin_quests();
		}

		public function details($id){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			$Q = mysql_query('SELECT * FROM iktt_docs WHERE id = "'.addslashes($id).'"');
			$Q = mysql_fetch_array($Q);
			if($Q['uid'] != $_SESSION['logged_as'])
				exit(0);
			$prepare = unserialize($Q['questions_array']);
			echo '<h3>Document: '.$Q['docname'].'</h3>
			<b>Domain:</b> '.$Q['site_url'].'<br>
			<b>URL:</b> <a href = "'.$Q['doc_url'].'">'.$Q['doc_url'].'</a><br>
			<b>Trophy name:</b> '.$Q['trophy_name'].'<br>
			<b>Trophy icon:</b><br>
			<img border = "0" src = "'.$Q['trophy_icon'].'"><br>
			<b>Questions required to win:</b> '.$Q['minimum_win'].' ('.(floor(($Q['minimum_win'] / count($prepare)) * 100)).'%)<br><br>';
			foreach($prepare as $X){
				echo '<b>'.stripslashes($X[0]).'</b><br>';
				foreach($X[2] as $K => $V){
					echo '<b>'.$K.')</b> '.stripslashes($V).' '.($K == $X[1] ? '<small>(<b>correct</b>)</small>' : '').'<br>';
				}
				echo '<br>';
			}
			echo '<b>Winners: </b>';
			$X = mysql_query('SELECT COUNT(*) AS ch FROM iktt_trophies WHERE qid = "'.addslashes($Q['id']).'"');
			$X = mysql_fetch_array($X);
			echo $X['ch'];
		}
	}

	class c_account {
		public function login_admin($login, $pass){
			global $errmsg;
			$login = addslashes($login);
			$cpass = addslashes($pass);
			$pass = md5(sha1($pass));
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_admins WHERE login = "'.$login.'" AND pass = "'.$pass.'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] != 0)
				$this->do_login($login, $cpass, true);
			else
				$errmsg = 'Wrong login or password.';
		}

		public function register_admin($login, $pass, $rpass, $mail){
			global $errmsg;
			if($pass != $rpass){
				$errmsg = 'Passwords are not the same.';
				return -1;
			}
			if(empty($pass) || empty($login)){
				$errmsg = 'Login and password are required.';
				return -1;
			}
			if(strlen($pass) < 5){
				$errmsg = 'Password should contain at least five characters.';
				return -1;
			}
			$login = addslashes(strip_tags($login));
			$pass = addslashes($pass);
			$mail = addslashes(strip_tags($mail));
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_admins WHERE login = "'.$login.'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] != 0){
				$errmsg = 'Account with such login is already registred.';
				return -1;
			}
			$cpass = $pass;
			$pass = md5(sha1($pass));
			mysql_query("INSERT INTO iktt_admins VALUES ('', '$mail', '$login', '$pass')");
			$this->do_login($login, $cpass, true);
		}

		private function do_login($login, $pass, $is_admin){
			$login = addslashes($login);
			$pass = addslashes($pass);
			if($is_admin){
				$Q = mysql_query('SELECT id FROM iktt_admins WHERE login = "'.$login.'" AND pass = "'.md5(sha1($pass)).'"');
				$Q = mysql_fetch_array($Q);
				$_SESSION['logged_as'] = $Q['id'];
				$_SESSION['ulogin'] = $login;
				$_SESSION['is_admin'] = true;
				header('Location: index.php?s=admin_logged');
			}
		}

		public function do_logout(){
			$_SESSION['logged_as'] = null;
			$_SESSION['ulogin'] = null;
			$_SESSION['is_admin'] = null;
			header('Location: index.php');
		}

		public function is_logged($is_admin){
			if(empty($_SESSION['logged_as']) || !is_numeric($_SESSION['logged_as']))
				return false;
			if($is_admin != $_SESSION['is_admin'])
				return false;
			return true;
		}
	}

	class c_interface {
		public function show_implementation(){
			?>
<h2>Implementation</h2>
<h3>New document</h3>
First, You will need admin-level account. It's easy to acquire one - just <a href = "index.php?s=register" target = "_blank">go here</a>. After <a href = "index.php?s=login" target = "_blank">signing in</a> (You'll be signed in right after registration), <a href = "index.php?s=admin_addq" target = "_blank">create new "document"</a> (questions set and trophy in fact, not document itself, but we're using this name). Play around with document editor's features, test it a bit, feel into it, master Your freakin' DOCUMENT EDITOR'S FEATURES KUNG-FU, <b>MAKE ME PROUD</b>, <b><u>BE THE OVERLORD OF DAMN DOCUMENT EDI</u></b>... <small>uhm... results of lack of sleep, sorry</small><br><br>
<h3>IKTT API</h3>
When You have set of (most likely example) questions and fancy trophy (most likely example one) - You can check it all out in <a href = "index.php?s=admin_quests" target = "_blank">manager</a> - You can embed IKTT anywhere You want. Like this:<br><br>
<textarea rows = "2" cols = "130">
<div class = "iktt-api" iktt-act="info" docid=5 attachstats=y showtrophy=y></div>
<script type = "text/javascript" src = "http://54.174.198.214/iktt.js"></script>
</textarea>
<br><br>
Result for code above will look like:<br><br>
<div class = "iktt-api" iktt-act="info" docid=5 attachstats=y showtrophy=y></div><br><br>
&lt;div&gt; is basically gateway to IKTT API - part that is responsible for communication with user. Administrative functions can be accessed from any programming language (<small>note: administrative API has been disabled for now</small>). Let's focus on &lt;div&gt; first.<br>
&lt;div&gt; must have class "<b>iktt-api</b>", attribute <b>iktt-act</b> and <b>docid</b>. Possible values for iktt-act are <b>info</b> and <b>main</b>. <b>info</b> creates infobox (with fancy stuff), and <b>main</b> is used to show quiz itself (with fancy stuff, too!). If You won't specify iktt-act, it'll be <b>void</b> (which is valid value too). <b>void</b> does nothing beside showing some default message:<br><br>
<div class = "iktt-api"></div><br><br>
Script iktt.js should be included after all &lt;div&gt;s used. If You want to load it in &lt;head&gt; or somewhere "up", You'll have to execute this script after &lt;div&gt;s definitions:<br><br>
<textarea rows = "3" cols = "130">
<script type = "text/javascript">
iktt_bind();
</script>
</textarea>
<br><br>
<h3>docid attribute</h3>
<b>docid</b> is unique ID of document You're attaching. You can find it out in semi-hackish way reading $_GET variables, or just take it from <a href = "index.php?s=generator" target = "_blank">generator</a>
<h3>"info" box attributes</h3>
Value of attribute doesn't matter - API just check if it's defined. "y" is prefered. Because.<br>Possible attributes are:<br>
<b>attachstats</b> - includes attempts statistics (how many peoples tried to acquire trophy, how many acquired it).<br>
<b>attachuserinfo</b> - shows user's data and allows logging in. <br>
<b>onlylocal</b> - do not show global data when showin user's data (show only data associated to current trophy, don't show all trophies acquired).<br>
<b>showtrophy</b> - shows trophy name and icon.<br>
<b>showtrophyname</b> - shows only trophy name. Cannot be used along with <b>showtrophy</b>.<br>
<b>allowlogin</b> - allows logging in when <b>attachuserinfo</b> is not set. Do not enable it when <b>attachuserinfo</b> is enabled (one will disable another anyway).<br>
<b>attachpanel</b> - shows several links to user's panel on main IKTT page, like link to page where user can see all his trophies.<br><br>
Some examples:<br><br>
<textarea rows = "1" cols = "130">
<div class = "iktt-api" iktt-act="info" docid=5 attachstats=y showtrophyname=y attachuserinfo=y></div>
</textarea>
<div class = "iktt-api" iktt-act="info" docid=5 attachstats=y showtrophyname=y attachuserinfo=y></div><br><br>
<textarea rows = "1" cols = "130">
<div class = "iktt-api" iktt-act="info" docid=5 attachuserinfo=y attachpanel=y></div>
</textarea>
<div class = "iktt-api" iktt-act="info" docid=5 attachuserinfo=y attachpanel=y></div><br><br>
<textarea rows = "1" cols = "130">
<div class = "iktt-api" iktt-act="info" docid=5 allowlogin=y showtrophy=y></div>
</textarea>
<div class = "iktt-api" iktt-act="info" docid=5 allowlogin=y showtrophy=y></div><br><br>
Results will be different if You'll be logged in/out/someone already acquired trophy/etceteraetcetera. Logging in and clicking "continue" in one box will automagically log You in all boxes. And - in fact - pretty much everywhere. Accounts are global, session too. Boxes will be refreshed, so You can have distinct <b>info</b> and <b>main</b> if You like.<br><br>
<h3>"main" box attributes</h3>
<b>attachstats</b> - includes attempts statistics (how many peoples tried to acquire trophy, how many acquired it).<br>
<b>showtrophy</b> - shows trophy name and icon.<br>
<b>showtrophyname</b> - shows only trophy name. Cannot be used along with <b>showtrophy</b>.<br>
Note: at the end, <b>main</b> shows trophy along with icon - two settings above are only for starting screen.<br><br>
<textarea rows = "1" cols = "130">
<div class = "iktt-api" iktt-act="main" docid=5 attachstats=y showtrophy=y attachstats=y></div>
</textarea><br>
<div class = "iktt-api" iktt-act="main" docid=5 attachstats=y showtrophy=y attachstats=y></div>
<br><br>
<h3>Generator</h3>
No reason for learning it all if You're just testing it, just use <a href = "index.php?s=generator" target = "_blank">this</a>.<br><br>
<h3>Customisation</h3>
As gateway to IKTT API is &lt;div&gt;, You can freely change it's styles, colors, look of inputs, width, height, backgrounds and all that.


<script type = "text/javascript" src = "http://54.174.198.214/iktt.js"></script>
			<?php
		}

		public function show_register(){
			echo '<h2>Register</h2>'.$this->handle_error().'
			<form method = "POST">
			<table border = "0" cellspacing = "0" cellpadding = "2">
			<tr><td><b>Login:</b></td><td><center><input type = "text" name = "f2_login" placeholder = "Required."></center></td></tr>
			<tr><td><b>Password:</b></td><td><center><input type = "password" name = "f2_pass" placeholder = "Required."></center></td></tr>
			<tr><td><b>Repeat password:</b></td><td><center><input type = "password" name = "f2_passr" placeholder = "Required."></center></td></tr>
			<tr><td><b>E-mail address:</b></td><td><center><input type = "text" name = "f2_mail" placeholder = "Optional."></center></td></tr>
			<tr><td colspan = "2"><center><input type = "submit" style = "width:100%" value = "I can\'t wait for my account, man."></center></td></tr>
			</table>
			<br>Already got one of that shiny fabulous accounts? <a href = "index.php?s=login">Click here</a>.
			<input type = "hidden" name = "action" value = "f2">
			</form>';
		}

		public function show_about(){
			?>
<h2>About project</h2>
This is metasite for hackathon judges and anyone interested in this project as part of Koding's hackathon 2014. Let's answer several questions and show several things here.<br><br>
<?php
	if($_GET['s'] == 'example'){
?>
<h3>I just clicked "example" button but I see "about" page.</h3>
This page is just perfect for example!<br><br>
<?php
	}
?>
<h3>Why this site is so ugly?</h3>
As I often tell my clients, I'm really terrible, horrible graphic designer. I always focus on features instead of look and feel. I'm the only one in my team, so I don't have any designer here. While I'm able to create something way fancier than this, it takes days for me. Weeks. Months of painful labor I'm not familiar with. I hope that Your eyes won't hurt too much.<br><br>
<h3>Site doesn't look official.</h3>
Contents are pretty much relaxed, just like look and feel. In current state, design and contents are more like mockup of how it may look like if we'll turn it into official project (which can be done within two, three days) - just add <strike>water</strike> good design and contents, some <strike>sugar</strike> fancy dashboards and more stats, <strike>cook on low heat</strike> buy good domain and we're done!<br><br>
<h3>Give me some example accounts.</h3>
You can quickly register new accounts, but if You want to see some instant results, like configured documents, You may want to use:<br>
<b>Admin-level account</b> (to login <a href = "index.php?s=login" target = "_blank">here</a>):<br>
Login: tester<br>
Password: asdfg<br>
<b>User-level account</b> (to login into API created boxes):<br>
Login: tester<br>
Password: asdfg<br>
Do not remove docs from tester's account - it's used in "Implementation" and "About project" page.<br><br>
<h3>What's IKTT?</h3>
It's service allowing anyone to create "trophy" for reading given text - by design, TOS-like texts, but of course it may be used anywhere else after small changes. Key to fun context - we're creating serious contrast between stereotype of boring fine print and game. Trophies are global, and user can accumulate them. While it looks bit crazy, it's potentially surprisingly addictive...<br>As amount of (hypothetical) clients willing to use our API on their sites would be propably small, IKTT grants user access to list of documents binded. User can happily travel around completely random sites reading their whatevers and exploring World of e-business.
<br><br>
<h3>External technologies used</h3>
-> nginx & PHP & MySQL & Linux - awesome combination<br>
-> Pure JavaScript - I don't like jQuery and such<br>
-> datadoghq.com - non-invasive monitoring<br>
-> apitools.com - for API calls monitoring.<br><br>
<h3>External technologies unused</h3>
I wanted to use 3scale.net and mashape.com, but I refused - I had no time and IKTT API is nonauth anyway. I also plannet do pubnub.com, but also refused - I decided that it's completely unnecessary in this case (unless we're going to have massive traffic).<br><br>
<h3>Facebook and stuff</h3>
You can notice small references to Facebook and other social medias in one place (logging in). That's right, I feel that without social media integration - logging in with Twitter, sharing et cetera - project is kinda incomplete. But I started it very late, it's 19:16:35 PST when I'm writing this words, and I can't resist urge to continue this concept's madness. It's just too exticing for me to abandon my way and do something "just becasue it should be done" - just like everywhere. As Hackathon's participant I should think about winning and just invest several hours for that Facebook things, because I feel it's like requirement. But hell no. If I'll decide to continue this project in future, I'll add that stuff because I should. But for now, let's fly out of the box together!<br><br>
<h3>...but Facebook!</h3>
Yes, I know. But let's go. We have yet another World to see.<br><br>
<h3>Example</h3>
<div class = "iktt-api" iktt-act="main" docid="6" attachstats=y showtrophy=y></div>
<script type = "text/javascript" src = "http://54.174.198.214/iktt.js"></script>


			<?php
		}

		public function show_main(){
			?>
			<i>Welcome to Zombo.com. This is Zombo.com. Welcome. This is Zombo.com, welcome to Zombo.com. You can do anything at Zombo.com. Anything at all. The only limit is yourself. Welcome to Zombo.com. Welcome to Zombo.com. This is Zombo.com. Welcome to Zombo.com. This is Zombo.com, welcome. Yes, this is Zombo.com. This is Zombo.com, and welcome to you, who have come to Zombo.com. Anything is possible at Zombo.com. You can do anything at Zombo.com. The infinite is possible at Zombo.com. The unattainable is unknown at Zombo.com. Welcome to Zombo.com. This is Zombo.com. Welcome to Zombo.com. Welcome. This is Zombo.com. Welcome to Zombo.com. Welcome to Zombo.com.</i>
			<?php
		}

		public function show_login(){
			echo '<h2>Sign In</h2>'.$this->handle_error().'
			<form method = "POST">
			<table border = "0" cellspacing = "0" cellpadding = "2">
			<tr><td><b>Login:</b></td><td><center><input type = "text" name = "f1_login"></center></td></tr>
			<tr><td><b>Password:</b></td><td><center><input type = "password" name = "f1_pass"></center></td></tr>
			<tr><td colspan = "2"><center><input type = "submit" style = "width:100%" value = "Sign In!"></center></td></tr>
			</table>
			<br>No account? Want one? Close your eyes, focus on your wish and <a href = "index.php?s=register">click here</a>.
			<input type = "hidden" name = "action" value = "f1">
			</form>';
		}

		public function show_generator(){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			echo '<h2>Code Generator</h2>';
			if(isset($_GET['doc'])){
				$docid = addslashes($_GET['doc']);
				$Q = mysql_query('SELECT id, docname FROM iktt_docs WHERE id = "'.$docid.'" AND uid = "'.addslashes($_SESSION['logged_as']).'"');
				$Q = mysql_fetch_array($Q);
				?>
<script type = "text/javascript">
	function do_generation(){
		var result = '<div class = "iktt-api" iktt-act="' + document.getElementById('rinp_1').value + '" docid="<?php echo strip_tags($docid); ?>"';
		if(document.getElementById('rinp_2').checked){
			result = result + ' attachstats=y';
		}
		if(document.getElementById('rinp_3').checked){
			result = result + ' attachuserinfo=y';
		}
		if(document.getElementById('rinp_4').checked){
			result = result + ' onlylocal=y';
		}
		if(document.getElementById('rinp_5').checked){
			result = result + ' showtrophyname=y';
		}
		if(document.getElementById('rinp_6').checked){
			result = result + ' showtrophy=y';
		}
		if(document.getElementById('rinp_7').checked){
			result = result + ' allowlogin=y';
		}
		if(document.getElementById('rinp_8').checked){
			result = result + ' attachpanel=y';
		}
		result = result + "></div><script type = \"text/javascript\" src = \"http:\/\/54.174.198.214\/iktt.js\"><\/script>";
		document.getElementById('txid').value = result;
		document.getElementById('rxwrap').innerHTML = result;
		iktt_bind();
	}
</script>
				<?php
				echo '<table border = "0" cellspacing = "0" cellpadding = "2">
				<tr><td colspan = "2"><div id = "rxwrap"><div class = "iktt-api" iktt-act="info" docid="'.strip_tags($Q['id']).'" attachstats=y showtrophy=y></div><script type = "text/javascript" src = "http://54.174.198.214/iktt.js"></script></div></td></tr>
				<tr><td colspan = "2"><center><button onClick = "do_generation()" style = "width:100%">Generate</button></td></tr>
				<tr><td colpsan = "2"><center><textarea rows = "5" cols = "100" id = "txid"><div class = "iktt-api" iktt-act="info" docid="'.strip_tags($Q['id']).'" attachstats=y showtrophy=y></div>
<script type = "text/javascript" src = "http://54.174.198.214/iktt.js"></script></textarea></center></td></tr>
				<tr><td colspan = "2">
				<select id = "rinp_1">
				<option value = "info">Infobox</option>
				<option value = "main">Quiz box</option>
				</select> 
				<input type = "checkbox" id = "rinp_2"> Attach stats.
				<input type = "checkbox" id = "rinp_3"> Attach user data and logging in / registration.
				<input type = "checkbox" id = "rinp_4"> Do not show global data.
				<input type = "checkbox" id = "rinp_5"> Show trophy name (only).
				<input type = "checkbox" id = "rinp_6"> Show trophy name and icon.
				<input type = "checkbox" id = "rinp_7"> Hide user data but allow logging in and registration.
				<input type = "checkbox" id = "rinp_8"> Attach URLs to user panel on IKTT page.
				</td></tr>
				</table><br><br>';
			}
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_docs WHERE uid = "'.addslashes($_SESSION['logged_as']).'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] == 0)
				echo 'You don\'t have any documents configured. <a href = "index.php?s=admin_addq">Click here</a> to add one.';
			else {
				$R = mysql_query('SELECT id, docname FROM iktt_docs WHERE uid = "'.addslashes($_SESSION['logged_as']).'" ORDER BY id DESC');
				echo 'For which document You want to generate code?<br>';
				while($Q = mysql_fetch_array($R)){
					echo '-> <a href = "index.php?s=generator&doc='.$Q['id'].'">'.$Q['docname'].'</a><br>';
				}
			}
		}

		public function show_admin_main(){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			echo '<h2>Dashboard</h2>
			Hello, '.$_SESSION['ulogin'].'!<br>';
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_docs WHERE uid = "'.addslashes($_SESSION['logged_as']).'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] == 0)
				echo 'You don\'t have any documents configured. <a href = "index.php?s=admin_addq">Click here</a> to add one.';
			else {
				echo 'You have '.$Q['ch'].' document'.($Q['ch'] > 1 ? 's' : '').' configured. <a href = "index.php?s=admin_quests">Click here</a> to see details.';
			}
		}

		public function show_admin_addq(){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			echo '<h2>Create new document</h2>';
			$this->load_doc_editor(true);
		}

		public function show_admin_quests(){
			global $account;
			if(!$account->is_logged(true)){
				header('Location: index.php?s=login');
				return -1;
			}
			echo '<h2>Documents</h2>';
			$Q = mysql_query('SELECT COUNT(*) AS ch FROM iktt_docs WHERE uid = "'.addslashes($_SESSION['logged_as']).'"');
			$Q = mysql_fetch_array($Q);
			if($Q['ch'] == 0){
				echo 'You don\'t have any documents configured. <a href = "index.php?s=admin_addq">Click here</a> to add one.';
				return 0;
			}
			echo '<table border = "1" cellspacing = "0" cellpadding = "2">
			<tr><td><center><b>Name</b></center></td><td><center><b>Domain</b></center></td><td><center><b>URL</b></center></td><td><center><b>Questions</b></center></td><td><center><b>Winning amount</b></center></td><td><center><b>Winners</b></center></td><td><center><b>Players</b></center></td><td><center><b>Total attempts</b></center></td><td><center><b>Details</b></center></td><td><center><b>Remove</b></center></td><td><center><b>Code</b></center></td></tr>';
			$R = mysql_query('SELECT * FROM iktt_docs WHERE uid = "'.addslashes($_SESSION['logged_as']).'" ORDER BY id DESC');
			while($Q = mysql_fetch_array($R)){
				echo '<tr><td>'.$Q['docname'].'</td><td>'.$Q['site_url'].'</td><td><a href = "'.$Q['doc_url'].'">[ URL ]</a></td><td>';
				$prepare = unserialize($Q['questions_array']);
				echo count($prepare).'</td><td>'.$Q['minimum_win'].' ('.(floor(($Q['minimum_win'] / count($prepare)) * 100)).'%)</td><td>';
				$X = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.addslashes($Q['id']).'" AND result = true');
				$X = mysql_fetch_array($X);
				echo $X['ch'].'</td><td>';
				$X = mysql_query('SELECT COUNT(DISTINCT(uid)) AS ch FROM iktt_attempts WHERE qid = "'.addslashes($Q['id']).'"');
				$X = mysql_fetch_array($X);
				echo $X['ch'].'</td><td>';
				$X = mysql_query('SELECT COUNT(*) AS ch FROM iktt_attempts WHERE qid = "'.addslashes($Q['id']).'"');
				$X = mysql_fetch_array($X);
				echo $X['ch'].'</td>';
				echo '<td><a href = "index.php?s=quest_details&q='.$Q['id'].'">[ Details ]</a></td><td><a href = "index.php?s=remove_quest&q='.$Q['id'].'" onClick = "return confirm(\'Do you want to remove this document?\')">[ Remove ]</a></td><td><a href = "index.php?s=generator&doc='.$Q['id'].'">[ Generate ]</a></td></tr>';
			}
			echo '</table><br>
			<a href = "index.php?s=admin_addq">Add new document.</a>';
		}

		private function load_doc_editor($is_new){
			if(!$is_new){
				$docid = addslashes($_GET['docid']);
				if(!is_numeric($docid))
					exit(0);
				$Q = mysql_query('SELECT uid FROM iktt_docs WHERE id = "'.$docid.'"');
				$Q = mysql_fetch_array($Q);
				if($Q['uid'] != $_SESSION['logged_as'])
					exit(0);
				$Q = mysql_query('SELECT * FROM iktt_docs WHERE id = "'.$docid.'"');
				$Q = mysql_fetch_array($Q);
			}
			?>
<script type = "text/javascript">
	var last_question = 0;
	var last_questions = new Array();

	function icon_reload(){
		var icon_url = document.getElementById('fed_icon_url').value;
		document.getElementById('fed_icon').innerHTML = '<img border = "0" src = "' + icon_url + '">';
	}

	function change_qtype(qid){
		var new_type = document.getElementById('thinp_1_' + qid).value;
		var stable = document.getElementById('subq_' + qid);
		if(last_questions[qid] < new_type){
			while(last_questions[qid] < new_type){
				last_questions[qid]++;
				var nr = stable.insertRow(-1);
				var nc1 = nr.insertCell(0);
				var nc2 = nr.insertCell(1);
				nc1.innerHTML = '<b>Answer #' + last_questions[qid] + '</b>:';
				nc2.innerHTML = '<input style = "width:100%" name = "tquestion[' + qid + '][ans][' + last_questions[qid] + ']">';
			}
		}
		if(last_questions[qid] > new_type){
			while(last_questions[qid] > new_type){
				last_questions[qid]--;
				stable.deleteRow(-1);
			}
		}
		var xselecter = document.getElementById('correct_a_' + qid);
		xselecter.innerHTML = '<select id = "correct_a_' + qid + '" name = "tquestion[' + qid + '][answer]" style = "width:100%">';
		var count = 1;
		while(count <= new_type){
			xselecter.innerHTML = xselecter.innerHTML + '<option value = "' + count + '">Answer #' + count + '</option>';
			count++;
		}
		xselecter.innerHTML = xselecter.innerHTML + '</select>';
	}

	function question_add(){
		var table_handle = document.getElementById('rhtable');
		var nqrow = table_handle.insertRow(-1);
		var nc1 = nqrow.insertCell(0);
		last_question++;
		last_questions[last_question] = 4;
		console.log(last_questions);
		nc1.innerHTML = '<table border = "0" cellspacing = "0" cellpadding = "2"><tr><td colspan = "2"><b>Question #' + last_question + '</b></td></tr><tr><td colspan = "2"><input style = "width:100%" name = "tquestion[' + last_question + '][question]" placeholder = "Your question goes here."></td></tr><td>Answers amount:</td><td><select id = "thinp_1_' + last_question + '" style = "width:100%" onChange = "change_qtype(' + last_question + ')"><option value = "6">6 ansers</option><option value = "5">5 answers</option><option value = "4" SELECTED>4 anwsers</option><option value = "3">3 answers</option><option value = "2">2 answers</option></select></td></tr><tr><td>Correct answer:</td><td><select id = "correct_a_' + last_question + '" name = "tquestion[' + last_question + '][answer]" style = "width:100%"><option value = "1">Answer #1</option><option value = "2">Answer #2</option><option value = "3">Answer #3</option><option value = "4">Answer #4</option></select></td></tr></table><table border = "0" cellspacing = "0" cellpadding = "2" id = "subq_' + last_question + '"><tr><td><b>Answer #1:</b></td><td><input style = "width:100%" name = "tquestion[' + last_question + '][ans][1]"></td></tr><td><b>Answer #2:</b></td><td><input style = "width:100%" name = "tquestion[' + last_question + '][ans][2]"></td></tr><td><b>Answer #3:</b></td><td><input style = "width:100%" name = "tquestion[' + last_question + '][ans][3]"></td></tr><td><b>Answer #4:</b></td><td><input style = "width:100%" name = "tquestion[' + last_question + '][ans][4]"></td></tr>';
	}

	function question_remove(){
		var table_handle = document.getElementById('rhtable');
		if(last_question != 0){
			table_handle.deleteRow(-1);
			last_question--;
		}
		else {
			alert('There\'s no questions to remove.');
		}
	}

	function tvalid(){
		if(last_question < 1){
			alert('I know one guy in Nevada, he doesn\'t ask questions, neither do I - but let\'s ask at least one here.');
			return false;
		}
		if(document.getElementById('v1').value == ''){
			alert('Name is, like, you know, required.');
			return false;
		}
		if(document.getElementById('v2').value == ''){
			alert('Nameless trophy is good concept but I don\'t think they\'ll get it.');
			return false;
		}
		if(document.getElementById('v3').value == ''){
			alert('Error: URL to fancy trophy icon not found.');
			return false;
		}
		if(isNaN(document.getElementById('v4').value * 1) || document.getElementById('v4').value == ''){
			alert('Amount of questions required to acquire trophy is required to fulfil requirements for required action.');
			return false;
		}
		if(parseInt(document.getElementById('v4').value) > last_question){
			alert('Yeah, screw them! They must answers more questions than we have to acquire trophy! Yee-haw baby!');
			return false;
		}
		if(parseInt(document.getElementById('v4').value) < 1){
			alert('Zero or negative amount of questions required? Good one, man, good one. But way too easy.');
			return false;
		}
		return true;		
	}
</script>
<form method = "POST" onSubmit = "return tvalid()">
<input type = "hidden" name = "action" value = "<?php echo ($is_new ? 'create_doc' : 'edit_doc'); ?>">
<?php
	if(!$is_new)
		echo '<input type = "hidden" name = "docid" value = "'.strip_tags($Q['id']).'">';
?>
<table border = "0" cellspacing = "0" cellpadding = "2" width = "90%">
<col width = "20%">
<col width = "80%">
<tr><td colspan = "2"><h3>Configuration</h3></td></tr>
<tr><td><b>Document name</b></td><td><input id = "v1" type = "text" name = "name" style = "width:100%" placeholder = "Document name."></td></tr>
<tr><td colspan = "2">Name of your document, for example "The example.com TOS".</td></tr>
<tr><td><b>Document domain</b></td><td><input type = "text" name = "domain" style = "width:100%" placeholder = "Document domain."></td></tr>
<tr><td colspan = "2">Domain of site where document is, for example "example.com" or "docs.example.com".</td></tr>
<tr><td><b>Document URL</b></td><td><input type = "text" name = "url" style = "width:100%" placeholder = "Document URL."></td></tr>
<tr><td colspan = "2">Direct URL to document you're binding IKTT to, for example "http://example.com/docs/tos.html"</td></tr>
<tr><td><b>Trophy name</b></td><td><input id = "v2" type = "text" name = "trophy" style = "width:100%" placeholder = "Trophy name."></td></tr>
<tr><td colspan = "2">Name of trophy for user who readed your document, for example "Explorer of example.com".</td></tr>
<tr><td><b>Questions to win</b></td><td><input type = "number" id = "v4" type = "text" name = "trophymin" style = "width:100%" placeholder = "Questions to win."></td></tr>
<tr><td colspan = "2">Amount of questions user must answer correctly to acquire your trophy.</td></tr>
<tr><td><b>Trophy icon</b></td><td><input id = "v3" type = "text" name = "trophyicon" style = "width:100%" value = "http://54.174.198.214/default_trophy.png" onChange = "icon_reload()" id = "fed_icon_url"></td></tr>
<tr><td colspan = "2">URL to trophy icon. Should be small graphic with transparent background. Current icon:<br><div id = "fed_icon"><img border = "0" src = "http://54.174.198.214/default_trophy.png"></div><br><a href = "javascript:void(0);" onClick = "icon_reload()">Reload icon</a> | <a href = "javascript:void(0);" onClick = "document.getElementById('fed_icon_url').value='http://54.174.198.214/default_trophy.png';icon_reload()">Load default icon</a></td></tr>
</table>
<table border = "0" cellspacing = "0" cellpadding = "2" id = "rhtable">
<tr><td><h3>Questions</h3></td></tr>
</table>
<table border = "0" cellspacing = "0" cellpadding = "2" width = "90%">
<tr><td colspan = "2"><a href = "javascript:void(0);" onClick = "question_add()">Add one question</a> | <a href = "javascript:void(0);" onClick = "question_remove()">Remove last question</a><br></td></tr>
<tr><td colspan = "2"><center><input type = "submit" value = "Save!" style = "width:100%"></center></td></tr>
<tr><td colspan = "2"><center><small>Note: in current state (Hackathon version) questions and configuration should be static, so you won't be able to edit it after saving.</small></center></td></tr>
</table>
</form>
			<?php
		}

		public function load_sidebar(){
			global $account;
			echo '-> <a href = "index.php">Home</a><br>
				-> <a href = "index.php?s=implementation">Implementation</a><br>
				-> <a href = "index.php?s=example">Example</a><br>
				-> <a href = "index.php?s=login">Sign In</a><br>
				-> <a href = "index.php?s=aboutproject">About project</a><br>';
			if($account->is_logged(true)){
				echo '<br><b>[ '.$_SESSION['ulogin'].' ]</b><br>
				-> <a href = "index.php?s=admin_logged">Dashboard</a><br>
				-> <a href = "index.php?s=admin_quests">Documents</a><br>
				-> <a href = "index.php?s=admin_addq">New document</a><br>
				-> <a href = "index.php?s=generator">Code generator</a><br>
				-> <a href = "index.php?s=admin_logout">Logout</a><br>';
			}
		}

		private function handle_error(){
			global $errmsg;
			if(!empty($errmsg))
				return '<b>Error:</b> '.$errmsg.'<br>';
			return '';
		}
	}

	$quest = new c_quest();
	$interface = new c_interface();
	$account = new c_account();
	$bind = new c_bind();
?>

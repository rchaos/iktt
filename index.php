<?php
	require_once('lib.php');
	$bind->bind_post();
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
<h3>Turn reading TOS, EULA, legal documents and others into one of the favorite activities of Your clients.</h3>
</div>
<div id="left_sidebar">
<?php
	$interface->load_sidebar();
?>
</div>
<div id="main_content">
<?php
	$bind->bind_content();
?>
</div>
</div>	
</body>
</html>

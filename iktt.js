var iktt_current_panel = 1;

function iktt_mk_get(url, wheret){
	wheret.innerHTML = 'Loading...';
	var conn;
	if(window.XMLHttpRequest){
		conn = new XMLHttpRequest();
	}
	else {
		conn = new ActiveXObject("Microsoft.XMLHTTP");
	}
	conn.onreadystatechange = function(){
		if(conn.status == 200){
			wheret.innerHTML = conn.responseText;
		}
	}
	conn.withCredentials = true;
	conn.open("GET", url, true);
	conn.send();
}

function iktt_handle_stuff(element){
	var url = 'https://b88976b7-cc06b4e4e494.my.apitools.com/';
	var act = element.getAttribute('iktt-act');
	if(act != 'info' && act != 'main'){
		act = 'void';
	}
	url = url + '?act=' + act;
	if(element.hasAttribute('docid')){
		url = url + '&docid=' + element.getAttribute('docid');
	}
	if(element.hasAttribute('attachstats')){
		url = url + '&attachstats=y';
	}
	if(element.hasAttribute('attachuserinfo')){
		url = url + '&attachuserinfo=y';
	}
	if(element.hasAttribute('onlylocal')){
		url = url + '&onlylocal=y';
	}
	if(element.hasAttribute('showtrophy')){
		url = url + '&showtrophy=y';
	}
	if(element.hasAttribute('showtrophyname')){
		url = url + '&showtrophyname=y';
	}
	if(element.hasAttribute('allowlogin')){
		url = url + '&allowlogin=y';
	}
	if(element.hasAttribute('attachpanel')){
		url = url + '&attachpanel=y';
	}
	if(act == 'main'){
		iktt_current_panel = 1;
	}
	iktt_mk_get(url, element);
}

function iktt_do_login(element){
	iktt_mk_get('https://b88976b7-cc06b4e4e494.my.apitools.com/?act=dologin', element);
}

function iktt_do_register(element){
	iktt_mk_get('https://b88976b7-cc06b4e4e494.my.apitools.com/?act=doregister', element);
}

function iktt_do_login_step(element, unid){
	var login = document.getElementById('my_login_' + unid).value;
	var password = document.getElementById('my_password_' + unid).value;
	iktt_mk_get('https://b88976b7-cc06b4e4e494.my.apitools.com/?act=dologinstep&login=' + encodeURIComponent(login) + '&pass=' + encodeURIComponent(password), element);
}

function iktt_do_register_step(element, unid){
	var login = document.getElementById('my_login_' + unid).value;
	var password = document.getElementById('my_password_' + unid).value;
	var rpassword = document.getElementById('my_password_rep_' + unid).value;
	var mail = document.getElementById('my_mail_' + unid).value;
	var is_error = '';
	if(login == ''){
		is_error = is_error + 'Login is required. ';
	}
	if(password == ''){
		is_error = is_error + 'Password is required. ';
	}
	if(password != rpassword){
		is_error = is_error + 'Passwords doesn\'t match.';
	}
	if(is_error != ''){
		alert(is_error);
	}
	if(is_error == ''){
		iktt_mk_get('https://b88976b7-cc06b4e4e494.my.apitools.com/?act=doregisterstep&login=' + encodeURIComponent(login) + '&pass=' + encodeURIComponent(password) + '&mail=' + encodeURIComponent(mail), element);
	}
}

function iktt_mainq_next(){
	document.getElementById('iktt_mainq_' + iktt_current_panel).style.display = 'none';
	iktt_current_panel++;
	document.getElementById('iktt_mainq_' + iktt_current_panel).style.display = 'inline';
}

function iktt_mainq_final(docid){
	var qanswers = "";
	var counter = 0;
	var subcounter = 0;
	var answers = document.getElementsByName('iktt_answer_0');
	while(answers.length > 0){
		for(subcounter = 0; subcounter < answers.length; subcounter++){
			if(answers[subcounter].checked){
				qanswers = qanswers + '&ans_' + counter + '=' + answers[subcounter].value;
			}
		}
		counter++;
		answers = document.getElementsByName('iktt_answer_' + counter);
	}
	var url = 'https://b88976b7-cc06b4e4e494.my.apitools.com/?act=doquest&docid=' + docid + qanswers;
	iktt_mk_get(url, document.getElementById('iktt_mainq_1').parentNode);
}

function iktt_bind(){
	var bind = document.getElementsByClassName('iktt-api');
	var bind_c;
	for(bind_c = 0; bind_c < bind.length; bind_c++){
		iktt_handle_stuff(bind[bind_c]);
	}
}

iktt_bind();

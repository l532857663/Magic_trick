function login(){
	var data_User;
    var ip_socket = "ws://192.168.11.139:7000";
	var username = document.getElementById("username").value;
	var password = document.getElementById("password").value;
//	console.log(username,password);
	if (username == ""){
			alert("帐号为空，请填写")
			return
	}else if(password == ""){
			alert("密码为空，请填写")
			return
	}

	data_User = '{"userFlag":"ture","userNM":"' + username + '", "passWD":"' + password + '"}';
    ws = new WebSocket(ip_socket + '/htdoc');
    ws.onopen = function(){
        ws.send(data_User);
    }
    ws.onmessage = function(receive_Data){
        var Data = receive_Data.data;
        if(Data == '"FAILURE"'){
            loginNO();
            return;
        }
        var htmlAll = Data;
        console.log(htmlAll);
        htmlAll = "<!doctype html>\r\n<html>\r\n<head>\r\n<meta charset=\"utf-8\">\r\n<\/meta>\r\n<title>\u6570\u636e\u5f00\u53d1\u5e73\u53f0<\/title>\r\n<link href=\".\/css\/public.css\" rel=\"stylesheet\" type=\"text\/css\" \/>\r\n<\/head>\r\n<body style=\"background:#f8f3f0;\">\r\n    <div class=\"index_left\">\r\n        <div class=\"index_left_top\">\u6570\u636e\u5f00\u53d1\u5e73\u53f0<\/div>\r\n        <div class=\"menu\">\r\n            <ul id=\"ull\">\r\n                <li ><a href=\"#\" target=\"right\">\u6570\u636e\u5217\u8868<\/a><\/li>\r\n                <li ><a href=\"#\" target=\"right\">\u6848\u4ef6\u6dfb\u52a0<\/a><\/li>\r\n                <li ><a href=\"#\" target=\"right\">\u6848\u4ef6\u7ba1\u7406<\/a><\/li>\r\n                <li ><a href=\"#\" target=\"right\">\u6587\u672c\u89e3\u6790<\/a><\/li>\r\n                <li ><a href=\"#\" target=\"right\">\u6587\u4ef6\u4e0a\u4f20<\/a><\/li>\r\n            <\/ul>\r\n        <\/div>\r\n    <\/div>\r\n    <div class=\"index_right\">\r\n        <div class=\"index_right_top\">\r\n            <div class=\"admin\">\r\n                <b>Hi!<\/b>\r\n                <b>admin<\/b>\r\n            <\/div> \r\n            <div class=\"img01\">\r\n                <img src=\".\/images\/index05.png\" width=\"60\" height=\"60\">\r\n            <\/div>\r\n\r\n            <div class=\"clear\"><\/div>\r\n        <\/div>\r\n        <div class=\"index_right_con\">\r\n            <iframe name=\"right\" id=\"rightMain\" src=\"#\" frameborder=\"no\" scrolling=\"auto\"  height=\"100%\" allowtransparency=\"true\" width=\"100%\"\/>\r\n        <\/div>\r\n\r\n    <\/div>\r\n<\/body>\r\n<\/html>\r\n";
        console.log(htmlAll);
        document.documentElement.innerHTML = htmlAll;
    }

}

function KeyLogin()
{
	if (event.keyCode == 13)
	{
			event.returnValue=false;
			event.cancel = true;
			login();
	}
}

function loginNO() {
    //数据错误提示操作
	alert ("帐号或密码错误");
}

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

	data_User = '{"userFlag":"ture","userNM":"'+username+'", "passWD":"'+password+'", "htmlNM":"main.html"}';
    ws = new WebSocket(ip_socket);
    ws.onopen = function(){
        ws.send(data_User);
    }
    ws.onmessage = function(receive_Data){
        console.log ("get msg");
        var Data = receive_Data.data;
        if(Data == '"FAILURE"'){
            loginNO();
            return;
        }

        document.getElementsByTagName("html")[0].innerHTML = Data;
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

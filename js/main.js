
//菜单栏页面跳转
function jump_html(htmlname){
	data_User = '{"userFlag":"ture","htmlNM":"'+htmlname+'"}';
    ws.send(data_User);
    ws.onmessage = function(receive_Data){
        var Data = receive_Data.data;
        console.log(1,Data);
        if(Data == '"FAILURE"'){
            turnNO();
            return;
        }

        document.getElementById("main").innerHTML = Data;
    }
}

function turnNO() {
    alert("页面不存在");
}

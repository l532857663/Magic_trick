//a标签点击函数式跳转: htmlname->跳转地址、
function a_onclick(htmlname) {
    var idStr = "";
    var dataMessage = '{"userFlag":"ture","htmlNM":"'+ htmlname +'"}';
    ws.send(dataMessage);
    ws.onmessage = function(receive_data){
        var Data = receive_data.data;
        if(Data == ''){
            errorPrompt("页面不存在","alertWay");
            return;
        }
        document.getElementById(idStr).innerHTML = Data;
        window.location.href = window.location.pathname + "#" + htmlname;
    }
}

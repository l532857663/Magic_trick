//数据、局部等展示隐藏操作
function divShowHide(idStrBtn, idStrDiv){
    var oAdd = document.getElementById(idStrBtn);
    var add_con = document.getElementById(idStrDiv);
    var onFfadd = true;
    oAdd.onclick=function(){
        if(onFfadd){
            add_con.style.display = "block";
            onFfadd = !onFfadd;
        }else{
            add_con.style.display = "none";
            onFfadd = !onFfadd;
        }
    }
}

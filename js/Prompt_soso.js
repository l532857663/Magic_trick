//错误提示: errorData->提示内容,errotType->提示方式
    //弹框提示:errorType=alertWay
    //页面提示:errorType=pageWay
function errorPrompt(errorData, errorType){
    switch(errorType){
        case "alertWay": alert(errorData);break;
        case "pageWay":break;
        default:console.log("hehe");
    }
}







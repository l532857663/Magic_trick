<?php

//中转文件，访问数据的中转处理
class Transfer {

    #中转数据的基础信息
    public $access_filename;
    public $access_uri;
    public $access_method;
    public $access_type;

    function Jump_html($filename) {
        $uri = dirname(dirname(__FILE__))."/main/".$filename;
        if(file_exists($uri)){
            $this->access_filename = $filename;
            $this->access_uri = $uri;
            $htmlAllData = file_get_contents($uri);
            return $htmlAllData;
        }
    }
}

/**
 * 测试代码
 *
$t_obj = new Transfer();
$htmlAll = $t_obj->Jump_html("main.html");
echo $htmlAll;

 */

?>

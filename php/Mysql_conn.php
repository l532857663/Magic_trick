<?php

//数据库操作文件
class Mysql_conn {
    #数据库连接配置
    private $Host = "localhost";
    private $Post = "3306";
    private $Username = "root";
    private $Password = "123456";
    private $DBname = "Magic";
    protected $conn;

    #连接数据库
    public function __construct() {
        $conn = mysqli_connect($this->Host, $this->Username, $this->Password, $this->DBname, $this->Post);
        if ($conn->connect_error) {
            die("连接错误：" . $conn->connect_error);
        }
        $this->conn = $conn;
    }

    #关闭数据库
    public function __destruct() {
        mysqli_close($this->conn);
    }

    #数据库查询
    protected function Mysql_select($query) {
        $rs = mysqli_query($this->conn, $query);
        if(!$rs){
            $err_str = mysqli_errno($this->conn);
            return $err_str;
        }
        $rs_arr = array();
        while ($row = mysqli_fetch_assoc($rs)) {
            $rs_arr[] = $row;
        }
        return $rs_arr;
    }

    #数据库操作
    public function Authentication($username, $password) {
        $query = "select password from UserList where username=\"$username\"";
        $rs = $this->Mysql_select($query);
        $password = substr(md5($password),0,20);
        if(!$rs){
            return "\$this->failureLogin(\$socket);";
        }
        foreach($rs as $val) {
            if($val["password"] === $password) {
                //return "successLogin();"
                return "\$this->successLogin(\$socket);";
            }else{
                //return "failureLogin();";
                return "\$this->failureLogin(\$socket);";
            }
        }
    }

}

/** 测试代码
function successLogin(){
    echo "success for you";
}
function failureLogin(){
    echo "failure for you";
}
 
$a = "123123";
$b = "123456";
$sql_obj = new Mysql_conn();
$result = $sql_obj->Authentication($a,$b);
echo $result;
//eval($result);
 */

/**
 * 
 CREATE DATABASE Magic;
CREATE TABLE UserList (
    `id` int UNSIGNED AUTO_INCREMENT,
    `username` varchar(20) NOT NULL,
    `password` varchar(20) NOT NULL,
    `status` int(1) DEFAULT 1,
    PRIMARY KEY (`id`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

$a = "admin";
$b = "123456";
$c = substr(md5($b),0,20);
echo $c."\n";
$d = "insert into UserList (username,password) values ('$a','$c');";
echo $d."\n";
 **/
?>

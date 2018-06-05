<?php
require_once("./Mysql_conn.php");
require_once("./Transfer.php");

class SocketChat {
	private $timeout = 60;
	private $handShake = False;
	private $master = 1;
	private $port = 2000;
	private static $connectPool = [];
	private static $maxConnectNum = 1024;
    private static $chatUser = []; 
    public $Mysql_obj;
    public $Transfer_obj;

	public function __construct( $Mysql_obj, $Transfer_obj, $port = 0 ) {
        !empty( $port ) && $this->port = $port;
        $this->Mysql_obj = $Mysql_obj;
        $this->Transfer_obj = $Transfer_obj;
        $this->master = socket_create_listen( $this->port );
		if( !$this->master )
			throw new \ErrorException("listen {$this->port} fail !");
		self::$connectPool[] = $this->master;
		$this->startServer();
    }

	public function startServer() {
        while(true){
            $readFds = self::$connectPool;
            @socket_select( $readFds, $writeFds, $e = null, $this->timeout ); 

            $pid = pcntl_fork();
            //父进程和子进程都会执行下面代码
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } else if ($pid == 0) {
                //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
                $socket = end($readFds);
                $client = socket_accept( $socket ); 
                if ($client){
                    $this->keepLink($client);
                }
                break;
            } else {
                echo "ceshi3\n";
                sleep(1);
                //重新等待
                continue;
            }
        }
    }
            
    function keepLink($socket) {
        echo "ceshi8\n";
        while(true){
            $bytes = @socket_recv($socket, $buffer, 2048, 0);
            var_dump($bytes);
            if( $bytes == 0 ){
                $this->disConnect( $socket );
                echo "ceshi9\n";
                break;
            }else{
                if( !$this->handShake ){
                    $this->doHandShake( $socket, $buffer ); 
                }else{
                    $buffer = $this->decode( $buffer );
                    $this->parseMessage( $buffer, $socket );
                }
            }
            echo "ceshi10\n";
        }
        echo "over\n";
        return;
    }


	function doHandShake($socket, $buffer){
		list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
		$upgrade = "HTTP/1.1 101 Switching Protocol\r\n"
			. "Upgrade: websocket\r\n" 
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Accept: "
			. $this->calcKey($key)
			. "\r\n\r\n";
		socket_write($socket, $upgrade, strlen($upgrade)); 
		$this->handShake = true; return true;
    }

	function getHeaders( $req ) {
		$r = $h = $o = $key = null;
		if(preg_match("/GET (.*) HTTP/" , $req, $match)) 
		{ 
			$r = $match[1];
		}
		if (preg_match("/Host: (.*)\r\n/" , $req, $match))
		{ 
			$h = $match[1];
		} 
		if (preg_match("/Origin: (.*)\r\n/" , $req, $match))
		{
			$o = $match[1];
		} 
		if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match))
		{ 
			$key = $match[1];
		}
		return [$r, $h, $o, $key];
    }

	function calcKey( $key ) {
        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		return $accept;
    }

	public function frame( $buffer ){
		$len = strlen($buffer);
		if ($len <= 125){
			return "\x81" . chr($len) . $buffer;
		}
		else if ($len <= 65535){
			return "\x81" . chr(126) . pack("n", $len) . $buffer;
		} 
		else{
			return "\x81" . char(127) . pack("xxxxN", $len) . $buffer;
		} 
    }

	function decode( $buffer ){ 
		$len = $masks = $data = $decoded = null;
		$len = ord($buffer[1]) & 127;
		if ($len === 126)
		{ 
			$masks = substr($buffer, 4, 4); $data = substr($buffer, 8);
		} 
		else if ($len === 127)
		{ 
			$masks = substr($buffer, 10, 4); $data = substr($buffer, 14);
		}
		else 
		{ 
			$masks = substr($buffer, 2, 4);
			$data = substr($buffer, 6);
		} 
		for ($index = 0; $index < strlen($data); $index++)
		{ 
			$decoded .= $data[$index] ^ $masks[$index % 4];
		} 
		return $decoded;
    }

	function connect( $socket ){
		array_push( self::$connectPool, $socket );
		$this->runLog("\n" . $socket . " CONNECTED!");
		$this->runLog(date("Y-n-d H:i:s")); 
    }

	function disConnect( $socket ){
		$index = array_search( $socket, self::$connectPool );
		socket_close( $socket );
		$this->runLog( $socket . " DISCONNECTED!" );
		if ($index >= 0){
			array_splice( self::$connectPool, $index, 1 );
		}
    }
    
	public function runLog( $mess = '' ){
		echo $mess . PHP_EOL;
    }

	public function log( $mess = '' ){ 
		@file_put_contents( './' . date("Y-m-d") . ".log", date('Y-m-d H:i:s') . " " . $mess . PHP_EOL, FILE_APPEND );
	}

    protected function successLogin($socket,$htmlName) {
        $message = $this->Transfer_obj->Jump_html($htmlName);
        $this->send($socket, $message);
    }
    protected function failureLogin($socket) {
        $message = "FAILURE";
        $this->send($socket, $message);
    }

	public function parseMessage( $message, $socket ){
		$message = json_decode( $message, true );
        var_dump($message);
		$user = $message['userFlag'];
		if(!empty($user) && $user === "ture"){
            $username = $message['userNM'];
            $password = $message['passWD'];
            $htmlname = $message['htmlNM'];
            $result = $this->Mysql_obj->Authentication($username,$password,$htmlname);
            eval($result);
		}
    }

	public function send( $client, $msg ){
		//$msg = $this->frame( json_encode( $msg ) ); 
		$msg = $this->frame($msg); 
		$return = socket_write( $client, $msg, strlen($msg) ); 
		return $return;
	} 
} 
$port = 7000;
$mysql_obj = new Mysql_conn();
$transfer_obj = new Transfer();
new SocketChat( $mysql_obj, $transfer_obj, $port ); 

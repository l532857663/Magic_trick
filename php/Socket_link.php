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
		$this->startServer();
    }
/*
	public function startServer() {
        $this->master = socket_create_listen( $this->port );
		if( !$this->master )
			throw new \ErrorException("listen {$this->port} fail !");
		self::$connectPool[] = $this->master;
		while( true ){
            echo "ceshi1";
			$readFds = self::$connectPool;
            echo "ceshi2";
			@socket_select( $readFds, $writeFds, $e = null, $this->timeout ); 
            echo "ceshi3";
            foreach( $readFds as $socket ){
            echo "ceshi4";
				if( $this->master == $socket ){
            echo "ceshi5";
					$client = socket_accept( $this->master ); 
					$this->handShake = False;
					if ($client < 0){
						$this->log('clinet connect false!');
						continue; 
					}
					else{
						if( count( self::$connectPool ) > self::$maxConnectNum )
							continue;
						$this->connect( $client );
					} 
				}else{
            echo "ceshi6";
					$bytes = @socket_recv($socket, $buffer, 2048, 0);
					if( $bytes == 0 ){
						$this->disConnect( $socket );
					}else{ 
						if( !$this->handShake ){
							$this->doHandShake( $socket, $buffer ); 
						}else{
							$buffer = $this->decode( $buffer );
							$this->parseMessage( $buffer, $socket );
						}
					}
				}
			}
            echo "ceshi7";
        }
    }
*/
	public function startServer() {
        $this->master = socket_create_listen( $this->port );
		if( !$this->master )
			throw new \ErrorException("listen {$this->port} fail !");
		self::$connectPool[] = $this->master;
        echo "ceshi1\n";
        while(true) {
            $readFds = self::$connectPool;
            echo "ceshi2\n";
            @socket_select( $readFds, $writeFds, $e = null, $this->timeout ); 
            echo "ceshi3\n";
        }
        foreach( $readFds as $socket ){
        echo "ceshi4\n";
            if( $this->master == $socket ){
        echo "ceshi5\n";
                $client = socket_accept( $this->master ); 
                $this->handShake = False;
                if ($client < 0){
                    $this->log('clinet connect false!');
                    continue; 
                }
                else{
                    if( count( self::$connectPool ) > self::$maxConnectNum )
                        continue;
                    $this->connect( $client );
                } 
            }else{
        echo "ceshi6\n";
                $bytes = @socket_recv($socket, $buffer, 2048, 0);
                if( $bytes == 0 ){
                    $this->disConnect( $socket );
                }else{ 
                    if( !$this->handShake ){
                        $this->doHandShake( $socket, $buffer ); 
                    }else{
                        $buffer = $this->decode( $buffer );
                        $this->parseMessage( $buffer, $socket );
                    }
                }
            }
        }
        echo "ceshi7\n";
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

<?php
require_once("./Mysql_conn.php");
require_once("./Transfer.php");

class Socket_communication {
    private $timeout = 600;
	private $handShake = False;
	private $userLogin = False;
	private $master;
    private $port;
    private $i = 0;
    private static $connectPool = [];
    public $Transfer_obj;

	//public function __construct($Mysql_obj, $Transfer_obj, $port = 0){
	public function __construct($Transfer_obj, $port = 0){
        if(empty($port)){
            die("Not add port\n");
        }
        $this->port = $port;
        $this->Transfer_obj = $Transfer_obj;
        $this->startServer();
    }

    //握手信息
	protected function doHandShake($socket, $buffer){ 
		list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
		$upgrade = "HTTP/1.1 101 Switching Protocol\r\n"
			. "Upgrade: websocket\r\n" 
			. "Connection: Upgrade\r\n"
			. "Sec-WebSocket-Accept: "
			. $this->calcKey($key)
			. "\r\n\r\n";
		socket_write($socket, $upgrade, strlen($upgrade));
        $this->handShake = True;
        return True;
	}
	private function getHeaders($req){
		$r = $h = $o = $key = null;
		if(preg_match("/GET (.*) HTTP/" , $req, $match)){
			$r = $match[1];
		}
		if (preg_match("/Host: (.*)\r\n/" , $req, $match)){
			$h = $match[1];
		}
		if (preg_match("/Origin: (.*)\r\n/" , $req, $match)){
			$o = $match[1];
		}
		if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $req, $match)){
			$key = $match[1];
		}
		return [$r, $h, $o, $key];
	}
	private function calcKey($key){ 
		$accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		return $accept;
	}
	public function frame($buffer){
		$len = strlen($buffer);
		if ($len <= 125){
			return "\x81" . chr($len) . $buffer;
		}else if ($len <= 65535){
			return "\x81" . chr(126) . pack("n", $len) . $buffer;
		}else{
			return "\x81" . chr(127) . pack("xxxxN", $len) . $buffer;
		}
    }

    //编码转换
	private function decode($buffer){
		$len = $masks = $data = $decoded = null;
		$len = ord($buffer[1]) & 127;
		if ($len === 126){
			$masks = substr($buffer, 4, 4); $data = substr($buffer, 8);
		}else if ($len === 127){
			$masks = substr($buffer, 10, 4); $data = substr($buffer, 14);
        }else{
            $masks = substr($buffer, 2, 4);
			$data = substr($buffer, 6);
		}
        for ($index = 0; $index < strlen($data); $index++){
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
		return $decoded;
	}

    //关闭清除链接
	protected function disConnect($socket){ 
        $index = array_search($socket, self::$connectPool);
		socket_close($socket);
		if ($index >= 0){
			array_splice( self::$connectPool, $index, 1 );
		} 
	} 

    public function startServer(){
        $this->master = socket_create_listen($this->port);
        if(!$this->master){
            throw new \ErrorException("listen {$this->port} fail !");
        }
        self::$connectPool[] = $this->master;
        while(True){
            $readFds = self::$connectPool;
            @socket_select( $readFds, $writeFds, $e = null, $this->timeout );
            $pid = pcntl_fork();
            if($pid == -1){
                die("Process open error");
            }else if($pid == 0){
                if(end($readFds)){
                    $socket = socket_accept(end($readFds));
                    if($socket < 0){
                        die("Clint connect error");
                    }
                    array_push(self::$connectPool, $socket);
                    $this->keeplink($socket);
                }
                die();
            }else{
                sleep(1);
                continue;
            }
        }
    }

    protected function keeplink($socket){
        while(True){
            $bytes = @socket_recv($socket, $buffer, 2048, 0);
            if($bytes == 0){
                echo "over2\n";
                $this->userLogin = False;
				$this->disConnect($socket);
                die();
			}else{
				if(!$this->handShake){
					$this->doHandShake($socket, $buffer);
				}else{
					$buffer = $this->decode($buffer);
					$this->parseMessage($buffer, $socket);
				}
            }
            echo "over1\n";
        }
    }

    public function parseMessage($message, $socket){
        $message = json_decode( $message, true );
        echo "flage: \n";
        var_dump($this->userLogin);
        if($this->userLogin){
        }else{
            $Mysql_obj = new Mysql_conn();
            $username = $message['userNM'];
            $password = $message['passWD'];
            $htmlname = $message['htmlNM'];
            $result = $Mysql_obj->Authentication($username,$password);
            if($result){
                $message = $this->Transfer_obj->Jump_html($htmlname);
                $this->userLogin = True;
            }else{
                $message = "FAILURE";
            }
            $this->send($socket, $message);
        }

    }

	public function send($socket, $msg){
		$msg = $this->frame($msg);
		socket_write($socket, $msg, strlen($msg));
	} 

}

$Port = 7000;
$Transfer_obj = new Transfer();
//new Socket_communication($Mysql_obj, $Transfer_obj, $Port);
new Socket_communication($Transfer_obj, $Port);
?>

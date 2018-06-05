<?php
require_once("./Transfer.php");

class SocketChat {
	private $timeout = 600;
	private $handShake = False;
	private $master = 1;
	private $port = 2000;
	private static $connectPool = [];
	private static $authPool = [];
	private static $maxConnectNum = 1024;
	private static $chatUser = []; 
	public $Transfer_obj;

	public function __construct( $Transfer_obj, $port = 0 ) 
	{ 
		!empty( $port ) && $this->port = $port;
		$this->Transfer_obj = $Transfer_obj;
		$this->startServer();
	}

	public function startServer() 
	{ 
		$this->master = socket_create_listen( $this->port );
		if( !$this->master )
			throw new \ErrorException("listen {$this->port} fail !");
		self::$connectPool[] = $this->master;
		while( true ){
			$readFds = self::$connectPool;
			@socket_select( $readFds, $writeFds, $e = null, $this->timeout ); 

			$pid = pcntl_fork();
			if($pid == -1)
			{
				die ();
			}else if($pid == 0)
			{
				if(end($readFds))
				{
					$socket = socket_accept( end($readFds) );
					$this->sonServer($socket);
				}
				break;
			}else {
				sleep(1);
				continue;
			}
		}

	} 

	public function sonServer($socket)
	{
		while (true)
		{
			$bytes = @socket_recv($socket, $buffer, 2048, 0);
			if( $bytes == 0 ){
				$re = $this->disConnect( $socket );
				if(!$re)
				{
					break;
				}
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

	public function parseMessage( $message, $socket )
	{
		$message = json_decode( $message, true );
		$authlogin = self::$authPool;
		$index = in_array( $socket, $authlogin );
		if ($index){
			//开始传送主页的部分html代码
			//$mystr = $this->partHtml($message['htmlNM']);
			//$status = $this->send($socket, $mystr);
			//if($status == false)
			//{
			//	die();
			//}
			$message = $this->Transfer_obj->Jump_html($message['htmlNM']);
			if($message){
				$this->send($socket, $message);
			}else{
				$this->send($socket, "FAILURE");
			}
			
			sleep(2);

		}else{

		//	$user = $message['userFlag'];
		//	if(!empty($user) && $user === "ture"){
				$mysql_user = '';
				$mysql_pass = '';
				$username = $message['userNM'];
				$password = $message['passWD'];
				$htmlname = $message['htmlNM'];
				$conn = mysqli_connect('localhost','root','123456','Magic');
				if ($conn->connect_error) {
					die("连接错误：" . $conn->connect_error);
				}
				$result_mysql = mysqli_query($conn, 'select * from UserList');
				if($result_mysql)
				{
					$row = mysqli_fetch_array($result_mysql);
					if(is_array($row))
					{
						$mysql_user = $row['username'];
						$mysql_pass = $row['password'];
						/*
						var_dump($row);
						foreach ($row as $key=>$value)
						{
							var_dump($key);
							var_dump($value);
							echo '---'. "\r\n";
						}
						 */
					}
				}else{
					echo 'mysql 查询失败';
				}

        			$password = substr(md5($password),0,20);
				if($username === $mysql_user && $password === $mysql_pass)
				{
					self::$authPool[] = $socket;
					$message = $this->Transfer_obj->Jump_html($htmlname);
					if($message){
						$this->send($socket, $message);
					}else{
						$this->send($socket, "FAILURE");
					}
				}else{
					die();
				}
		//	}


		} 


	}
	public function partHtml($path)
	{
		$file = file_exists($path);
		if($file)
		{
			$myfile = fopen($path);
			if($myfile)
			{
				$mystr = fread($myfile,filesize($path));
				if($mystr)
				{
					fclose($myfile);
				}else{
					return false;
				}
			}else{
				return false;
			}
			return $mystr;
		}else{
			return false;
		}


	}
	public function send( $client, $msg )
	{
		$msg = $this->frame( $msg ); 
		if($return = socket_write( $client, $msg, strlen($msg) ))
		{
			return $return;
		}
		else{
			return false;
		}
	} 
	function doHandShake($socket, $buffer)
	{ 
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
	function getHeaders( $req ) 
	{ 
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
	function calcKey( $key ) 
	{ 
		$accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
		return $accept;
	} 
	public function frame( $buffer )
	{ 
		$len = strlen($buffer);
		if ($len <= 125)
		{ 
			return "\x81" . chr($len) . $buffer;
		} 
		else if ($len <= 65535)
		{ 
			return "\x81" . chr(126) . pack("n", $len) . $buffer;
		} 
		else
		{ 
			return "\x81" . chr(127) . pack("xxxxN", $len) . $buffer;
		} 
	} 
	function decode( $buffer )
	{ 
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
	function connect( $socket )
	{ 
		array_push( self::$connectPool, $socket );
	} 
	function disConnect( $socket )
	{ 
		$index = array_search( $socket, self::$connectPool );
		if(!socket_close($socket))
		{
			return false;	
		}
		socket_close( $socket );
		if ($index >= 0){
			array_splice( self::$connectPool, $index, 1 );
		} 
	} 
} 
$port = 9999;
$transfer_obj = new Transfer();
new SocketChat( $transfer_obj, $port ); 

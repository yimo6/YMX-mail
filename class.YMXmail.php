<?php
class YMXmail{

	private $version = '1.0';

	private $socket;    //Socket

	private $from;    //来源地址
	private $to;      //发送到...
	private $server;  //发送服务器
	private $port;    //发送服务器端口
	private $ssl;     //是否开启SSL
	private $login;   //是否登录

	private $errcode; //错误吗
	private $errstr;  //错误文本
	
	private $mail = array();
	private $log = array();
	private $openlog;

	public function __construct(bool $is_ssl = false,$openlog = true){
		$this -> ssl = $is_ssl;
		$this -> login = false;
		$this -> openlog = false;
	}

	/*
	 * 自动调整发信服务器
	 * @param string $name (名称)
	 */
	public function autoset(string $name){
		switch($name){
			case "qq":
				$this -> server = 'smtp.qq.com';
				$this -> port   = 465;
				break;
			default:
				break;
		}
	}

	/*
	 * 设置发信服务器
	 * @param string $server (发信服务器)
	 * @param int    $port (发信服务器端口)
	 */
	public function set(string $server,int $port){
		$this -> server = $server;
		$this -> port   = $port;
	}

	/*
	 * 发送
	 * @param string $title (标题)
	 * @param string $context (主体内容)
	 * @param string $to (发送到...)
	 * @param bool $html (是否为html,默认否)
	 * @param string $hostname (发送人)
	 */
	public function send(string $title,string $context,string $to,bool $html=false,string $hostname=''){
		if(!isset($this->server,$this->port)) $this->Error("请先使用autoset/set方法设置发信服务器");
		if(!$this->login)  $this->Error("请先使用login方法登入");
		$this->put("RCPT TO:<{$to}>","250");
		$this->put("DATA","354");
		$data = "";
		$data .= "MIME-Version:1.0\r\n";
		$html ? $data .= "Content-type:text/html;charset=utf-8\r\n" : $data .= "Content-type:text/plain;charset=utf-8\r\n";
		$data .= "To: {$to}\r\n";
		$data .= "From: {$hostname}<{$this->from}>\r\n";
		$data .= "Subject: {$title}\r\n\r\n";
		$data .= "{$context}\r\n.\r\n";
		$this->put($data,"250");
		$this->put("QUIT","221");
		$this->close();
		if($this->openlog) $this->writeLog();
		printf("%s","success");
	}

	/*
	 * 登入
	 * @param string $user (账户)
	 * @param string $passwd (密码)
	 */
	public function login($user,$passwd){
		if(!$this->ssl){
			$this -> socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
			socket_connect($this->socket,$this->server,$this->port);
		}else{
			$addr = "tcp://{$this->server}:{$this->port}";
			$this->socket = stream_socket_client($addr,$this->errcode,$this->errstr);
			stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT);
		}
		fread($this->socket,512);
		$this->put("HELO smtp",["220","250"]);
		$this->put("AUTH LOGIN ".base64_encode($user),"334");
		$this->put(base64_encode($passwd),"235");
		$this->put("MAIL FROM:<{$user}>","250");
		$this->from = $user;
		$this->login = true;
	}

	/*
	 * 执行参数
	 * @param string $text/html (执行内容)
	 * @param string $code (预期返回码)
	 */
	public function put($string,$code="0"){
		$string .= "\r\n";
		$this->mail[] = $string;
		$this->ssl ? $this->safe_exec($string,$code) : $this->exec($string,$code);
	}

	/*
	 * 执行
	 * @param string $text (执行内容)
	 * @param string $code (预期返回码)
	 */
	public function exec($text,$code){
		socket_write($this->socket,$text);
		$log = socket_read($this->socket,512);
		$lastcode = substr($res,0,3);
		if(is_array($code)){
			foreach($code as $v){
				if($lastcode==$v) return;
			}
		}else{
			if(($lastcode != (string)$code) && $code!=0) $this->Error(socket_strerror(socket_last_error()));
		}
		$this->log[] = $log;
	}

	/*
	 * 安全执行
	 * @param string $text (执行内容)
	 * @param string $code (预期返回码)
	 */
	public function safe_exec($text,$code){
		fwrite($this->socket,$text);
		$res = fread($this->socket,512);
		$this->log[] = $res;
		$lastcode = substr($res,0,3);
		if(is_array($code)){
			foreach($code as $v){
				if($lastcode==$v) return;
			}
		}else{
			if(($lastcode != (string)$code) && $code!=0) $this->Error("server:{$res}\ncode: {$code}\n{$this->errstr}");
		}
	}

	/*
	 * 关闭连接
	 */
	public function close(){
		$this->ssl ? $this->safe_close() : $this->sock_close();
	}

	/*
	 * Socket关闭连接
	 */
	public function sock_close(){
		socket_close($this->socket);
	}

	/*
	 * 流关闭连接
	 */
	public function safe_close(){
		stream_socket_shutdown($this->socket, STREAM_SHUT_WR);
	}

	/*
	 * 获取日志
	 */
	public function get_log(){
		return $this->log;
	}

	/*
	 * 写日志
	 */
	private function writeLog(){
		$fp = fopen('lastmail.log','w+');
		foreach($this->mail as $value){
			fwrite($fp,$value."\n");
		}
		fclose($fp);
		$fp = fopen('mail.log','a+');
		foreach($this->mail as $value){
			fwrite($fp,$value."\n");
		}
		fwrite($fp,"\n-------------------\n");
		fclose($fp);
	}

	/*
	 * 错误信息
	 * @param string $string (文本)
	 */
	private function Error($string){
		exit(printf("%s\n",$string));
	}

}
?>
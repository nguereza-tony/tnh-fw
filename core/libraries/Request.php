<?php

	class Request{
		public $get = null;
		public $post = null;
		public $server = null;
		public $cookie = null;
		public $file = null;
		public $session = null;
		public $query = null;
		public $method = null;
		public $requestUri = null;
		public $header = null;
		
		public function __construct(){
			$this->get = $_GET;
			$this->post = $_POST;
			$this->server = $_SERVER;
			$this->query = $_REQUEST;
			$this->cookie = $_COOKIE;
			$this->file = $_FILES;
			$this->session = new Session();
			$this->method = $this->server('REQUEST_METHOD');
			$this->requestUri = $this->server('REQUEST_URI');
			$this->header = array();
			if(function_exists('apache_request_headers')){
				$this->header = apache_request_headers();
			}
			else if(function_exists('getallheaders')){
				$this->header = getallheaders();
			}
		}
		
		public function get($key, $xss = true){
			$get = isset($this->get[$key])?$this->get[$key]:null;
			if($xss){
				if(is_array($get)){
					$get = array_map('htmlspecialchars', $get);
				}
				else{
					$get =  htmlspecialchars($get);
				}
			}
			return $get;
		}
		
		
		public function query($key, $xss = true){
			$query = isset($this->query[$key])?$this->query[$key]:null;
			if($xss){
				if(is_array($query)){
					$query = array_map('htmlspecialchars', $query);
				}
				else{
					$query =  htmlspecialchars($query);
				}
			}
			return $query;
		}
		
		public function post($key, $xss = true){
			$post = isset($this->post[$key])?$this->post[$key]:null;
			if($xss){
				if(is_array($post)){
					$post = array_map('htmlspecialchars', $post);
				}
				else{
					$post =  htmlspecialchars($post);
				}
			}
			return $post;
		}
		
		public function server($key, $xss = true){
			$server = isset($this->server[$key])?$this->server[$key]:null;
			if($xss){
				if(is_array($server)){
					$server = array_map('htmlspecialchars', $server);
				}
				else{
					$server =  htmlspecialchars($server);
				}
			}
			return $server;
		}
		
		
		public function cookie($key, $xss = true){
			$cookie = isset($this->cookie[$key])?$this->cookie[$key]:null;
			if($xss){
				if(is_array($cookie)){
					$cookie = array_map('htmlspecialchars', $cookie);
				}
				else{
					$cookie =  htmlspecialchars($cookie);
				}
			}
			return $cookie;
		}
		
		public function file($key, $xss = true){
			$file = isset($this->file[$key])?$this->file[$key]:null;
			return $file;
		}
		
		
		public function session($key, $xss = true){
			$session = $this->session->get($key);
			if($xss){
				if(is_array($session)){
					$session = array_map('htmlspecialchars', $session);
				}
				else{
					$session =  htmlspecialchars($session);
				}
			}
			return $session;
		}
		
		public function method(){
			return $this->method;
		}
		
		public function requestUri(){
			return $this->requestUri;
		}
		
		public function header($key, $xss = true){
			$header = isset($this->header[$key])?$this->header[$key]:null;
			if($xss){
				if(is_array($header)){
					$header = array_map('htmlspecialchars', $header);
				}
				else{
					$header =  htmlspecialchars($header);
				}
			}
			return $header;
		}
	}
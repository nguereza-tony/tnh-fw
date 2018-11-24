<?php
	/**
	 * TNH Framework
	 *
	 * A simple PHP framework created using the concept of codeigniter with bootstrap twitter
	 *
	 * This content is released under the GNU GPL License (GPL)
	 *
	 * Copyright (C) 2017 Tony NGUEREZA
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License
	 * as published by the Free Software Foundation; either version 3
	 * of the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
	*/

	/**
	 *  @file common.php
	 *  
	 *  Contains most of the utility functions used by the system
	 *  
	 *  @package	core
	 *  @author	Tony NGUEREZA
	 *  @copyright	Copyright (c) 2017
	 *  @license	https://opensource.org/licenses/gpl-3.0.html GNU GPL License (GPL)
	 *  @link	http://www.iacademy.cf
	 *  @version 1.0.0
	 *  @filesource
	 */
	 
	 
	/**
	 *  This function displays an error message to the user and ends the execution of the script.
	 *  
	 *  @param $msg the message to display
	 *  @param $title the message title: "error", "info", "warning", etc.
	 */
	function show_error($msg, $title = 'error'){
		$data['error'] = $msg;
		$data['title'] = ucfirst($title);
		Log::error('['.$title.'] '.strip_tags($msg));
		Response::sendError($data);
		die();
	}
	
	/**
	 *  Function defined for handling PHP exception error message, 
	 *  it displays an error message using the function "show_error"
	 *  
	 *  
	 *  @param object $ex instance of the "Exception" class or a derived class
	 *  @return boolean
	 *  
	 */
	function exception_handler($ex){
		show_error('An exception is occured in file <b>'.$ex->getFile().'</b> at line <b>'.$ex->getLine().'</b> raison : '.$ex->getMessage(), 'PHP Exception #'.$ex->getCode());
		return true;
	}
	
	/**
	 *  function defined for PHP error message handling
	 *  			
	 *  @param int $errno the type of error for example: E_USER_ERROR, E_USER_WARNING, etc.
	 *  @param string $errstr the error message
	 *  @param string $errfile the file where the error occurred
	 *  @param int $errline the line number where the error occurred
	 *  @param array $errcontext the context
	 *  @return boolean	
	 *  
	 */
	function error_handler($errno , $errstr, $errfile , $errline, array $errcontext){
		if (!(error_reporting() & $errno)) {
			return;
		}
		$error_type = 'error';
		switch ($errno) {
			case E_USER_ERROR:
				$error_type = 'error';
				break;

			case E_USER_WARNING:
				$error_type = 'warning';
				break;

			case E_USER_NOTICE:
				$error_type = 'notice';
				break;

			default:
				$error_type = 'error';
				break;
		}
		show_error('An error is occurred in the file <b>'.$errfile.'</b> at line <b>'.$errline.'</b> raison : '.$errstr, 'PHP '.$error_type);
		return true;
	}

	/**
	 * this function is used to set the initial session config regarding the configuration set
	 */
	function set_session_config(){
		//set session params
		$session_handler = Config::get('session_handler', 'files'); //the default is to store in the files
		$session_name = Config::get('session_name');
		if($session_name){
			session_name($session_name);
		}

		if($session_handler == 'files'){
			$session_save_path = Config::get('session_save_path');
			if($session_save_path){
				if(!is_dir($session_save_path)){
					mkdir($session_save_path, 0777);
				}
				session_save_path($session_save_path);
			}
		}
		else if($session_handler == 'database'){
			//load database session handle library
			Loader::library('DBSessionHandler');
			$obj = & get_instance();
			/**
			 * set the session handler class to manage session
			 * TODO: use the best way to load the class DBSessionHandler.
			 */
			session_set_save_handler($obj->dbsessionhandler, true);
			
		}
		$lifetime = Config::get('session_cookie_lifetime', 0);
		$path = Config::get('session_cookie_path', '/');
		$domain = Config::get('session_cookie_domain', '');
		$secure = Config::get('session_cookie_secure', false);
		$httponly = Config::get('session_cookie_httponly', false);
		session_set_cookie_params(
			$lifetime,
			$path,
			$domain,
			$secure,
			$httponly
		);
		if((function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE) || !session_id()){
			session_start();
		}
	}
	
	/**
	* This function is very useful, it allows to recover the instance of the global controller.
	* Note this function always returns the address of the super instance.
	* For example :
	* $obj = & get_instance();
	*  
	*  @return Controller the instance of the "Controller" class
	*  
	*/
	function & get_instance(){
		return Controller::get_instance();
	}
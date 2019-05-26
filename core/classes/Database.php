<?php
    defined('ROOT_PATH') || exit('Access denied');
  /**
   * TNH Framework
   *
   * A simple PHP framework using HMVC architecture
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
  class Database{
	
	/**
	 * The PDO instance
	 * @var object
	*/
    private $pdo                 = null;
    
	/**
	 * The database name used for the application
	 * @var string
	*/
	private $databaseName        = null;
    
	/**
	 * The SQL SELECT statment
	 * @var string
	*/
	private $select              = '*';
	
	/**
	 * The SQL FROM statment
	 * @var string
	*/
    private $from                = null;
	
	/**
	 * The SQL WHERE statment
	 * @var string
	*/
    private $where               = null;
	
	/**
	 * The SQL LIMIT statment
	 * @var string
	*/
    private $limit               = null;
	
	/**
	 * The SQL JOIN statment
	 * @var string
	*/
    private $join                = null;
	
	/**
	 * The SQL ORDER BY statment
	 * @var string
	*/
    private $orderBy             = null;
	
	/**
	 * The SQL GROUP BY statment
	 * @var string
	*/
    private $groupBy             = null;
	
	/**
	 * The SQL HAVING statment
	 * @var string
	*/
    private $having              = null;
	
	/**
	 * The number of rows returned by the last query
	 * @var int
	*/
    private $numRows             = 0;
	
	/**
	 * The last insert id for the primary key column that have auto increment or sequence
	 * @var mixed
	*/
    private $insertId            = null;
	
	/**
	 * The full SQL query statment after build for each command
	 * @var string
	*/
    private $query               = null;
	
	/**
	 * The error returned for the last query
	 * @var string
	*/
    private $error               = null;
	
	/**
	 * The result returned for the last query
	 * @var mixed
	*/
    private $result              = array();
	
	/**
	 * The prefix used in each database table
	 * @var string
	*/
    private $prefix              = null;
	
	/**
	 * The list of SQL valid operators
	 * @var array
	*/
    private $operatorList        = array('=','!=','<','>','<=','>=','<>');
    
	/**
	 * The cache default time to live in second. 0 means no need to use the cache feature
	 * @var int
	*/
	private $cacheTtl            = 0;
	
	/**
	 * The cache current time to live. 0 means no need to use the cache feature
	 * @var int
	*/
    private $temporaryCacheTtl   = 0;
	
	/**
	 * The number of executed query for the current request
	 * @var int
	*/
    private $queryCount         = 0;
	
	/**
	 * The default data to be used in the statments query INSERT, UPDATE
	 * @var array
	*/
    private $data                = array();
	
	/**
	 * The database configuration
	 * @var array
	*/
    private static $config       = array();
	
	/**
	 * The logger instance
	 * @var Log
	 */
    private $logger              = null;

    /**
     * Construct new database
     * @param array $overwriteConfig the config to overwrite with the config set in database.php
     */
    public function __construct($overwriteConfig = array()){
        /**
         * instance of the Log class
         */
        $this->logger =& class_loader('Log', 'classes');
        $this->logger->setLogger('Library::Database');

      	if(file_exists(CONFIG_PATH . 'database.php')){
          //here don't use require_once because somewhere user can create database instance directly
      	  require CONFIG_PATH . 'database.php';
          if(empty($db) || !is_array($db)){
      			show_error('No database configuration found in database.php');
		  }
		  else{
  				if(! empty($overwriteConfig)){
  				  $db = array_merge($db, $overwriteConfig);
  				}
  				$config['driver']    = isset($db['driver']) ? $db['driver'] : 'mysql';
  				$config['username']  = isset($db['username']) ? $db['username'] : 'root';
  				$config['password']  = isset($db['password']) ? $db['password'] : '';
  				$config['database']  = isset($db['database']) ? $db['database'] : '';
  				$config['hostname']  = isset($db['hostname']) ? $db['hostname'] : 'localhost';
  				$config['charset']   = isset($db['charset']) ? $db['charset'] : 'utf8';
  				$config['collation'] = isset($db['collation']) ? $db['collation'] : 'utf8_general_ci';
  				$config['prefix']    = isset($db['prefix']) ? $db['prefix'] : '';
  				$config['port']      = (strstr($config['hostname'], ':') ? explode(':', $config['hostname'])[1] : '');
  				$this->prefix        = $config['prefix'];
  				$this->databaseName  = $config['database'];
  				
				$dsn = '';
  				if($config['driver'] == 'mysql' || $config['driver'] == '' || $config['driver'] == 'pgsql'){
  					  $dsn = $config['driver'] . ':host=' . $config['hostname'] . ';'
  						. (($config['port']) != '' ? 'port=' . $config['port'] . ';' : '')
  						. 'dbname=' . $config['database'];
  				}
  				else if ($config['driver'] == 'sqlite'){
  				  $dsn = 'sqlite:' . $config['database'];
  				}
  				else if($config['driver'] == 'oracle'){
  				  $dsn = 'oci:dbname=' . $config['host'] . '/' . $config['database'];
  				}
				
  				try{
  				  $this->pdo = new PDO($dsn, $config['username'], $config['password']);
  				  $this->pdo->exec("SET NAMES '" . $config['charset'] . "' COLLATE '" . $config['collation'] . "'");
  				  $this->pdo->exec("SET CHARACTER SET '" . $config['charset'] . "'");
  				  $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
  				}
  				catch (PDOException $e){
  				  $this->logger->fatal($e->getMessage());
  				  show_error('Cannot connect to Database.');
  				}
  				static::$config = $config;
				$this->temporaryCacheTtl = $this->cacheTtl;
  				$this->logger->info('The database configuration are listed below: ' . stringfy_vars(array_merge($config, array('password' => string_hidden($config['password'])))));
  			}
    	}
    	else{
    		show_error('Unable to find database configuration');
    	}
    }

    /**
     * Set the SQL FROM statment
     * @param  string|array $table the table name or array of table list
     * @return object        the current Database instance
     */
    public function from($table){
      if(is_array($table)){
        $froms = '';
        foreach($table as $key){
          $froms .= $this->prefix . $key . ', ';
        }
        $this->from = rtrim($froms, ', ');
      }
      else{
        $this->from = $this->prefix . $table;
      }
      return $this;
    }

    /**
     * Set the SQL SELECT statment
     * @param  string|array $fields the field name or array of field list
     * @return object        the current Database instance
     */
    public function select($fields){
      $select = (is_array($fields) ? implode(', ', $fields) : $fields);
      $this->select = ($this->select == '*' ? $select : $this->select . ', ' . $select);
      return $this;
    }

    /**
     * Set the SQL SELECT DISTINCT statment
     * @param  string $field the field name to distinct
     * @return object        the current Database instance
     */
    public function distinct($field){
      $distinct = ' DISTINCT ' . $field;
      $this->select = ($this->select == '*' ? $distinct : $this->select . ', ' . $distinct);

      return $this;
    }

    /**
     * Set the SQL function MAX in SELECT statment
     * @param  string $field the field name
     * @param  string $name  if is not null represent the alias used for this field in the result
     * @return object        the current Database instance
     */
    public function max($field, $name = null){
      $func = 'MAX(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
      $this->select = ($this->select == '*' ? $func : $this->select . ', ' . $func);
      return $this;
    }

    /**
     * Set the SQL function MIN in SELECT statment
     * @param  string $field the field name
     * @param  string $name  if is not null represent the alias used for this field in the result
     * @return object        the current Database instance
     */
    public function min($field, $name = null){
      $func = 'MIN(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
      $this->select = ($this->select == '*' ? $func : $this->select . ', ' . $func);
      return $this;
    }

    /**
     * Set the SQL function SUM in SELECT statment
     * @param  string $field the field name
     * @param  string $name  if is not null represent the alias used for this field in the result
     * @return object        the current Database instance
     */
    public function sum($field, $name = null){
      $func = 'SUM(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
      $this->select = ($this->select == '*' ? $func : $this->select . ', ' . $func);
      return $this;
    }

    /**
     * Set the SQL function COUNT in SELECT statment
     * @param  string $field the field name
     * @param  string $name  if is not null represent the alias used for this field in the result
     * @return object        the current Database instance
     */
    public function count($field = '*', $name = null){
      $func = 'COUNT(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
      $this->select = ($this->select == '*' ? $func : $this->select . ', ' . $func);
      return $this;
    }

    /**
     * Set the SQL function AVG in SELECT statment
     * @param  string $field the field name
     * @param  string $name  if is not null represent the alias used for this field in the result
     * @return object        the current Database instance
     */
    public function avg($field, $name = null){
      $func = 'AVG(' . $field . ')' . (!is_null($name) ? ' AS ' . $name : '');
      $this->select = ($this->select == '*' ? $func : $this->select . ', ' . $func);
      return $this;
    }

    /**
     * Set the SQL JOIN statment
     * @param  string $table  the join table name
     * @param  string $field1 the first field for join conditions	
     * @param  string $op     the join condition operator. If is null the default will be "="
     * @param  string $field2 the second field for join conditions
     * @param  string $type   the type of join (INNER, LEFT, RIGHT)
     * @return object        the current Database instance
     */
    public function join($table, $field1 = null, $op = null, $field2 = null, $type = ''){
      $on = $field1;
      $table = $this->prefix . $table;
      if(! is_null($op)){
        $on = (! in_array($op, $this->operatorList) ? $this->prefix . $field1 . ' = ' . $this->prefix . $op : $this->prefix . $field1 . ' ' . $op . ' ' . $this->prefix . $field2);
      }
      if (is_null($this->join)){
        $this->join = ' ' . $type . 'JOIN' . ' ' . $table . ' ON ' . $on;
      }
      else{
        $this->join = $this->join . ' ' . $type . 'JOIN' . ' ' . $table . ' ON ' . $on;
      }
      return $this;
    }

    /**
     * Set the SQL INNER JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function innerJoin($table, $field1, $op = null, $field2 = ''){
      return $this->join($table, $field1, $op, $field2, 'INNER ');
    }

    /**
     * Set the SQL LEFT JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function leftJoin($table, $field1, $op = null, $field2 = ''){
      return $this->join($table, $field1, $op, $field2, 'LEFT ');
	}

	/**
     * Set the SQL RIGHT JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function rightJoin($table, $field1, $op = null, $field2 = ''){
      return $this->join($table, $field1, $op, $field2, 'RIGHT ');
    }

    /**
     * Set the SQL FULL OUTER JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function fullOuterJoin($table, $field1, $op = null, $field2 = ''){
    	return $this->join($table, $field1, $op, $field2, 'FULL OUTER ');
    }

    /**
     * Set the SQL LEFT OUTER JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function leftOuterJoin($table, $field1, $op = null, $field2 = ''){
      return $this->join($table, $field1, $op, $field2, 'LEFT OUTER ');
    }

    /**
     * Set the SQL RIGHT OUTER JOIN statment
     * @see  Database::join()
     * @return object        the current Database instance
     */
    public function rightOuterJoin($table, $field1, $op = null, $field2 = ''){
      return $this->join($table, $field1, $op, $field2, 'RIGHT OUTER ');
    }

    /**
     * Set the SQL WHERE CLAUSE for IS NULL
     * @param  string|array $field  the field name or array of field list
     * @param  string $andOr the separator type used 'AND', 'OR', etc.
     * @return object        the current Database instance
     */
    public function whereIsNull($field, $andOr = 'AND'){
      if(is_array($field)){
        foreach($field as $f){
        	$this->whereIsNull($f, $andOr);
        }
      }
      else{
        if (! $this->where){
          $this->where = $field.' IS NULL ';
        }
        else{
            $this->where = $this->where . ' '.$andOr.' ' . $field.' IS NULL ';
          }
      }
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE for IS NOT NULL
     * @param  string|array $field  the field name or array of field list
     * @param  string $andOr the separator type used 'AND', 'OR', etc.
     * @return object        the current Database instance
     */
    public function whereIsNotNull($field, $andOr = 'AND'){
      if(is_array($field)){
        foreach($field as $f){
          $this->whereIsNull($f, $andOr);
        }
      }
      else{
        if (! $this->where){
          $this->where = $field.' IS NOT NULL ';
        }
        else{
            $this->where = $this->where . ' '.$andOr.' ' . $field.' IS NOT NULL ';
          }
      }
      return $this;
    }
    
    /**
     * Set the SQL WHERE CLAUSE statment
     * @param  string|array  $where the where field or array of field list
     * @param  string  $op     the condition operator. If is null the default will be "="
     * @param  mixed  $val    the where value
     * @param  string  $type   the type used for this where clause (NOT, etc.)
     * @param  string  $andOr the separator type used 'AND', 'OR', etc.
     * @param  boolean $escape whether to escape or not the $val
     * @return object        the current Database instance
     */
    public function where($where, $op = null, $val = null, $type = '', $andOr = 'AND', $escape = true){
      if (is_array($where)){
        $_where = array();
        foreach ($where as $column => $data){
          $_where[] = $type . $column . '=' . ($escape ? $this->escape($data) : $data);
        }
        $where = implode(' '.$andOr.' ', $_where);
      }
      else{
        if(is_array($op)){
          $x = explode('?', $where);
          $w = '';
          foreach($x as $k => $v){
            if(! empty($v)){
                $w .= $type . $v . (isset($op[$k]) ? ($escape ? $this->escape($op[$k]) : $op[$k]) : '');
            }
          }
          $where = $w;
        }
        else if (! in_array((string)$op, $this->operatorList)){
        	$where = $type . $where . ' = ' . ($escape ? $this->escape($op) : $op);
        }
        else{
        	$where = $type . $where . $op . ($escape ? $this->escape($val) : $val);
        }
      }
      if (is_null($this->where)){
        $this->where = $where;
      }
      else{
        if(substr($this->where, -1) == '('){
          $this->where = $this->where . ' ' . $where;
        }
        else{
          $this->where = $this->where . ' '.$andOr.' ' . $where;
        }
      }
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE statment using OR
     * @see  Database::where()
     * @return object        the current Database instance
     */
    public function orWhere($where, $op = null, $val = null, $escape = true){
      return $this->where($where, $op, $val, '', 'OR', $escape);
    }


    /**
     * Set the SQL WHERE CLAUSE statment using AND and NOT
     * @see  Database::where()
     * @return object        the current Database instance
     */
    public function notWhere($where, $op = null, $val = null, $escape = true){
      return $this->where($where, $op, $val, 'NOT ', 'AND', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment using OR and NOT
     * @see  Database::where()
     * @return object        the current Database instance
     */
    public function orNotWhere($where, $op = null, $val = null, $escape = true){
    	return $this->where($where, $op, $val, 'NOT ', 'OR', $escape);
    }

    /**
     * Set the opened parenthesis for the complex SQL query
     * @param  string $type   the type of this grouped (NOT, etc.)
     * @param  string $andOr the multiple conditions separator (AND, OR, etc.)
     * @return object        the current Database instance
     */
    public function groupStart($type = '', $andOr = ' AND'){
      if (is_null($this->where)){
        $this->where = $type . ' (';
      }
      else{
          if(substr($this->where, -1) == '('){
            $this->where .= $type . ' (';
          }
          else{
          	$this->where .= $andOr . ' ' . $type . ' (';
          }
      }
      return $this;
    }

    /**
     * Set the opened parenthesis for the complex SQL query using NOT type
     * @see  Database::groupStart()
     * @return object        the current Database instance
     */
    public function notGroupStart(){
      return $this->groupStart('NOT');
    }

    /**
     * Set the opened parenthesis for the complex SQL query using OR for separator
     * @see  Database::groupStart()
     * @return object        the current Database instance
     */
    public function orGroupStart(){
      return $this->groupStart('', ' OR');
    }

     /**
     * Set the opened parenthesis for the complex SQL query using OR for separator and NOT for type
     * @see  Database::groupStart()
     * @return object        the current Database instance
     */
    public function orNotGroupStart(){
      return $this->groupStart('NOT', ' OR');
    }

    /**
     * Close the parenthesis for the grouped SQL
     * @return object        the current Database instance
     */
    public function groupEnd(){
      $this->where .= ')';
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE statment for IN
     * @param  string  $field  the field name for IN statment
     * @param  array   $keys   the list of values used
     * @param  string  $type   the condition separator type (NOT)
     * @param  string  $andOr the multiple conditions separator (OR, AND)
     * @param  boolean $escape whether to escape or not the values
     * @return object        the current Database instance
     */
    public function in($field, array $keys, $type = '', $andOr = 'AND', $escape = true){
      if (is_array($keys)){
        $_keys = array();
        foreach ($keys as $k => $v){
          $_keys[] = (is_numeric($v) ? $v : ($escape ? $this->escape($v) : $v));
        }
        $keys = implode(', ', $_keys);
        if (is_null($this->where)){
          $this->where = $field . ' ' . $type . 'IN (' . $keys . ')';
        }
        else{
          if(substr($this->where, -1) == '('){
            $this->where = $this->where . ' ' . $field . ' '.$type.'IN (' . $keys . ')';
          }
          else{
            $this->where = $this->where . ' ' . $andOr . ' ' . $field . ' '.$type.'IN (' . $keys . ')';
          }
        }
      }
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE statment for NOT IN with AND separator
     * @see  Database::in()
     * @return object        the current Database instance
     */
    public function notIn($field, array $keys, $escape = true){
      return $this->in($field, $keys, 'NOT ', 'AND', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for IN with OR separator
     * @see  Database::in()
     * @return object        the current Database instance
     */
    public function orIn($field, array $keys, $escape = true){
      return $this->in($field, $keys, '', 'OR', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for NOT IN with OR separator
     * @see  Database::in()
     * @return object        the current Database instance
     */
    public function orNotIn($field, array $keys, $escape = true){
      return $this->in($field, $keys, 'NOT ', 'OR', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for BETWEEN
     * @param  string  $field  the field used for the BETWEEN statment
     * @param  mixed  $value1 the BETWEEN begin value
     * @param  mixed  $value2 the BETWEEN end value
     * @param  string  $type   the condition separator type (NOT)
     * @param  string  $andOr the multiple conditions separator (OR, AND)
     * @param  boolean $escape whether to escape or not the values
     * @return object        the current Database instance
     */
    public function between($field, $value1, $value2, $type = '', $andOr = 'AND', $escape = true){
      if (is_null($this->where)){
      	$this->where = $field . ' ' . $type . 'BETWEEN ' . ($escape ? $this->escape($value1) : $value1) . ' AND ' . ($escape ? $this->escape($value2) : $value2);
      }
      else{
        if(substr($this->where, -1) == '('){
          $this->where = $this->where . ' ' . $field . ' ' . $type . 'BETWEEN ' . ($escape ? $this->escape($value1) : $value1) . ' AND ' . ($escape ? $this->escape($value2) : $value2);
        }
        else{
          $this->where = $this->where . ' ' . $andOr . ' ' . $field . ' ' . $type . 'BETWEEN ' . ($escape ? $this->escape($value1) : $value1) . ' AND ' . ($escape ? $this->escape($value2) : $value2);
        }
      }
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE statment for BETWEEN with NOT type and AND separator
     * @see  Database::between()
     * @return object        the current Database instance
     */
    public function notBetween($field, $value1, $value2, $escape = true){
      return $this->between($field, $value1, $value2, 'NOT ', 'AND', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for BETWEEN with OR separator
     * @see  Database::between()
     * @return object        the current Database instance
     */
    public function orBetween($field, $value1, $value2, $escape = true){
      return $this->between($field, $value1, $value2, '', 'OR', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for BETWEEN with NOT type and OR separator
     * @see  Database::between()
     * @return object        the current Database instance
     */
    public function orNotBetween($field, $value1, $value2, $escape = true){
      return $this->between($field, $value1, $value2, 'NOT ', 'OR', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for LIKE
     * @param  string  $field  the field name used in LIKE statment
     * @param  string  $data   the LIKE value for this field including the '%', and '_' part
     * @param  string  $type   the condition separator type (NOT)
     * @param  string  $andOr the multiple conditions separator (OR, AND)
     * @param  boolean $escape whether to escape or not the values
     * @return object        the current Database instance
     */
    public function like($field, $data, $type = '', $andOr = 'AND', $escape = true){
      $like = $escape ? $this->escape($data) : $data;
      if (is_null($this->where)){
        $this->where = $field . ' ' . $type . 'LIKE ' . $like;
      }
      else{
        if(substr($this->where, -1) == '('){
          $this->where = $this->where . ' ' . $field . ' ' . $type . 'LIKE ' . $like;
        }
        else{
          $this->where = $this->where . ' '.$andOr.' ' . $field . ' ' . $type . 'LIKE ' . $like;
        }
      }
      return $this;
    }

    /**
     * Set the SQL WHERE CLAUSE statment for LIKE with OR separator
     * @see  Database::like()
     * @return object        the current Database instance
     */
    public function orLike($field, $data, $escape = true){
      return $this->like($field, $data, '', 'OR', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for LIKE with NOT type and AND separator
     * @see  Database::like()
     * @return object        the current Database instance
     */
    public function notLike($field, $data, $escape = true){
      return $this->like($field, $data, 'NOT ', 'AND', $escape);
    }

    /**
     * Set the SQL WHERE CLAUSE statment for LIKE with NOT type and OR separator
     * @see  Database::like()
     * @return object        the current Database instance
     */
    public function orNotLike($field, $data, $escape = true){
      return $this->like($field, $data, 'NOT ', 'OR', $escape);
    }

    /**
     * Set the SQL LIMIT statment
     * @param  int $limit    the limit offset. If $limitEnd is null this will be the limit count
     * like LIMIT n;
     * @param  int $limitEnd the limit count
     * @return object        the current Database instance
     */
    public function limit($limit, $limitEnd = null){
      if (! is_null($limitEnd)){
        $this->limit = $limit . ', ' . $limitEnd;
      }
      else{
        $this->limit = $limit;
      }
      return $this;
    }

    /**
     * Set the SQL ORDER BY CLAUSE statment
     * @param  string $orderBy   the field name used for order
     * @param  string $orderDir the order direction (ASC or DESC)
     * @return object        the current Database instance
     */
    public function orderBy($orderBy, $orderDir = ' ASC'){
      if (! is_null($orderDir)){
        $this->orderBy = ! $this->orderBy ? ($orderBy . ' ' . strtoupper($orderDir)) : $this->orderBy . ', ' . $orderBy . ' ' . strtoupper($orderDir);
      }
      else{
        if(stristr($orderBy, ' ') || $orderBy == 'rand()'){
          $this->orderBy = ! $this->orderBy ? $orderBy : $this->orderBy . ', ' . $orderBy;
        }
        else{
          $this->orderBy = ! $this->orderBy ? ($orderBy . ' ASC') : $this->orderBy . ', ' . ($orderBy . ' ASC');
        }
      }
      return $this;
    }

    /**
     * Set the SQL GROUP BY CLAUSE statment
     * @param  string|array $field the field name used or array of field list
     * @return object        the current Database instance
     */
    public function groupBy($field){
      if(is_array($field)){
        $this->groupBy = implode(', ', $field);
      }
      else{
        $this->groupBy = $field;
      }
      return $this;
    }

    /**
     * Set the SQL HAVING CLAUSE statment
     * @param  string  $field  the field name used for HAVING statment
     * @param  string|array  $op     the operator used or array
     * @param  mixed  $val    the value for HAVING comparaison
     * @param  boolean $escape whether to escape or not the values
     * @return object        the current Database instance
     */
    public function having($field, $op = null, $val = null, $escape = true){
      if(is_array($op)){
        $x = explode('?', $field);
        $w = '';
        foreach($x as $k => $v){
	      if(!empty($v)){
	      	$w .= $v . (isset($op[$k]) ? ($escape ? $this->escape($op[$k]) : $op[$k]) : '');
	      }
      	}
        $this->having = $w;
      }
      else if (! in_array($op, $this->operatorList)){
        $this->having = $field . ' > ' . ($escape ? $this->escape($op) : $op);
      }
      else{
        $this->having = $field . ' ' . $op . ' ' . ($escape ? $this->escape($val) : $val);
      }
      return $this;
    }

    /**
     * Return the number of rows returned by the current query
     * @return int
     */
    public function numRows(){
      return $this->numRows;
    }

    /**
     * Return the last insert id value
     * @return mixed
     */
    public function insertId(){
      return $this->insertId;
    }

    /**
     * Show an error got from the current query (SQL command synthax error, database driver returned error, etc.)
     */
    public function error(){
		if($this->error){
			show_error('Query: "' . $this->query . '" Error: ' . $this->error, 'Database Error');
		}
    }

    /**
     * Get the result of one record rows returned by the current query
     * @param  boolean $returnSQLQueryOrResultType if is boolean and true will return the SQL query string.
     * If is string will determine the result type "array" or "object"
     * @return mixed       the query SQL string or the record result
     */
    public function get($returnSQLQueryOrResultType = false){
      $this->limit = 1;
      $query = $this->getAll(true);
      if($returnSQLQueryOrResultType === true){
        return $query;
      }
      else{
        return $this->query( $query, false, (($returnSQLQueryOrResultType == 'array') ? true : false) );
      }
    }

    /**
     * Get the result of record rows list returned by the current query
     * @param  boolean $returnSQLQueryOrResultType if is boolean and true will return the SQL query string.
     * If is string will determine the result type "array" or "object"
     * @return mixed       the query SQL string or the record result
     */
    public function getAll($returnSQLQueryOrResultType = false){
      $query = 'SELECT ' . $this->select . ' FROM ' . $this->from;
      if (! is_null($this->join)){
        $query .= $this->join;
      }
	  
      if (! is_null($this->where)){
        $query .= ' WHERE ' . $this->where;
      }

      if (! is_null($this->groupBy)){
        $query .= ' GROUP BY ' . $this->groupBy;
      }

      if (! is_null($this->having)){
        $query .= ' HAVING ' . $this->having;
      }

      if (! is_null($this->orderBy)){
          $query .= ' ORDER BY ' . $this->orderBy;
      }

      if(! is_null($this->limit)){
      	$query .= ' LIMIT ' . $this->limit;
      }
	  
	  if($returnSQLQueryOrResultType === true){
    	return $query;
      }
      else{
    	return $this->query($query, true, (($returnSQLQueryOrResultType == 'array') ? true : false) );
      }
    }

    /**
     * Insert new record in the database
     * @param  array   $data   the record data if is empty will use the $this->data array.
     * @param  boolean $escape  whether to escape or not the values
     * @return mixed          the insert id of the new record or null
     */
    public function insert($data = array(), $escape = true){
      $column = array();
      $val = array();
      if(! $data && $this->getData()){
        $columns = array_keys($this->getData());
        $column = implode(',', $columns);
        $val = implode(', ', $this->getData());
      }
      else{
        $columns = array_keys($data);
        $column = implode(',', $columns);
        $val = implode(', ', ($escape ? array_map(array($this, 'escape'), $data) : $data));
      }

      $query = 'INSERT INTO ' . $this->from . ' (' . $column . ') VALUES (' . $val . ')';
      $query = $this->query($query);

      if ($query){
        $this->insertId = $this->pdo->lastInsertId();
        return $this->insertId();
      }
      else{
		  return false;
      }
    }

    /**
     * Update record in the database
     * @param  array   $data   the record data if is empty will use the $this->data array.
     * @param  boolean $escape  whether to escape or not the values
     * @return mixed          the update status
     */
    public function update($data = array(), $escape = true){
      $query = 'UPDATE ' . $this->from . ' SET ';
      $values = array();
      if(! $data && $this->getData()){
        foreach ($this->getData() as $column => $val){
          $values[] = $column . ' = ' . $val;
        }
      }
      else{
        foreach ($data as $column => $val){
          $values[] = $column . '=' . ($escape ? $this->escape($val) : $val);
        }
      }
      $query .= (is_array($data) ? implode(', ', $values) : $data);
      if (! is_null($this->where)){
        $query .= ' WHERE ' . $this->where;
      }

      if (! is_null($this->orderBy)){
        $query .= ' ORDER BY ' . $this->orderBy;
      }

      if (! is_null($this->limit)){
        $query .= ' LIMIT ' . $this->limit;
      }
      return $this->query($query);
    }

    /**
     * Delete the record in database
     * @return mixed the delete status
     */
    public function delete(){
    	$query = 'DELETE FROM ' . $this->from;

    	if (! is_null($this->where)){
    		$query .= ' WHERE ' . $this->where;
      	}

    	if (! is_null($this->orderBy)){
    	  $query .= ' ORDER BY ' . $this->orderBy;
      	}

    	if (! is_null($this->limit)){
    		$query .= ' LIMIT ' . $this->limit;
      	}

    	if($query == 'DELETE FROM ' . $this->from){
    		$query = 'TRUNCATE TABLE ' . $this->from;
      	}
    	return $this->query($query);
    }

    /**
     * Execute an SQL query
     * @param  string  $query the query SQL string
     * @param  boolean $all   whether to return all record or not
     * @param  boolean $array return the result as array
     * @return mixed         the query result
     */
    public function query($query, $all = true, $array = false){
      $this->reset();
      if(is_array($all)){
        $x = explode('?', $query);
        $q = '';
        foreach($x as $k => $v){
          if(! empty($v)){
            $q .= $v . (isset($all[$k]) ? $this->escape($all[$k]) : '');
          }
        }
        $query = $q;
      }

      $this->query = preg_replace('/\s\s+|\t\t+/', ' ', trim($query));
      $sqlSELECTQuery = stristr($this->query, 'SELECT');
      $this->logger->info('Execute SQL query ['.$this->query.'], return type: ' . ($array?'ARRAY':'OBJECT') .', return as list: ' . ($all ? 'YES':'NO'));
      //cache expire time
	  $cacheExpire = $this->temporaryCacheTtl;
	  
	  //return to the initial cache time
	  $this->temporaryCacheTtl = $this->cacheTtl;
	  
	  //config for cache
      $cacheEnable = get_config('cache_enable');
	  
	  //the database cache content
      $cacheContent = null;
	  
	  //this database query cache key
      $cacheKey = null;
	  
	  //the cache manager instance
      $cacheInstance = null;
	  
	  //the instance of the super controller
      $obj = & get_instance();
	  
	  //if can use cache feature for this query
	  $dbCacheStatus = $cacheEnable && $cacheExpire > 0;
	  
      if ($dbCacheStatus && $sqlSELECTQuery){
        $this->logger->info('The cache is enabled for this query, try to get result from cache'); 
        $cacheKey = md5($query . $all . $array);
        $cacheInstance = $obj->cache;
        $cacheContent = $cacheInstance->get($cacheKey);        
      }
      else{
		  $this->logger->info('The cache is not enabled for this query or is not the SELECT query, get the result directly from real database');
      }
      
      if (! $cacheContent && $sqlSELECTQuery)
      {
		//for database query execution time
        $benchmarkMarkerKey = md5($query . $all . $array);
        $obj->benchmark->mark('DATABASE_QUERY_START(' . $benchmarkMarkerKey . ')');
        //Now execute the query
		$sqlQuery = $this->pdo->query($this->query);
        
		//get response time for this query
        $responseTime = $obj->benchmark->elapsedTime('DATABASE_QUERY_START(' . $benchmarkMarkerKey . ')', 'DATABASE_QUERY_END(' . $benchmarkMarkerKey . ')');
		//TODO use the configuration value for the high response time currently is 1 second
        if($responseTime >= 1 ){
            $this->logger->warning('High response time while processing database query [' .$query. ']. The response time is [' .$responseTime. '] sec.');
        }
        if ($sqlQuery){
          $this->numRows = $sqlQuery->rowCount();
          if (($this->numRows > 0)){
			//if need return all result like list of record
            if ($all){
				$this->result = ($array == false) ? $sqlQuery->fetchAll(PDO::FETCH_OBJ) : $sqlQuery->fetchAll(PDO::FETCH_ASSOC);
		    }
            else{
				$this->result = ($array == false) ? $sqlQuery->fetch(PDO::FETCH_OBJ) : $sqlQuery->fetch(PDO::FETCH_ASSOC);
            }
          }
          if ($dbCacheStatus && $sqlSELECTQuery){
            $this->logger->info('Save the result for query [' .$this->query. '] into cache for future use');
            $cacheInstance->set($cacheKey, $this->result, $cacheExpire);
          }
        }
        else{
          $error = $this->pdo->errorInfo();
          $this->error = $error[2];
          $this->logger->fatal('The database query execution got error: ' . stringfy_vars($error));
          $this->error();
        }
      }
      else if ((! $cacheContent && !$sqlSELECTQuery) || ($cacheContent && !$sqlSELECTQuery)){
		$queryStr = $this->pdo->query($this->query);
		if($queryStr){
			$this->result = $queryStr->rowCount() >= 0; //to test the result for the query like UPDATE, INSERT, DELETE
			$this->numRows = $queryStr->rowCount();
		}
        if (! $this->result){
          $error = $this->pdo->errorInfo();
          $this->error = $error[2];
          $this->logger->fatal('The database query execution got error: ' . stringfy_vars($error));
          $this->error();
        }
      }
      else{
        $this->logger->info('The result for query [' .$this->query. '] already cached use it');
        $this->result = $cacheContent;
		$this->numRows = count($this->result);
      }
      $this->queryCount++;
      if(! $this->result){
        $this->logger->info('No result where found for the query [' . $query . ']');
      }
      return $this->result;
    }

    /**
     * Set database cache time to live
     * @param integer $ttl the cache time to live in second
     * @return object        the current Database instance
     */
    public function setCache($ttl = 0){
      if($ttl > 0){
        $this->cacheTtl = $ttl;
		$this->temporaryCacheTtl = $ttl;
      }
      return $this;
    }
	
	/**
	 * Enabled cache temporary for the current query not globally	
	 * @param  integer $ttl the cache time to live in second
	 * @return object        the current Database instance
	 */
	public function cached($ttl = 0){
      if($ttl > 0){
        $this->temporaryCacheTtl = $ttl;
      }
	  return $this;
    }

    /**
     * Escape the data before execute query useful for security.
     * @param  mixed $data the data to be escaped
     * @return mixed       the data after escaped
     */
    public function escape($data){
      if(is_null($data)){
        return null;
      }
      return $this->pdo->quote(trim($data));
    }

    /**
     * Return the number query executed count for the current request
     * @return int
     */
    public function queryCount(){
      return $this->queryCount;
    }

    /**
     * Return the current query SQL string
     * @return string
     */
    public function getQuery(){
      return $this->query;
    }

    /**
     * Return the application database name
     * @return string
     */
    public function getDatabaseName(){
      return $this->databaseName;
    }

     /**
     * Return the database configuration
     * @return array
     */
    public static function getDatabaseConfiguration(){
      return static::$config;
    }

    /**
     * Return the PDO instance
     * @return PDO
     */
    public function getPdo(){
      return $this->pdo;
    }

    /**
     * Return the data to be used for insert, update, etc.
     * @return array
     */
    public function getData(){
      return $this->data;
    }

    /**
     * Set the data to be used for insert, update, etc.
     * @param string $key the data key identified
     * @param mixed $value the data value
     * @param boolean $escape whether to escape or not the $value
     * @return object        the current Database instance
     */
    public function setData($key, $value, $escape = true){
      $this->data[$key] = $escape ? $this->escape($value) : $value;
      return $this;
    }


  /**
   * Reset the database class attributs to the initail values before each query.
   */
  private function reset(){
    $this->select   = '*';
    $this->from     = null;
    $this->where    = null;
    $this->limit    = null;
    $this->orderBy  = null;
    $this->groupBy  = null;
    $this->having   = null;
    $this->join     = null;
    $this->numRows  = 0;
    $this->insertId = null;
    $this->query    = null;
    $this->error    = null;
    $this->result   = array();
    $this->data     = array();
  }

  /**
   * The class destructor
   */
  function __destruct(){
    $this->pdo = null;
  }

}
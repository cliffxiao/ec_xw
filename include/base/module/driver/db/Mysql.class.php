<?php

class Mysql {
    private static $_Link = NULL;
    private static $dbConfig = array();
    public $sql = "";
	private $_istrans = false; //是否按事务处理
	private $_istranserror = false;//事务中的query是否错误

    public function __construct($dbConfig = array()) {
        self::$dbConfig = $dbConfig;
        self::$_Link = $this->_connect();
    }
	
	//事务处理 开始事物
	public function start_trans(){
		mysql_query("SET AUTOCOMMIT=0", self::$_Link);//设置为不自动提交
		$this->_istrans=true;
	}
	//事物处理  提交事物
	public function commit_trans(){
		if($this->_istranserror){
			mysql_query("ROLLBACK",$this->_writeLink);
			mysql_query("SET AUTOCOMMIT=1", self::$_Link);
			$this->_istrans=false;
			return false;
		}
		if(mysql_query("COMMIT", self::$_Link)){
			mysql_query("SET AUTOCOMMIT=1", self::$_Link);
			$this->_istrans=false;
			return true;
		}else{
			mysql_query("ROLLBACK",$this->_writeLink);
			mysql_query("SET AUTOCOMMIT=1", self::$_Link);
			$this->_istrans=false;
			return false;
		}

	}
	//事务处理 回滚事务
	public function rollback_trans(){
		mysql_query("ROLLBACK",$this->_writeLink);
		mysql_query("SET AUTOCOMMIT=1", self::$_Link);
		$this->_istrans=false;
		return true;
	}

    //执行sql命令
    public function execute($sql, $params = array()) {
        foreach ($params as $k => $v) {
            $sql = str_replace(':' . $k, $this->escape($v), $sql);
        }
        $this->sql = $sql;
        if ($query = mysql_query($sql, self::$_Link))
            return $query;
        else
            $this->_istranserror = $this->_istrans ? true : false;
            $this->error('MySQL Query Error');
    }

    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有
    public function fetchArray($query, $result_type = MYSQL_ASSOC) {
        return $this->unEscape(mysql_fetch_array($query, $result_type));
    }

    //取得前一次 MySQL 操作所影响的记录行数
    public function affectedRows() {
        return mysql_affected_rows(self::$_Link);
    }

    //获取上一次插入的id
    public function lastId() {
        return ($id = mysql_insert_id(self::$_Link)) >= 0 ? $id : mysql_result($this->execute("SELECT last_insert_id()"), 0);
    }

    //获取表结构
    public function getFields($table) {
        $this->sql = "SHOW FULL FIELDS FROM {$table}";
        $query = $this->query($this->sql);
        $data = array();
        while ($row = $this->fetchArray($query)) {
            $data[] = $row;
        }
        return $data;
    }

    //获取行数
    public function count($table, $where) {
        $this->sql = "SELECT count(*) FROM $table $where";
        $query = $this->query($this->sql);
        $data = $this->fetchArray($query);
        return $data['count(*)'];
    }

    //数据过滤
    public function escape($value) {
        if (is_array($value)) {
            return array_map(array($this, 'escape'), $value);
        } else {
            if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            return "'" . mysql_real_escape_string($value, self::$_Link) . "'";
        }
    }

    //数据过滤
    public function unEscape($value) {
        if (is_array($value)) {
            return array_map('stripslashes', $value);
        } else {
            return stripslashes($value);
        }
    }

    //解析待添加或修改的数据
    public function parseData($options, $type) {
        //如果数据是字符串，直接返回
        if (is_string($options['data'])) {
            return $options['data'];
        }
        if (is_array($options) && !empty($options)) {
            switch ($type) {
                case 'add':
                    $data = array();
                    $data['fields'] = array_keys($options['data']);
                    $data['values'] = $this->escape(array_values($options['data']));
                    return " (`" . implode("`,`", $data['fields']) . "`) VALUES (" . implode(",", $data['values']) . ") ";
                case 'save':
                    $data = array();
                    foreach ($options['data'] as $key => $value) {
                        $data[] = " `$key` = " . $this->escape($value);
                    }
                    return implode(',', $data);
                default:return false;
            }
        }
        return false;
    }

    //解析查询条件
    public function parseCondition($options) {
        $condition = "";
        if (!empty($options['where'])) {
            $condition = " WHERE ";
            if (is_string($options['where'])) {
                $condition .= $options['where'];
            } else if (is_array($options['where'])) {
                foreach ($options['where'] as $key => $value) {
                    $condition .= " `$key` = " . $this->escape($value) . " AND ";
                }
                $condition = substr($condition, 0, -4);
            } else {
                $condition = "";
            }
        }

        if (!empty($options['group']) && is_string($options['group'])) {
            $condition .= " GROUP BY " . $options['group'];
        }
        if (!empty($options['having']) && is_string($options['having'])) {
            $condition .= " HAVING " . $options['having'];
        }
        if (!empty($options['order']) && is_string($options['order'])) {
            $condition .= " ORDER BY " . $options['order'];
        }
        if (!empty($options['limit']) && (is_string($options['limit']) || is_numeric($options['limit']))) {
            $condition .= " LIMIT " . $options['limit'];
        }
        if (empty($condition))
            return "";
        return $condition;
    }

    //输出错误信息
    public function error($message = '') {
        $error = mysql_error();
        $errorno = mysql_errno();
        if (DEBUG) {
            $str = " {$message}<br>
					<b>SQL</b>: {$this->sql}<br>
					<b>错误详情</b>: {$error}<br>
					<b>错误代码</b>:{$errorno}<br>";
        } else {
            $str = "<b>出错</b>: $message<br>";
        }
        throw new Exception($str);
    }

    /*     * ******** 兼容以前的版本 ********* */

    //选择数据库
    public function select_db($dbname) {
        return mysql_select_db($dbname, self::$_Link);
    }

    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有
    public function fetch_array($query, $result_type = MYSQL_ASSOC) {
        return $this->fetchArray($query, $result_type);
    }

    //获取上一次插入的id
    public function insert_id() {
        return $this->lastId();
    }

    //取得前一次 MySQL 操作所影响的记录行数
    public function affected_rows() {
        return $this->affectedRows();
    }

    //取得结果集中行的数目
    public function num_rows($query) {
        return mysql_num_rows($query);
    }

    //数据库链接
    private function _connect() {
        if(!self::$_Link){
            self::$_Link = @mysql_connect(self::$dbConfig['host'] . ':' . self::$dbConfig['port'], self::$dbConfig['user'], self::$dbConfig['pwd']);
        }
        if (!self::$_Link) {
            $this->error('无法连接到数据库服务器');
        }
//        mysql_query("SET character_set_connection = " . self::$dbConfig['charset'] . ", character_set_results = " . self::$dbConfig['charset'] . ", character_set_client = binary, SET sql_mode = ''", self::$_Link);
        mysql_select_db(self::$dbConfig['name'], self::$_Link);
        return self::$_Link;
    }

    //关闭数据库
    public function __destruct() {
        is_resource(self::$_Link) && @mysql_close(self::$_Link);
    }
}

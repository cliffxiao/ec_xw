<?php

class Mysqlpdo {
    private static $_Link = NULL;
    private $dbConfig = array();
    public $sql = "";
    protected $affectedRows = -1;
    private $_istrans = false; //是否按事务处理
    private $_istranserror = false; //事务中的query是否错误

    public function __construct($dbConfig = array()) {
        $this->dbConfig = $dbConfig;
        $this->_connect();
        //to_be_here,这个类才开始写
    }

    //执行sql查询	
    public function query($sql, $params = array()) {
        echo $this->sql = $sql;
        $sth = $this->_bindParam($sql, $params, $this->_getReadLink());
        $sth->execute();
        var_dump($sth->fetch(PDO::FETCH_ASSOC));
        $errorinfo = $sth->errorInfo();
        if ($errorinfo[2] != '') {
            $this->error('MySQL Query Error', $errorinfo[2], $errorinfo[1]);
        } else {
            return $sth;
        }
    }

    //执行sql命令
    public function execute($sql, $params = array()) {
        $this->sql = $sql;
        $sth = $this->_bindParam($sql, $params, $this->_getWriteLink());
        $sth->execute();
        $errorinfo = $sth->errorInfo();
        if ($errorinfo[2] != '') {
            $this->error('MySQL Query Error', $errorinfo[2], $errorinfo[1]);
        } else {
            $this->affectedRows = $sth->rowCount();
            return $sth;
        }
    }

    //从结果集中取得一行作为关联数组，或数字数组，或二者兼有 
    public function fetchArray($sth, $result_type = PDO::FETCH_ASSOC) {
        return $this->unEscape($sth->fetch($result_type));
    }

    //取得前一次 MySQL 操作所影响的记录行数
    public function affectedRows() {
        return $this->affectedRows;
    }

    //获取上一次插入的id
    public function lastId() {
        return $this->_getWriteLink()->lastInsertId();
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
        if (isset($this->_readLink)) {
            $link = $this->_readLink;
        } elseif (isset($this->_writeLink)) {
            $link = $this->_writeLink;
        } else {
            $link = $this->_getReadLink();
        }

        if (is_array($value)) {
            return array_map(array($this, 'escape'), $value);
        } else {
            if (is_null($value))
                return 'null';
            if (is_bool($value))
                return $value ? 1 : 0;
            if (is_int($value))
                return (int) $value;
            if (get_magic_quotes_gpc()) {
                $value = stripslashes($value);
            }
            return $link->quote($value);
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
    public function error($message = '', $error = '', $errorno = '') {
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

    //获取从服务器连接
    private function _getReadLink() {
        if (isset($this->_readLink)) {
            return $this->_readLink;
        } else {
            if (!$this->_replication) {
                return $this->_getWriteLink();
            } else {
                $this->_readLink = $this->_connect(false);
                return $this->_readLink;
            }
        }
    }

    //获取主服务器连接
    private function _getWriteLink() {
        if (isset($this->_writeLink)) {
            return $this->_writeLink;
        } else {
            $this->_writeLink = $this->_connect(true);
            return $this->_writeLink;
        }
    }

    //数据库链接
    private function _connect() {
        if(self::$_Link == NULL){
            $dsn = 'mysql:host=' . $this->dbConfig['host'] . ';port=' . $this->dbConfig['port'] . ';dbname=' . $this->dbConfig['name'];
            $isError = false;
            try {
                self::$_Link = new PDO($dsn, $this->dbConfig['user'], $this->dbConfig['pwd']);
            } catch (PDOException $e) {
                $isError = true;
            }

            if ($isError && isset($e)) {
                $this->error('无法连接到数据库服务器', $e->errorInfo(), $e->errorCode());
            }
            //设置编码
            self::$_Link->query("SET NAMES {$this->dbConfig['charset']}");
        }
        return self::$_Link;
    }

    private function _bindParam($sql, $params, $link) {
        $sth = $link->prepare($sql);
        foreach ($params as $k => $v) {
            $sth->bindParam(":" . $k, $this->escape($v));
        }
        return $sth;
    }

    //关闭数据库
    public function __destruct() {
        if ($this->_writeLink) {
            $this->_writeLink = NULL;
        }
        if ($this->_readLink) {
            $this->_readLink = NULL;
        }
    }

}

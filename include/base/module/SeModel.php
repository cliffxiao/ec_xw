<?php

class SeModel {
    public $db = NULL; // 当前数据库操作对象
    public $cache = NULL; //缓存对象
    public $sql = ''; //sql语句，主要用于输出构造成的sql语句
    public $pre = ''; //表前缀，主要用于在其他地方获取表前缀
    public $is_master = false; //是否选择主
    protected $options = array(); // 查询表达式参数
    protected $config = array(); // 当前配置信息

    /**
     * 构造函数
     * @param unknown $config
     */
    public function __construct($master = false) {
        $this->is_master = $master;
        $this->config = C('DB'); //参数配置
        $this->options['field'] = '*'; //默认查询字段
        $this->pre = $this->config['prefix']; //数据表前缀
    }

    /**
     * 连接数据库
     */
    public function connect($master) {
        static $config = array();
        if($this->is_master == false && $master == false){
            $type = 'master';
        }else{
            $type = 'slave';
        }
        if(empty($config[$type])){
            $dbDriver = ucfirst($this->config['type']);
            require_once(dirname(__FILE__) . '/driver/db/' . $dbDriver . '.class.php');
            $config[$type] = new $dbDriver($this->config[$type]); //实例化数据库驱动类
        }
        $this->db = $config[$type];
    }

	//开始事务
	public function start_trans(){
        $this->connect(true);
		$this->db->start_trans();
	}
	//提交事务
	public function commit_trans(){
        $this->connect(true);
		return $this->db->commit_trans();
	}
	//回滚事务
	public function rollback_trans(){
        $this->connect(true);
		return $this->db->rollback_trans();
	}
    /**
     * 设置表，$$ignore_prefix为true的时候，不加上默认的表前缀
     * @param unknown $table
     * @param string $ignorePre
     * @return EcModel
     */
    public function table($table, $ignorePre = false) {
        if ($ignorePre) {
            $this->options['table'] = $table;
        } else {
            $this->options['table'] = $this->pre . $table;
        }
        return $this;
    }

    /**
     * 回调方法，连贯操作的实现
     * @param unknown $method
     * @param unknown $args
     * @throws Exception
     * @return EcModel
     */
    public function __call($method, $args) {
        $method = strtolower($method);
        if (in_array($method, array('field', 'data', 'where', 'group', 'having', 'order', 'limit', 'cache'))) {
            $this->options[$method] = $args[0]; //接收数据
            if ($this->options['field'] == '')
                $this->options['field'] = '*';
            return $this; //返回对象，连贯查询
        } else {
            throw new Exception($method . '方法在SeModel.php类中没有定义');
        }
    }

    /**
     * 执行原生sql语句，如果sql是查询语句，返回二维数组
     * @param unknown $sql
     * @param unknown $params
     * @param string $is_query
     * @return boolean|unknown|Ambigous <multitype:, unknown>
     */
    public function query($sql, $params = array(), $is_query = false) {
//        echo $sql."<br>";
//        file_put_contents(ROOT_PATH . 'sql.log', $sql . "\r\n", FILE_APPEND);
        if (empty($sql))
            return false;
        $sql = str_replace('{pre}', $this->pre, $sql); //表前缀替换
        $this->sql = $sql;
        //判断当前的sql是否是查询语句
        if ($is_query || stripos(trim($sql), 'select') === 0) {
            if(!$this->db){
                $this->connect(false);
            }
            $data = array();
            $query = $this->db->execute($this->sql, $params);
            while ($row = $this->db->fetchArray($query)) {
                $data[] = $row;
            }
            return $data;
        } else {
            $this->connect(true);
            return $this->db->execute($this->sql, $params); //不是查询条件，直接执行
        }
    }

    /**
     * 统计行数
     * @return Ambigous <boolean, unknown>|unknown
     */
    public function count() {
        $this->connect(true);
        $table = $this->options['table']; //当前表
        $field = 'count(*)'; //查询的字段
        $where = $this->_parseCondition(); //条件
        $this->sql = "SELECT $field FROM $table $where"; //这不是真正执行的sql，仅作缓存的key使用
        $data = $this->db->count($table, $where);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    /**
     * 只查询一条信息，返回一维数组
     * @return boolean
     */	
    public function find() {
        $this->options['limit'] = 1; //限制只查询一条数据
        $data = $this->select();
        return isset($data[0]) ? $data[0] : false;
    }

    /**
     * 返回一个字段
     * @return boolean
     */
    public function getOne() {
        $this->options['limit'] = 1; //限制只查询一条数据
        $field = $this->options['field'];
        $data = $this->select();
        return isset($data[0][$field]) ? $data[0][$field] : false;
    }

    /**
     * 返回指定列
     * @return Ambigous <boolean, unknown>
     */
    public function getCol() {
        $field = $this->options['field'];
        $data = $this->select();
        foreach($data as $vo){
            $arr[] = $vo[$field];
        }
        return isset($arr) ? $arr : false;
    }
    
    /**
     * 查询多条信息，返回数组
     */
    public function select() {
        $this->connect(false);
        $table = $this->options['table']; //当前表
        $field = $this->options['field']; //查询的字段
        $where = $this->_parseCondition(); //条件
        return $this->query("SELECT $field FROM $table $where", array(), true);
    }

    /**
     * 获取一张表的所有字段
     * @return Ambigous <boolean, unknown>|unknown
     */
    public function getFields() {
        $this->connect(false);
        $table = $this->options['table'];
        $this->sql = "SHOW FULL FIELDS FROM {$table}"; //这不是真正执行的sql，仅作缓存的key使用
        $data = $this->db->getFields($table);
        $this->sql = $this->db->sql; //从驱动层返回真正的sql语句，供调试使用
        return $data;
    }

    /**
     * 插入数据
     * @param string $replace
     * @return unknown|boolean
     */
    public function insert($replace = false) {
        $this->connect(true);
        $table = $this->options['table']; //当前表
        $data = $this->_parseData('add'); //要插入的数据
        $INSERT = $replace ? 'REPLACE' : 'INSERT';
        $this->sql = "$INSERT INTO $table $data";
//        file_put_contents(ROOT_PATH . 'sql.log', $this->sql . "\r\n", FILE_APPEND);
        $query = $this->db->execute($this->sql);
        if ($this->db->affectedRows()) {
            $id = $this->db->lastId();
            return empty($id) ? $this->db->affectedRows() : $id;
        }
        return false;
    }

    /**
     * 替换数据
     * @return Ambigous <unknown, boolean, unknown>
     */
    public function replace() {
        return $this->insert(true);
    }

    /**
     * 修改更新
     * @return boolean
     */
    public function update() {
        $this->connect(true);
        $table = $this->options['table']; //当前表
        $data = $this->_parseData('save'); //要更新的数据
        $where = $this->_parseCondition(); //更新条件
        if (empty($where))
            return false; //修改条件为空时，则返回false，避免不小心将整个表数据修改了

        $this->sql = "UPDATE $table SET $data $where";
//        file_put_contents(ROOT_PATH.'sql.log',$this->sql."\r\n",FILE_APPEND);
//        echo $this->sql."<br>";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    /**
     * 删除
     * @return boolean
     */
    public function delete() {
        $this->connect(true);
        $table = $this->options['table']; //当前表
        $where = $this->_parseCondition(); //条件
        if (empty($where))
            return false; //删除条件为空时，则返回false，避免数据不小心被全部删除
        $this->sql = "DELETE FROM $table $where";
        $query = $this->db->execute($this->sql);
        return $this->db->affectedRows();
    }

    /**
     * 数据过滤
     * @param unknown $value
     */
    public function escape($value) {
        return $this->db->escape($value);
    }

    /**
     * 返回sql语句
     * @return string
     */
    public function getSql() {
        return $this->sql;
    }

    /**
     * 解析数据
     * @param unknown $type
     * @return unknown
     */  
    private function _parseData($type) {
        $data = $this->db->parseData($this->options, $type);
        $this->options['data'] = '';
        return $data;
    }

    /**
     * 解析条件
     * @return unknown
     */
    private function _parseCondition() {
        $condition = $this->db->parseCondition($this->options);
        $this->options['where'] = '';
        $this->options['group'] = '';
        $this->options['having'] = '';
        $this->options['order'] = '';
        $this->options['limit'] = '';
        $this->options['field'] = '*';
        return $condition;
    }

}

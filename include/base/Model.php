<?php
/**
 * Created file Model.php, 2016/12/23 上午11:18.
 * @author xiaowen
 * filename:Model.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */
class Model
{
    public $model = NULL;
    protected $db = NULL;
    protected $pre = NULL;
    protected $table = "";
    protected $ignoreTablePrefix = false;

    public function __construct()
    {
        $this->model = self::connect();
        $this->db = $this->model->db;
        $this->pre = $this->model->pre;
    }

    static public function connect()
    {
        static $model = NULL;
        if (empty($model)) {
            $model = new SeModel();
        }
        return $model;
    }

    //开始事物
    public function start_trans()
    {
        $this->model->start_trans();
    }

    //提交事物
    public function commit_trans()
    {
        return $this->model->commit_trans();
    }

    //回滚事务
    public function rollback_trans()
    {
        return $this->model->rollback_trans();
    }

    public function query($sql)
    {
        return $this->model->query($sql);
    }

    public function row($sql)
    {
        $data = $this->query($sql);
        return isset($data[0]) ? $data[0] : false;
    }

    public function gecol($condition = '', $field = '', $order = '')
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->getCol();
    }

    public function find($condition = '', $field = '', $order = '')
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
    }

    public function field($field = '', $condition = '', $order = '')
    {
        $result = $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->find();
        return $result[$field];
    }

    public function select($condition = '', $field = '', $order = '', $limit = '')
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->field($field)->where($condition)->order($order)->limit($limit)->select();
    }

    public function count($condition = '')
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->count();
    }

    public function insert($data = array())
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->insert();
    }

    public function update($condition, $data = array())
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->data($data)->where($condition)->update();
    }

    public function delete($condition)
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->where($condition)->delete();
    }

    public function getFields()
    {
        return $this->model->table($this->table, $this->ignoreTablePrefix)->getFields();
    }

    public function getSql()
    {
        return $this->model->getSql();
    }

    public function escape($value)
    {
        return $this->model->escape($value);
    }

    public function cache($time = 0)
    {
        $this->model->cache($time);
        return $this;
    }

}
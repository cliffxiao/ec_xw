<?php
/**
 * Created by xiaowen, 2016/12/02 10:49.
 * @author xiaowen.
 * filename:Mysql.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */

class Session_Save_Mysql implements Session_Save_Interface
{
    protected $_mc = null;
    protected $_lifetime = 1440;

    public function __construct(SeMemcache $memcache)
    {
        if (null === $this->_mc) {
            $this->_mc = $memcache;
        }
        $this->_lifetime = (int)ini_get('session.gc_maxlifetime');
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return $this->_mc->get($id);
    }

    public function write($id, $data)
    {
        $result = $this->_mc->set($id, $data, $this->_lifetime);
        return true;
    }

    public function destroy($id)
    {
        $this->_mc->delete($id);
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
} 
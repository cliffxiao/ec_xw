<?php

class Session_Save_Redis implements SF_Session_Save_Interface
{
    protected $_redis = null;
    protected $_lifetime = 1440;

    public function __construct(SF_Redis $redis)
    {
        if (null === $this->_redis) {
            $this->_redis = $redis;
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
        return $this->_redis->get($id);
    }

    public function write($id, $data)
    {
        $result = $this->_redis->set($id, $data, $this->_lifetime);
        return true;
    }

    public function destroy($id)
    {
        $this->_redis->delete($id);
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}
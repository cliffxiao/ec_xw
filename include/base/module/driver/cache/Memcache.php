<?php

class Se_Memcache
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 11211;
    const DEFAULT_PERSISTENT = false;
    const DEFAULT_EXPIRE = 1200;

    protected $_mc = null;

    protected $_options = array(
        'servers' => array(array(
            'host' => self::DEFAULT_HOST,
            'port' => self::DEFAULT_PORT,
            'persistent' => self::DEFAULT_PERSISTENT
        )),
        'compression' => false,
        'expire' => self::DEFAULT_EXPIRE
    );


    /**
     * 实例，有些memcache构造所需的参数因为并不怎么用到，所以用默认
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        if (!extension_loaded('memcache')) {
            throw new SF_Exception('Memcache扩展不存在');
        }
        $this->_mc = new Memcache;
        $this->setOptions($options);
        foreach ($this->_options['servers'] as $server) {
            isset($server['host']) || $server['host'] = self::DEFAULT_HOST;
            isset($server['port']) || $server['port'] = self::DEFAULT_PORT;
            isset($server['persistent']) || $server['persistent'] = self::DEFAULT_PERSISTENT;
            $this->_mc->addServer($server['host'], $server['port'], $server['persistent']);
        }
    }

    
    public function setOptions(array $options)
    {
        foreach ($options as $k=>$v) {
            $this->_options[$k] = $v;
        }
    }


    public function set($key, $value, $expire = null)
    {
        $flag = (isset($this->_options['compression']) && $this->_options['compression'] == true) ? MEMCACHE_COMPRESSED : 0;
        $expire = ($expire == null) ? $this->_options['expire'] : $expire;
        return $this->_mc->set($key, $value, $flag, $expire);
    }


    public function get($key)
    {
        return $this->_mc->get($key);
    }
    
	public function del($key)
    {
        return $this->_mc->delete($key);
    }


    public function __call($method, array $args)
    {
        return call_user_func_array(array($this->_mc, $method), $args);
    }
}

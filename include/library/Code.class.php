<?php
/**
*  激活码生成
*/
class Code{
	private $prefix = '';
	private $oCode = '';
	private $code = '';
    private $num = 0;
	
	function __construct($prefix = 'zft'){
		if (!empty($prefix)) {
			$this->prefix = $prefix;
		}
	}

	protected function originCode(){
        $code = md5(rand(0,10000).$this->prefix.microtime(true));
        $code = str_shuffle($code);
	    $this->oCode = substr($code,-8,8);
    }

    protected function getCode(){
        if (model('Code')->checkCode($this->oCode)){
            $this->num++;
            if ($this->num > 6){
                $this->originCode();
            }else{
                $this->oCode = str_shuffle($this->oCode);
            }
            $this->getCode();
        }else{
            model('code')->addCode($this->oCode);
        }
    }

    public function returnCode(){
        $this->originCode();
        $this->getCode();
        $this->code = $this->oCode;
        return $this->code;
    }
}
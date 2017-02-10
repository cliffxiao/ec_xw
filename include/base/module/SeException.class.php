<?php
/**
 * Created by xiaowen, 2016/12/02 10:57.
 * @author xiaowen.
 * filename:SeException.class.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */

class SeException extends Exception
{

    public function __construct($msg = '', $code = 0)
    {
        parent::__construct($msg, $code);
        echo '<h1>Exception:</h1>';
        echo $this->getMessage() . '<br /><br />';
        echo $this->getFile() . ' 行：' . $this->getLine();
        exit;
    }
} 
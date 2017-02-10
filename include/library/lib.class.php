<?php
/**
 * Created file lib.class.php, 2016/11/16 下午3:22.
 * @author xiaowen
 * filename:lib.class.php
 * Copyright (c) 2016 xiaowen. All rights reserved.
 */



function get_pinyin($srt = '')
{
    $py = new Pinyin();
    return $py->output($srt); // 输出
}
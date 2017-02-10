<?php
/**
 * 用户级错误处理类
 */
class EcsError {

    var $_message = array();
    var $_template = '';
    var $error_no = 0;

    /**
     * 构造函数
     *
     * @access  public
     * @param   string  $tpl
     * @return  void
     */
    function __construct($tpl) {
        $this->_template = $tpl;
    }

    /**
     * 添加一条错误信息
     *
     * @access  public
     * @param   string  $msg
     * @param   integer $errno
     * @return  void
     */
    function add($msg, $errno = 1) {
        if (is_array($msg)) {
            $this->_message = array_merge($this->_message, $msg);
        } else {
            $this->_message[] = $msg;
        }

        $this->error_no = $errno;
    }

    /**
     * 清空错误信息
     *
     * @access  public
     * @return  void
     */
    function clean() {
        $this->_message = array();
        $this->error_no = 0;
    }

    /**
     * 返回所有的错误信息的数组
     *
     * @access  public
     * @return  array
     */
    function get_all() {
        return $this->_message;
    }

    /**
     * 返回最后一条错误信息
     *
     * @access  public
     * @return  void
     */
    function last_message() {
        return array_slice($this->_message, -1);
    }

    /**
     * 显示错误信息
     *
     * @access  public
     * @param   string  $link
     * @param   string  $href
     * @return  void
     */
    function show($link = '', $href = '') {
        if ($this->error_no > 0) {
            $message = array();

            $link = (empty($link)) ? L('back_up_page') : $link;
            $href = (empty($href)) ? 'javascript:history.back();' : $href;
            $message['url_info'][$link] = $href;
            $message['back_url'] = $href;

            foreach ($this->_message AS $msg) {
                $message['content'] =  htmlspecialchars($msg);
            }
			$view = ECTouch::view();
            if (isset($view)) {
                ECTouch::view()->assign('message', $message);
                ECTouch::view()->display($this->_template);
            } else {
                die($message['content']);
            }

            exit;
        }
    }

    function success($link = '', $jumpUrl = '', $ajax = false)
    {
        if (true === $ajax || IS_AJAX) { // AJAX提交
            $data['success'] = true;
            foreach ($this->_message AS $msg) {
                $data['desc'] = htmlspecialchars($msg);
            }
            $data['result'] = is_array($ajax) ? $ajax : array();
            if (!empty($this->_result)) {
                $data['result'] = array_merge($data['result'], $this->_result);
            }
            if (!empty($jumpUrl)) {
                $data['result'] = array_merge($data['result'], array('url' => $jumpUrl));
            }
            $this->ajaxReturn($data);
        }
        $this->show($link = $link, $href = $jumpUrl);
    }

    function error($link = '', $jumpUrl = '', $ajax = false)
    {
        if (true === $ajax || IS_AJAX) { // AJAX提交
            $data['success'] = false;
            foreach ($this->_message AS $msg) {
                $data['desc'] = htmlspecialchars($msg);
            }
            $data['result'] = is_array($ajax) ? $ajax : array();
            if (!empty($this->_result)) {
                $data['result'] = array_merge($data['result'], $this->_result);
            }
            if (!empty($jumpUrl)) {
                $data['result'] = array_merge($data['result'], array('url' => $jumpUrl));
            }
            $this->ajaxReturn($data);
        }
        $this->show($link = $link, $href = $jumpUrl);

    }

    function ajaxReturn($data, $type = '', $json_option = 0)
    {
        if (empty($type)) $type = 'JSON';
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                // 指定允许其他域名访问
                header('Access-Control-Allow-Origin: http://localhost:63342');
                // 允许携带 用户认证凭据（也就是允许客户端发送的请求携带Cookie）
                header('Access-Control-Allow-Credentials:true');
                // 响应类型
                //header('Access-Control-Allow-Methods:POST');
                // 响应头设置
                //header('Access-Control-Allow-Headers:x-requested-with,content-type');
                exit(json_encode($data, $json_option));
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                exit(xml_encode($data));
            default     :
                return $data;
        }
    }


}

?>
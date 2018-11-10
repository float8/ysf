<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/8
 * @time: 下午9:06
 */

namespace RealTime\Engine\SocketIO\Engine;


trait Route
{
    /**
     * @desc 获取默认路由
     * @return array
     */
    private function defaultRoute()
    {
        return [
            'module' => 'Index',
            'controller' => 'Index',
            'action' => 'Index'
        ];
    }

    /**
     * @desc 路由解析器
     * @param $nsp
     * @param null $event
     * @return array
     */
    private function routeEventParser($nsp, $event = null)
    {
        [
            'module'=>$module,
            'controller'=>$controller,
            'action'=>$action
        ] = $this->defaultRoute();

        $action = empty($event) ?
            $action :
            str_replace(' ', '', ucwords(trim($event)));//解析action
        //解析 module controller
        $nsp = parse_url($nsp, PHP_URL_PATH);
        $nsp = preg_replace("/\/(?=\/)/", "\\1", trim($nsp));
        if ($nsp == '/') {
            goto _return;
        }
        $nsp = explode('/', trim($nsp, '/'));
        $cnt = count($nsp);
        if ($cnt == 1) {
            $controller = ucfirst($nsp[0]);
            goto _return;
        }
        $_module = ucfirst($nsp[0]);
        if(isset($this->modules[$_module])){
            $module = ucfirst($nsp[0]);
            $controller = ucfirst($nsp[1]);
            goto _return;
        }
        $controller = ucfirst($nsp[0]);
        _return :
        return [
            'module' => $module,
            'controller' => $controller,
            'action' => $action
        ];
    }

    /**
     * @desc 获取query
     * @param $pathInfo
     */
    private function query($pathInfo)
    {
        $query = [];
        if(empty($pathInfo)){
            goto _return;
        }
        $cnt = count($pathInfo);
        for ($i = 0; $i < $cnt; $i+=2){
            $query[$pathInfo[$i]] = isset($pathInfo[$i+1]) ? $pathInfo[$i+1] : null;
        }
        _return :
        return $query;
    }

    /**
     * @desc web路由解析器
     * @param $pathInfo
     * @return array
     */
    private function routeWebParser($pathInfo)
    {
        [
            'module'=>$module,
            'controller'=>$controller,
            'action'=>$action
        ] = $this->defaultRoute();
        $query = [];
        //解析 module controller
        $pathInfo = parse_url($pathInfo, PHP_URL_PATH);
        $pathInfo = preg_replace("/\/(?=\/)/", "\\1", trim($pathInfo));
        if ($pathInfo == '/') {
            goto _return;
        }
        $pathInfo = explode('/', trim($pathInfo, '/'));
        $cnt = count($pathInfo);

        //只存在一个的时候
        if($cnt == 1){
            $controller = ucfirst($pathInfo[0]);
            goto _return;
        }
        //存在两个的时候
        if($cnt == 2){
            $controller = ucfirst($pathInfo[0]);
            $action = ucfirst($pathInfo[1]);
            goto _return;
        }
        //存在三个及以上的时候
        $_module = ucfirst($pathInfo[0]);
        switch (isset($this->modules[$_module])) {
            case true :
                $module = $_module;
                $controller = ucfirst($pathInfo[1]);
                $action = ucfirst($pathInfo[2]);
                $query = array_slice($pathInfo, 3);
                $query = $this->query($query);
                goto _return;
                break;
            case false:
                $controller = ucfirst($pathInfo[0]);
                $action = ucfirst($pathInfo[1]);
                $query = array_slice($pathInfo, 2);
                $query = $this->query($query);
                goto _return;
                break;
        }
        _return :
        return [
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'query' => $query
        ];

    }
}



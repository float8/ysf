<?php
namespace Core\Utils\Tools;

/**
 * @desc: 一致性hash
 * @author: wanghongfeng
 * @date: 2017/11/21
 * @time: 下午8:04
 */
class Hash
{
    //落点集合,可以缓存起来
    private $_locations = [];
    //虚拟节点数量
    private $virtualNodeNum = 24;
    //维护的另一种节点和虚拟节点对应关系，方便删除
    private $_nodes;

    /**
     * @desc 设置虚拟节点数量
     * @param $num
     */
    public function setVirtualNodeNum($num){
        $this->virtualNodeNum = $num;
    }

    //将字符串转成数字
    private function _hash($str)
    {
        return sprintf('%u', crc32($str));
    }

    /**
     * 寻找字符串所在的机器位置
     * @param $str
     * @return bool|mixed
     */
    public function getLocation($str)
    {
        if(empty($this->_locations)) {
            return false;
        }
        $position = $this->_hash($str);
        //默认取第一个节点
        $node = current($this->_locations);
        foreach($this->_locations as $k=>$v){
            //如果当前的位置，小于或等于节点组中的一个节点，那么当前位置对应该节点
            if($position <= $k){
                $node = $v;
                break;
            }
        }
        return $node;
    }

    /**
     * 添加一个节点
     * @param $node
     */
    public function addNode($node)
    {
        //生成虚拟节点
        for($i=0;$i<$this->virtualNodeNum;$i++){
            $tmp = $this->_hash($node.$i);
            $this->_locations[$tmp] = $node;
            $this->_nodes[$node][] = $tmp;
        }
        //对节点排序
        ksort($this->_locations,SORT_NUMERIC);
    }

    /**
     * 删除一个节点
     * @param $node
     */
    public function deleteNode($node)
    {
        foreach($this->_nodes[$node] as $v){
            unset($this->_locations[$v]);
        }
    }

    public static function getInstance() {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }
}
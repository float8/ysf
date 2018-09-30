<?PHP
namespace Core\Utils\Tools;

use Core\Base\Config;
use Core\Base\Exception;

class Validator
{
    /**
     * @desc 错误信息
     * @var array
     */
    private $errors = array();
    /**
     * @desc 数据源
     * @var null
     */
    private $dataSource = null;
    /**
     * @desc 抛异常
     * @var boolean
     */
    private $throw = true;
    /**
     * @desc 验证器数组
     * @var array
     */
    private $validators = array();
    /**
     * @desc 字段
     * @var null
     */
    private $fields = null;

    /**
     * @desc 设置数据源
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->dataSource = is_array($data) ? $data : [];
        return $this;
    }

    /**
     * @desc 设置fields
     * @param array $fields
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
        return $this;
    }

    /**
     * @desc 设置是否直接抛异常
     * @param bool $throw
     * @return $this
     */
    public function setThrow(bool $throw = true)
    {
        $this->throw = $throw;
        return $this;
    }

    /**
     * @desc 设置验证器
     * @param $key
     * @param $Validator
     * @param $code
     * @param $sprintfParam
     * @return $this
     */
    public function setValidator($key, $Validator, $code, $sprintfParam = null)
    {
        $msg = $this->getExcMsg($code, $sprintfParam);
        $this->validators[$key][] = array($Validator, $code, $msg);
        return $this;
    }

    /**
     * @desc 获取错误信息
     * @param $code
     * @param $sprintfParam
     * @return mixed
     */
    private function getExcMsg($code, $sprintfParam = null) {
        $message = Config::module('code.'.$code);
        if(empty($message)) {
            return $sprintfParam;
        }
        if(empty($sprintfParam)){
            return $message;
        }
        if(is_array($sprintfParam)){
            array_unshift($sprintfParam, $message);
            return call_user_func_array('sprintf', $sprintfParam);
        }
        return call_user_func('sprintf', $message, $sprintfParam);
    }

    /**
     * @desc 设置验证器集
     * @param $data
     * @return $this
     */
    public function setValidators($data)
    {
        $this->validators = $data;
        return $this;
    }

    /**
     * @desc 验证器
     * @param $key
     * @param $validator
     * @param $code
     * @param $sprintfParam
     * @return $this
     */
    public function validation($key, $validator, $code, $sprintfParam = null)
    {
        if(isset($this->errors[$key]) && !empty($this->errors[$key])) {
            return $this;
        }
        $msg = $this->getExcMsg($code, $sprintfParam);
        $validator = trim($validator, '|');
        !empty($validator) || $this->throw(null ,'验证器不能为空!',0 );
        $validator = explode('|', $validator);
        $method = 'v' . ucfirst(strtolower($validator[0]));
        $params = array_merge(array($key, null), array_splice($validator, 1), array($msg, $code));
        call_user_func_array(array($this, $method), $params);
        return $this;
    }

    /**
     * @desc 验证 验证器数组集
     * @return array
     */
    public function run()
    {
        $fields = $this->getData();
        if(empty($this->validators)) {
            return $fields;
        }
        $validation = function($obj, $key, $validators) {
            foreach($validators as $k => $v) {
                call_user_func([$obj, 'validation'], $key, $v[0], $v[1], $v[2]);
            }
        };
        foreach($this->validators as $key => $val) {
            $validation($this, $key, $val);
        }
        return $fields;
    }

    /**
     * @desc 获取过滤的数据
     * @return array
     */
    public function getData()
    {
        $data = array();
        if(empty($this->fields)) {
            return $data;
        }
        $fields = array_flip($this->fields);
        foreach($this->dataSource as $k => $v) {
            if(!isset($fields[$k])) {
                continue;
            }
            $data[$k] = $v;
        }
        return $data;
    }

    /**
     * @desc 获取所有错误
     * @param null $variable
     * @return array|mixed|string
     */
    public function getError($variable = null)
    {
        if(empty($variable)) {
            return $this->errors;
        }

        if(!isset($this->errors[$variable]) || empty($this->errors[$variable])) {
            return '';
        }
        return $this->errors[$variable];
    }

    /**
     * @desc 获取数据值
     * @param string $key
     * @param mixed $value
     * @return string
     */
    private function getValue(string $key, $value = '')
    {
        if(strlen($value) !== 0) {
            return $value;
        }
        if(!isset($this->dataSource[$key])) {
            return '';
        }
        return $this->dataSource[$key];
    }

    /**
     * @desc 空 不包含 false/0
     * @param $value
     * @return bool
     */
    private function _empty($value)
    {
        $value = trim($value);
        if($value !== false && strlen($value) == 0) {
            return true;
        }
        return false;
    }


    /**
     * @desc 必填
     * @param $key
     * @param $value
     * @param $error
     * @param $code
     * @return $this
     */
    public function vRequired($key, $value, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            $this->throw($key, $error, $code);
        }
        return $this;
    }

    /**
     * @desc 最大值
     * @param $key
     * @param $value
     * @param $maxVal
     * @param $error
     * @param $code
     * @return $this
     */
    public function vMax($key, $value, $maxVal, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(is_numeric($value) && is_numeric($maxVal) && $value > $maxVal) {
            $this->throw($key, $error, $code);
        }

        if(strcmp($value, $maxVal) > 0) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 最小值
     * @param $key
     * @param $value
     * @param $minVal
     * @param $error
     * @param $code
     * @return $this
     */
    public function vMin($key, $value, $minVal, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(is_numeric($value) && is_numeric($minVal) && $value < $minVal) {
            $this->throw($key, $error, $code);
        }

        if(strcmp($value, $minVal) < 0) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 最大长度
     * @param $key
     * @param $value
     * @param $maxLength
     * @param $error
     * @param $code
     * @return $this
     */
    public function vMaxlength($key, $value, $maxLength, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(strlen($value) > $maxLength) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 最小长度
     * @param $key
     * @param $value
     * @param $minlength
     * @param $error
     * @param $code
     * @return $this
     */
    public function vMinlength($key, $value, $minlength, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(strlen($value) > $minlength) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 等于x长度
     * @param $key
     * @param $value
     * @param $length
     * @param $error
     * @param $code
     * @return $this
     */
    public function vEqlength($key, $value, $length, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(strlen($value) != $length) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 等于
     * @param $key
     * @param $value
     * @param $tovalue
     * @param $error
     * @param $code
     * @return $this
     */
    public function vEqualTo($key, $value, $tovalue, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(!strcmp($value, $tovalue)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 数字
     * @param $key
     * @param $value
     * @param $error
     * @param $code
     * @return $this
     */
    public function vNumber($key, $value, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        if(!is_numeric($value)) {
            $this->throw($key, $error, $code);
        }
        return $this;
    }

    /**
     * @desc email
     * @param $key
     * @param $value
     * @param $error
     * @param $code
     * @return $this
     */
    public function vEmail($key, $value, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        if(!preg_match("/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,6}$/", $value)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 范围
     * @param $key
     * @param $value
     * @param $min
     * @param $max
     * @param $error
     * @param $code
     * @return $this
     */
    public function vRange($key, $value, $min, $max, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        //数字
        if(is_numeric($value) && is_numeric($min) && is_numeric($max) && ($value < $min || $value > $max)) {
            $this->throw($key, $error, $code);
        }
        //字符串
        if(strcmp($value, $min) < 0 || strcmp($value, $max) > 0) {
            $this->throw($key, $error, $code);
        }
        return $this;
    }

    /**
     * @desc 长度范围
     * @param $key
     * @param $value
     * @param $minlength
     * @param $maxLength
     * @param $error
     * @param $code
     * @return $this
     */
    public function vRangelength($key, $value, $minlength, $maxLength, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        $length = strlen($value);
        //数字
        if($length < $minlength || $length > $maxLength) {
            $this->throw($key, $error, $code);
        }
        return $this;
    }

    /**
     * @ In_array
     * @param $key
     * @param $value
     * @param $haystack
     * @param $error
     * @param $code
     * @return $this
     */
    public function vIn_array($key, $value, $haystack, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        eval("\$haystack = {$haystack};");
        $haystack = array_flip($haystack);
        if(!isset($haystack[$value])) {
            $this->throw($key, $error, $code);
        }
        return $this;
    }

    /**
     * @desc url
     * @param $key
     * @param $value
     * @param $error
     * @param $code
     * @return $this
     */
    public function vUrl($key, $value, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(preg_match("/^http:\/\/$/", $value)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 日期
     * @param $key
     * @param $value
     * @param $format
     * @param $error
     * @param $code
     * @return $this
     */
    public function vDate($key, $value, $format, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }
        $format = empty($format) ? 'Y-m-d H:i:s' : $format;
        //验证日期
        $validateDate = function($date, $format = 'Y-m-d H:i:s') {
            $d = \DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        };

        if(!$validateDate($value, $format)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 正则
     * @param $key
     * @param $value
     * @param $regexp
     * @param $error
     * @param $code
     * @return $this
     */
    public function vRegexp($key, $value, $regexp, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(preg_match($regexp, $value)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 空
     * @param $key
     * @param $value
     * @param $error
     * @param $code
     * @return $this
     */
    public function vEmpty($key, $value, $error, $code)
    {
        $value = $this->getValue($key, $value);
        if($this->_empty($value)) {
            return $this;
        }

        if(empty($value)) {
            $this->throw($key, $error, $code);
        }

        return $this;
    }

    /**
     * @desc 抛异常
     * @param $key
     * @param $msg
     * @param $code
     * @return $this
     * @throws Exception
     */
    private function throw($key, $msg, $code)
    {
        $this->errors[$key] = $msg;
        if($this->throw) {
            throw new Exception($msg, $code);
        }
        return $this;
    }

    /**
     * @return self
     */
    public static function getInstance() {
        static $obj = null;
        return empty($obj) ? ($obj = new self()) : $obj;
    }
}
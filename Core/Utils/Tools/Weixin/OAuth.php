<?php
namespace Core\Utils\Tools\Weixin;
use Core\Utils\Tools\Fun;

/**
 * @desc:授权
 * @author: wanghongfeng
 * @date: 2017/7/13
 * @time: 下午2:37
 */
trait OAuth
{
    /**
     * @var string
     */
    private $authorizeUrl = '/connect/oauth2/authorize';
    /**
     * @desc code说明 ： code作为换取access_token的票据，每次用户授权带上的code将不一样，code只能使用一次，5分钟未被使用自动过期。
     * @var string
     */
    private $oauthCode = '';

    /**
     * @desc 第一步：用户同意授权，获取code
     * @param string $redirect_uri
     * @param string $scope
     * @param string $state
     * @return $this
     */
    public function authorize(string $redirect_uri, $scope = 'snsapi_base', string $state = 'redirect')
    {
        $code = Fun::getArrayValue($_GET, 'code');
        if(empty($code)){
            $query = [];
            $query['appid'] = $this->getAppid();
            $query['redirect_uri'] = $redirect_uri;
            $query['response_type'] = 'code';
            $query['scope'] = $scope;
            $query['state'] = "{$state}#wechat_redirect";
            $url = $this->getUrl($this->authorizeUrl, $query ,'open');
            $this->redirect($url);
        }
        return $this;
    }

    /**
     * @var string
     */
    private $accessTokenUrl = '/sns/oauth2/access_token';
    /**
     * @desc 通过code换取网页授权access_token
     * @return array
     */
    private function getOAuthAccessToken() :array
    {
        $code = Fun::getArrayValue($_GET, 'code');
        if(empty($code)){
            return [];
        }
        $query = [];
        $query['appid'] = $this->getAppid();
        $query['secret'] = $this->getSecret();
        $query['code'] = $code;
        $query['grant_type'] = 'authorization_code';
        $result = $this->get($this->accessTokenUrl, $query, 'api');
        $result = $this->jsonDecode($result,'weixin:oauth2:access_token:fail-');
        return $result;
    }

    /**
     * @desc 获取openid
     * @return string
     */
    public function getOpenid() :string
    {
        $result = $this->getOAuthAccessToken();
        if(empty($result)) {
            return '';
        }
        $openid = Fun::getArrayValue($result, 'openid');
        return $openid;
    }

    /**
     * @var string
     */
    private $userinfoUrl = '/sns/userinfo';
    /**
     * @desc 拉取用户信息(需scope为 snsapi_userinfo)
     * @return array
     */
    public function getUserinfo() :array
    {
        $accessToken = $this->getOAuthAccessToken();
        if(empty($accessToken)) {
            return [];
        }
        $query = [];
        $query['access_token'] = Fun::getArrayValue($accessToken, 'access_token');
        $query['openid'] = Fun::getArrayValue($accessToken, 'openid');
        $query['lang'] = 'zh_CN';
        $result = $this->get($this->userinfoUrl, $query);
        $result = $this->jsonDecode($result,'weixin:sns:userinfo:fail-');
        return $result;
    }
}
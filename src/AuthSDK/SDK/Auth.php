<?php

namespace AuthSDK\SDK;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Http\Client;
use Zend\Http\Request;

class Auth implements ServiceLocatorAwareInterface {
	use ServiceLocatorAwareTrait;
	
	protected $pingInfo;
	protected $endpoint;
	protected $appId;
	protected $user;
	protected $token;
	protected $error;
	
	/**
	 * HTTP Client to cURL API connection
	 * @var \Zend\Http\Client
	 */
	protected $client;
	
	public function getAppId() {
		return $this->appId;
	}
	protected function setAppId($appId) {
		$this->appId = $appId;
		
		return $this;
	}

	public function getEndpoint() {
		return $this->endpoint;
	}
	protected function setEndpoint($endpoint) {
		$this->endpoint = $endpoint;
		
		return $this;
	}

	/**
	 * @return \Zend\Http\Client
	 */
	public function getClient() {
		return $this->client;
	}
	protected function setClient($client) {
		$this->client = $client;
		
		return $this;
	}
	
	public function getUser() {
		return $this->user;
	}
	public function setUser($user) {
		$this->user = $user;
		
		return $this;
	}

	public function getToken() {
		return $this->token;
	}
	public function setToken($token) {
		$this->token = $token;
		
		return $this;
	}
	
	public function getError() {
		return $this->error;
	}

	public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
	{
		$this->serviceLocator = $serviceLocator;
		
		$this->checkPrerequisites();
	
		return $this;
	}
	
	public function login($identity, $credential) {
		$result	= false;
		
		$this->getClient()->setUri($this->getEndpoint() . '/session/signin');
		
		$this->sendPost(array(
			'identity' => $identity,
			'credential' => $credential
		));
		
		if ($this->getToken()) {
			$result = true;
			
			$this->createCookie();
		}
		
		return $result;
	}
	
	public function logout() {
		$this->getClient()->setUri($this->getEndpoint() . '/session/signout');
		
		try {
			$data = $this->readPost();

			return true;
		} catch(ExceptionApiCall $exApi) {
			return true;
		} catch(\Exception $ex) {
			return true;
		}
		
		return true;
	}	
	
	public function checkLoginStatus() {
		echo 'enviar una peticion al servidor...';
	}
	
	public function updateHeartbeat() {
		echo 'enviar una petición con los campos identity=&token=&appId';
	}
	
	public function getUserInfo() {
		$this->getClient()->setUri($this->getEndpoint() . '/user/info');
		
		try {
			$data = $this->readPost();
						
			return $data->user;
		} catch(ExceptionApiCall $exApi) {
			return $exApi;
		} catch(\Exception $ex) {
			return $ex;
		}
	}
	
	public function getUserPermissions() {
		$this->getClient()->setUri($this->getEndpoint() . '/user/permissions');
		
		try {
			$data = $this->readPost();
			
			return $data->permissions;
		} catch(ExceptionApiCall $exApi) {
			return $exApi;
		} catch(\Exception $ex) {
			return $ex;
		}
	}
	
	public function getUserVars() {
	$this->getClient()->setUri($this->getEndpoint() . '/user/vars');
		
		try {
			$data = $this->readPost();
			
			return $data->variables;
		} catch(ExceptionApiCall $exApi) {
			return $exApi;
		} catch(\Exception $ex) {
			return $ex;
		}
	}
	
	public function getApplicationInfo($appId) {
		echo 'enviar una petición con los campos appId';
	}
	
	public function getApplicationVars() {
		echo 'enviar una petición con los campos appId';
	}
	
	protected function sendPost($postdata) {
		$postdata['appId'] = $this->getAppId();
		
		$this->getClient()->setMethod(Request::METHOD_POST);
		$this->getClient()->setParameterPost($postdata);
		
		$response	= $this->getClient()->send();
		$data		= json_decode($response->getBody());
		
		if ($data->success) {
			$this->setToken($data->auth_token);
			$this->setUser($data->user);
		} else {
			$this->error = $data;
		}
	}
	
	protected function readPost() {
		$postdata	= array();
		
		$postdata['appId'] 		= $this->getAppId();
		$postdata['token'] 		= $this->getToken();
		$postdata['identity']	= $this->getUser();
		
		$this->getClient()->setMethod(Request::METHOD_POST);
		$this->getClient()->setParameterPost($postdata);
		
		$response	= $this->getClient()->send();
		$data		= json_decode($response->getBody());
		
		if ($data->success) {
			if ($data->code == 200) {
				return $data;
			} else {
				throw new ExceptionApiCall($data->message . " (" . $data->code . ")");
			}
		} else {
			throw new ExceptionApiCall($data->message . " (" . $data->code . ")");
		}
	}
	
	protected function checkPrerequisites() {
		$config	= $this->getServiceLocator()->get('config');
		
		if (!isset($config['sdk']) || !isset($config['sdk']['appid']) || !isset($config['sdk']['appsecret']) || !isset($config['sdk']['endpoint'])) {
			throw new ExceptionAuth();
		}
		
		$config		= $this->getServiceLocator()->get('config');
		$endpoint	= $config['sdk']['endpoint'];
		$client		= new Client($endpoint, array(
			'useragent' => 'C.A.S.A. SDK/ZF2_PHP v1.0.0'
		));
		
		$this->setEndpoint($endpoint);
		$this->setClient($client);
		$this->setAppId($config['sdk']['appid']);
		
		try {
			$response	= $client->send();
			
			if ($response->getStatusCode() != 200) {
				throw new ExceptionEndpoint($response->getReasonPhrase());
			} else {
				$client->setUri($endpoint . '/ping' );
				
				$response	= $client->send();
				if ($response->getStatusCode() == 200) {
					$this->pingInfo = json_decode($response->getBody());
					
					if(isset($_COOKIE['CASAID'])) {
						$data	= explode('|', base64_decode($_COOKIE['CASAID']));
						
						$this->setUser($data[0]);
						$this->setToken($data[1]);
					}
				} else {
					throw new ExceptionEndpoint('Invalid Client/SDK');
				}
				
				
			}	
		} catch (\Exception $e) {
			throw  new ExceptionEndpoint($e->getMessage());
		}
	}
	
	protected function createCookie() {
		setcookie('CASAID', base64_encode($this->getUser() . '|' . $this->getToken()));
	}
}
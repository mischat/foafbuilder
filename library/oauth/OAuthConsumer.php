<?php

/**
 * Consumer layer over the OAuthRequest handler
 * 
 * @author Mischa Tuffield <mischa.tuffield@garlik.com>
 * 
 * 
 * The MIT License
 * 
 * Copyright (c) 2008 Garlik Ltd
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

require_once 'OAuthRequest.php';

class OAuthConsumer extends OAuthRequest
{
 
	/**
	 * TODO MISCHA do i need a constructor here, one to create a new instance of OAuthClient 
	 */



	/**
	 * Generate the variables needed to 
	 * 
	 * request url: http://oauth-dev.qdos.com/oauth/request_token?oauth_version=1.0&oauth_nonce=69bf645768da45a18ed3d6fadbeb8fb4&oauth_timestamp=1224774988&oauth_consumer_key=9f494da472de593dea3fdbc64778d1650490089fc&oauth_signature_method=HMAC-SHA1&oauth_signature=%2BF%2F%2BkcabJGGQYtggpC1eyLInE1U%3D
OAuthRequest Object
(
    [parameters:private] => Array
        (
            [oauth_version] => 1.0
            [oauth_nonce] => 69bf645768da45a18ed3d6fadbeb8fb4
            [oauth_timestamp] => 1224774988
            [oauth_consumer_key] => 9f494da472de593dea3fdbc64778d1650490089fc
            [oauth_signature_method] => HMAC-SHA1
            [oauth_signature] => +F/+kcabJGGQYtggpC1eyLInE1U=
        )

    [http_method:private] => GET
    [http_url:private] => http://oauth-dev.qdos.com/oauth/request_token
    [base_string] => GET&http%3A%2F%2Foauth-dev.qdos.com%2Foauth%2Frequest_token&oauth_consumer_key%3D9f494da472de593dea3fdbc64778d1650490089fc%26oauth_nonce%3D69bf645768da45a18ed3d6fadbeb8fb4%26oauth_signature_method%3DHMAC-SHA1%26oauth_timestamp%3D1224774988%26oauth_version%3D1.0
)
	 * 
	 * @return array()	return array needed to generate POST URL
	 */
	public function generate_requestToken($key,$secret) {
		$this->setParam('oauth_version', '1.0');
		$this->setParam('oauth_nonce', $this->generateNonce());
		$this->setParam('oauth_timestamp', $this->generateTimestamp());
	}

	/**
	* TODO MISCHA ... this needs some serious fixing ...
	* pretty much a helper function to set up the request
	*/
	//public static function from_consumer_and_token($consumer, $http_method, $http_url, $parameters) {/*{{{*/
	public function generateRequestToken($http_method, $http_url, $parameters) {/*{{{*/

		foreach ($parameters as $name => $value) {
			$this->setParam($name,$value);
                }
		$this->setParam("oauth_version","1.0");
		$this->setParam("oauth_nonce",$this->generateNonce());
		$this->setParam("oauth_timestamp",$this->generateTimestamp());

/*
		$defaults = array("oauth_version" => "1.0",
		"oauth_nonce" => OAuthRequest::generateNonce(),
		"oauth_timestamp" => OAuthRequest::generateTimestamp());
		$params = array_merge($defaults, $parameters);
*/
	}

        /*TODO MISCHA */
        public function generateNonce()
        {
                return md5(uniqid(rand(), true));
        }

        public function generateTimestamp()
        {
                return time();
        }


	//TODO MISCHA 
	//All function below are from the Server...

	/**
	 * Handle the request_token request.
	 * Returns the new request token and request token secret.
	 * 
	 * TODO: add correct result code to exception
	 * 
	 * @return string 	returned request token, false on an error
	 */
	public function requestToken ()
	{
		OAuthRequestLogger::start($this);
		try
		{
			$this->verify(false);
			
			// Create a request token
			$store  = OAuthStore::instance();
			$token  = $store->addConsumerRequestToken($this->getParam('oauth_consumer_key', true));
			$result = 'oauth_token='.$this->urlencode($token['token'])
					.'&oauth_token_secret='.$this->urlencode($token['token_secret']);

			$request_token = $token['token'];
					
			header('HTTP/1.1 200 OK');
			header('Content-Length: '.strlen($result));
			header('Content-Type: application/x-www-form-urlencoded');

			echo $result;
		}
		catch (OAuthException $e)
		{
			$request_token = false;

			header('HTTP/1.1 401 Unauthorized');
			header('Content-Type: text/plain');

			echo "OAuth Verification Failed: " . $e->getMessage();
		}

		OAuthRequestLogger::flush();
		return $request_token;
	}
	
	
	/**
	 * Verify the start of an authorization request.  Verifies if the request token is valid.
	 * Next step is the method authorizeFinish()
	 * 
	 * Nota bene: this stores the current token, consumer key and callback in the _SESSION
	 * 
	 * @exception OAuthException thrown when not a valid request
	 * @return array token description
	 */
	public function authorizeVerify ( )
	{
		OAuthRequestLogger::start($this);

		$store = OAuthStore::instance();
		$token = $this->getParam('oauth_token', true);
		$rs    = $store->getConsumerRequestToken($token);
		if (empty($rs))
		{
			throw new OAuthException('Unknown request token "'.$token.'"');
		}

		// We need to remember the callback		
		if (	empty($_SESSION['verify_oauth_token'])
			||	strcmp($_SESSION['verify_oauth_token'], $rs['token']))
		{
			$_SESSION['verify_oauth_token'] 		= $rs['token'];
			$_SESSION['verify_oauth_consumer_key']	= $rs['consumer_key'];
			$_SESSION['verify_oauth_callback']		= $this->getParam('oauth_callback', true);
		}
		OAuthRequestLogger::flush();
		return $rs;
	}
	
	
	/**
	 * Overrule this method when you want to display a nice page when
	 * the authorization is finished.  This function does not know if the authorization was
	 * succesfull, you need to check the token in the database.
	 * 
	 * @param boolean authorized	if the current token (oauth_token param) is authorized or not
	 * @param int user_id			user for which the token was authorized (or denied)
	 */
	public function authorizeFinish ( $authorized, $user_id )
	{
		OAuthRequestLogger::start($this);

		$token = $this->getParam('oauth_token', true);
		if (	isset($_SESSION['verify_oauth_token']) 
			&&	$_SESSION['verify_oauth_token'] == $token)
		{
			// Flag the token as authorized, or remove the token when not authorized
			$store = OAuthStore::instance();

			// Fetch the referrer host from the oauth callback parameter
			$referrer_host  = '';
			$oauth_callback = false;
			if (!empty($_SESSION['verify_oauth_callback']))
			{
				$oauth_callback = $_SESSION['verify_oauth_callback'];
				$ps = parse_url($oauth_callback);
				if (isset($ps['host']))
				{
					$referrer_host = $ps['host'];
				}
			}
			
			if ($authorized)
			{
				OAuthRequestLogger::addNote('Authorized token "'.$token.'" for user '.$user_id.' with referrer "'.$referrer_host.'"');
				$store->authorizeConsumerRequestToken($token, $user_id, $referrer_host);
			}
			else
			{
				OAuthRequestLogger::addNote('Authorization rejected for token "'.$token.'" for user '.$user_id."\nToken has been deleted");
				$store->deleteConsumerRequestToken($token);
			}
			
			if (!empty($oauth_callback))
			{
				$this->redirect($oauth_callback, array('oauth_token'=>rawurlencode($token)));
			}
		}
		OAuthRequestLogger::flush();
	}
	
	
	/**
	 * Exchange a request token for an access token.
	 * The exchange is only succesful iff the request token has been authorized.
	 * 
	 * Never returns, calls exit() when token is exchanged or when error is returned.
	 */
	public function accessToken ()
	{
		OAuthRequestLogger::start($this);

		try
		{
			$this->verify('request');
			
			$store  = OAuthStore::instance();
			$token  = $store->exchangeConsumerRequestForAccessToken($this->getParam('oauth_token', true));
			$result = 'oauth_token='.$this->urlencode($token['token'])
					.'&oauth_token_secret='.$this->urlencode($token['token_secret']);
					
			header('HTTP/1.1 200 OK');
			header('Content-Length: '.strlen($result));
			header('Content-Type: application/x-www-form-urlencoded');

			echo $result;
		}
		catch (OAuthException $e)
		{
			header('HTTP/1.1 401 Access Denied');
			header('Content-Type: text/plain');

			echo "OAuth Verification Failed: " . $e->getMessage();
		}
		
		OAuthRequestLogger::flush();
		exit();
	}	
}

?>

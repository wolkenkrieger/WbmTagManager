<?php declare(strict_types=1);
/**
 * Copyright 2016 Google Inc. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace ItswCar\Components\Google\ContentApi;

use Google\Auth\Cache\InvalidArgumentException;
use Google\Exception;
use Google_Client;
use Google_Service_ShoppingContent;
use ItswCar\Traits\LoggingTrait;

class ContentSession {
	use LoggingTrait;
	
	protected const CONFIGFILE_NAME = 'merchant-info.json';
	protected const SERVICE_ACCOUNT_FILE_NAME = 'service-account.json';
	
	private	$config;
	public string $merchantId;
	public bool $mcaStatus;
	public Google_Service_ShoppingContent $service;
	public string $websiteUrl;
	
	/**
	 * @param array $config
	 * @param int   $shopID
	 * @throws \Google\Exception
	 * @throws \JsonException
	 */
	public function __construct (array $config = [], int $shopID = 1) {
		$this->config = $config;
		$clientConfig = $authConfig = [];
		
		if (empty($this->config)) {
			$configDir = implode(DIRECTORY_SEPARATOR, [__DIR__, 'config']);
			$configFile = implode(DIRECTORY_SEPARATOR, [$configDir, self::CONFIGFILE_NAME]);
			if (file_exists($configFile)) {
				$clientConfig = json_decode(file_get_contents($configFile), TRUE, 512, JSON_THROW_ON_ERROR);
				if (is_null($clientConfig)) {
					throw new InvalidArgumentException();
				}
			}
			
			$accountFile = implode(DIRECTORY_SEPARATOR, [$configDir, self::SERVICE_ACCOUNT_FILE_NAME]);
			if (file_exists($accountFile)) {
				$authConfig = json_decode(file_get_contents($accountFile), TRUE, 512, JSON_THROW_ON_ERROR);
				if (is_null($authConfig)) {
					throw new InvalidArgumentException();
				}
			}
			
			$this->config = array_merge($clientConfig, $authConfig);
		}
		
		$client = new Google_Client();
		$client->setApplicationName('ATW Onlineshop Google Shopping');
		$client->setScopes(Google_Service_ShoppingContent::CONTENT);
		$this->authenticateFromConfig($client);
		$this->prepareServices($client);
		$this->retrieveConfig();
	}
	
	/**
	 * @param \Google_Client $client
	 */
	protected function authenticateFromConfig(Google_Client $client): void {
		try {
			$client->setAuthConfig($this->config);
			$client->setScopes(Google_Service_ShoppingContent::CONTENT);
		} catch (Exception $exception) {
			throw new InvalidArgumentException();
		}
	}
	
	/**
	 * @param $client
	 */
	private function prepareServices($client): void {
		$this->service = new Google_Service_ShoppingContent($client);
	}
	
	/**
	 * Retrieves information that can be determined via API calls, including
	 * configuration fields that were not provided.
	 * <p>Retrieves the following fields if missing:
	 * <ul>
	 * <li>merchantId
	 * </ul>
	 * <p>Retrieves the following fields, ignoring any existing configuration:
	 * <ul>
	 * <li>isMCA
	 * <li>websiteUrl
	 * </ul>
	 * @throws \Google\Exception
	 */
	public function retrieveConfig(): void {
		$response = $this->service->accounts->authinfo();
		
		if (is_null($response->getAccountIdentifiers())) {
			throw new InvalidArgumentException('Authenticated user has no access to any Merchant Center accounts');
		}
		
		if (array_key_exists('merchantId', $this->config)) {
			$this->merchantId = (string)$this->config['merchantId'];
		} else {
			$firstAccount = $response->getAccountIdentifiers()[0];
			if (!is_null($firstAccount->getMerchantId())) {
				$this->merchantId = $firstAccount->getMerchantId();
			} else {
				$this->merchantId = $firstAccount->getAggregatorId();
			}
		}
		
		$this->mcaStatus = false;
		foreach ($response->getAccountIdentifiers() as $accountId) {
			if (($accountId->getAggregatorId() === $this->merchantId) && !is_null($accountId->getAggregatorId())) {
				$this->mcaStatus = true;
				break;
			}
			if (($accountId->getMerchantId() === $this->merchantId) && !is_null($accountId->getMerchantId())) {
				break;
			}
		}
		
		$account = $this->service->accounts->get($this->merchantId, $this->merchantId);
		$this->websiteUrl = $account->getWebsiteUrl();
		if (is_null($this->websiteUrl)) {
			throw new Exception('No website URL assigned to merchant center account');
		}
	}
	
	/**
	 * @return string
	 */
	public function getHome(): string {
		$home = null;
		
		if (!empty(getenv('HOME'))) {
			// Try the environmental variables.
			$home = getenv('HOME');
		} else if (!empty($_SERVER['HOME'])) {
			// If not in the environment variables, check the superglobal $_SERVER as
			// a last resort.
			$home = $_SERVER['HOME'];
		} else if(!empty(getenv('HOMEDRIVE')) && !empty(getenv('HOMEPATH'))) {
			// If the 'HOME' environmental variable wasn't found, we may be on
			// Windows.
			$home = getenv('HOMEDRIVE') . getenv('HOMEPATH');
		} else if(!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
			$home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
		}
		
		if ($home === null) {
			throw new UnexpectedValueException('Could not locate home directory.');
		}
		
		return rtrim($home, '\\/');
	}
	
	/**
	 * @param     $object
	 * @param     $function
	 * @param     $parameter
	 * @param int $maxAttempts
	 * @return false|mixed|void
	 */
	public function retry($object, $function, $parameter, $maxAttempts = 5) {
		$attempts = 1;
		
		while ($attempts <= $maxAttempts) {
			try {
				return call_user_func([$object, $function], $parameter);
			} catch (Google_Service_Exception $exception) {
				$sleepTime = $attempts * $attempts;
				printf("Attempt to call %s failed, retrying in %d second(s).\n",
					$function, $sleepTime);
				sleep($sleepTime);
				$attempts++;
			}
		}
	}
}

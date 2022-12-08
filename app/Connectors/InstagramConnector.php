<?php
namespace App\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;

/**
 * https://developers.facebook.com/docs/instagram-basic-display-api/guides/getting-access-tokens-and-permissions?locale=fr_FR
 * https://developers.facebook.com/docs/development/register
 */
class InstagramConnector
{
	/**
	 * @var InstagramConnector
	 */
	private static $instance;

	/**
	 * @var string
	 */
	private $clientId;

	/**
	 * @var string
	 */
	private $clientSecret;

	/**
	 * Disable instantiation
	 */
	private function __construct()
	{
		//
	}

	/**
	 * Get the singleton instance
	 *
	 * @return InstagramConnector
	 */
	public static function getInstance(): InstagramConnector
	{
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set the client ID
	 *
	 * @param string $clientId
	 * @return InstagramConnector
	 */
	public function setClientId(string $clientId): InstagramConnector
	{
		$this->clientId = $clientId;

		return $this;
	}

	/**
	 * Set the client secret
	 *
	 * @param string $clientSecret
	 * @return InstagramConnector
	 */
	public function setClientSecret(string $clientSecret): InstagramConnector
	{
		$this->clientSecret = $clientSecret;

		return $this;
	}

	/**
	 * Redirect the user to Instagram to authorize the app
	 *
	 * @param string $redirectUrl
	 * @return RedirectResponse
	 */
	public function authorize(string $redirectUrl): RedirectResponse
	{
		$url = 'https://api.instagram.com/oauth/authorize';
		$query = http_build_query([
			'client_id' => $this->clientId,
			'redirect_uri' => $redirectUrl,
			'scope' => 'user_profile,user_media',
			'response_type' => 'code',
		]);

		return redirect()->to($url . '?' . $query);
	}


	/**
	 * Get the access token
	 *
	 * @return string
	 */
	public function getAccessToken(): string
	{
		$now = now();

		$access_token = Cache::get('instagram_access_token');
		$expiration = Cache::get('instagram_access_token_expiration');
		if($access_token) {  // Handles either the token refresh, or that the cached token is returned
			if ($expiration <= $now) {
				Cache::forget('instagram_access_token');
				Cache::forget('instagram_access_token_expiration');
				return $this->getAccessToken();
			}
			return $access_token;
		}

		/**
		 * Authorization Code
		 */
		$authorization_code = Cache::get('instagram_authorization_code');
		$expiration_authorization_code = Cache::get('instagram_authorization_code_expiration');
		if(!$authorization_code || $expiration_authorization_code <= $now) {
			Cache::forget('instagram_authorization_code');
			Cache::forget('instagram_authorization_code_expiration');
			throw new Exception('A new Instagram Authorization Code is needed.');
		}

		/**
		 * Access Token Request
		 */
		$response = Http::post('https://api.instagram.com/oauth/access_token', [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'code' => $authorization_code,
			'grant_type' => 'authorization_code',
			'redirect_uri' => route('/')
		]);

		$error_message = request()->query('error_message');
		if($error_message) {
			throw new Exception($error_message);
		}

		$access_token = $response->json()['access_token'];
		$expiration = $now->addMinutes(60);

		Cache::put('instagram_access_token', $access_token);
		Cache::put('instagram_access_token', $expiration);

		return $access_token;
	}
}
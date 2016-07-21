<?php

/**
 * This class represents a key/token request for a Tyk API policy
 *
 * This class is a bit of a mess. It represents a key request and a token.
 * Maybe we should refactor it into two classes that each serve one purpose.
 */
class Tyk_Token
{
	/**
	 * Key request ID
	 * @var string
	 */
	protected $id;

	/**
	 * Key/access token
	 * This is the unhashed access token we show to user but do not save
	 * @var string
	 */
	protected $key;

	/**
	 * Key/access token hash
	 * This is the hashed access token we save
	 * @var string
	 */
	protected $hash;

	/**
	 * API/policy id
	 * @var string
	 */
	protected $policy;

	/**
	 * Tyk API handler
	 * @var Tyk_API
	 */
	protected $api;

	/**
	 * Tyk portal user
	 * @var Tyk_Portal_User
	 */
	protected $user;

	/**
	 * Setup the class
	 *
	 * @param Portal_User $user
	 * @param string $policy
	 */
	public function __construct(Tyk_Portal_User $user, $policy) {
		$this->api = new Tyk_API();
		$this->user = $user;
		$this->policy = $policy;
	}

	/**
	 * Set an existing token
	 * 
	 * @param array $token
	 * @param Portal_User $user
	 */
	public static function init(array $token, Tyk_Portal_User $user) {
		if (isset($token['api_id']) && isset($token['hash'])) {
			$instance = new Tyk_Token($user, $token['api_id']);
			$instance->set_hash($token['hash']);
			return $instance;
		}
		else {
			throw new InvalidArgumentException('Invalid token specified');
		}
	}

	/**
	 * Get the key request id (not the actual token)
	 * 
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Set the key request id
	 * You shouldn't use this, this is for testing
	 * 
	 * @return string
	 */
	public function set_id($id) {
		$this->id = $id;
	}

	/**
	 * Get the key/access token
	 * 
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Set the hashed token
	 * 
	 * @param string $hash
	 */
	public function set_hash($hash) {
		$this->hash = $hash;
	}

	/**
	 * Get the hashed token
	 * 
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}

	/**
	 * Get the api policy ID
	 * 
	 * @return string
	 */
	public function get_policy() {
		return $this->policy;
	}

	/**
	 * Make a key request for a tyk api plan/policy
	 *
	 * @return string
	 */
	public function request() {
		$request_id = $this->api->post('/portal/requests', array(
			'by_user' => $this->user->get_tyk_id(),
			'for_plan' => $this->policy,
			// it's possible to have key requests approved manually
			'approved' => TYK_AUTO_APPROVE_KEY_REQUESTS,
			// this is a bit absurd but tyk api doesn't set this by itself
			'date_created' => date('c'),
			));

		// save key request id
		if (is_string($request_id)) {
			$this->id = $request_id;
		}
		else {
			throw new UnexpectedValueException('Received an invalid response for key request');
		}
	}

	/**
	 * Approve a key request
	 * 
	 * Unfortunately, tyk api doesn't support making and approving a key
	 * request in the same request, so this method must be invoked after
	 * issuing {@link this::request()}.
	 *
	 * @throws Exception When we don't get a token bac from API
	 *
	 * @return void
	 */
	public function approve() {
		if (!is_string($this->id) || empty($this->id)) {
			throw new InvalidArgumentException('Invalid key request');
		}

		try {
			$token = $this->api->put('/portal/requests/approve', $this->id);
			$developer = $this->user->fetch_from_tyk();

			if (is_object($token) && isset($token->RawKey)) {
				$this->key = $token->RawKey;

				if (is_object($developer) && isset($developer->subscriptions)) {
					if (isset($developer->subscriptions->{$this->policy})) {
						$this->hash = $developer->subscriptions->{$this->policy};
					}
				}
			}
			else {
				throw new Exception('Could not register for API');
			}
		}
		catch (Exception $e) {
			throw new UnexpectedValueException($e->getMessage());
		}
	}
}
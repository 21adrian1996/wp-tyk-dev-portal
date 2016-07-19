<?php

require_once 'TykDevPortalTestcase.php';

class KeyRequestTest extends Tyk_Dev_Portal_Testcase {
	// test making a key request for an api policy
	function testKeyRequest() {
		$user = $this->createPortalUser();
		
		$token = new Tyk_Token();
		$token->request($user, TYK_TEST_API_POLICY);

		// it's hard to check if the key is valid, but let's make sure 
		// it's not empty and is at leaast 5 chars long
		$this->assertNotEmpty($token->get_id());
		$this->assertTrue(strlen($token->get_id()) > 5);
	}

	/**
	 * Disabled because Tyk API doesn't care if the policy exists or not
	 * @see https://github.com/TykTechnologies/tyk/issues/272
	 * expectedException UnexpectedValueException
	 *
	function testInvalidKeyRequest() {
		$user = $this->createPortalUser();
		
		$token = new Tyk_Token();
		$token->request($user, 'invalid api');
		print $token->get_id();
	}*/

	// test making and approving a key request to get an access token
	// @todo test failure when using an invalid key
	function testKeyApproval() {
		$user = $this->createPortalUser();

		$token = new Tyk_Token();
		$token->request($user, TYK_TEST_API_POLICY);
		$token->approve();
		
		// it's hard to check if the token is valid, but let's make sure 
		// it's not empty and is at leaast 5 chars long
		$this->assertNotEmpty($token->get_key());
		$this->assertTrue(strlen($token->get_key()) > 5);
	}

	/**
	 * test that you can't approve a key without an id
	 * @expectedException InvalidArgumentException
	 */
	function testEmptyKeyApproval() {
		$user = $this->createPortalUser();

		$token = new Tyk_Token();
		$token->request($user, TYK_TEST_API_POLICY);
		// let's set the internal id to something invalid
		$token->set_id(null);
		$token->approve();
	}

	/**
	 * test that you can't approve an invalid key
	 * @expectedException UnexpectedValueException
	 */
	function testInvalidKeyApproval() {
		$user = $this->createPortalUser();

		$token = new Tyk_Token();
		$token->request($user, TYK_TEST_API_POLICY);
		// let's set the internal id to an id that isn't on tyk
		$token->set_id('not an actual id');
		$token->approve();
	}

	/**
	 * test that we can't instantiate a token with invalid values
	 * @expectedException InvalidArgumentException
	 */
	function testInvalidTokenInstantiation() {
		$token = new Tyk_Token(array('foo' => 'bar'));
	}

	// test revoking a key
	/*function testRevokeKey() {
		$user = $this->createPortalUser();

		// request a token
		$token = new Tyk_Token();
		$token->request($user, TYK_TEST_API_POLICY);
		
		// approve it
		$token->approve();
		$this->assertNotEmpty($token->get_key());
		$this->assertTrue(strlen($token->get_key()) > 5);

		// revoke it
	}*/
}
<?php
namespace Aero\Tests;

use Models\ApiModel;
use RafaelMoran\UITest\UITestCase;

class APIModelCase_166140061511 extends UITestCase
{
	private $api = null;

	public function __construct()
	{
		$this->api = ApiModel::getInstance();
	}

	/**
	 * Tests if APIModel::get_deal_messages() returns messages aa string.
	 *
	 * @return void
	 */
	public function test_get_deal_messages() : void
	{
		# https://www.php.net/manual/en/function.proc-open.php
		# https://zetcode.com/php/getpostrequest/
		# https://github.com/WordPress/WordPress/blob/30f9771a5dc148742cfd693926ddb786b322f912/wp-includes/class-http.php#L1424
		$url = "http://api.aero-local.test/api_public/deal/messages";

		$ch = curl_init();

		$payload = json_encode(["var" => 1]);

		$headers = [
			'Content-Type: application/json',
			'Authorization: Bearer 123qewwer'
		];

		curl_setopt($ch, CURLOPT_URL, $url);
		// curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		// curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		// $header  = curl_getinfo($ch);

		curl_close($ch);

		$this->assertIsString($response);
	}
}
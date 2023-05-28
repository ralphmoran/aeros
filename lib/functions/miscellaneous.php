<?php

if (! function_exists('sendemail')) {

	/**
	 * Sends or schedules an email with/out attachment(s).
	 * 
	 * Exmple:
	 * 
	 * sendemail(
	 *		subject: 'Subject: Test',
	 *		to: [
	 *			'to-test@test.com' => 'Test user',
	 *		],
	 *		cc: [
	 *			'cc-test1@test.com' => 'Cc User test 1',
	 *			'cc-test2@test.com' => 'Cc User test 2',
	 *		],
	 *		bcc: [
	 *			'bcc-test1@test.com' => 'BCc User test 1',
	 *			'bcc-test2@test.com' => 'BCc User test 2',
	 *		],
	 *		from: [
	 *			'test@test.com' => 'Test user'
	 *		]
	 * );
	 *
	 * @param array|mixed ...$settings
	 * @return bool
	*/
	function sendemail(...$settings) : bool
	{
		return Classes\Email::getInstance()
			->compose($settings)
			->send();
	}
}

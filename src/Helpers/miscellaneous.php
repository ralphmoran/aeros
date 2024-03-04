<?php

// if (! function_exists('sendemail')) {

// 	/**
// 	 * Sends or schedules an email with/out attachment(s).
// 	 * 
// 	 * Exmple:
// 	 * 
// 	 * sendemail(
// 	 *  subject: 'Subject: Test',
// 	 *  to: [
// 	 *      'to-test@test.com' => 'Test user',
// 	 *  	],
// 	 *  cc: [
// 	 *			'cc-test1@test.com' => 'Cc User test 1',
// 	 *			'cc-test2@test.com' => 'Cc User test 2',
// 	 *		],
// 	 *  bcc: [
// 	 *			'bcc-test1@test.com' => 'BCc User test 1',
// 	 *			'bcc-test2@test.com' => 'BCc User test 2',
// 	 *		],
// 	 *  from: [
// 	 *			'test@test.com' => 'Test user'
// 	 *		]
// 	 * );
// 	 *
// 	 * @param arra|mixed ...$settings
// 	 * @return void
// 	 */
// 	function sendemail(...$settings) {
// 		return app()->email
// 			->compose($settings)
// 			->send();
// 	}
// }

if (! function_exists('isInternal')) {

	/**
	 * Validates if the current user is internal.
	 *
	 * @return boolean
	 */
	function isInternal(): bool
	{
		// Only on Staging or PROD
		if (isEnv(['staging', 'production'])) {

			// In our VPN
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
				&& in_array($_SERVER['HTTP_X_FORWARDED_FOR'], ['146.70.143.83', '146.70.143.91'])
			) {
				return true;
			}
		}

		return isEnv('development');
	}
}

<?php

namespace Aeros\Lib\Classes;

use SendGrid\Mail\Mail;

// https://github.com/sendgrid/sendgrid-php/blob/main/USE_CASES.md#send-an-email-to-a-single-recipient
// https://github.com/sendgrid/sendgrid-php/blob/main/lib/mail/Mail.php

final class Email
{
	private $email;
	private $sendgrid;

	public function __construct() {
		$this->email = new Mail();
		$this->sendgrid = new \SendGrid(env("SENDGRID_KEY"));
	}

	/**
	 * Configures the email body.
	 *
	 * @param array|mixed $settings
	 * @return Email
	 */
	public function compose($settings) : Email
	{
		extract($settings);

		// subject: 'Test subject',
		if (isset($subject) && ! empty($subject)) {
			$this->email->setSubject($subject);
		}

		// to: [
		// 	'to-test1@test.com' => 'To User test 1',
		// 	'to-test2@test.com' => 'To User test 2',
		// ],
		if (isset($to) && ! empty($to) && is_array($to)) {
			$this->email->addTos($to);
		}

		// cc: [
		// 	'cc-test1@test.com' => 'Cc User test 1',
		// 	'cc-test2@test.com' => 'Cc User test 2',
		// ],
		if (isset($cc) && ! empty($cc) && is_array($cc)) {
			$this->email->addCcs($cc);
		}

		// bcc: [
		// 	'bcc-test1@test.com' => 'Bcc User test 1',
		// 	'bcc-test2@test.com' => 'Bcc User test 2',
		// ],
		if (isset($bcc) && ! empty($bcc) && is_array($bcc)) {
			$this->email->addBccs($bcc);
		}

		// from: [
		// 	'test@test.com' => 'Test user',
		// ],
		if (isset($from) && ! empty($from) && is_array($from)) {
			$this->email->setFrom(key($from), current($from));
		}

		// replyto: [
		// 	'test@test.com' => 'Test user'
		// ],
		if (isset($replyto) && ! empty($replyto) && is_array($replyto)) {
			$this->email->setReplyTo(key($replyto), current($replyto));
		}

		// Headers
		// $this->email->addHeaders($headers);

		// content: [
		// 	'text/plain' => 'This is the email body for test',
		// ],
		if (isset($content) && ! empty($content)) {
			$this->email->addContents($content);
		}

		// attachments: [
		// 	[
		// 		"base64 encoded content2", // $content
		// 		"image/jpeg"             , // $type
		// 		"banner2.jpeg"           , // $filename
		// 		"attachment"             , // $disposition
		// 		"Banner 3"                 // $content_id
		// 	],
		// 	[
		// 		"base64 encoded contentN", // $content
		// 		"image/jpeg"             , // $type
		// 		"bannerN.jpeg"           , // $filename
		// 		"attachment"             , // $disposition
		// 		"Banner N"                 // $content_id
		// 	],
		// ],
		if (isset($attachments) && ! empty($attachments)) {
			$this->email->addAttachments($attachments);
		}

		// template: 'd-13b8f94fbcae4ec6b75270d6cb59f932',
		if (isset($template) && ! empty($template)) {
			$this->email->setTemplateId($template);
		}

		// substitutions: [
		// 	'template_key_1' => 'Value for key 1',
		// 	'template_key_2' => 'Value for key 2',
		// 	'template_key_3' => 'Value for key 3',
		// 	...
		// ],
		if (isset($substitutions) && ! empty($substitutions)) {
			$this->email->addSubstitutions($substitutions);
		}

		// Categories
		// $this->email->addCategories($categories);

		// Dynamic Template Datas
		// $this->email->addDynamicTemplateDatas($substitutions);

		// Custom arguments
		// $this->email->addCustomArgs($customArgs);

		// Send at: Unix timestamp. See more at https://www.php.net/manual/en/function.strtotime.php
		// sendat: '+1 day', // One day from now
		if (isset($sendat) && ! empty($sendat)) {
			$this->email->setSendAt(strtotime($sendat));
		}

		return $this;
	}

	/**
	 * Sends the Sendgrid email.
	 *
	 * @return bool
	 * @throws Exception In case of failure
	 */
	public function send()
	{
		try {
			$this->sendgrid
				->send(
					$this->email
				);

			return 1;
		} catch (\Exception $e) {
			\Sentry\captureException($e);
			sprintf('Exception: %s', $e->getMessage());

			return 0;
		}
	}
}

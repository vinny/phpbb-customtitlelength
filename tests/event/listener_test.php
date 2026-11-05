<?php
/**
 *
 * Custom Topic Title Length. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Vinny <https://github.com/vinny>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vinny\customtitlelength\tests\event;

use phpbb\config\config;
use phpbb\event\data;
use phpbb\language\language;
use phpbb\template\template;
use vinny\customtitlelength\event\listener;

class listener_test extends \phpbb_test_case
{
	/** @var config */
	protected $config;

	/** @var language|\PHPUnit\Framework\MockObject\MockObject */
	protected $language;

	/** @var template|\PHPUnit\Framework\MockObject\MockObject */
	protected $template;

	/** @var listener */
	protected $listener;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = new config([
			'customtitlelength_max_chars' => 200,
		]);

		$this->language = $this->getMockBuilder('\phpbb\language\language')
			->disableOriginalConstructor()
			->getMock();

		$this->template = $this->getMockBuilder('\phpbb\template\template')
			->disableOriginalConstructor()
			->getMock();

		$this->listener = new listener(
			$this->config,
			$this->language,
			$this->template
		);
	}

	public function test_getSubscribedEvents()
	{
		$this->assertEquals([
			'core.acp_board_config_edit_add',
			'core.page_header',
			'core.modify_submit_post_data',
			'core.submit_post_modify_sql_data',
		], array_keys(listener::getSubscribedEvents()));
	}

	public function test_assign_template_vars()
	{
		$this->template->expects($this->once())
			->method('assign_vars')
			->with([
				'MAX_TOPIC_TITLE_LENGTH'	=> 200,
				'MAX_REPLY_TITLE_LENGTH'	=> 204,
			]);

		$this->listener->assign_template_vars();
	}

	public function test_capture_and_apply_custom_topic_title_length()
	{
		$long_title = str_repeat('a', 180);

		$submit_event = new data([
			'mode'		=> 'post',
			'subject'	=> $long_title,
			'data'		=> [
				'topic_title' => $long_title,
			],
		]);

		$this->listener->capture_subject_before_truncate($submit_event);

		// Simulate phpBB core truncating to 120 chars before SQL event
		$truncated_120 = substr($long_title, 0, 120);
		$sql_event = new data([
			'subject'	=> $truncated_120,
			'data'		=> [
				'topic_title' => $truncated_120,
			],
			'sql_data'	=> [
				POSTS_TABLE		=> ['sql' => ['post_subject' => $truncated_120]],
				TOPICS_TABLE	=> ['sql' => ['topic_title' => $truncated_120]],
			],
		]);

		$this->listener->apply_custom_title_length($sql_event);

		$this->assertEquals(180, strlen($sql_event['subject']));
		$this->assertEquals($long_title, $sql_event['sql_data'][POSTS_TABLE]['sql']['post_subject']);
		$this->assertEquals($long_title, $sql_event['sql_data'][TOPICS_TABLE]['sql']['topic_title']);
	}

	public function test_reply_allows_re_prefix()
	{
		$base_title = str_repeat('b', 200);
		$reply_subject = 'Re: ' . $base_title;

		$submit_event = new data([
			'mode'		=> 'reply',
			'subject'	=> $reply_subject,
			'data'		=> [
				'topic_title' => $base_title,
			],
		]);

		$this->listener->capture_subject_before_truncate($submit_event);

		$this->assertEquals(204, strlen($submit_event['subject']));
		$this->assertEquals($reply_subject, $submit_event['subject']);
	}
}

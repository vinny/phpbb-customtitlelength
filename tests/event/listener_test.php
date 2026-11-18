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
			'customtitlelength_max_chars'		=> 200,
			'customtitlelength_show_counter'	=> 1,
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

	public function get_max_chars_data()
	{
		return [
			'default when missing'	=> [[], 120],
			'clamped min zero'		=> [['customtitlelength_max_chars' => 0], 1],
			'clamped min negative'	=> [['customtitlelength_max_chars' => -15], 1],
			'valid mid value'		=> [['customtitlelength_max_chars' => 180], 180],
			'clamped max'			=> [['customtitlelength_max_chars' => 999], 250],
		];
	}

	/**
	 * @dataProvider get_max_chars_data
	 */
	public function test_get_max_chars(array $config_data, int $expected)
	{
		$listener = new listener(
			new config($config_data),
			$this->language,
			$this->template
		);

		$this->assertSame($expected, $listener->get_max_chars());
	}

	public function test_add_acp_post_config_inserts_before_max_post_chars()
	{
		global $phpbb_root_path, $phpEx;

		if (!function_exists('phpbb_insert_config_array'))
		{
			include_once($phpbb_root_path . 'includes/functions_acp.' . $phpEx);
		}

		$this->language->expects($this->once())
			->method('add_lang')
			->with('acp_customtitlelength', 'vinny/customtitlelength');

		$event = new data([
			'mode'			=> 'post',
			'display_vars'	=> [
				'vars' => [
					'legend1'			=> 'POSTING',
					'max_post_chars'	=> ['lang' => 'CHAR_LIMIT'],
				],
			],
		]);

		$this->listener->add_acp_post_config($event);

		$keys = array_keys($event['display_vars']['vars']);
		$this->assertSame([
			'legend1',
			'customtitlelength_max_chars',
			'customtitlelength_show_counter',
			'max_post_chars',
		], $keys);
	}

	public function test_add_acp_post_config_ignores_other_modes()
	{
		$this->language->expects($this->never())
			->method('add_lang');

		$event = new data([
			'mode'			=> 'settings',
			'display_vars'	=> [
				'vars' => [
					'max_post_chars' => ['lang' => 'CHAR_LIMIT'],
				],
			],
		]);

		$this->listener->add_acp_post_config($event);

		$this->assertArrayNotHasKey('customtitlelength_max_chars', $event['display_vars']['vars']);
	}

	public function test_assign_template_vars()
	{
		$this->template->expects($this->once())
			->method('assign_vars')
			->with([
				'MAX_TOPIC_TITLE_LENGTH'			=> 200,
				'MAX_REPLY_TITLE_LENGTH'			=> 204,
				'S_CUSTOMTITLELENGTH_SHOW_COUNTER'	=> true,
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
			'post_mode'	=> 'post',
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
		$this->assertEquals($long_title, $sql_event['data']['topic_title']);
		$this->assertEquals($long_title, $sql_event['sql_data'][POSTS_TABLE]['sql']['post_subject']);
		$this->assertEquals($long_title, $sql_event['sql_data'][TOPICS_TABLE]['sql']['topic_title']);
	}

	public function test_edit_first_post_updates_topic_title()
	{
		$old_title = str_repeat('x', 50);
		$new_title = str_repeat('y', 175);

		$submit_event = new data([
			'mode'		=> 'edit',
			'subject'	=> $new_title,
			'data'		=> [
				'topic_title'				=> $old_title,
				'topic_first_post_id'		=> 10,
				'post_id'					=> 10,
				'topic_posts_approved'		=> 3,
				'topic_posts_unapproved'	=> 0,
				'topic_posts_softdeleted'	=> 0,
			],
		]);

		$this->listener->capture_subject_before_truncate($submit_event);

		$truncated_120 = substr($new_title, 0, 120);
		$sql_event = new data([
			'post_mode'	=> 'edit_first_post',
			'subject'	=> $truncated_120,
			'data'		=> [
				'topic_title' => $old_title,
			],
			'sql_data'	=> [
				POSTS_TABLE		=> ['sql' => ['post_subject' => $truncated_120]],
				TOPICS_TABLE	=> ['sql' => ['topic_title' => $truncated_120]],
			],
		]);

		$this->listener->apply_custom_title_length($sql_event);

		$this->assertSame($new_title, $sql_event['subject']);
		$this->assertSame($new_title, $sql_event['data']['topic_title']);
		$this->assertSame($new_title, $sql_event['sql_data'][TOPICS_TABLE]['sql']['topic_title']);
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

	public function test_delete_mode_resets_previous_state()
	{
		$long_title = str_repeat('z', 180);

		$this->listener->capture_subject_before_truncate(new data([
			'mode'		=> 'post',
			'subject'	=> $long_title,
			'data'		=> ['topic_title' => $long_title],
		]));

		$this->listener->capture_subject_before_truncate(new data([
			'mode'		=> 'delete',
			'subject'	=> '',
			'data'		=> [],
		]));

		$sql_event = new data([
			'post_mode'	=> 'post',
			'subject'	=> 'original',
			'data'		=> ['topic_title' => 'original'],
			'sql_data'	=> [
				POSTS_TABLE => ['sql' => ['post_subject' => 'original']],
			],
		]);

		$this->listener->apply_custom_title_length($sql_event);

		$this->assertSame('original', $sql_event['subject']);
		$this->assertSame('original', $sql_event['sql_data'][POSTS_TABLE]['sql']['post_subject']);
	}

	public function test_utf8_multibyte_and_entities_truncation()
	{
		$title = str_repeat('é', 150) . ' &amp; ' . str_repeat('ç', 100);

		$submit_event = new data([
			'mode'		=> 'post',
			'subject'	=> $title,
			'data'		=> ['topic_title' => $title],
		]);

		$this->listener->capture_subject_before_truncate($submit_event);

		$this->assertLessThanOrEqual(255, utf8_strlen($submit_event['subject']));
		$this->assertGreaterThan(120, utf8_strlen($submit_event['subject']));
	}
}

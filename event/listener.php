<?php
/**
 *
 * Custom Topic Title Length. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2026 Vinny <https://github.com/vinny>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace vinny\customtitlelength\event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class listener implements EventSubscriberInterface
{
	public const DEFAULT_MAX_CHARS = 120;
	public const MIN_CHARS = 1;
	public const MAX_CHARS = 250;
	public const MAX_STORE_LENGTH = 255;
	public const REPLY_PREFIX_LENGTH = 4;

	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var string|null */
	protected $custom_subject;

	/** @var string|null */
	protected $custom_topic_title;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\template\template	$template	Template object
	 */
	public function __construct(
		\phpbb\config\config $config,
		\phpbb\language\language $language,
		\phpbb\template\template $template
	)
	{
		$this->config = $config;
		$this->language = $language;
		$this->template = $template;
	}

	/**
	 * Assign functions defined in this class to event listeners in the core.
	 *
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'core.acp_board_config_edit_add'	=> 'add_acp_post_config',
			'core.page_header'					=> 'assign_template_vars',
			'core.modify_submit_post_data'		=> 'capture_subject_before_truncate',
			'core.submit_post_modify_sql_data'	=> 'apply_custom_title_length',
		];
	}

	/**
	 * Return the configured maximum topic title length clamped to [1, 250].
	 *
	 * @return int
	 */
	public function get_max_chars()
	{
		$max_chars = isset($this->config['customtitlelength_max_chars'])
			? (int) $this->config['customtitlelength_max_chars']
			: self::DEFAULT_MAX_CHARS;

		return max(self::MIN_CHARS, min(self::MAX_CHARS, $max_chars));
	}

	/**
	 * Add maximum topic title length setting to ACP Post settings.
	 *
	 * @param \phpbb\event\data $event Event data
	 * @return void
	 */
	public function add_acp_post_config($event)
	{
		if ($event['mode'] !== 'post' || !isset($event['display_vars']['vars']['max_post_chars']))
		{
			return;
		}

		$this->language->add_lang('acp_customtitlelength', 'vinny/customtitlelength');

		$add_config_vars = [
			'customtitlelength_max_chars' => [
				'lang'		=> 'MAX_TOPIC_TITLE_CHARS',
				'validate'	=> 'int:' . self::MIN_CHARS . ':' . self::MAX_CHARS,
				'type'		=> 'number:' . self::MIN_CHARS . ':' . self::MAX_CHARS,
				'explain'	=> true,
			],
		];

		$display_vars = $event['display_vars'];
		$display_vars['vars'] = phpbb_insert_config_array(
			$display_vars['vars'],
			$add_config_vars,
			['before' => 'max_post_chars']
		);
		$event['display_vars'] = $display_vars;
	}

	/**
	 * Assign maximum topic and reply title length variables to the template.
	 *
	 * @return void
	 */
	public function assign_template_vars()
	{
		$max_chars = $this->get_max_chars();

		$this->template->assign_vars([
			'MAX_TOPIC_TITLE_LENGTH'	=> $max_chars,
			'MAX_REPLY_TITLE_LENGTH'	=> $max_chars + self::REPLY_PREFIX_LENGTH,
		]);
	}

	/**
	 * Capture and truncate the post subject and topic title according to the
	 * configured ACP limit before submit_post() applies its hardcoded 120 limit.
	 *
	 * @param \phpbb\event\data $event Event data
	 * @return void
	 */
	public function capture_subject_before_truncate($event)
	{
		$mode = $event['mode'];
		if ($mode === 'delete')
		{
			return;
		}

		$data = $event['data'];
		$subject = (string) $event['subject'];
		$topic_title = isset($data['topic_title']) ? (string) $data['topic_title'] : '';

		$max_chars = $this->get_max_chars();
		$is_first_post = ($mode === 'post') || ($mode === 'edit' && (
			(isset($data['topic_posts_approved'], $data['topic_posts_unapproved'], $data['topic_posts_softdeleted']) && ($data['topic_posts_approved'] + $data['topic_posts_unapproved'] + $data['topic_posts_softdeleted'] == 1)) ||
			(isset($data['topic_first_post_id'], $data['post_id']) && (int) $data['topic_first_post_id'] === (int) $data['post_id'])
		));

		if ($is_first_post)
		{
			$this->custom_subject = truncate_string($subject, $max_chars, self::MAX_STORE_LENGTH, false);
		}
		else if (strpos($subject, 'Re: ') === 0)
		{
			$this->custom_subject = truncate_string(
				$subject,
				$max_chars,
				self::MAX_STORE_LENGTH - self::REPLY_PREFIX_LENGTH,
				true
			);
		}
		else
		{
			$this->custom_subject = truncate_string(
				$subject,
				$max_chars + self::REPLY_PREFIX_LENGTH,
				self::MAX_STORE_LENGTH,
				false
			);
		}

		$this->custom_topic_title = truncate_string($topic_title, $max_chars, self::MAX_STORE_LENGTH, false);

		$event['subject'] = $this->custom_subject;
		$data['topic_title'] = $this->custom_topic_title;
		$event['data'] = $data;
	}

	/**
	 * Restore the custom-truncated subject and topic title before SQL queries run.
	 *
	 * @param \phpbb\event\data $event Event data
	 * @return void
	 */
	public function apply_custom_title_length($event)
	{
		if ($this->custom_subject === null)
		{
			return;
		}

		$event['subject'] = $this->custom_subject;

		$data = $event['data'];
		$data['topic_title'] = ($this->custom_topic_title !== null && $this->custom_topic_title !== '')
			? $this->custom_topic_title
			: $this->custom_subject;
		$event['data'] = $data;

		$sql_data = $event['sql_data'];

		if (isset($sql_data[POSTS_TABLE]['sql']['post_subject']))
		{
			$sql_data[POSTS_TABLE]['sql']['post_subject'] = $this->custom_subject;
		}

		if (isset($sql_data[TOPICS_TABLE]['sql']['topic_title']))
		{
			$sql_data[TOPICS_TABLE]['sql']['topic_title'] = $this->custom_subject;
		}

		$event['sql_data'] = $sql_data;

		$this->custom_subject = null;
		$this->custom_topic_title = null;
	}
}

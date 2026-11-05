# Custom Topic Title Length [![Tests](https://github.com/vinny/customtitlelength/actions/workflows/tests.yml/badge.svg)](https://github.com/vinny/customtitlelength/actions/workflows/tests.yml)

Custom Topic Title Length lets board administrators adjust the maximum character limit for topic titles and post subjects directly from the Admin Control Panel, removing phpBB’s default 120-character restriction.

## Features

- **Configurable character limit:** Set the maximum topic title length between 1 and 250 characters in **ACP > Post settings** (default: 120).
- **Reply prefix support:** Automatically reserves 4 extra characters for the `Re: ` prefix in replies and quotes (up to 254 characters).
- **Full interface support:** Updates the `maxlength` attribute in the full post editor, topic draft editor, Quick Reply, and the Moderator Control Panel topic split form.
- **No core table changes:** Works within phpBB’s native `VARCHAR(255)` columns (`topic_title`, `post_subject`, `topic_last_post_subject`, `forum_last_post_subject`, and `draft_subject`).
- **Scoped to topics and posts:** Private messages keep phpBB’s default character limit.

## Requirements

- phpBB 3.3.0 or newer
- PHP 7.2.0 or newer

## Installation

1. Copy the extension files to `ext/vinny/customtitlelength`.
2. In the Admin Control Panel, go to **Customise > Manage extensions**.
3. Locate **Custom Topic Title Length** under Disabled Extensions and click **Enable**.

## Configuration

1. In the Admin Control Panel, go to **General > Board configuration > Post settings**.
2. Adjust **Maximum characters per topic title** (between 1 and 250) under the **Posting** section.
3. Click **Submit**.

## License

[GNU General Public License v2 (GPL-2.0-only)](license.txt)


# Custom Topic Title Length [![Tests](https://github.com/vinny/phpbb-customtitlelength/actions/workflows/tests.yml/badge.svg)](https://github.com/vinny/phpbb-customtitlelength/actions/workflows/tests.yml)

A phpBB extension that lets board administrators change the maximum character limit for topic titles and post subjects from the Admin Control Panel, replacing phpBB’s hardcoded 120-character limit.

## Features

- **Custom character limit:** Set the maximum topic title length between 1 and 250 characters in **ACP > Post settings** (default: 120).
- **Reply prefix support:** Reserves 4 extra characters for the `Re: ` prefix in replies and quotes (up to 254 characters).
- **Consistent form limits:** Updates the `maxlength` attribute in the full post editor, topic draft editor, Quick Reply, and the Moderator Control Panel topic split form.
- **No schema changes:** Uses phpBB’s existing `VARCHAR(255)` columns (`topic_title`, `post_subject`, `topic_last_post_subject`, `forum_last_post_subject`, and `draft_subject`).
- **Topics and posts only:** Leaves private message subject limits unchanged.

## Requirements

- phpBB 3.3.0 or higher
- PHP 7.2.0 or higher

## Installation

1. Download the extension and copy the files to `ext/vinny/customtitlelength/`.
2. In the **Admin Control Panel**, go to **Customise** > **Manage extensions**.
3. Find **Custom Topic Title Length** under *Disabled Extensions* and click **Enable**.

## Configuration

1. Go to **ACP** > **General** > **Post settings**.
2. Set **Maximum characters per topic title** (between 1 and 250) under the **Posting** section.
3. Click **Submit**.

## Uninstallation

1. Go to **ACP** > **Customise** > **Manage extensions**.
2. Click **Disable** next to **Custom Topic Title Length**.
3. To remove the configuration entry from the database, click **Delete data**.

## Development

JavaScript code style is checked with ESLint:

```bash
npm install
npm test
```

## Support

[![Donate with Ko-fi](https://img.shields.io/badge/Donate-Ko--fi-29ABE2.svg)](https://ko-fi.com/vinny1)

## License

[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](license.txt)

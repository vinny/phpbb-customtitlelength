# Changelog

All notable changes to the Custom Topic Title Length extension will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-18

### Added
- Optional live character counter (`customtitlelength_show_counter`) displayed next to the subject input while typing, configurable in **ACP > Post settings** (enabled by default).
- Subtle prosilver-compatible stylesheet (`styles/all/theme/customtitlelength.css`) with RTL-safe logical spacing and limit highlight styling.
- Expanded PHPUnit test coverage for ACP config insertion, boundary clamping, first-post editing, `delete` mode state reset, and UTF-8/HTML entity truncation.

### Changed
- Modernized `styles/all/template/js/customtitlelength.js` to ES6 Vanilla JavaScript with zero jQuery dependency and form-scoped subject input lookup.
- Replaced `class="hidden"` and unused `data-customtitlelength-target` attributes in template events with the native HTML5 `hidden` attribute.
- Refactored first-post detection in `event/listener.php` into a dedicated `is_first_post()` helper with strict integer comparisons.
- Updated `composer/installers` constraint to `~1.0 || ~2.0`.

### Fixed
- Removed ineffective `S_LINK_FORUM` top-level check from `posting_editor_subject_append.html`.
- Added defensive state reset for `$custom_subject` and `$custom_topic_title` at the start of `capture_subject_before_truncate()`.

## [1.0.0] - 2026-11-04

### Added
- Initial release of Custom Topic Title Length extension for phpBB.
- Configurable maximum topic title length setting (`customtitlelength_max_chars`, from 1 to 250 characters, default: 120) integrated directly into **ACP > Post settings**.
- Automatic 4-character reservation for the `Re: ` prefix in replies and quotes (up to 254 characters) while respecting phpBB's native `VARCHAR(255)` column storage limits.
- Server-side truncation override in `submit_post()` via `core.modify_submit_post_data` and `core.submit_post_modify_sql_data` without modifying core database table schemas.
- Dynamic `maxlength` updates across the full post editor (`posting_editor.html`), UCP topic draft editor (`ucp_main_drafts.html`), Quick Reply (`quickreply_editor.html`), and MCP Split Topic (`mcp_topic.html`).
- Private message exclusion (`not S_PRIVMSGS`) so private message subject limits remain untouched.
- English (`en`) and Brazilian Portuguese (`pt_br`) language packs.
- Automated test suites and linters including PHPUnit unit tests, ESLint, PHP_CodeSniffer, and GitHub Actions CI workflow.

### Security
- Explicit integer clamping (`1` to `250`) and multi-byte safe `truncate_string()` enforcement within phpBB's 255-byte storage limit.
- Strict `|e('html_attr')` Twig attribute escaping and decoupled jQuery handler without selector injection sinks.

[1.1.0]: https://github.com/vinny/phpbb-customtitlelength/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/vinny/phpbb-customtitlelength/releases/tag/1.0.0

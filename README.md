# Terminus Composer Logs Plugin

![Github Actions Status](https://github.com/pantheon-systems/terminus-composer-logs-plugin/actions/workflows/ci.yml/badge.svg)
[![Early Access](https://img.shields.io/badge/Pantheon-Early_Access-yellow?logo=pantheon&color=FFDC28)](https://docs.pantheon.io/oss-support-levels#early-access)
[![Terminus v3.x Compatible](https://img.shields.io/badge/terminus-3.x-green.svg)](https://github.com/pantheon-systems/terminus-composer-logs-plugin/tree/main)

A plugin that shows composer logs via Terminus.

Adds commands 'composer:logs' and 'composer:logs:upstream-update' to Terminus. Learn more about Terminus Plugins in the
[Terminus Plugins documentation](https://pantheon.io/docs/terminus/plugins)

## Installation

To install this plugin using Terminus 3:
```
terminus self:plugin:install terminus-composer-logs-plugin
```

## Usage

### `composer:logs`

Use this command to get composer logs for a commit (default to latest commit).

Examples:

```
# Get logs for latest commit on given site/env
terminus composer:logs $SITE.$ENV

# Get logs for commit abcdef on given site/env
terminus composer:logs $SITE.$ENV --commit=abcdef
```

Example output:

```
Composer version 2.5.8 2023-06-09 17:13:21
Cache directory does not exist (cache-vcs-dir):
Cache directory does not exist (cache-repo-dir):
Cache directory does not exist (cache-files-dir):
Clearing cache (cache-dir): /home/pantheon-app/.composer/cache
All caches cleared.
Running composer install...
composer --no-interaction --no-progress --prefer-dist --ansi install
No patches supplied.
Installing dependencies from lock file (including require-dev)
Verifying lock file contents can be installed on current platform.
Nothing to install, update or remove
Package doctrine/reflection is abandoned, you should avoid using it. Use roave/better-reflection instead.
Package symfony/debug is abandoned, you should avoid using it. Use symfony/error-handler instead.
Package webmozart/path-util is abandoned, you should avoid using it. Use symfony/filesystem instead.
Generating autoload files
91 packages you are using are looking for funding.
Use the `composer fund` command to find out more!
Scaffolding files for pantheon-systems/drupal-integrations:
  - Copy [project-root]/.drush-lock-update from assets/drush-lock-update
```

### `composer:logs:upstream-update`

Use this command to get composer logs for a upstream update (apply or check). This is specially useful for debugging failed upstream updates.

Example:

```
# Get logs for latest upstream update workflow
terminus composer:logs:upstream-update $SITE.$ENV
```

Example output:

```
Applying the latest version of your site's upstream...
Using merge strategy: --strategy-option theirs
Using PHP-based Site Repository Tool...
Using '--update-behavior heirloom' option
Site Repository Tool response: {
    "clone": false,
    "pull": true,
    "push": false,
    "logs": [
        "Upstream remote has been added",
        "Updates have been fetched",
        "Updates have been merged",
        "Updates have been committed"
    ],
    "conflicts": "",
    "errormessage": ""
}
Composer version 2.5.8 2023-06-09 17:13:21
Cache directory does not exist (cache-vcs-dir):
Cache directory does not exist (cache-repo-dir):
Cache directory does not exist (cache-files-dir):
Clearing cache (cache-dir): /home/pantheon-app/.composer/cache
All caches cleared.
Running composer update...
composer --no-interaction --no-progress --prefer-dist --ansi update
> DrupalComposerManaged\ComposerScripts::preUpdate
Setting platform.php from '8.1.13' to '8.2.0' to conform to pantheon php version.
Loading composer repositories with package information
Updating dependencies
Your requirements could not be resolved to an installable set of packages.

  Problem 1
    - SOME COMPOSER ERROR HERE

We couldn't check for Composer updates because Composer errored
```

## Help

Run `terminus list composer` for a complete list of available commands. Use terminus help <command> to get help on one command.

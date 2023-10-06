# Terminus Composer Logs Plugin

![Github Actions Status](https://github.com/pantheon-systems/terminus-composer-logs-plugin/actions/workflows/ci.yml/badge.svg)

[![Early Access](https://img.shields.io/badge/Pantheon-Early_Access-yellow?logo=pantheon&color=FFDC28)](https://docs.pantheon.io/oss-support-levels#early-access)

[![Terminus v3.x Compatible](https://img.shields.io/badge/terminus-3.x-green.svg)](https://github.com/pantheon-systems/terminus-composer-logs-plugin/tree/main)

A plugin that shows composer logs via Terminus.

Adds commands 'composer:logs' and 'composer:logs-update' to Terminus. Learn more about Terminus Plugins in the
[Terminus Plugins documentation](https://pantheon.io/docs/terminus/plugins)

## Configuration

These commands require no configuration

## Usage
* `terminus composer:logs SITE.ENV`
* `terminus composer:logs-update SITE.ENV`

## Installation

To install this plugin using Terminus 3:
```
terminus self:plugin:install terminus-composer-logs-plugin
```


## Help
Run `terminus help composer:logs` for help.

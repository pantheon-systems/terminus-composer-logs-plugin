# Terminus Composer Logs Plugin

[![CircleCI](https://circleci.com/gh/pantheon-systems/terminus-plugin-example.svg?style=shield)](https://circleci.com/gh/pantheon-systems/terminus-plugin-example)
[![Actively Maintained](https://img.shields.io/badge/Pantheon-Actively_Maintained-yellow?logo=pantheon&color=FFDC28)](https://pantheon.io/docs/oss-support-levels#actively-maintained-support)

[![Terminus v2.x - v3.x Compatible](https://img.shields.io/badge/terminus-2.x%20--%203.x-green.svg)](https://github.com/pantheon-systems/terminus-plugin-example/tree/2.x)

A simple plugin that shows composer logs via Terminus.

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

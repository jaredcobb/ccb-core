# Church Community Builder Core API

[![Build Status](https://travis-ci.org/jaredcobb/ccb-core.svg?branch=master)](https://travis-ci.org/jaredcobb/ccb-core)

## A WordPress Plugin that syncs your church data

CCB Core API is a WordPress plugin that has one simple job: It **synchronizes** your church data from [Church Community Builder](https://www.churchcommunitybuilder.com/) into your WordPress database as [Custom Post Types](https://codex.wordpress.org/Post_Types#Custom_Post_Types), [Custom Taxonomies](https://codex.wordpress.org/Taxonomies#Custom_Taxonomies), and [Post Meta](https://codex.wordpress.org/Custom_Fields).

## Who should use this?

This plugin is geared toward Developers, Designers, and Site Administrators who are familiar with customizing WordPress templates. While it does a great job of synchronizing the data, you'll still need to alter your theme in order to take *advantage* of the data.

## What's included?

Out of the box, there are two complete integrations:

### Public Groups

This integration will synchronize any groups that are both _publicly listed_ and _active_ from the Church Community Builder `group_profiles` service to a Custom Post Type named `ccb_core_groups`.

### Public Calendar (Events)

This integration will synchronize all events from the Church Community Builder `public_calendar_listing` service to a Custom Post Type named `ccb_core_calendar`.

## Features

* **Auto Synchronize** - Set it and forget it! The plugin works in the background, never interrupting you or your visitors.
* **Secure** - Your credentials are encrypted, and so is the connection with the Church Community Builder API.
* **WordPress Standards** - The plugin follows WordPress coding standards and best practices, so it's easy to extend and build upon.
* **Free** - Free as in "speech" or free as in "beer"? Yes! It's [GPLv2 licensed](https://tldrlegal.com/license/gnu-general-public-license-v2). Don't you love open source?

## Customizing & Extending

* Setup additional integrations with other Church Community Builder API services.
* Write your own plugin that builds upon this one.
* Customize the existing integrations (Groups & Events).

**[The Wiki](https://github.com/jaredcobb/ccb-core/wiki) has more information and code samples.**

## General Usage

General usage information (setting up the plugin and customizing your theme) can be found in the [usage docs](https://www.wpccb.com/documentation/).

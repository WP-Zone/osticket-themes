# Themes for osTicket


## Overview

This osTicket plugin introduces basic theming functionality to osTicket. When the plugin is active, themes can be created as subdirectories under `(osTicket Root Directory)/themes`, and activated via osTicket Admin Panel > Manage tab > Plugins > Themes > Instances tab > (select instance) > Config tab > Theme dropdown. Theme directory names may not contain characters other than letters, numbers, and underscores.

## Theme Anatomy

Each theme directory must contain a `theme.php` file with a class that extends `OsTicketTheme`. Theme class names should be unique and not likely to conflict with other classes in the osTicket codebase or plugins. Each theme class is required to declare a `getName()` method that returns the name of the theme.

Theme directories may contain asset files such as CSS and JS files, including in subdirectories.

An example theme is included in the `example_theme` directory.

## Theme Methods

Theme classes may override the following class methods to implement theme customizations:

### `getHeaderStyles()`

Returns an array of CSS stylesheet URLs to load in the page header (right before the closing `</head>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash.

### `getHeaderScripts()`

Returns an array of JavaScript URLs to load in the page header (right before the closing `</head>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash.

### `getFooterScripts()`

Returns an array of JavaScript URLs to load in the page footer (right before the closing `</body>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash.

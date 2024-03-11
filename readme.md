# Themes for osTicket


## Overview

This osTicket plugin introduces basic theming functionality to osTicket. When the plugin is active, themes can be created as subdirectories under `(osTicket Root Directory)/themes`, and activated via osTicket Admin Panel > Manage tab > Plugins > Themes > Instances tab > (select instance) > Config tab > Theme dropdown. Theme directory names may not contain characters other than letters, numbers, and underscores.

## Theme Anatomy

Each theme directory must contain a `theme.php` file with a class that extends `OsTicketTheme`. Theme class names should be unique and not likely to conflict with other classes in the osTicket codebase or plugins. Each theme class is required to declare a `getName()` method that returns the name of the theme.

Theme directories may contain a `templates` directory with `clients` and/or `staff` subdirectories containing PHP templates for client and staff pages. All of these are optional.

Theme directories may contain asset files such as CSS and JS files, including in subdirectories.

An example theme is included in the `example_theme` directory.

## Theme Methods

Theme classes may override the following class methods to implement theme customizations:

### `getHeaderStyles($isStaffView)`

Returns an array of CSS stylesheet URLs to load in the page header (right before the closing `</head>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash. `$isStaffView` is `true` if the current page is in the staff/agent area and `false` if it is in the client area, and can be used to load different stylesheets for these areas.

### `getHeaderScripts($isStaffView)`

Returns an array of JavaScript URLs to load in the page header (right before the closing `</head>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash. `$isStaffView` is `true` if the current page is in the staff/agent area and `false` if it is in the client area, and can be used to load different scripts for these areas.

### `getFooterScripts($isStaffView)`

Returns an array of JavaScript URLs to load in the page footer (right before the closing `</body>` tag). Use the `getBaseUrl()` helper method to get the base URL of the theme, including trailing slash. `$isStaffView` is `true` if the current page is in the staff/agent area and `false` if it is in the client area, and can be used to load different scripts for these areas.

### `getMinimumLogoAspectRatio()`

Allows the theme to override or disable the minimum aspect ratio constraint when a logo is uploaded via the admin area. By default, osTicket will output an error when attempting to upload a logo with an aspect ratio less than 2 (2:1). Returning `-1` from this function will disable the aspect ratio requirement altogether.

### `isLoggedInAsClient()`

Returns `true` if the current user is a logged in client.

### `isLoggedInAsStaff()`

Returns `true` if the current user is a logged in staff member.

### `isLoggedInAsAdmin()`

Returns `true` if the current user is a logged in staff member with admin privileges.

## Template Files

The following template files are supported in the `templates` directory (all of these are optional and the default HTML will be rendered for any that are omitted). Each template file must return the template content as a string (do not use `echo`, etc.). Templates are only used for rendering HTML within the `<body>` tag.

### `clients/header.php`

Contains the header (above navigation) template for the clients view.

### `clients/nav.php`

Contains the navigation template for the clients view.

### `clients/footer.php`

Contains the footer template for the clients view.

### `clients/landing_page.php`

Contains the landing page body template.

### `clients/login_page.php`

Contains the login page body template.

### `staff/header.php`

Contains the header (above navigation) template for the staff view.

### `staff/nav.php`

Contains the navigation template (tabs and secondary navigation bar) for the staff view.

### `staff/footer.php`

Contains the footer template for the staff view.

## Template Variables

The following variables contain HTML for various system-generated elements that can be used in templates. These variables are not guaranteed to be set.

### $logo

The HTML for the linked logo image, typically shown in the header.

### $topnav

The HTML for the top right navigation (typically contains logged in status, log out link, etc.)

### $nav

The HTML for the main navigation `<ul>`.

### $subnav

The HTML for the secondary navigation `<nav>` element. **Staff templates only.**

### $footer

The default footer content HTML.

### $landing

The default landing page body HTML. **Clients landing page template only.**

### $loginform

The login form HTML. **Clients login page template only.**

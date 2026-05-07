# PHP Newsletter

PHP Newsletter is a self-hosted email marketing and subscriber management system built on Laravel 10. It helps you manage subscriber lists, create templates, schedule campaigns, track opens and clicks, and keep all campaign data on your own server.

This README is based on `manual_en.html` and summarizes the project for installation, launch, and day-to-day use.

## Overview

PHP Newsletter is suitable for:

- newsletters and editorial digests;
- marketing campaigns and promotional mailings;
- service notifications and website announcements;
- internal or member-only mailing lists.

It allows you to:

- manage subscribers and categories;
- create HTML and plain text campaigns;
- personalize messages with macros;
- attach files to templates;
- schedule campaigns for future delivery;
- collect open and click statistics;
- run the whole system on your own infrastructure.

## Key Features

### Email Delivery

- SMTP, `mail()`, and `sendmail` support
- scheduled and manual mailing
- resend flow for unsent messages
- attachments support
- custom email headers

### Templates and Personalization

- WYSIWYG editor
- HTML and plain text messages
- macros for subscriber personalization
- multi-encoding support

### Subscriber Management

- categories and segmentation
- manual subscriber creation
- import and export tools
- subscription confirmation and unsubscribe flow

### Analytics and Reporting

- sent and failed delivery history
- HTML open tracking
- link click tracking
- downloadable spreadsheet reports

## Quick Start

1. Install the application and complete the setup wizard.
2. Log in to the admin panel as the administrator.
3. Configure the delivery method: SMTP, `mail()`, or `sendmail`.
4. Create subscriber categories.
5. Add or import subscribers.
6. Create a template and define macros if needed.
7. Send a test email.
8. Create a schedule entry and configure cron.
9. Review logs and reports after the first live mailing.

## System Requirements

- PHP 8.2 or newer
- Laravel 10
- MySQL 5.6 or newer
- Apache 2+ with `mod_rewrite`, or Nginx pointing to `public/index.php`

### Required PHP Extensions

- `mbstring`
- `zip`
- `curl`
- `iconv`
- `gd`
- `fileinfo`

## Installation

1. Upload or extract the project into your website directory.
2. Configure the virtual host or domain to point to the application.
3. Make sure the writable directories are writable by PHP.
4. Open the installer in the browser:

```text
http://your-domain/install/
```

5. Complete the setup wizard:

- system requirement check
- permission check
- database configuration
- administrator creation
- installation completion

The installer creates `.env`, writes database credentials, runs migrations and seeders, generates the application key, and creates the first administrator account.

### Writable Directories

The application typically needs write access to:

- `storage/app`
- `storage/framework/cache`
- `storage/framework/sessions`
- `storage/framework/views`
- `storage/logs`
- `bootstrap/cache`

## First Login Checklist

After installation:

1. Open `/login` and sign in.
2. Go to `Settings` and configure:
- sender email address
- sender name
- content type
- character set
- subscription confirmation behavior
3. Go to `SMTP` and add at least one valid SMTP profile if SMTP will be used.
4. Verify the site URL and unsubscribe links.
5. Create a test template and send a test email.

## Admin Panel Modules

| Module | Purpose |
| --- | --- |
| Templates | Create and edit email templates, add attachments, and start manual sends |
| Category | Create subscriber categories and audience segments |
| Subscribers | Manage subscribers, import/export data, activate, deactivate, and remove entries |
| Macros | Define reusable placeholders for personalization |
| Schedule | Plan campaign delivery by date, time, and category |
| Log | Review send history and download reports |
| Redirect | Track link clicks recorded through internal redirects |
| SMTP | Manage SMTP servers and related settings |
| Settings | Control global behavior, delivery, content, and intervals |
| Users | Manage admin panel users and roles |
| Pages | Utility pages such as PHP info, subscription form, and cron help |

## Subscribers

### Import

Supported import formats:

- TXT
- CSV
- XLS
- XLSX
- ODS

Before import, it is recommended to:

- remove duplicates;
- validate email addresses;
- prepare destination categories in advance;
- check file encoding.

### Export

Supported export formats:

- TXT
- XLSX

### Public Subscription Endpoints

The application includes built-in public endpoints:

- `/form` - subscription form
- `/categories` - category list for the public form
- `/add-sub` - add a new subscriber
- `/unsubscribe/{subscriber}/{token}` - unsubscribe link
- `/subscribe/{subscriber}/{token}` - subscription confirmation link

## Templates, Macros, and Attachments

Templates can contain:

- message subject or name;
- HTML or plain text body;
- delivery priority;
- optional attachments.

Macros are placeholders used to personalize outgoing mail. Typical use cases include subscriber name, email address, service links, and other custom values.

Attached files are linked to a template and added automatically during delivery.

## Delivery Methods

| Mode | Recommended Use |
| --- | --- |
| SMTP | Recommended for production and reliable delivery |
| `mail()` | Suitable only for simple or temporary setups |
| `sendmail` | Use when a working sendmail binary is available |

Key settings to review:

- sender email address and sender name;
- content type: HTML or plain text;
- character set;
- sendmail path if this mode is used;
- send limits per run;
- sleep interval between messages;
- resend interval for the same subscriber.

## Tracking and Reports

### Open Tracking

For HTML email, the system injects a `1x1` tracking image into the message body. When the image is loaded, the message is marked as opened.

Notes:

- available only for HTML emails;
- depends on external image loading in the recipient mail client;
- useful for analytics, but never perfectly accurate.

### Click Tracking

Links can be routed through an internal redirect endpoint so the system records click statistics before sending the user to the final destination.

### Reports

The system stores:

- send history;
- success and failure statuses;
- HTML open data;
- click-through records;
- downloadable spreadsheet reports.

## Scheduler and Cron

The project uses two console commands:

- `php artisan emails:send` - processes scheduled delivery
- `php artisan emails:unsent` - retries unsent messages

The Laravel scheduler is currently configured to run:

- `emails:send` every minute
- `emails:unsent` every ten minutes

Example direct cron commands shown by the project:

```text
/usr/bin/php -q /path/to/artisan emails:send
/usr/bin/php -q /path/to/artisan emails:unsent
```

If cron is not configured, scheduled campaigns will not start automatically.

## Roles and Security

Main roles:

- `admin` - full access to all modules, including SMTP, settings, and users
- `moderator` - access to categories, subscribers, and macros

Recommendations:

- use a strong administrator password;
- restrict admin panel access by IP if possible;
- create database backups before upgrades;
- do not use production SMTP credentials in development or test environments;
- review send logs and application logs regularly.

## Deliverability Recommendations

- prefer SMTP over `mail()` for production campaigns;
- configure SPF, DKIM, and ideally DMARC for the sender domain;
- avoid sudden high-volume mailing without warming up the domain and IP;
- make sure unsubscribe links are valid and publicly reachable;
- keep attachments and HTML complexity under control;
- send test campaigns to several mailbox providers before a large mailing.

## Troubleshooting

### Emails Are Not Sent

- verify SMTP host, port, username, and password;
- make sure the firewall allows outbound SMTP connections;
- check send logs and application logs;
- confirm that cron is active.

### Subscriptions Do Not Arrive

- check that the public subscription form is available and `/add-sub` works;
- make sure the email address is not already in the database;
- verify whether confirmation is enabled and whether confirmation emails are delivered.

### Open Tracking Does Not Work

- make sure the campaign is sent as HTML;
- remember that many mail clients block external images;
- confirm that the application URL is correct and the tracking pixel is publicly reachable.

### Scheduled Campaigns Do Not Start

- check cron configuration and `php artisan schedule:run` usage if you rely on Laravel scheduler;
- make sure the schedule entry falls into the correct time window;
- verify the server time zone.

## Project Structure

- `app/` - controllers, models, services, DTOs, middleware, helpers, and console commands
- `routes/` - web, API, and console routes
- `resources/views/` - admin templates, public subscription pages, and installer screens
- `database/migrations/` - database schema definitions
- `storage/` - logs, cache, temporary files, and stored attachments
- `public/` - public assets and entry point

## Summary

PHP Newsletter is a complete self-hosted mailing solution with subscriber management, segmentation, templates, analytics, and scheduling. For stable production use, pay special attention to SMTP configuration, cron, writable directories, and sender-domain DNS records.

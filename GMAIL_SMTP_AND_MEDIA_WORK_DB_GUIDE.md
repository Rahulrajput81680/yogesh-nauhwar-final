# Admin SMTP and Security Notes

This file is a short handover for SMTP, admin security, blog storage, and the media coverage image fix.

## Where To Add Real SMTP Details

Use [admin/smtp-settings.php](admin/smtp-settings.php) first. Those values are saved in the `admin_settings` table and override the fallback values in [admin/config/config.php](admin/config/config.php).

If you want code defaults, edit [admin/config/config.php](admin/config/config.php) and change the `MAIL_SMTP_*` constants. The mail sender itself lives in [admin/core/mailer.php](admin/core/mailer.php).

For Gmail you usually need:

1. SMTP host: `smtp.gmail.com`
2. Port: `587`
3. Encryption: `tls`
4. Username: your full Gmail address
5. Password: a Google App Password, not your normal Gmail password

## How Admin Mail Works

- Contact form submissions are stored from [contact.php](contact.php) and auto-reply to the user email.
- Password reset requests are handled in [admin/core/password_reset.php](admin/core/password_reset.php).
- Both flows send mail through [admin/core/mailer.php](admin/core/mailer.php).
- The reset expiry time is displayed in India time now, while the token itself is still stored safely in the database.

## Admin Security Rules Checked

- [admin/init.php](admin/init.php) is the main bootstrap for every admin page.
- [admin/core/security.php](admin/core/security.php) blocks expired CSRF tokens and protects login-required pages.
- [admin/core/functions.php](admin/core/functions.php) provides login checks, flash messages, escaping, and activity logging.
- Sensitive admin pages call `require_login()` and `require_permission()` before doing work.
- Direct access to core files is blocked with `ADMIN_INIT` guards.
- Form submissions use CSRF protection.
- Output is escaped before rendering most admin data.
- The SMTP password field no longer echoes the saved secret back into the HTML form.

Security note: [admin/config/config.php](admin/config/config.php) still contains fallback credentials, so treat it as sensitive and do not expose it publicly.

## Blog Content Error Fix

The blog editor can save HTML from Summernote, including images, so `blogs.content` was updated from `TEXT` to `LONGTEXT` in [database/schema.sql](database/schema.sql) and [admin/core/schema_bootstrap.php](admin/core/schema_bootstrap.php).

That fixes the `Data too long for column 'content'` error when you add image-heavy blog content.

If your database already exists, you do not need to rebuild everything. The admin bootstrap runs the same upgrade automatically when you open any admin page. If you want to do it manually, run this once in MySQL:

```sql
ALTER TABLE blogs MODIFY content LONGTEXT NOT NULL;
```

The `import-db.php` file is only for fresh local setup or a full re-import. You can delete it after setup if you do not want a public import script on the server, but keep a local copy if you may need to rebuild the database later.

## Media Coverage Image Scale Fix

The zoom effect on the active media coverage card was removed in [assets/css/main.css](assets/css/main.css). The active slide now keeps the normal image size instead of scaling up.

## Why `schema_bootstrap.php` Exists

[admin/core/schema_bootstrap.php](admin/core/schema_bootstrap.php) is a safety net. It makes sure important tables and columns exist even if the database was imported partially or an older schema is still present.

That file is why the admin panel can repair missing pieces without asking you to re-import the whole database every time.

## Why `media` And `our_work` Tables Do Not Appear

Your app uses one image table called `gallery`.

The sections are separated by the `display_section` column:

- `gallery`
- `media_coverage`
- `our_work`

So `media` and `our_work` are not missing. They are just logical sections inside the `gallery` table, not separate tables in MySQL.

That is why [media-coverage.php](media-coverage.php) and [our-work.php](our-work.php) read from the same table but show different sections.

## Short File Map

- [admin/config/config.php](admin/config/config.php): database, URL, session, and fallback mail defaults.
- [admin/init.php](admin/init.php): loads config, security, permissions, mail, and schema bootstrap.
- [admin/core/security.php](admin/core/security.php): CSRF, login guard, and request protection.
- [admin/core/functions.php](admin/core/functions.php): helpers for escaping, settings, logging, and redirects.
- [admin/core/mailer.php](admin/core/mailer.php): SMTP mail sender used by contact and password reset.
- [admin/core/password_reset.php](admin/core/password_reset.php): reset token creation, validation, expiry, and email template.
- [admin/smtp-settings.php](admin/smtp-settings.php): admin form to save real SMTP values.
- [admin/forgot-password.php](admin/forgot-password.php): password reset request screen.
- [admin/reset-password.php](admin/reset-password.php): token-based password reset screen.
- [admin/modules/blog/create.php](admin/modules/blog/create.php) and [admin/modules/blog/edit.php](admin/modules/blog/edit.php): blog HTML editor and save/update logic.
- [admin/modules/media/index.php](admin/modules/media/index.php), [admin/modules/media/create.php](admin/modules/media/create.php), [admin/modules/media/edit.php](admin/modules/media/edit.php), and [admin/modules/media/delete.php](admin/modules/media/delete.php): media coverage upload, edit, list, and delete screens.
- [media-coverage.php](media-coverage.php): frontend media coverage page.

## One-Line Summary

SMTP is configured in the admin settings page, admin access is guarded by bootstrap plus login and CSRF checks, blog HTML now fits larger content, and the media coverage cards no longer zoom the active image.

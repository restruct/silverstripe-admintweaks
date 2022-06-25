# Restruct Silverstripe Admin Tweaks module

This module serves as a portable set of small Silverstripe functionality & styling tweaks by Restruct.

Currently it has been partially updated to SS4, most stuff is deactivated & will be reactivated when needed in projects.


## Functionality:

- Provides a global $themeDirResourceURL ($ThemeDir replacement, eg: `{$themeDirResourceURL('my-theme')}/videos/vid-{$RandomNumber}.mp4`)
- SSViewer_ExtraIterators (eg "col-2-of-4"): GroupSize, PosInGroup, FirstOfGroup, LastOfGroup, FirstLastOfGroup, GroupOfGroups
- CacheHelpers::cached_http_request(), cached_json_request() & cached_jsonLD_request()
- GeneralHelpers::safelyGetProperty() & download_and_save_asset()
- Various stuff added to Page by PageHelpersExtension
- ScheduledMethodCall class to call any method on a schedule using QueuedJobs module (if installed)
- FormFieldBootstrapExtension to simply add .form-control class to formfields (optional)
- FormFieldTweaksExtension to add classes & attributes to form holders only (combine with ->setFieldHolderTemplate('FormFieldTweaks_holder'))
- EnforceCMSPermission trait to have DataObjects require CMS access credentials for CRUD actions
- GridFieldConfigs::editable_orderable()
- SelectiveLumberjack class (fixes Lumberjack to NOT filter listview and also take hide_from_cms_tree (core functionality) into account)
- Sets Session.cookie_secure true
- Makes UserDefinedForm NOT save to server by default (GDPR)
- Sets slightly higher image quality values & activates WEBP format (if webmen/silverstripe-webp-images is installed)
- Activates MimeUploadValidator (if silverstripe/mimevalidator is installed)
- Sets default ("cacheblock") cache to 24 hours & creates a "appcache" of 1 hour
- Has some email config helpers (see "Email config" below)
- Sets some image manipulation fallbacks in case methods are missing (legacy, focuspoint, cropper)
- Sets a slightly more secure password policy
- Activates googlesitemaps (if module installed)
- Registers FeaturedImage & CurrentYear shortcodes (if shortcode module is installed)
- Adds stylish pageicons for common pagetypes (font awesome icons)
- (Bigfork:) Hides pagetypes that cannot be created & admin sections that clients rarely use
- Hides CampaignAdmin & ReportAdmin in nav (rarely used by clients)
- Groups RedirectedURLAdmin, ArchiveAdmin, QueuedJobsAdmin, SubsiteAdmin and SiteConfig nav-buttons under "Advanced" dropdown (if symbiote/silverstripe-grouped-cms-menu is installed)
- ...

## Untested / dropped-in functionality

Stuff quickly copied into this module for portability but may need some tweaking/generalization before being actually usable:

- IpAddressField formfield with IP validation (may currently be only usable in front-end, not sure)
- DBNullableInt & MySQLSchemaManagerNullable
- ...

## Notes:

- Legacy .margin-left & .small formfield styling -> (v4) just $field->removeExtraClass('stacked')->setRows(15)

## Email config

Define email config in .env (environment) to have it auto-applied:

```yml
# EMAIL.yml
APP_SYSTEM_EMAIL_SENDER="" # eg System X/Y
APP_SYSTEM_EMAIL_ADDRESS="" # eg noreply@host.tld

# SMTP mailserver, NOTE: SwiftMail 'ssl' => SMTP over SSL/TLS / 'tls' => STARTTLS
# LIVE/Default: Mailgun servers (listen on ports 25, 465 (SSL/TLS), 587 (STARTTLS), and 2525)
APP_SMTP_HOST=""
APP_SMTP_PORT=""
APP_SMTP_ENCRYPTION=""
APP_SMTP_USERNAME=""
APP_SMTP_PASSWORD=""

# Send error logs via email
APP_LOG_MAIL_RECIPIENT=""
APP_LOG_MAIL_SUBJECT=""
APP_LOG_MAIL_SENDER=""
APP_LOG_MAIL_LEVEL=""
```

## TODO/REACTIVEATE/UPDATE for SS4:

- JS: Add optional loading feedback overlay to buttons
- JS: Inline gridfieldeditablecolumns datepicker fixes
- JS: Advanced search toggle for modeladmins
- Check & improve

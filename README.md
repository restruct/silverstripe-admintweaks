# Restruct Silverstripe Admin Tweaks module

This module serves as a portable set of small Silverstripe functionality & styling tweaks by Restruct.

Currently it has been partially updated to SS4, most stuff is deactivated & will be reactivated when needed in projects.


## Functionality:

- ImagePlaceholder functionality (SVG)
- Contact & social media fields in SiteConfig (optional/configurable)
- Raw html head/body tag fields in SiteConfig (optional/configurable)
- Browser-chrome colorpicker field in SiteConfig (optional/configurable)
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
- Various Block (Elemental) tweaks (+ block icon/thumbnail preview route at `admin/blocktypeicons`)
- Fix to make empty/unchecked checkboxes in editablegridfields submit data (eg unset)
- Workaround checkboxes being unset by $form->loadData() when they dont have a 1:1 fieldname/relation on object (set attribute data-setactivecheckboxvalues to force a value onto checkboxsetfields)
- ...


## Untested / dropped-in functionality

Stuff quickly copied into this module for portability but may need some tweaking/generalization before being actually usable:

- IpAddressField formfield with IP validation (may currently be only usable in front-end, not sure)
- DBNullableInt & MySQLSchemaManagerNullable
- ...


## Notes:

- Legacy .margin-left & .small formfield styling -> (v4) just $field->removeExtraClass('stacked')->setRows(15)


## SiteConfig ... config

Set wether to 'decorate' siteconfig or not:

```yml
# NOTE: this extension adds various extra fields & functionality to siteadmin, activate on a per-project basis
SilverStripe\SiteConfig\SiteConfig:
  extensions:
    - Restruct\Silverstripe\AdminTweaks\Extensions\SiteConfigExtension
  # move 'access' fields to main tab & remove 'access' tab
  rearrange_access_fields: false # (default true)
  enable_browser_color_theme_field: false # (default true)
  enable_subnav_activation_field: false # (default true)
  enable_contact_social_media_fields: false # (default true)
  # use in templates: {$SiteConfig.ExtraHTML_HeadStart.RAW} to include extra html
  enable_raw_head_body_fields: false # (default true)
  # deactivate container classes (or set array to override)
  theme_container_classes: false # default bootstrap container classes

## Image placeholder

<img width="191" src="https://user-images.githubusercontent.com/1005986/177027008-2c711cad-9c0c-47ea-a56a-1dc6f4861ba7.png">

Include SVG directly in template:
```
<% include ImagePlaceholder W=180, H=50, Label='logo', AddClass='rounded' %>
```
Include SVG directly:
```
$ImagePlaceholder(180, 50, 'logo', '', 'rounded')
```
Include SVG as img src (base64 data-uri):
```
<img class="rounded" src="$ImagePlaceholder(180, 50, 'logo', true)" alt="logo" width="180" height="50">
```

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

## Show Block design/thumbnails instead of icons in admin UI (Elemental)

1. copy & adapt below section to specific project code css to show designed block previews instead of icons
2. set private static $icon to 'block-design block-section {block-name-offset}'
3. add stacked blocks img to app/client (.block-name-offset sets offset if multiple stacked in one image)

```scss
i.block-section, button.block-section:before {
  background-image: url(~app/client/imgs/block-group-designs_stacked.png);
  background-position: 0 0;
}
i.block-section, button.block-section {
  &.block-name-offset {&, &:before {
    background-position: 0 -128px;
  }}
  &.block-othername-offset {&, &:before {
    background-position: 0 -28px;
  }}
}

## TODO/REACTIVEATE/UPDATE for SS4:

- JS: Add optional loading feedback overlay to buttons
- JS: Inline gridfieldeditablecolumns datepicker fixes
- JS: Advanced search toggle for modeladmins
- Check & improve

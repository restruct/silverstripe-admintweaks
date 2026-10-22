<?php

namespace Restruct\Silverstripe\AdminTweaks\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;

/**
 * Optional per-ModelAdmin tweaks, opt-in via config on the ModelAdmin subclass (or project-wide
 * via yml on SilverStripe\Admin\ModelAdmin, with per-subclass overrides).
 */
class ModelAdminExtension extends Extension
{
    /**
     * @config
     * Auto-expand the GridField search bar on load (instead of hiding it behind the magnifier
     * icon). Opt-in PER ModelAdmin subclass:
     *   private static bool $auto_expand_gridfield_search = true;
     * Adds a marker class on the GridField which the matching entwine block in admintweaks.js
     * acts on (it clicks the React search toggle — staying compatible with the SS admin React
     * layer instead of re-implementing its open/close state).
     */
    private static bool $auto_expand_gridfield_search = false;

    /**
     * @config
     * Hide the framework-scaffolded Export to CSV / Print / Import CSV buttons that ModelAdmin
     * adds to EVERY managed model (the scaffolded CSV export just dumps summary_fields, and the
     * default CsvBulkLoader import is a data-integrity risk on synced/managed models).
     * Enable project-wide via yml on SilverStripe\Admin\ModelAdmin, opt back OUT per subclass:
     *   private static bool $hide_scaffolded_csv_buttons = false;
     * (or re-add specific components in that admin's getGridFieldConfig()).
     *
     * Also switches the CSV IMPORT itself off, not just its button: ModelAdmin's ImportForm and
     * import actions stay routable (ImportForm is in its allowed_actions) and only check the
     * public $showImportForm property, and with model_importers unset core registers a generic
     * CsvBulkLoader per model. So without this a hand-built POST to <admin>/<Model>/ImportForm
     * still imported, with the button gone. See onBeforeInit().
     * SecurityAdmin is a ModelAdmin too (Silverstripe 5 and 6), so enabling this project-wide also
     * removes its member CSV import; opt it back in with `hide_scaffolded_csv_buttons: false`
     * on SilverStripe\Admin\SecurityAdmin.
     */
    private static bool $hide_scaffolded_csv_buttons = false;

    /**
     * $showImportForm is a public instance property, not config, so it is set on the controller
     * here: before init() and before any action is handled (including the import form's own
     * submission). Core then neither adds the Import button nor builds or accepts the form.
     * Only ever switches it OFF; an admin that left the setting off keeps its own value.
     */
    public function onBeforeInit(): void
    {
        if ($this->getOwner()->config()->get('hide_scaffolded_csv_buttons')) {
            $this->getOwner()->showImportForm = false;
        }
    }

    public function updateGridField(GridField $field): void
    {
        if ($this->getOwner()->config()->get('auto_expand_gridfield_search')) {
            $field->addExtraClass('at-auto-expand-search');
        }
    }

    public function updateGridFieldConfig(GridFieldConfig $config): void
    {
        if ($this->getOwner()->config()->get('hide_scaffolded_csv_buttons')) {
            # The Import button would already be absent (onBeforeInit() switched showImportForm
            # off); removing it here as well covers an admin that adds it back by hand.
            $config->removeComponentsByType([
                GridFieldExportButton::class,
                GridFieldPrintButton::class,
                GridFieldImportButton::class,
            ]);
        }
    }
}

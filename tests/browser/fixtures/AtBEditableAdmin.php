<?php

namespace Restruct\AtBrowser;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextField;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;

/**
 * BROWSER-TEST FIXTURE ONLY - AtBEditableRecord in GridFields with GridFieldEditableColumns,
 * /admin/atb-editable, for editable-gridfield.spec.ts (admintweaks#114):
 * - "editable": the columns of AtBEditableRecord, with the usual edit link per row,
 * - "nolink": the same columns, without GridFieldEditButton, so no row has an edit link,
 * - "toggle": the same columns plus "Featured", a checkbox shown as a toggle switch (toggle-switch.spec.ts),
 *   and "Hidden", an inverted one (toggle-switch.spec.ts).
 *
 * Needs symbiote/silverstripe-gridfieldextensions in the host (SS5_EXTRA_REQUIRE in targets.sh).
 * (See AtBRecord for why this never loads in a real install.)
 */
class AtBEditableAdmin extends ModelAdmin
{
    private static $url_segment = 'atb-editable';

    private static $menu_title = 'AdminTweaks editable';

    # Keyed managed_models: the key becomes the URL segment and the GridField name.
    private static $managed_models = [
        'editable' => ['dataClass' => AtBEditableRecord::class, 'title' => 'Editable'],
        'nolink' => ['dataClass' => AtBEditableRecord::class, 'title' => 'No edit link'],
        'toggle' => ['dataClass' => AtBEditableRecord::class, 'title' => 'Toggle switch'],
    ];

    protected function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        # Editable columns in place of the plain data columns, as a project would set them up.
        $config->removeComponentsByType(GridFieldDataColumns::class);
        $editable = GridFieldEditableColumns::create();
        $columns = [
            'Title' => ['title' => 'Title', 'field' => TextField::class],
            'Category' => [
                'title' => 'Category',
                'callback' => fn () => DropdownField::create('Category', 'Category', AtBEditableRecord::CATEGORIES),
            ],
            'Active' => ['title' => 'Active', 'field' => CheckboxField::class],
            'Tags' => [
                'title' => 'Tags',
                'callback' => fn () => CheckboxSetField::create('Tags', 'Tags', AtBEditableRecord::TAGS),
            ],
            # Read-only: a LiteralField is never marked editable-column-field.
            'Summary' => ['title' => 'Summary', 'field' => LiteralField::class],
        ];
        if ($this->modelTab === 'toggle') {
            # A column's field is built from the column config, so the class goes on in a callback
            # (docs/form-fields.md). Only on this tab, so the other tabs keep the columns their
            # specs count.
            $columns['Featured'] = [
                'title' => 'Featured',
                'callback' => fn ($record, $col) => CheckboxField::create($col)->addExtraClass('toggle-switch'),
            ];
            # Inverted (ON while unchecked), with both classes by hand on the input
            $columns['Hidden'] = [
                'title' => 'Hidden',
                'callback' => fn ($record, $col) => CheckboxField::create($col)->addExtraClass('toggle-switch toggle-switch--inverted'),
            ];
        }
        $editable->setDisplayFields($columns);
        # Two lines in the read-only column (escaped value, then a fixed second line).
        $editable->setFieldFormatting([
            'Summary' => fn ($value) => htmlspecialchars((string) $value) . '<br>Second line',
        ]);
        # Before the action columns, where the data columns were.
        $config->addComponent($editable, GridFieldEditButton::class);

        if ($this->modelTab === 'nolink') {
            $config->removeComponentsByType(GridFieldEditButton::class);
        }

        return $config;
    }
}

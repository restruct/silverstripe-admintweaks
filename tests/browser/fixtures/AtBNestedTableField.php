<?php

namespace Restruct\AtBrowser;

use SilverStripe\Forms\TextField;
use SilverStripe\ORM\FieldType\DBField;

/**
 * BROWSER-TEST FIXTURE ONLY - a text field that renders a table of its own next to its input, like
 * a widget that draws its UI as a table. Used in the "nested" tab of AtBEditableAdmin
 * (editable-gridfield.spec.ts): a click inside that inner table must not be taken for a click on
 * the row's own cell (same case as silverstripe/silverstripe-gridfieldextensions#477).
 *
 * (See AtBRecord for why this never loads in a real install.)
 */
class AtBNestedTableField extends TextField
{
    public function Field($properties = [])
    {
        # The inner cell gets padding of its own, so a spec can click the inner td itself (not its content).
        $html = parent::Field($properties)->getValue()
            . '<table class="atb-nested-table"><tbody><tr>'
            . '<td class="atb-nested-table-cell" style="padding: 12px 16px;">'
            . '<span class="atb-nested-table-content">Nested table</span>'
            . '</td></tr></tbody></table>';

        return DBField::create_field('HTMLFragment', $html);
    }
}

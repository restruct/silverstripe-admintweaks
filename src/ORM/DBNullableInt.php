<?php

namespace Restruct\BrillOOP\ORM;

use SilverStripe\ORM\DB;

class DBNullableInt extends \SilverStripe\ORM\FieldType\DBInt
{
    public function __construct($name = null, $precision = 11)
    {
        $this->precision = is_int($precision) ? $precision : 11;

        parent::__construct($name);
    }

    public function requireField()
    {
        $parts = [
            'datatype' => 'int',
            'precision' => $this->precision,
//            'null' => 'not null',
            'default' => null,
            'arrayValue' => $this->arrayValue
        ];
        // MySQLSchemaManager::class; // Hacked to include 'nullable_int'...
        $values = ['type' => 'nullable_int', 'parts' => $parts];
        DB::require_field($this->tableName, $this->name, $values);
    }
}

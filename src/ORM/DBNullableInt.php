<?php

namespace Restruct\BrillOOP\ORM;

use SilverStripe\ORM\FieldType\DBInt;
use Override;
use SilverStripe\ORM\DB;

class DBNullableInt extends DBInt
{
    public function __construct($name = null, $precision = 11)
    {
        $this->precision = is_int($precision) ? $precision : 11;

        parent::__construct($name);
    }

    #[Override]
    public function requireField(): void
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

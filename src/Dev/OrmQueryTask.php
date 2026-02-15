<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;

/**
 * Read-only ORM query task for quick data lookups from CLI.
 *
 * Usage:
 *   sake dev/tasks/orm-query class=Member limit=5
 *   sake dev/tasks/orm-query class=Member "filter[Email:PartialMatch]=example" fields=ID,Email
 *   sake dev/tasks/orm-query class=File schema=1
 *   sake dev/tasks/orm-query class=Page sql=1
 *   sake dev/tasks/orm-query class=Page "where=ParentID > 0 AND ShowInMenus = 1" limit=10
 */
class OrmQueryTask extends BuildTask
{
    private static $segment = 'orm-query';

    protected $title = 'ORM Query';

    protected $description = 'Read-only ORM query tool for quick data lookups (CLI + dev mode only)';

    public function run($request)
    {
        if (!Director::is_cli()) {
            echo "This task can only be run from the command line.\n";
            return;
        }

        if (!Director::isDev()) {
            echo "This task can only be run in dev mode.\n";
            return;
        }

        $className = $request->getVar('class');
        if (!$className) {
            $this->printUsage();
            return;
        }

        // Resolve short class name to FQCN
        $fqcn = $this->resolveClass($className);
        if (!$fqcn) {
            echo "Unknown class: {$className}\n";
            echo "Try the fully qualified class name, e.g. App\\Model\\MyModel\n";
            return;
        }

        // Schema mode — show class structure and exit
        if ($request->getVar('schema')) {
            $this->printSchema($fqcn);
            return;
        }

        // Build query
        $list = $fqcn::get();

        // Apply filters (supports ORM filter operators via bracket notation)
        $filters = $request->getVar('filter');
        if ($filters && is_array($filters)) {
            $list = $list->filter($filters);
        }

        // Apply excludes
        $excludes = $request->getVar('exclude');
        if ($excludes && is_array($excludes)) {
            $list = $list->exclude($excludes);
        }

        // Apply raw WHERE clause
        $where = $request->getVar('where');
        if ($where) {
            $list = $list->where($where);
        }

        // Apply sort
        $sort = $request->getVar('sort');
        if ($sort) {
            $parts = explode(',', $sort);
            $field = $parts[0];
            $dir = $parts[1] ?? 'ASC';
            $list = $list->sort($field, $dir);
        }

        // SQL mode — show generated query and exit
        if ($request->getVar('sql')) {
            echo "Class: {$fqcn}\n";
            echo "Table: " . DataObject::getSchema()->tableName($fqcn) . "\n\n";
            echo $list->dataQuery()->sql() . "\n";
            return;
        }

        // Count-only mode
        if ($request->getVar('count')) {
            echo "Class: {$fqcn}\n";
            echo "Count: {$list->count()}\n";
            return;
        }

        // Apply limit
        $limit = min((int) ($request->getVar('limit') ?: 20), 100);
        $list = $list->limit($limit);

        // Determine fields to display
        $fields = $request->getVar('fields');
        if ($fields) {
            $fieldNames = explode(',', $fields);
        } else {
            $summaryFields = $fqcn::config()->get('summary_fields') ?: [];
            // summary_fields can be ['Field'] or ['Field' => 'Label']
            $fieldNames = [];
            foreach ($summaryFields as $key => $value) {
                $fieldNames[] = is_numeric($key) ? $value : $key;
            }
            $fieldNames = $fieldNames ?: ['ID', 'ClassName', 'Title', 'Created'];
        }

        $total = $fqcn::get()->count();
        $hasFilters = $filters || $excludes || $where;
        $filtered = $hasFilters ? " (filtered from {$total})" : '';

        echo "Class: {$fqcn}\n";
        echo "Showing: {$list->count()} records{$filtered}\n\n";

        if ($list->count() === 0) {
            echo "No records found.\n";
            return;
        }

        $this->printTable($list, $fieldNames);
    }

    /**
     * Resolve a short class name (e.g. "DataSyncItem") to its FQCN.
     */
    private function resolveClass(string $className): ?string
    {
        // Already a FQCN that exists
        if (class_exists($className) && is_subclass_of($className, DataObject::class)) {
            return $className;
        }

        // Search all DataObject subclasses for matching short name
        $allClasses = ClassInfo::getValidSubClasses(DataObject::class);
        foreach ($allClasses as $fqcn) {
            $shortName = ClassInfo::shortName($fqcn);
            if (strcasecmp($shortName, $className) === 0) {
                return $fqcn;
            }
        }

        return null;
    }

    /**
     * Print class schema: $db fields, relations, table name, extensions.
     */
    private function printSchema(string $fqcn): void
    {
        $schema = DataObject::getSchema();
        $singleton = $fqcn::singleton();

        echo "Class: {$fqcn}\n";
        echo "Table: " . $schema->tableName($fqcn) . "\n";

        $ancestry = ClassInfo::ancestry($fqcn);
        $baseClass = reset($ancestry);
        if ($baseClass !== $fqcn) {
            echo "Base:  {$baseClass}\n";
        }

        // Extensions
        $extensions = $fqcn::config()->get('extensions') ?: [];
        if ($extensions) {
            echo "\nExtensions:\n";
            foreach ($extensions as $key => $ext) {
                $label = is_numeric($key) ? '' : "{$key}: ";
                echo "  {$label}{$ext}\n";
            }
        }

        // $db fields
        $dbFields = $fqcn::config()->get('db') ?: [];
        if ($dbFields) {
            echo "\n\$db:\n";
            foreach ($dbFields as $name => $type) {
                echo "  {$name}: {$type}\n";
            }
        }

        // has_one
        $hasOne = $fqcn::config()->get('has_one') ?: [];
        if ($hasOne) {
            echo "\n\$has_one:\n";
            foreach ($hasOne as $name => $class) {
                echo "  {$name} => {$class}\n";
            }
        }

        // has_many
        $hasMany = $fqcn::config()->get('has_many') ?: [];
        if ($hasMany) {
            echo "\n\$has_many:\n";
            foreach ($hasMany as $name => $class) {
                echo "  {$name} => {$class}\n";
            }
        }

        // many_many
        $manyMany = $fqcn::config()->get('many_many') ?: [];
        if ($manyMany) {
            echo "\n\$many_many:\n";
            foreach ($manyMany as $name => $spec) {
                if (is_array($spec)) {
                    echo "  {$name} (through):\n";
                    foreach ($spec as $k => $v) {
                        echo "    {$k}: {$v}\n";
                    }
                } else {
                    echo "  {$name} => {$spec}\n";
                }
            }
        }

        // belongs_many_many
        $belongsManyMany = $fqcn::config()->get('belongs_many_many') ?: [];
        if ($belongsManyMany) {
            echo "\n\$belongs_many_many:\n";
            foreach ($belongsManyMany as $name => $class) {
                echo "  {$name} => {$class}\n";
            }
        }

        // belongs_to
        $belongsTo = $fqcn::config()->get('belongs_to') ?: [];
        if ($belongsTo) {
            echo "\n\$belongs_to:\n";
            foreach ($belongsTo as $name => $class) {
                echo "  {$name} => {$class}\n";
            }
        }

        // Record count
        echo "\nRecords: " . $fqcn::get()->count() . "\n";
    }

    /**
     * Print records as an aligned text table.
     */
    private function printTable($list, array $fieldNames): void
    {
        // Collect rows and calculate column widths
        $rows = [];
        $widths = [];
        foreach ($fieldNames as $name) {
            $widths[$name] = strlen($name);
        }

        foreach ($list as $record) {
            $row = [];
            foreach ($fieldNames as $name) {
                $value = $this->getFieldValue($record, $name);
                $row[$name] = $value;
                $widths[$name] = max($widths[$name], strlen($value));
            }
            $rows[] = $row;
        }

        // Cap column widths at 50 chars
        foreach ($widths as $name => $width) {
            $widths[$name] = min($width, 50);
        }

        // Print header
        $header = '';
        $separator = '';
        foreach ($fieldNames as $name) {
            $header .= str_pad($name, $widths[$name] + 2);
            $separator .= str_repeat('-', $widths[$name]) . '  ';
        }
        echo rtrim($header) . "\n";
        echo rtrim($separator) . "\n";

        // Print rows
        foreach ($rows as $row) {
            $line = '';
            foreach ($fieldNames as $name) {
                $val = $row[$name];
                if (strlen($val) > 50) {
                    $val = substr($val, 0, 47) . '...';
                }
                $line .= str_pad($val, $widths[$name] + 2);
            }
            echo rtrim($line) . "\n";
        }
    }

    /**
     * Get a display value for a field, handling relations and special types.
     */
    private function getFieldValue(DataObject $record, string $fieldName): string
    {
        // Handle dot-notation (e.g. "Author.Name")
        if (str_contains($fieldName, '.')) {
            $parts = explode('.', $fieldName, 2);
            $relation = $record->{$parts[0]}();
            if ($relation && $relation->exists()) {
                return (string) $relation->{$parts[1]};
            }
            return '';
        }

        $value = $record->relField($fieldName);
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function printUsage(): void
    {
        echo "Usage: sake dev/tasks/orm-query class=ClassName [options]\n\n";
        echo "Options:\n";
        echo "  class=Name                       Short or fully qualified class name (required)\n";
        echo "  filter[Field]=Value              Filter by field value (exact match)\n";
        echo "  filter[Field:Operator]=Value     Filter with ORM operator\n";
        echo "  exclude[Field]=Value             Exclude matching records\n";
        echo "  where=\"SQL condition\"             Raw SQL WHERE clause\n";
        echo "  fields=ID,Title,...              Comma-separated fields to display\n";
        echo "  sort=Field,DESC                  Sort field and direction\n";
        echo "  limit=N                          Max records (default 20, max 100)\n";
        echo "  count=1                          Show count only\n";
        echo "  sql=1                            Show generated SQL query\n";
        echo "  schema=1                         Show class schema (\$db, relations, extensions)\n";
        echo "\n";
        echo "Filter operators:\n";
        echo "  :PartialMatch      LIKE %%value%%        filter[Title:PartialMatch]=test\n";
        echo "  :ExactMatch        = value             filter[Code:ExactMatch]=ETW\n";
        echo "  :StartsWith        LIKE value%%          filter[Name:StartsWith]=John\n";
        echo "  :EndsWith          LIKE %%value           filter[Email:EndsWith]=.com\n";
        echo "  :GreaterThan       > value             filter[Created:GreaterThan]=2024-01-01\n";
        echo "  :LessThan          < value             filter[Sort:LessThan]=10\n";
        echo "  :GreaterThanOrEqual  >= value           filter[ID:GreaterThanOrEqual]=100\n";
        echo "  :LessThanOrEqual   <= value            filter[ID:LessThanOrEqual]=50\n";
        echo "  :not               != value            filter[Status:not]=Archived\n";
        echo "\n";
        echo "Examples:\n";
        echo "  sake dev/tasks/orm-query class=Member limit=5\n";
        echo "  sake dev/tasks/orm-query class=Member fields=ID,Email,FirstName\n";
        echo "  sake dev/tasks/orm-query class=File \"filter[Name:EndsWith]=.pdf\" count=1\n";
        echo "  sake dev/tasks/orm-query class=Page \"filter[Created:GreaterThan]=2024-01-01\" sort=Created,DESC\n";
        echo "  sake dev/tasks/orm-query class=Page \"exclude[ClassName]=ErrorPage\"\n";
        echo "  sake dev/tasks/orm-query class=Page \"where=ParentID > 0 AND ShowInMenus = 1\"\n";
        echo "  sake dev/tasks/orm-query class=Page sql=1\n";
        echo "  sake dev/tasks/orm-query class=File schema=1\n";
    }
}

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
 *   vendor/bin/sake dev/tasks/orm-query class=DataSyncItem limit=5
 *   vendor/bin/sake dev/tasks/orm-query class=DataSyncItem "filter[Code]=ETW" fields=ID,Code,Title
 *   vendor/bin/sake dev/tasks/orm-query class=DataSyncItem count=1
 *   vendor/bin/sake dev/tasks/orm-query class=DataSyncItem sort=Created,DESC limit=5
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

        // Build query
        $list = $fqcn::get();

        // Apply filters
        $filters = $request->getVar('filter');
        if ($filters && is_array($filters)) {
            $list = $list->filter($filters);
        }

        // Apply sort
        $sort = $request->getVar('sort');
        if ($sort) {
            $parts = explode(',', $sort);
            $field = $parts[0];
            $dir = $parts[1] ?? 'ASC';
            $list = $list->sort($field, $dir);
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
        $filtered = $filters ? " (filtered from {$total})" : '';

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
        echo "Usage: vendor/bin/sake dev/tasks/orm-query class=ClassName [options]\n\n";
        echo "Options:\n";
        echo "  class=Name           Short or fully qualified class name (required)\n";
        echo "  filter[Field]=Value  Filter by field value\n";
        echo "  fields=ID,Title,...  Comma-separated fields to display\n";
        echo "  sort=Field,DESC      Sort field and direction\n";
        echo "  limit=N              Max records, default 20, max 100\n";
        echo "  count=1              Show count only\n";
        echo "\n";
        echo "Examples:\n";
        echo "  vendor/bin/sake dev/tasks/orm-query class=DataSyncItem limit=5\n";
        echo "  vendor/bin/sake dev/tasks/orm-query class=Member fields=ID,Email,FirstName\n";
        echo "  vendor/bin/sake dev/tasks/orm-query class=DataSyncItem \"filter[Code]=ETW\" count=1\n";
    }
}

<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use SilverStripe\ORM\DataObject;

/**
 * Read-only ORM query task for quick data lookups from CLI.
 *
 * Usage (SS6 console form - the SS5 `dev/tasks/orm-query class=X` query-string form is gone,
 * see UPGRADING.md):
 *   sake tasks:orm-query --class=Member --limit=5
 *   sake tasks:orm-query --class=Member --filter='Email:PartialMatch=example' --fields=ID,Email
 *   sake tasks:orm-query --class=File --schema
 *   sake tasks:orm-query --class=Page --sql
 *   sake tasks:orm-query --class=Page --where='ParentID > 0 AND ShowInMenus = 1' --limit=10
 *   sake tasks:orm-query --class=DataSyncItem --group-by=Type
 *   sake tasks:orm-query --class=DataSyncItem --min=Created --max=Created
 */
class OrmQueryTask extends BuildTask
{
    protected static string $commandName = 'orm-query';

    protected string $title = 'ORM Query';

    protected static string $description = 'Read-only ORM query tool for quick data lookups (CLI + dev mode only)';

    private PolyOutput $output;

    /**
     * Holds partial output between out() calls: the printers below build a line out of several
     * writes, while PolyOutput::writeln() emits whole lines. See out().
     */
    private string $buffer = '';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;

        if (!Director::is_cli()) {
            $this->out("This task can only be run from the command line.\n");
            return Command::FAILURE;
        }

        if (!Director::isDev()) {
            $this->out("This task can only be run in dev mode.\n");
            return Command::FAILURE;
        }

        // SS6: every parameter below is a declared console option (see getOptions()), read off
        // the InputInterface instead of the HTTPRequest the SS5 BuildTask API handed in.
        $className = $input->getOption('class');
        if (!$className) {
            $this->printUsage();
            return Command::INVALID;
        }

        // Resolve short class name to FQCN
        $fqcn = $this->resolveClass($className);
        if (!$fqcn) {
            $this->out("Unknown class: {$className}\n");
            $this->out("Try the fully qualified class name, e.g. App\\Model\\MyModel\n");
            return Command::SUCCESS;
        }

        // Schema mode — show class structure and exit
        if ($input->getOption('schema')) {
            $this->printSchema($fqcn);
            return Command::SUCCESS;
        }

        // Build query
        $list = $fqcn::get();

        // Apply filters (supports ORM filter operators via the `Field:Operator=Value` form)
        $filters = $this->parsePairs($input->getOption('filter'));
        if ($filters) {
            $list = $list->filter($filters);
        }

        // Apply excludes
        $excludes = $this->parsePairs($input->getOption('exclude'));
        if ($excludes) {
            $list = $list->exclude($excludes);
        }

        // Apply raw WHERE clause
        $where = $input->getOption('where');
        if ($where) {
            $list = $list->where($where);
        }

        // Apply sort
        $sort = $input->getOption('sort');
        if ($sort) {
            $parts = explode(',', $sort);
            $field = $parts[0];
            $dir = $parts[1] ?? 'ASC';
            $list = $list->sort($field, $dir);
        }

        // SQL mode — show generated query and exit
        if ($input->getOption('sql')) {
            $this->out("Class: {$fqcn}\n");
            $this->out("Table: " . DataObject::getSchema()->tableName($fqcn) . "\n\n");
            $this->out($list->dataQuery()->sql() . "\n");
            return Command::SUCCESS;
        }

        $hasFilters = $filters || $excludes || $where;

        // Count-only mode
        if ($input->getOption('count')) {
            $this->out("Class: {$fqcn}\n");
            $this->out("Count: {$list->count()}\n");
            return Command::SUCCESS;
        }

        // GroupBy mode — group by field and show counts per value
        $groupByField = $input->getOption('group-by');
        if ($groupByField) {
            $groupLimit = min((int) ($input->getOption('limit') ?: 50), 100);
            $this->printGroupBy($list, $fqcn, $groupByField, $groupLimit, $hasFilters);
            return Command::SUCCESS;
        }

        // Aggregate mode — show sum/avg/min/max values
        $aggregates = $this->getAggregates($input);
        if ($aggregates) {
            $this->printAggregates($list, $fqcn, $aggregates, $hasFilters);
            return Command::SUCCESS;
        }

        // Apply limit
        $limit = min((int) ($input->getOption('limit') ?: 20), 100);
        $list = $list->limit($limit);

        // Determine fields to display
        $fields = $input->getOption('fields');
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
        $filtered = $hasFilters ? " (filtered from {$total})" : '';

        $this->out("Class: {$fqcn}\n");
        $this->out("Showing: {$list->count()} records{$filtered}\n\n");

        if ($list->count() === 0) {
            $this->out("No records found.\n");
            return Command::SUCCESS;
        }

        $this->printTable($list, $fieldNames);

        return Command::SUCCESS;
    }

    /**
     * Buffered writer standing in for the echo calls this task used under the SS5 BuildTask API.
     *
     * The printers below compose a single line out of several writes (str_pad column by column),
     * whereas PolyOutput::writeln() emits one whole line per call. Buffering until a newline keeps
     * the rendered output byte-identical to what the task produced before the SS6 port.
     */
    private function out(string $text): void
    {
        $this->buffer .= $text;
        while (($newline = strpos($this->buffer, "\n")) !== false) {
            $this->output->writeln(substr($this->buffer, 0, $newline));
            $this->buffer = substr($this->buffer, $newline + 1);
        }
    }

    /**
     * Parse repeatable `Field:Operator=Value` options into the assoc array DataList::filter() wants.
     *
     * The SS5 form was `filter[Field:Operator]=Value`, which relied on PHP parsing bracket notation
     * out of a query string. symfony/console has no equivalent, so the key and value are carried in
     * one string and split on the FIRST `=` (values may legitimately contain `=`).
     *
     * @param string[] $pairs
     */
    private function parsePairs(array $pairs): array
    {
        $parsed = [];
        foreach ($pairs as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $pair, 2);
            $parsed[$key] = $value;
        }

        return $parsed;
    }

    public function getOptions(): array
    {
        return [
            new InputOption('class', null, InputOption::VALUE_REQUIRED, 'Short or fully qualified class name (required)'),
            new InputOption('filter', null, InputOption::VALUE_REQUIRED | InputOption::IS_ARRAY, 'Filter as Field=Value or Field:Operator=Value (repeatable)'),
            new InputOption('exclude', null, InputOption::VALUE_REQUIRED | InputOption::IS_ARRAY, 'Exclude as Field=Value or Field:Operator=Value (repeatable)'),
            new InputOption('where', null, InputOption::VALUE_REQUIRED, 'Raw SQL WHERE clause'),
            new InputOption('fields', null, InputOption::VALUE_REQUIRED, 'Comma-separated fields to display'),
            new InputOption('sort', null, InputOption::VALUE_REQUIRED, 'Sort as Field or Field,DESC'),
            new InputOption('limit', null, InputOption::VALUE_REQUIRED, 'Max records (default 20, max 100)'),
            new InputOption('count', null, InputOption::VALUE_NONE, 'Show count only'),
            new InputOption('group-by', null, InputOption::VALUE_REQUIRED, 'Group by field with counts and percentages'),
            new InputOption('sum', null, InputOption::VALUE_REQUIRED, 'Sum of field values'),
            new InputOption('avg', null, InputOption::VALUE_REQUIRED, 'Average of field values'),
            new InputOption('min', null, InputOption::VALUE_REQUIRED, 'Minimum field value'),
            new InputOption('max', null, InputOption::VALUE_REQUIRED, 'Maximum field value'),
            new InputOption('sql', null, InputOption::VALUE_NONE, 'Show generated SQL query'),
            new InputOption('schema', null, InputOption::VALUE_NONE, 'Show class schema ($db, relations, extensions)'),
        ];
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

        $this->out("Class: {$fqcn}\n");
        $this->out("Table: " . $schema->tableName($fqcn) . "\n");

        $ancestry = ClassInfo::ancestry($fqcn);
        $baseClass = reset($ancestry);
        if ($baseClass !== $fqcn) {
            $this->out("Base:  {$baseClass}\n");
        }

        // Extensions
        $extensions = $fqcn::config()->get('extensions') ?: [];
        if ($extensions) {
            $this->out("\nExtensions:\n");
            foreach ($extensions as $key => $ext) {
                $label = is_numeric($key) ? '' : "{$key}: ";
                $this->out("  {$label}{$ext}\n");
            }
        }

        // $db fields
        $dbFields = $fqcn::config()->get('db') ?: [];
        if ($dbFields) {
            $this->out("\n\$db:\n");
            foreach ($dbFields as $name => $type) {
                $this->out("  {$name}: {$type}\n");
            }
        }

        // has_one
        $hasOne = $fqcn::config()->get('has_one') ?: [];
        if ($hasOne) {
            $this->out("\n\$has_one:\n");
            foreach ($hasOne as $name => $class) {
                $this->out("  {$name} => {$class}\n");
            }
        }

        // has_many
        $hasMany = $fqcn::config()->get('has_many') ?: [];
        if ($hasMany) {
            $this->out("\n\$has_many:\n");
            foreach ($hasMany as $name => $class) {
                $this->out("  {$name} => {$class}\n");
            }
        }

        // many_many
        $manyMany = $fqcn::config()->get('many_many') ?: [];
        if ($manyMany) {
            $this->out("\n\$many_many:\n");
            foreach ($manyMany as $name => $spec) {
                if (is_array($spec)) {
                    $this->out("  {$name} (through):\n");
                    foreach ($spec as $k => $v) {
                        $this->out("    {$k}: {$v}\n");
                    }
                } else {
                    $this->out("  {$name} => {$spec}\n");
                }
            }
        }

        // belongs_many_many
        $belongsManyMany = $fqcn::config()->get('belongs_many_many') ?: [];
        if ($belongsManyMany) {
            $this->out("\n\$belongs_many_many:\n");
            foreach ($belongsManyMany as $name => $class) {
                $this->out("  {$name} => {$class}\n");
            }
        }

        // belongs_to
        $belongsTo = $fqcn::config()->get('belongs_to') ?: [];
        if ($belongsTo) {
            $this->out("\n\$belongs_to:\n");
            foreach ($belongsTo as $name => $class) {
                $this->out("  {$name} => {$class}\n");
            }
        }

        // Record count
        $this->out("\nRecords: " . $fqcn::get()->count() . "\n");
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
        $this->out(rtrim($header) . "\n");
        $this->out(rtrim($separator) . "\n");

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
            $this->out(rtrim($line) . "\n");
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

    /**
     * Group by a field and show counts per unique value.
     */
    private function printGroupBy($list, string $fqcn, string $field, int $limit, bool $hasFilters): void
    {
        # Get all values for the field (no limit — we need all matching records)
        try {
            $values = $list->limit(null)->column($field);
        } catch (\InvalidArgumentException $e) {
            $this->out("Invalid field: {$field}\n");
            $this->out("Use schema=1 to see available fields.\n");
            return;
        }
        $total = count($values);

        # Count occurrences, treating null/empty as "(empty)"
        $counts = [];
        foreach ($values as $value) {
            $key = ($value === null || $value === '') ? '(empty)' : (string) $value;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        # Sort by count descending
        arsort($counts);

        $groupCount = count($counts);
        $filtered = $hasFilters ? ' (filtered)' : '';

        $this->out("Class: {$fqcn}{$filtered}\n");
        $this->out("Total: {$total} records in {$groupCount} groups\n\n");

        if ($groupCount === 0) {
            $this->out("No records found.\n");
            return;
        }

        # Determine column widths
        $maxFieldWidth = max(strlen($field), ...array_map('strlen', array_keys($counts)));
        $maxFieldWidth = min($maxFieldWidth, 50);
        $maxCountWidth = max(5, strlen((string) max($counts)));

        # Print header
        $this->out(str_pad($field, $maxFieldWidth + 2));
        $this->out(str_pad('Count', $maxCountWidth + 2));
        $this->out("%\n");
        $this->out(str_repeat('-', $maxFieldWidth) . '  ');
        $this->out(str_repeat('-', $maxCountWidth) . '  ');
        $this->out("------\n");

        # Print rows
        $shown = 0;
        foreach ($counts as $value => $count) {
            if ($shown >= $limit) {
                break;
            }
            $displayValue = strlen((string) $value) > 50
                ? substr((string) $value, 0, 47) . '...'
                : (string) $value;
            $pct = $total > 0 ? round($count / $total * 100, 1) : 0;
            $this->out(str_pad($displayValue, $maxFieldWidth + 2));
            $this->out(str_pad((string) $count, $maxCountWidth + 2));
            $this->out("{$pct}%\n");
            $shown++;
        }

        if ($groupCount > $limit) {
            $this->out("\n... and " . ($groupCount - $limit) . " more groups (use limit=N to show more)\n");
        }
    }

    /**
     * Collect aggregate parameters (sum, avg, min, max) from the request.
     */
    private function getAggregates(InputInterface $input): array
    {
        $aggregates = [];
        foreach (['sum', 'avg', 'min', 'max'] as $func) {
            $field = $input->getOption($func);
            if ($field) {
                $aggregates[] = ['func' => $func, 'field' => $field];
            }
        }
        return $aggregates;
    }

    /**
     * Print aggregate values (sum, avg, min, max) for the list.
     */
    private function printAggregates($list, string $fqcn, array $aggregates, bool $hasFilters): void
    {
        $filtered = $hasFilters ? ' (filtered)' : '';
        $this->out("Class: {$fqcn}{$filtered}\n");
        $this->out("Records: {$list->count()}\n\n");

        foreach ($aggregates as $agg) {
            $func = $agg['func'];
            $field = $agg['field'];
            try {
                $value = $list->$func($field);
            } catch (\InvalidArgumentException $e) {
                $this->out(ucfirst($func) . "({$field}): ERROR - Invalid field\n");
                continue;
            }
            $label = ucfirst($func) . "({$field})";
            $this->out("{$label}: {$value}\n");
        }
    }

    private function printUsage(): void
    {
        $this->out("Usage: sake tasks:orm-query --class=ClassName [options]\n\n");
        $this->out("Options:\n");
        $this->out("  --class=Name                     Short or fully qualified class name (required)\n");
        $this->out("  --filter='Field=Value'           Filter by field value (exact match, repeatable)\n");
        $this->out("  --filter='Field:Operator=Value'  Filter with ORM operator (repeatable)\n");
        $this->out("  --exclude='Field=Value'          Exclude matching records (repeatable)\n");
        $this->out("  --where='SQL condition'          Raw SQL WHERE clause\n");
        $this->out("  --fields=ID,Title,...            Comma-separated fields to display\n");
        $this->out("  --sort=Field,DESC                Sort field and direction\n");
        $this->out("  --limit=N                        Max records (default 20, max 100)\n");
        $this->out("  --count                          Show count only\n");
        $this->out("  --group-by=Field                 Group by field with counts and percentages\n");
        $this->out("  --sum=Field                      Sum of field values\n");
        $this->out("  --avg=Field                      Average of field values\n");
        $this->out("  --min=Field                      Minimum field value\n");
        $this->out("  --max=Field                      Maximum field value\n");
        $this->out("  --sql                            Show generated SQL query\n");
        $this->out("  --schema                         Show class schema (\$db, relations, extensions)\n");
        $this->out("\n");
        $this->out("Filter operators:\n");
        $this->out("  :PartialMatch      LIKE %%value%%        --filter='Title:PartialMatch=test'\n");
        $this->out("  :ExactMatch        = value             --filter='Code:ExactMatch=ETW'\n");
        $this->out("  :StartsWith        LIKE value%%          --filter='Name:StartsWith=John'\n");
        $this->out("  :EndsWith          LIKE %%value           --filter='Email:EndsWith=.com'\n");
        $this->out("  :GreaterThan       > value             --filter='Created:GreaterThan=2024-01-01'\n");
        $this->out("  :LessThan          < value             --filter='Sort:LessThan=10'\n");
        $this->out("  :GreaterThanOrEqual  >= value           --filter='ID:GreaterThanOrEqual=100'\n");
        $this->out("  :LessThanOrEqual   <= value            --filter='ID:LessThanOrEqual=50'\n");
        $this->out("  :not               != value            --filter='Status:not=Archived'\n");
        $this->out("\n");
        $this->out("Examples:\n");
        $this->out("  sake tasks:orm-query --class=Member --limit=5\n");
        $this->out("  sake tasks:orm-query --class=Member --fields=ID,Email,FirstName\n");
        $this->out("  sake tasks:orm-query --class=File --filter='Name:EndsWith=.pdf' --count\n");
        $this->out("  sake tasks:orm-query --class=Page --filter='Created:GreaterThan=2024-01-01' --sort=Created,DESC\n");
        $this->out("  sake tasks:orm-query --class=Page --exclude='ClassName=ErrorPage'\n");
        $this->out("  sake tasks:orm-query --class=Page --where='ParentID > 0 AND ShowInMenus = 1'\n");
        $this->out("  sake tasks:orm-query --class=Page --sql\n");
        $this->out("  sake tasks:orm-query --class=File --schema\n");
        $this->out("  sake tasks:orm-query --class=DataSyncItem --group-by=ClassName\n");
        $this->out("  sake tasks:orm-query --class=DataSyncItem --filter='ClassName=Page' --group-by=ParentID\n");
        $this->out("  sake tasks:orm-query --class=Member --min=Created --max=Created\n");
        $this->out("  sake tasks:orm-query --class=File --sum=Size --avg=Size\n");
    }
}

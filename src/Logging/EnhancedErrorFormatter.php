<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\Debug;

/**
 * Enhanced error formatter for email notifications.
 *
 * Improvements over DetailedErrorFormatter:
 * - Smaller, more readable header (not giant h1 text)
 * - Adds "Details" section with useful $_SERVER variables
 * - Cleaner HTML structure for email rendering
 */
class EnhancedErrorFormatter implements FormatterInterface
{
    /**
     * $_SERVER keys to include in Details section
     */
    private static array $server_vars = [
        'HTTP_ACCEPT',
        'HTTP_ACCEPT_LANGUAGE',
        'HTTP_ACCEPT_ENCODING',
        'HTTP_REFERER',
        'HTTP_USER_AGENT',
        'HTTPS',
        'REMOTE_ADDR',
        'REQUEST_METHOD',
        'REQUEST_URI',
        'SERVER_NAME',
        'SERVER_SOFTWARE',
    ];

    public function format(array|LogRecord $record): string
    {
        if (isset($record['context']['exception'])) {
            /** @var \Exception $exception */
            $exception = $record['context']['exception'];
            $context = [
                'code' => $exception->getCode(),
                'message' => 'Uncaught ' . get_class($exception) . ': ' . $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        } else {
            $context = isset($record['context']) ? $record['context'] : $record;
            foreach (['code', 'message', 'file', 'line'] as $key) {
                if (!isset($context[$key])) {
                    $context[$key] = isset($record[$key]) ? $record[$key] : null;
                }
            }

            if (!isset($context['trace'])) {
                $trace = debug_backtrace();
                $i = $this->findInTrace($trace, $context['file'], $context['line']);
                if ($i !== null) {
                    $context['trace'] = array_slice($trace, $i);
                } else {
                    // Even without file/line match, include the trace (filtering out monolog internals)
                    $context['trace'] = $this->filterMonologFromTrace($trace);
                }
            }
        }

        return $this->output(
            $context['code'],
            $context['message'],
            $context['file'],
            $context['line'],
            $context['trace']
        );
    }

    public function formatBatch(array $records): string
    {
        return implode("\n", array_map([$this, 'format'], $records));
    }

    protected function findInTrace(array $trace, ?string $file, ?int $line): ?int
    {
        foreach ($trace as $i => $call) {
            if (isset($call['file'], $call['line']) && $call['file'] == $file && $call['line'] == $line) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Filter out Monolog internals from trace to show relevant application code
     */
    protected function filterMonologFromTrace(array $trace): array
    {
        $filtered = [];
        $foundAppCode = false;

        foreach ($trace as $call) {
            $file = $call['file'] ?? '';
            $class = $call['class'] ?? '';

            // Skip Monolog internals
            if (str_contains($class, 'Monolog\\') || str_contains($file, '/monolog/')) {
                continue;
            }

            // Skip this formatter
            if (str_contains($class, 'EnhancedErrorFormatter')) {
                continue;
            }

            $filtered[] = $call;
            $foundAppCode = true;
        }

        return $foundAppCode ? $filtered : $trace;
    }

    protected function output($errno, $errstr, $errfile, $errline, $errcontext): string
    {
        $httpRequest = $this->getHttpRequest();

        $output = $this->renderHeader();
        $output .= $this->renderError($httpRequest, $errno, $errstr, $errfile, $errline);

        if (file_exists($errfile ?? '')) {
            $lines = file($errfile);
            array_unshift($lines, "");
            unset($lines[0]);

            $offset = max(0, $errline - 10);
            $lines = array_slice($lines, $offset, 16, true);
            $output .= $this->renderSourceFragment($lines, $errline);
        }

        $output .= $this->renderTrace($errcontext);
        $output .= $this->renderServerDetails();
        $output .= $this->renderFooter();

        return $output;
    }

    protected function getHttpRequest(): string
    {
        $httpRequest = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $httpRequest = $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $httpRequest = $_SERVER['REQUEST_METHOD'] . ' ' . $httpRequest;
        }
        return $httpRequest;
    }

    protected function renderHeader(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Error Report</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .error-container { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .error-header { background: #dc3545; color: #fff; padding: 15px 20px; }
        .error-header h1 { margin: 0; font-size: 16px; font-weight: 600; }
        .error-header h3 { margin: 5px 0 0; font-size: 13px; font-weight: 400; opacity: 0.9; }
        .error-location { padding: 10px 20px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; font-size: 13px; color: #495057; }
        .error-location strong { color: #212529; }
        .section { padding: 15px 20px; border-bottom: 1px solid #dee2e6; }
        .section:last-child { border-bottom: none; }
        .section h3 { margin: 0 0 10px; font-size: 14px; font-weight: 600; color: #495057; }
        pre { margin: 0; padding: 15px; background: #2d2d2d; color: #f8f8f2; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5; }
        pre .line-number { color: #6c757d; display: inline-block; width: 35px; text-align: right; margin-right: 15px; user-select: none; }
        pre .error-line { background: #dc354533; display: block; margin: 0 -15px; padding: 0 15px; }
        .trace-list { list-style: none; margin: 0; padding: 0; }
        .trace-list li { padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
        .trace-list li:last-child { border-bottom: none; }
        .trace-function { font-weight: 600; color: #212529; }
        .trace-file { color: #6c757d; font-size: 12px; }
        .details-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .details-table th { text-align: left; padding: 6px 10px; background: #f8f9fa; font-weight: 600; width: 200px; border: 1px solid #dee2e6; }
        .details-table td { padding: 6px 10px; border: 1px solid #dee2e6; word-break: break-all; }
    </style>
</head>
<body>
<div class="error-container">
HTML;
    }

    protected function renderError(string $httpRequest, $errno, string $errstr, ?string $errfile, ?int $errline): string
    {
        $httpRequestEnt = htmlentities($httpRequest, ENT_COMPAT, 'UTF-8');
        $errstr = strip_tags($errstr);

        $output = <<<HTML
<div class="error-header">
    <h1>[Error] {$errstr}</h1>
    <h3>{$httpRequestEnt}</h3>
</div>
HTML;

        if ($errfile && $errline) {
            $output .= <<<HTML
<div class="error-location">
    Line <strong>{$errline}</strong> in <strong>{$errfile}</strong>
</div>
HTML;
        }

        return $output;
    }

    protected function renderSourceFragment(array $lines, int $errline): string
    {
        $output = '<div class="section"><h3>Source</h3><pre>';

        foreach ($lines as $offset => $line) {
            $lineNum = sprintf('<span class="line-number">%d</span>', $offset);
            $lineContent = htmlentities($line, ENT_COMPAT, 'UTF-8');

            if ($offset == $errline) {
                $output .= "<span class=\"error-line\">{$lineNum}{$lineContent}</span>";
            } else {
                $output .= "{$lineNum}{$lineContent}";
            }
        }

        $output .= '</pre></div>';
        return $output;
    }

    protected function renderTrace(?array $trace): string
    {
        if (empty($trace)) {
            return '';
        }

        $output = '<div class="section"><h3>Trace</h3><ul class="trace-list">';

        foreach ($trace as $item) {
            $function = '';
            if (isset($item['class'])) {
                $function = $item['class'] . ($item['type'] ?? '::');
            }
            $function .= $item['function'] ?? '';

            $file = '';
            if (isset($item['file'])) {
                $file = basename($item['file']);
                if (isset($item['line'])) {
                    $file .= ':' . $item['line'];
                }
            }

            $output .= '<li>';
            $output .= '<span class="trace-function">' . htmlentities($function, ENT_COMPAT, 'UTF-8') . '()</span>';
            if ($file) {
                $output .= '<br><span class="trace-file">' . htmlentities($file, ENT_COMPAT, 'UTF-8') . '</span>';
            }
            $output .= '</li>';
        }

        $output .= '</ul></div>';
        return $output;
    }

    /**
     * Render the Details section with $_SERVER variables
     */
    protected function renderServerDetails(): string
    {
        $output = '<div class="section"><h3>Details</h3><table class="details-table">';

        foreach (self::$server_vars as $key) {
            $value = $_SERVER[$key] ?? '';
            if ($value !== '') {
                $output .= '<tr>';
                $output .= '<th>$_SERVER[\'' . htmlentities($key, ENT_COMPAT, 'UTF-8') . '\']</th>';
                $output .= '<td>' . htmlentities($value, ENT_COMPAT, 'UTF-8') . '</td>';
                $output .= '</tr>';
            }
        }

        $output .= '</table></div>';
        return $output;
    }

    protected function renderFooter(): string
    {
        $timestamp = date('Y-m-d H:i:s T');
        return <<<HTML
<div class="section" style="font-size: 11px; color: #6c757d; text-align: right;">
    Generated at {$timestamp}
</div>
</div>
</body>
</html>
HTML;
    }
}

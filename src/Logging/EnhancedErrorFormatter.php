<?php

namespace Restruct\Silverstripe\AdminTweaks\Logging;

use Monolog\LogRecord;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Dev\CliDebugView;
use SilverStripe\Dev\DebugView;
use SilverStripe\Logging\DetailedErrorFormatter;

/**
 * Enhanced error formatter for email notifications.
 *
 * Extends DetailedErrorFormatter — inherits format(), formatBatch(), findInTrace()
 * for record parsing and context extraction.
 *
 * Root problem solved: parent's output() calls Debug::create_debug_view() which
 * returns CliDebugView (plain text) when there's no HTTP request context — which
 * is the case during error handling. This produces unreadable "wall of text" emails.
 *
 * Solution: override output() to instantiate DebugView directly, and render
 * email-friendly HTML with inline styles (no external CSS links).
 *
 * Supports two modes via `format_mode` config:
 * - 'html' (default): styled HTML email with inline CSS
 * - 'plaintext': monospace email using CliDebugView output wrapped in <pre>
 */
class EnhancedErrorFormatter extends DetailedErrorFormatter
{
    use Configurable;

    /** @config string 'html' or 'plaintext' */
    private static $format_mode = 'html';

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

    /**
     * Override format() to use filterMonologFromTrace() when parent would give up
     * on finding the trace (parent sets trace to null when file/line not found).
     */
    public function format(array|LogRecord $record): string
    {
        # If no exception context, we may need to filter monolog from trace
        if (!isset($record['context']['exception'])) {
            $context = isset($record['context']) ? $record['context'] : $record;

            if (!isset($context['trace'])) {
                $trace = debug_backtrace();
                $file = $context['file'] ?? $record['file'] ?? null;
                $line = $context['line'] ?? $record['line'] ?? null;

                $i = $this->findInTrace($trace, $file, $line);
                if ($i === null) {
                    # Parent would set trace to null — we filter monolog internals instead
                    $record['context']['trace'] = $this->filterMonologFromTrace($trace);
                }
            }
        }

        return parent::format($record);
    }

    /**
     * Override output() — instantiate DebugView directly instead of
     * Debug::create_debug_view() which falls back to CliDebugView.
     */
    protected function output($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $mode = static::config()->get('format_mode');

        if ($mode === 'plaintext') {
            return $this->outputPlaintext($errno, $errstr, $errfile, $errline, $errcontext);
        }

        return $this->outputHtml($errno, $errstr, $errfile, $errline, $errcontext);
    }

    /**
     * HTML mode: styled email with inline CSS.
     * Uses DebugView directly for renderError/renderSourceFragment/renderTrace,
     * but wraps in own HTML shell with inline styles (DebugView's renderHeader
     * outputs <!DOCTYPE> with external CSS <link> which gets stripped in email context).
     */
    protected function outputHtml($errno, $errstr, $errfile, $errline, $errcontext): string
    {
        $reporter = DebugView::create();
        $httpRequest = $this->getHttpRequest();

        # Start HTML with inline styles (no external CSS links — they don't work in email)
        $output = $this->renderEmailHtmlOpen();

        # Error header + source + trace via DebugView
        $output .= $reporter->renderError($httpRequest, $errno, $errstr, $errfile, $errline);

        if (file_exists($errfile ?? '')) {
            $lines = file($errfile ?? '');
            array_unshift($lines, "");
            unset($lines[0]);

            $offset = max(0, $errline - 10);
            $lines = array_slice($lines, $offset, 16, true);
            $output .= $reporter->renderSourceFragment($lines, $errline);
        }

        $output .= $reporter->renderTrace($errcontext);

        # Add $_SERVER details + timestamp (not in parent)
        $output .= $this->renderServerDetails();
        $output .= $this->renderTimestamp();

        $output .= '</body></html>';

        return $output;
    }

    /**
     * Plaintext mode: CliDebugView output wrapped in <pre> for readable monospace email.
     */
    protected function outputPlaintext($errno, $errstr, $errfile, $errline, $errcontext): string
    {
        $reporter = CliDebugView::create();
        $httpRequest = $this->getHttpRequest();

        # Render via CliDebugView (produces plain text with === separators)
        $text = $reporter->renderError($httpRequest, $errno, $errstr, $errfile, $errline);

        if (file_exists($errfile ?? '')) {
            $lines = file($errfile ?? '');
            array_unshift($lines, "");
            unset($lines[0]);

            $offset = max(0, $errline - 10);
            $lines = array_slice($lines, $offset, 16, true);
            $text .= $reporter->renderSourceFragment($lines, $errline);
        }

        $text .= $reporter->renderTrace($errcontext);

        # Add $_SERVER details as key=value lines
        $text .= "\n== Server Details ==\n";
        foreach (self::$server_vars as $key) {
            $value = $_SERVER[$key] ?? '';
            if ($value !== '') {
                $text .= "  {$key} = {$value}\n";
            }
        }

        $text .= "\n" . date('Y-m-d H:i:s T') . "\n";

        # Wrap in <pre> for readable monospace email
        $escaped = htmlentities($text, ENT_COMPAT, 'UTF-8');
        return '<html><body>'
            . '<pre style="font-family: \'Courier New\', Courier, monospace; font-size: 13px; line-height: 1.5; padding: 15px;">'
            . $escaped
            . '</pre>'
            . '</body></html>';
    }

    /**
     * Build HTTP request string from $_SERVER
     */
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

    /**
     * Filter out Monolog internals from trace to show relevant application code.
     * Parent's findInTrace() returns null when file/line not found in trace,
     * and then sets trace to null. This provides a useful fallback.
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

    /**
     * Render $_SERVER details as HTML table
     */
    protected function renderServerDetails(): string
    {
        $output = '<div class="info"><h3>Details</h3><table class="details-table">';

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

    /**
     * Render timestamp footer
     */
    protected function renderTimestamp(): string
    {
        $timestamp = date('Y-m-d H:i:s T');
        return '<div class="info" style="font-size: 11px; color: #6c757d; text-align: right; padding: 8px 10px;">'
            . "Generated at {$timestamp}"
            . '</div>';
    }

    /**
     * Open HTML with inline styles for email rendering.
     * DebugView's renderHeader() outputs <!DOCTYPE> with an external CSS <link>
     * that gets stripped/ignored in email context — we provide inline styles instead.
     */
    protected function renderEmailHtmlOpen(): string
    {
        return <<<'HTML'
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; color: #212529; }
    .header { border-radius: 4px; margin-bottom: 10px; padding: 10px 15px; }
    .header.error { background: #dc3545; color: #fff; }
    .header.warning { background: #ffc107; color: #212529; }
    .header.notice { background: #17a2b8; color: #fff; }
    .header h1 { margin: 0; font-size: 16px; font-weight: 600; }
    .header h3 { margin: 5px 0 0; font-size: 13px; font-weight: 400; opacity: 0.9; }
    .header p { margin: 5px 0 0; font-size: 13px; }
    .info { padding: 10px 15px; margin-bottom: 10px; background: #fff; border: 1px solid #dee2e6; border-radius: 4px; }
    .info h3 { margin: 0 0 8px; font-size: 14px; font-weight: 600; color: #495057; }
    pre { margin: 0; padding: 15px; background: #2d2d2d; color: #f8f8f2; border-radius: 4px; overflow-x: auto; font-size: 12px; line-height: 1.5; }
    pre span.error { background: #dc354533; }
    .details-table { width: 100%; border-collapse: collapse; font-size: 12px; }
    .details-table th { text-align: left; padding: 6px 10px; background: #f8f9fa; font-weight: 600; width: 200px; border: 1px solid #dee2e6; }
    .details-table td { padding: 6px 10px; border: 1px solid #dee2e6; word-break: break-all; }
    ul { list-style: none; margin: 0; padding: 0; }
    ul li { padding: 4px 0; border-bottom: 1px solid #f0f0f0; font-size: 13px; }
    ul li:last-child { border-bottom: none; }
</style>
</head>
<body>
HTML;
    }
}

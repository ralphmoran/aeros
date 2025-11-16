<?php

namespace Aeros\Src\Classes;

/**
 * Class Debugger
 *
 * Enhanced debugging utility with beautiful HTML output and improved CLI support.
 * Optimized for PHP 8.2+
 */
class Debugger
{
    /**
     * Outputs information about the provided variables and ends execution.
     * This is called from framework.php dd() helper function.
     *
     * @param   array   $args    Arguments to debug
     * @return  void
     */
    public function dd(array $args): void
    {
        // CLI mode - simplified output
        if (php_sapi_name() === 'cli') {
            $this->ddCli($args);
            exit(1);
        }

        // Web mode - beautiful HTML output
        $this->ddWeb($args);
        exit;
    }

    /**
     * CLI mode debug output with colors and formatting
     */
    private function ddCli(array $args): void
    {
        // CORRECT OFFSET: Frame 2 is the actual call site
        // Frame 0: Debugger::dd()
        // Frame 1: framework.php dd()
        // Frame 2: ACTUAL CALLER
        $trace = debug_backtrace();
        $caller = $trace[2] ?? $trace[1] ?? $trace[0] ?? [];

        // ANSI color codes
        $colors = [
                'reset' => "\033[0m",
                'bold' => "\033[1m",
                'dim' => "\033[2m",
                'cyan' => "\033[36m",
                'yellow' => "\033[33m",
                'green' => "\033[32m",
                'red' => "\033[31m",
                'blue' => "\033[34m",
                'magenta' => "\033[35m",
        ];

        // Header
        echo "\n{$colors['cyan']}{$colors['bold']}╔══════════════════════════════════════════════════════════════╗{$colors['reset']}\n";
        echo "{$colors['cyan']}{$colors['bold']}║{$colors['reset']}  🐛 {$colors['bold']}AEROS DEBUG OUTPUT{$colors['reset']}                                     {$colors['cyan']}{$colors['bold']}║{$colors['reset']}\n";
        echo "{$colors['cyan']}{$colors['bold']}╚══════════════════════════════════════════════════════════════╝{$colors['reset']}\n\n";

        // File and line
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;
        $rootPath = env('APP_ROOT_DIR') ?? app()->basedir;
        $relativeFile = str_replace($rootPath, '', $file);
        $relativeFile = ltrim($relativeFile, '/\\');

        echo "{$colors['yellow']}📁 File:{$colors['reset']} {$colors['dim']}{$relativeFile}:{$line}{$colors['reset']}\n";

        // Context location - look at frame 3 for the calling method
        if (isset($trace[3])) {
            $class = $trace[3]['class'] ?? '';
            $function = $trace[3]['function'] ?? '';
            $type = $trace[3]['type'] ?? '';

            if ($class || $function) {
                $context = $class ? "{$class}{$type}{$function}()" : "{$function}()";
                echo "{$colors['green']}➤ Context:{$colors['reset']} {$colors['dim']}{$context}{$colors['reset']}\n";
            }
        }

        echo "\n{$colors['cyan']}{$colors['bold']}Variables:{$colors['reset']}\n";
        echo str_repeat('─', 64) . "\n\n";

        // Dump each argument
        foreach ($args as $index => $arg) {
            $argNum = $index + 1;
            echo "{$colors['magenta']}{$colors['bold']}[Argument #{$argNum}]{$colors['reset']}\n";

            // Use var_export for clean output
            $output = var_export($arg, true);
            $output = $this->colorizeCliOutput($output, $colors);
            echo $output . "\n\n";
        }

        // Stack trace - skip the first 2 frames (Debugger::dd() + framework.php)
        $fullTrace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 2);
        if (!empty($fullTrace)) {
            echo "{$colors['cyan']}{$colors['bold']}Stack Trace (" . count($fullTrace) . " frames):{$colors['reset']}\n";
            echo str_repeat('─', 64) . "\n\n";

            foreach ($fullTrace as $index => $frame) {
                $class = $frame['class'] ?? '';
                $function = $frame['function'] ?? '';
                $type = $frame['type'] ?? '';
                $file = $frame['file'] ?? 'unknown';
                $line = $frame['line'] ?? 0;

                $relativeFile = str_replace($rootPath, '', $file);
                $relativeFile = ltrim($relativeFile, '/\\');

                $call = $class ? "{$class}{$type}{$function}()" : "{$function}()";

                echo "{$colors['blue']}#{$index}{$colors['reset']} ";
                echo "{$colors['yellow']}{$call}{$colors['reset']}\n";
                echo "    {$colors['dim']}{$relativeFile}:{$line}{$colors['reset']}\n\n";
            }
        }

        echo "{$colors['dim']}" . str_repeat('═', 64) . "{$colors['reset']}\n\n";
    }

    /**
     * Add colors to var_export output for CLI
     */
    private function colorizeCliOutput(string $output, array $colors): string
    {
        // String values
        $output = preg_replace("/'([^']*)'/", "{$colors['green']}'$1'{$colors['reset']}", $output);

        // Numbers
        $output = preg_replace('/\b(\d+)\b/', "{$colors['cyan']}$1{$colors['reset']}", $output);

        // Boolean and NULL
        $output = preg_replace('/\b(true|false|NULL)\b/', "{$colors['magenta']}$1{$colors['reset']}", $output);

        // Array keywords
        $output = preg_replace('/\b(array)\s*\(/', "{$colors['yellow']}array{$colors['reset']}(", $output);

        return $output;
    }

    /**
     * Web mode debug output with beautiful HTML
     */
    private function ddWeb(array $args): void
    {
        // CORRECT OFFSET: Frame 2 is the actual call site
        // Frame 0: Debugger::dd()
        // Frame 1: framework.php dd()
        // Frame 2: ACTUAL CALLER
        $trace = debug_backtrace();
        $caller = $trace[2] ?? $trace[1] ?? [];

        // Get file and line information
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        // Make file path relative
        $rootPath = env('APP_ROOT_DIR') ?? app()->basedir ?? $_SERVER['DOCUMENT_ROOT'] ?? dirname(__FILE__);
        $relativeFile = str_replace($rootPath, '', $file);
        $relativeFile = ltrim($relativeFile, '/\\');

        // Build context location - look at frame 3 for calling method
        $contextLocation = '';
        if (isset($trace[3])) {
            $class = $trace[3]['class'] ?? '';
            $type = $trace[3]['type'] ?? '';
            $function = $trace[3]['function'] ?? '';

            if ($class) {
                $contextLocation = $class . $type . $function . '()';
            } elseif ($function) {
                $contextLocation = $function . '()';
            }
        }

        // Get full trace (skip first 2 frames: Debugger::dd() + framework.php)
        $fullTrace = array_slice($trace, 2);

        // Output HTML
        $this->renderHtml($args, $relativeFile, $line, $contextLocation, $fullTrace, $rootPath);
    }

    /**
     * Render beautiful HTML output
     */
    private function renderHtml(array $args, string $file, int $line, string $context, array $trace, string $rootPath): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Aeros Debug Output</title>
            <style>
                :root {
                    --aeros-primary: #00a6fb;
                    --aeros-secondary: #0582ca;
                    --bg-main: #0a0a0a;
                    --bg-card: #141414;
                    --bg-code: #0f0f0f;
                    --bg-hover: #1a1a1a;
                    --border: #2a2a2a;
                    --border-light: #333;
                    --text-primary: #e8e8e8;
                    --text-secondary: #a8a8a8;
                    --text-dim: #707070;

                    --type-string: #50fa7b;
                    --type-number: #ff79c6;
                    --type-bool: #8be9fd;
                    --type-null: #6c6c6c;
                    --type-array: #bd93f9;
                    --type-object: #ffb86c;
                    --type-key: #f1fa8c;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "SF Pro Display", Roboto, "Helvetica Neue", Arial, sans-serif;
                    background: var(--bg-main);
                    color: var(--text-primary);
                    padding: 2.5rem;
                    min-height: 100vh;
                    line-height: 1.7;
                    font-size: 15px;
                    -webkit-font-smoothing: antialiased;
                }

                .dd-wrapper {
                    max-width: 1400px;
                    margin: 0 auto;
                }

                .dd-container {
                    background: var(--bg-card);
                    border-radius: 16px;
                    overflow: hidden;
                    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
                    margin-bottom: 2rem;
                }

                .dd-header {
                    background: linear-gradient(135deg, var(--aeros-primary) 0%, var(--aeros-secondary) 100%);
                    color: white;
                    padding: 1.5rem 2rem;
                    font-weight: 600;
                    font-size: 0.95rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    display: flex;
                    align-items: center;
                    gap: 0.875rem;
                }

                .dd-header::before {
                    content: "🐛";
                    font-size: 1.5rem;
                }

                .dd-context {
                    background: var(--bg-hover);
                    border-left: 4px solid var(--aeros-primary);
                    padding: 1.5rem 2rem;
                    display: flex;
                    flex-direction: column;
                    gap: 0.75rem;
                    border-bottom: 1px solid var(--border);
                }

                .dd-context-file {
                    color: var(--text-primary);
                    font-size: 1rem;
                    font-weight: 500;
                    font-family: 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
                    letter-spacing: -0.2px;
                }

                .dd-context-file::before {
                    content: "📍 ";
                    margin-right: 0.625rem;
                }

                .dd-context-location {
                    color: var(--text-secondary);
                    font-size: 0.9rem;
                    padding-left: 2.25rem;
                    font-family: 'SF Mono', Consolas, monospace;
                    letter-spacing: -0.2px;
                }

                .dd-context-location::before {
                    content: "→ ";
                    color: var(--aeros-primary);
                    margin-right: 0.375rem;
                }

                .dd-content {
                    padding: 2rem;
                }

                .dd-arg-header {
                    color: var(--text-dim);
                    font-size: 0.75rem;
                    text-transform: uppercase;
                    letter-spacing: 1.5px;
                    font-weight: 600;
                    margin: 2.5rem 0 1.25rem 0;
                    padding: 0.75rem 0;
                    border-bottom: 2px solid var(--border);
                    display: flex;
                    align-items: center;
                    gap: 0.875rem;
                }

                .dd-arg-header:first-child {
                    margin-top: 0;
                }

                .dd-arg-badge {
                    background: var(--aeros-primary);
                    color: white;
                    padding: 0.375rem 0.875rem;
                    border-radius: 24px;
                    font-size: 0.8125rem;
                    font-weight: 700;
                    letter-spacing: 0.3px;
                }

                .dd-dump {
                    background: var(--bg-code);
                    border: 1px solid var(--border);
                    border-radius: 12px;
                    padding: 1.75rem;
                    overflow-x: auto;
                    font-size: 0.9375rem;
                    font-family: 'SF Mono', Consolas, monospace;
                    line-height: 1.9;
                    letter-spacing: -0.2px;
                }

                .dd-dump-scroll {
                    max-height: 600px;
                    overflow-y: auto;
                    background: var(--bg-code);
                    border: 1px solid var(--border);
                    border-radius: 12px;
                    padding: 1.75rem;
                    font-size: 0.9375rem;
                    font-family: 'SF Mono', Consolas, monospace;
                    line-height: 1.9;
                    letter-spacing: -0.2px;
                }

                .dd-dump::-webkit-scrollbar,
                .dd-dump-scroll::-webkit-scrollbar {
                    width: 12px;
                    height: 12px;
                }

                .dd-dump::-webkit-scrollbar-track,
                .dd-dump-scroll::-webkit-scrollbar-track {
                    background: var(--bg-card);
                    border-radius: 8px;
                }

                .dd-dump::-webkit-scrollbar-thumb,
                .dd-dump-scroll::-webkit-scrollbar-thumb {
                    background: var(--border);
                    border-radius: 8px;
                }

                .dd-line-indicator {
                    color: var(--text-dim);
                    font-size: 0.8125rem;
                    margin-top: 1rem;
                    font-style: italic;
                }

                /* Stack Trace - Timeline Style */
                .dd-backtrace {
                    margin-top: 3rem;
                    border-top: 2px solid var(--border);
                    padding-top: 2.5rem;
                }

                .dd-backtrace-header {
                    background: var(--bg-hover);
                    padding: 1.25rem 2rem;
                    border-radius: 12px;
                    font-weight: 600;
                    color: var(--text-primary);
                    font-size: 0.875rem;
                    text-transform: uppercase;
                    letter-spacing: 1.2px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    margin-bottom: 2rem;
                }

                .dd-backtrace-content {
                    background: transparent;
                    position: relative;
                    padding-left: 3rem;
                }

                /* Timeline vertical line */
                .dd-backtrace-content::before {
                    content: '';
                    position: absolute;
                    left: 1.5rem;
                    top: 1rem;
                    bottom: 1rem;
                    width: 2px;
                    background: linear-gradient(180deg, var(--aeros-primary) 0%, var(--border) 100%);
                }

                .dd-backtrace-scroll {
                    max-height: 700px;
                    overflow-y: auto;
                }

                .dd-backtrace-scroll::-webkit-scrollbar {
                    width: 12px;
                }

                .dd-backtrace-scroll::-webkit-scrollbar-track {
                    background: var(--bg-code);
                }

                .dd-backtrace-scroll::-webkit-scrollbar-thumb {
                    background: var(--border);
                    border-radius: 8px;
                }

                /* Timeline items */
                .dd-trace-details {
                    position: relative;
                    margin-bottom: 0.5rem;
                    transition: all 0.2s ease;
                }

                .dd-trace-details:last-child {
                    margin-bottom: 0;
                }

                /* Timeline dot */
                .dd-trace-details::before {
                    content: '';
                    position: absolute;
                    left: -2.375rem;
                    top: 1.75rem;
                    width: 12px;
                    height: 12px;
                    background: var(--aeros-primary);
                    border: 3px solid var(--bg-card);
                    border-radius: 50%;
                    z-index: 2;
                    transition: all 0.2s ease;
                }

                .dd-trace-details[open]::before {
                    background: var(--aeros-secondary);
                    transform: scale(1.2);
                    box-shadow: 0 0 0 4px rgba(0, 166, 251, 0.2);
                }

                .dd-trace-summary {
                    padding: 1.25rem 1.75rem;
                    cursor: pointer;
                    list-style: none;
                    user-select: none;
                    display: flex;
                    align-items: flex-start;
                    gap: 1.25rem;
                    background: var(--bg-card);
                    border: 1px solid var(--border);
                    border-radius: 12px;
                    transition: all 0.2s ease;
                }

                .dd-trace-summary::-webkit-details-marker,
                .dd-trace-summary::marker {
                    display: none;
                }

                .dd-trace-summary:hover {
                    background: var(--bg-hover);
                    border-color: var(--aeros-primary);
                    transform: translateX(4px);
                }

                .dd-trace-summary::before {
                    content: "›";
                    color: var(--text-dim);
                    font-size: 1.5rem;
                    font-weight: 600;
                    transition: transform 0.2s ease;
                    display: inline-block;
                    width: 1.25rem;
                    flex-shrink: 0;
                    margin-top: -0.125rem;
                }

                .dd-trace-details[open] .dd-trace-summary::before {
                    transform: rotate(90deg);
                    color: var(--aeros-primary);
                }

                .dd-trace-summary-content {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    gap: 0.625rem;
                }

                .dd-trace-main-line {
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    flex-wrap: wrap;
                }

                .dd-trace-number {
                    background: var(--bg-hover);
                    color: var(--aeros-primary);
                    font-weight: 700;
                    font-size: 0.8125rem;
                    padding: 0.375rem 0.75rem;
                    border-radius: 8px;
                    font-family: 'SF Mono', monospace;
                    letter-spacing: 0.5px;
                }

                .dd-trace-function {
                    color: var(--text-primary);
                    font-weight: 600;
                    font-size: 0.9375rem;
                    font-family: 'SF Mono', Consolas, monospace;
                    letter-spacing: -0.3px;
                }

                .dd-trace-class {
                    color: var(--type-object);
                    font-weight: 500;
                    font-family: 'SF Mono', monospace;
                    letter-spacing: -0.3px;
                }

                .dd-trace-file {
                    color: var(--text-secondary);
                    font-size: 0.875rem;
                    font-family: 'SF Mono', monospace;
                    letter-spacing: -0.2px;
                }

                .dd-trace-line {
                    color: var(--type-number);
                    font-weight: 600;
                }

                .dd-trace-args-summary {
                    color: var(--text-dim);
                    font-size: 0.8125rem;
                    font-style: italic;
                    letter-spacing: -0.2px;
                }

                .dd-trace-body {
                    padding: 1.75rem 2rem;
                    margin-top: 0.5rem;
                    background: var(--bg-code);
                    border: 1px solid var(--border);
                    border-radius: 12px;
                    margin-left: 0.25rem;
                }

                .dd-trace-info-row {
                    display: grid;
                    grid-template-columns: 140px 1fr;
                    gap: 1.5rem;
                    margin-bottom: 1.25rem;
                    font-size: 0.9rem;
                }

                .dd-trace-info-row:last-child {
                    margin-bottom: 0;
                }

                .dd-trace-info-label {
                    color: var(--text-dim);
                    font-weight: 600;
                    font-size: 0.8125rem;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                }

                .dd-trace-info-value {
                    color: var(--text-primary);
                    font-family: 'SF Mono', Consolas, monospace;
                    letter-spacing: -0.3px;
                    line-height: 1.6;
                }

                /* Collapsible arrays and objects */
                .dd-collapse-details {
                    display: inline;
                }

                .dd-collapse-summary {
                    cursor: pointer;
                    list-style: none;
                    user-select: none;
                    display: inline;
                    transition: opacity 0.15s ease;
                }

                .dd-collapse-summary::-webkit-details-marker,
                .dd-collapse-summary::marker {
                    display: none;
                }

                .dd-collapse-summary::before {
                    content: "▾ ";
                    color: var(--text-dim);
                    font-size: 0.875rem;
                    display: inline-block;
                    margin-right: 0.25rem;
                }

                .dd-collapse-details:not([open]) .dd-collapse-summary::before {
                    content: "▸ ";
                }

                .dd-collapse-summary:hover {
                    opacity: 0.7;
                }

                .dd-collapse-content {
                    display: inline;
                }

                /* Type styling */
                .dd-type {
                    color: var(--text-secondary);
                    font-weight: 600;
                    font-size: 0.8125rem;
                    text-transform: lowercase;
                    letter-spacing: 0.3px;
                }

                .dd-string {
                    color: var(--type-string);
                    font-weight: 500;
                }

                .dd-number {
                    color: var(--type-number);
                    font-weight: 600;
                }

                .dd-bool {
                    color: var(--type-bool);
                    font-weight: 700;
                    text-transform: lowercase;
                }

                .dd-null {
                    color: var(--type-null);
                    font-style: italic;
                    opacity: 0.8;
                }

                .dd-array {
                    color: var(--type-array);
                    font-weight: 700;
                }

                .dd-object {
                    color: var(--type-object);
                    font-weight: 600;
                }

                .dd-key {
                    color: var(--type-key);
                    font-weight: 500;
                }

                .dd-arrow {
                    color: var(--text-dim);
                    margin: 0 0.5rem;
                    font-weight: 400;
                }

                /* Responsive */
                @media (max-width: 768px) {
                    body { padding: 1.5rem; }
                    .dd-content { padding: 1.5rem; }
                    .dd-backtrace-content { padding-left: 2rem; }
                    .dd-trace-details::before { left: -1.5rem; }
                    .dd-trace-body { padding: 1.25rem 1.5rem; }
                    .dd-trace-info-row { grid-template-columns: 1fr; gap: 0.5rem; }
                }
            </style>
        </head>
        <body>
        <div class="dd-wrapper">
            <div class="dd-container">
                <div class="dd-header">
                    Debug Output
                </div>

                <div class="dd-context">
                    <div class="dd-context-file">
                        <?= htmlspecialchars($file) ?>:<?= $line ?>
                    </div>
                    <?php if ($context): ?>
                        <div class="dd-context-location">
                            <?= htmlspecialchars($context) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="dd-content">
                    <?php foreach ($args as $index => $arg): ?>
                        <div class="dd-arg-header">
                            <span class="dd-arg-badge">#<?= $index + 1 ?></span>
                            <span><?= $this->getTypeLabel($arg) ?></span>
                        </div>
                        <?php
                        ob_start();
                        $this->dumpVar($arg, 0);
                        $output = ob_get_clean();
                        $lineCount = substr_count($output, "\n");
                        $scrollClass = $lineCount > 15 ? 'dd-dump-scroll' : 'dd-dump';
                        ?>
                        <div class="<?= $scrollClass ?>"><?= $output ?></div>

                        <?php if ($lineCount > 15): ?>
                            <div class="dd-line-indicator">
                                <?= number_format($lineCount) ?> lines • Scrollable
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if (!empty($trace)): ?>
                        <div class="dd-backtrace">
                            <div class="dd-backtrace-header">
                                <span>Stack Trace</span>
                                <span><?= count($trace) ?> frames</span>
                            </div>

                            <div class="dd-backtrace-content <?= count($trace) > 10 ? 'dd-backtrace-scroll' : '' ?>">
                                <?php foreach ($trace as $index => $frame): ?>
                                    <details class="dd-trace-details">
                                        <summary class="dd-trace-summary">
                                            <div class="dd-trace-summary-content">
                                                <div class="dd-trace-main-line">
                                                    <span class="dd-trace-number">#<?= $index ?></span>

                                                    <?php if (isset($frame['class'])): ?>
                                                        <span class="dd-trace-class"><?= htmlspecialchars($frame['class']) ?></span>
                                                        <span style="color: #808080;"><?= htmlspecialchars($frame['type'] ?? '::') ?></span>
                                                    <?php endif; ?>

                                                    <?php if (isset($frame['function'])): ?>
                                                        <span class="dd-trace-function"><?= htmlspecialchars($frame['function']) ?>()</span>
                                                    <?php endif; ?>

                                                    <?php if (isset($frame['file']) && isset($frame['line'])): ?>
                                                        <?php
                                                        $traceFile = str_replace($rootPath, '', $frame['file']);
                                                        $traceFile = ltrim($traceFile, '/\\');
                                                        ?>
                                                        <span class="dd-trace-file">
                                                    <?= htmlspecialchars($traceFile) ?>:<span class="dd-trace-line"><?= $frame['line'] ?></span>
                                                </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (isset($frame['args']) && count($frame['args']) > 0): ?>
                                                    <div class="dd-trace-info-row">
                                                        <span class="dd-trace-info-label">📦 Arguments:</span>
                                                        <div class="dd-trace-info-value">
                                                            <?php foreach ($frame['args'] as $argIndex => $argValue): ?>
                                                                <div style="margin-bottom: 0.5rem;">
                                                                    <strong>[<?= $argIndex ?>]</strong>
                                                                    <div style="margin-left: 1rem;">
                                                                        <?php $this->dumpVar($argValue, 0); ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </summary>

                                        <div class="dd-trace-body">
                                            <?php if (isset($frame['file'])): ?>
                                                <?php
                                                $traceFile = str_replace($rootPath, '', $frame['file']);
                                                $traceFile = ltrim($traceFile, '/\\');
                                                ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">📁 File:</span>
                                                    <span class="dd-trace-info-value"><?= htmlspecialchars($traceFile) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['line'])): ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">📍 Line:</span>
                                                    <span class="dd-trace-info-value"><?= $frame['line'] ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['class'])): ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">🏛️ Class:</span>
                                                    <span class="dd-trace-info-value"><?= htmlspecialchars($frame['class']) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['function'])): ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">⚡ Function:</span>
                                                    <span class="dd-trace-info-value"><?= htmlspecialchars($frame['function']) ?>()</span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['type'])): ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">🔗 Type:</span>
                                                    <span class="dd-trace-info-value"><?= $frame['type'] === '->' ? 'Object Method' : 'Static Method' ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['args']) && count($frame['args']) > 0): ?>
                                                <div class="dd-trace-info-row">
                                                    <span class="dd-trace-info-label">📦 Arguments:</span>
                                                    <span class="dd-trace-info-value"><?= count($frame['args']) ?> argument<?= count($frame['args']) > 1 ? 's' : '' ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * Get argument types summary for stack trace
     */
    private function getArgsTypeSummary(array $args): string
    {
        $types = [];
        foreach ($args as $arg) {
            $types[] = match (true) {
                is_object($arg) => get_class($arg),
                is_array($arg) => 'array (' . count($arg) . ')',
                is_string($arg) => 'string (' . strlen($arg) . ')',
                default => gettype($arg),
            };
        }
        return implode(', ', $types);
    }

    /**
     * Recursively dump variables with enhanced colored output (PHP 8.2+)
     */
    private function dumpVar(mixed $var, int $indent = 0): void
    {
        $indentStr = str_repeat('  ', $indent);
        $type = gettype($var);

        match ($type) {
            'boolean' => print('<span class="dd-bool">' . ($var ? 'true' : 'false') . '</span>'),

            'integer' => print('<span class="dd-type">int</span> <span class="dd-number">' . number_format($var, 0, '', ',') . '</span>'),

            'double' => print('<span class="dd-type">float</span> <span class="dd-number">' . $var . '</span>'),

            'string' => $this->dumpString($var),

            'NULL' => print('<span class="dd-null">null</span>'),

            'array' => $this->dumpArray($var, $indent, $indentStr),

            'object' => $this->dumpObject($var, $indent, $indentStr),

            'resource' => print('<span class="dd-type">resource</span>(<span class="dd-string">' . get_resource_type($var) . '</span>)'),

            'resource (closed)' => print('<span class="dd-type">resource</span> <span class="dd-null">(closed)</span>'),

            default => print('<span class="dd-type">' . htmlspecialchars($type) . '</span>'),
        };
    }

    /**
     * Dump string with length and truncation
     */
    private function dumpString(string $var): void
    {
        $len = strlen($var);
        $display = htmlspecialchars($var, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($len > 1000) {
            $display = substr($display, 0, 1000) . '...';
            echo '<span class="dd-type">string</span> (' . number_format($len) . ') ';
            echo '<span class="dd-string">"' . $display . '" <i>(truncated)</i></span>';
        } else {
            echo '<span class="dd-type">string</span> (' . $len . ') ';
            echo '<span class="dd-string">"' . $display . '"</span>';
        }
    }

    /**
     * Dump array with proper formatting and collapsible details
     */
    private function dumpArray(array $var, int $indent, string $indentStr): void
    {
        $count = count($var);
        $isAssoc = $count > 0 && array_keys($var) !== range(0, $count - 1);

        if ($count === 0) {
            echo '<span class="dd-array">array</span> (0) {}';
            return;
        }

        // Make arrays collapsible
        $detailsId = 'dd-collapse-' . uniqid();
        echo '<details class="dd-collapse-details" open>';
        echo '<summary class="dd-collapse-summary">';
        echo '<span class="dd-array">array</span> (' . $count . ')';
        echo '</summary>';
        echo '<div class="dd-collapse-content">';
        echo " {\n";

        foreach ($var as $key => $value) {
            echo $indentStr . '  ';

            if ($isAssoc || is_string($key)) {
                echo '[<span class="dd-key">';
                echo is_string($key) ? '"' . htmlspecialchars($key, ENT_NOQUOTES) . '"' : $key;
                echo '</span>]';
            } else {
                echo '[<span class="dd-key">' . $key . '</span>]';
            }

            echo '<span class="dd-arrow"> => </span>';

            if (is_array($value) || is_object($value)) {
                echo "\n" . $indentStr . '  ';
                $this->dumpVar($value, $indent + 2);
            } else {
                $this->dumpVar($value, 0);
            }
            echo "\n";
        }

        echo $indentStr . "}";
        echo '</div>';
        echo '</details>';
    }

    /**
     * Dump object with reflection and collapsible details (PHP 8.2+)
     */
    private function dumpObject(object $var, int $indent, string $indentStr): void
    {
        $className = get_class($var);
        $reflection = new \ReflectionClass($var);
        $props = [];

        // Get all properties (public, protected, private)
        foreach ($reflection->getProperties() as $prop) {
            try {
                $props[$prop->getName()] = $prop->getValue($var);
            } catch (\Error $e) {
                // Property not initialized, skip it
            }
        }

        $propCount = count($props);

        if ($propCount === 0) {
            echo '<span class="dd-object">' . htmlspecialchars($className) . '</span> (0 properties) {}';
            return;
        }

        // Make objects collapsible
        echo '<details class="dd-collapse-details" open>';
        echo '<summary class="dd-collapse-summary">';
        echo '<span class="dd-object">' . htmlspecialchars($className) . '</span>';
        echo ' (' . $propCount . ' ' . ($propCount === 1 ? 'property' : 'properties') . ')';
        echo '</summary>';
        echo '<div class="dd-collapse-content">';
        echo " {\n";

        foreach ($props as $key => $value) {
            echo $indentStr . '  ';
            echo '<span class="dd-key">' . htmlspecialchars($key) . '</span>';
            echo '<span class="dd-arrow">: </span>';

            if (is_array($value) || is_object($value)) {
                echo "\n" . $indentStr . '  ';
                $this->dumpVar($value, $indent + 2);
            } else {
                $this->dumpVar($value, 0);
            }
            echo "\n";
        }

        echo $indentStr . "}";
        echo '</div>';
        echo '</details>';
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel(mixed $var): string
    {
        return match (true) {
            is_array($var) => 'Array (' . count($var) . ' items)',
            is_object($var) => 'Object: ' . get_class($var),
            is_string($var) => 'String (' . strlen($var) . ' chars)',
            is_int($var) => 'Integer',
            is_float($var) => 'Float',
            is_bool($var) => 'Boolean',
            is_null($var) => 'NULL',
            is_resource($var) => 'Resource: ' . get_resource_type($var),
            default => 'Unknown Type',
        };
    }
}
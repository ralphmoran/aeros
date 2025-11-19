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
        echo "{$colors['cyan']}{$colors['bold']}║{$colors['reset']}  🐛 {$colors['bold']} AEROS DEBUG OUTPUT  {$colors['reset']}                                     {$colors['cyan']}{$colors['bold']}║{$colors['reset']}\n";
        echo "{$colors['cyan']}{$colors['bold']}╚══════════════════════════════════════════════════════════════╝{$colors['reset']}\n\n";

        // File and line
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;
        $rootPath = env('APP_ROOT_DIR') ?? app()->basedir;
        $relativeFile = str_replace($rootPath, '', $file);
        $relativeFile = ltrim($relativeFile, '/\\');

        echo "{$colors['yellow']}📁  File:{$colors['reset']} {$colors['dim']}{$relativeFile}:{$line}{$colors['reset']}\n";

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
            <title>Debugger - Aeros Framework</title>
            <style>
                :root {
                    --devtools-bg: #242424;
                    --devtools-sidebar: #1e1e1e;
                    --devtools-border: #3e3e42;
                    --devtools-hover: #2d2d30;
                    --devtools-property: #9cdcfe;
                    --devtools-string: #ce9178;
                    --devtools-number: #b5cea8;
                    --devtools-bool: #569cd6;
                    --devtools-null: #808080;
                    --devtools-key: #9cdcfe;
                    --devtools-array: #4ec9b0;
                    --devtools-object: #4ec9b0;
                    --devtools-text: #cccccc;
                    --devtools-text-dim: #858585;
                }

                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: Menlo, Monaco, 'Courier New', monospace;
                    background: #1e1e1e;
                    color: var(--devtools-text);
                    padding: 0;
                    min-height: 100vh;
                    line-height: 1.5;
                    font-size: 11px;
                }

                .dd-wrapper {
                    max-width: 100%;
                    margin: 0;
                    display: flex;
                    flex-direction: column;
                    height: 100vh;
                }

                .dd-container {
                    background: var(--devtools-bg);
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    overflow: hidden;
                }

                /* DevTools-style header tabs */
                .dd-header {
                    background: var(--devtools-sidebar);
                    color: var(--devtools-text-dim);
                    padding: 0;
                    display: flex;
                    align-items: stretch;
                    border-bottom: 1px solid var(--devtools-border);
                    font-size: 11px;
                    font-weight: 400;
                    height: 37px;
                }

                .dd-header-tab {
                    padding: 0 16px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    border-right: 1px solid var(--devtools-border);
                    cursor: pointer;
                    transition: background 0.1s;
                }

                .dd-header-tab:hover {
                    background: var(--devtools-hover);
                }

                .dd-header-tab.active {
                    background: var(--devtools-bg);
                    color: var(--devtools-text);
                    border-bottom: 2px solid #569cd6;
                }

                .dd-header-icon {
                    font-size: 13px;
                }

                /* Toolbar */
                .dd-toolbar {
                    background: var(--devtools-sidebar);
                    padding: 8px 12px;
                    border-bottom: 1px solid var(--devtools-border);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 11px;
                }

                .dd-toolbar-label {
                    color: var(--devtools-text-dim);
                    font-weight: 600;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    font-size: 10px;
                }

                .dd-toolbar-value {
                    color: var(--devtools-text);
                    font-family: Menlo, Monaco, monospace;
                }

                .dd-toolbar-separator {
                    width: 1px;
                    height: 16px;
                    background: var(--devtools-border);
                    margin: 0 8px;
                }

                /* Content area */
                .dd-content {
                    flex: 1;
                    overflow-y: auto;
                    padding: 12px;
                }

                .dd-content::-webkit-scrollbar {
                    width: 10px;
                    height: 10px;
                }

                .dd-content::-webkit-scrollbar-track {
                    background: var(--devtools-sidebar);
                }

                .dd-content::-webkit-scrollbar-thumb {
                    background: #424242;
                    border-radius: 5px;
                }

                .dd-content::-webkit-scrollbar-thumb:hover {
                    background: #4e4e4e;
                }

                /* Variable sections */
                .dd-arg-section {
                    margin-bottom: 16px;
                    background: var(--devtools-sidebar);
                    border: 1px solid var(--devtools-border);
                    border-radius: 2px;
                }

                .dd-arg-header {
                    background: var(--devtools-bg);
                    padding: 6px 10px;
                    border-bottom: 1px solid var(--devtools-border);
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 11px;
                }

                .dd-arg-badge {
                    background: #0e639c;
                    color: white;
                    padding: 2px 6px;
                    border-radius: 2px;
                    font-size: 10px;
                    font-weight: 600;
                }

                .dd-arg-type {
                    color: var(--devtools-text-dim);
                    font-size: 10px;
                }

                .dd-dump {
                    padding: 8px 4px;
                    font-family: Menlo, Monaco, monospace;
                    font-size: 11px;
                    line-height: 17px;
                }

                /* DevTools tree structure */
                .dd-tree-item {
                    display: flex;
                    align-items: flex-start;
                    padding: 0 4px;
                    position: relative;
                }

                .dd-tree-item:hover {
                    background: var(--devtools-hover);
                }

                .dd-tree-indent {
                    flex-shrink: 0;
                }

                .dd-tree-arrow {
                    width: 10px;
                    height: 17px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    user-select: none;
                    color: var(--devtools-text-dim);
                    font-size: 10px;
                }

                .dd-tree-arrow::before {
                    content: '▸';
                }

                .dd-tree-expanded > .dd-tree-item > .dd-tree-arrow::before {
                    content: '▾';
                }

                .dd-tree-content {
                    flex: 1;
                    min-width: 0;
                }

                .dd-tree-line {
                    display: flex;
                    align-items: baseline;
                    gap: 4px;
                }

                .dd-tree-children {
                    display: none;
                }

                .dd-tree-expanded > .dd-tree-children {
                    display: block;
                }

                /* Property styling */
                .dd-prop-key {
                    color: var(--devtools-property);
                }

                .dd-prop-separator {
                    color: var(--devtools-text-dim);
                    margin: 0 4px;
                }

                .dd-prop-value {
                    word-break: break-all;
                }

                /* Type badges (keeping pills) */
                .dd-type-badge {
                    background: rgba(255, 255, 255, 0.08);
                    color: var(--devtools-text-dim);
                    padding: 1px 4px;
                    border-radius: 2px;
                    font-size: 9px;
                    text-transform: lowercase;
                    margin-left: 4px;
                }

                /* Value colors */
                .dd-value-string {
                    color: var(--devtools-string);
                }

                .dd-value-number {
                    color: var(--devtools-number);
                }

                .dd-value-bool {
                    color: var(--devtools-bool);
                }

                .dd-value-null {
                    color: var(--devtools-null);
                    font-style: italic;
                }

                .dd-value-array {
                    color: var(--devtools-array);
                }

                .dd-value-object {
                    color: var(--devtools-object);
                }

                /* Stack trace */
                .dd-backtrace {
                    margin-top: 16px;
                    background: var(--devtools-sidebar);
                    border: 1px solid var(--devtools-border);
                    border-radius: 2px;
                }

                .dd-backtrace-header {
                    background: var(--devtools-bg);
                    padding: 6px 10px;
                    border-bottom: 1px solid var(--devtools-border);
                    font-size: 11px;
                    font-weight: 600;
                    color: var(--devtools-text);
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }

                .dd-backtrace-content {
                    padding: 8px 4px;
                }

                .dd-trace-item {
                    padding: 4px 8px;
                    margin: 2px 0;
                    cursor: pointer;
                    border-radius: 2px;
                    font-size: 11px;
                    transition: background 0.1s;
                }

                .dd-trace-item:hover {
                    background: var(--devtools-hover);
                }

                .dd-trace-number {
                    color: var(--devtools-text-dim);
                    margin-right: 8px;
                    font-weight: 600;
                }

                .dd-trace-function {
                    color: var(--devtools-text);
                }

                .dd-trace-class {
                    color: var(--devtools-object);
                }

                .dd-trace-file {
                    color: var(--devtools-text-dim);
                    font-size: 10px;
                    margin-left: 24px;
                    display: block;
                }

                .dd-trace-line {
                    color: var(--devtools-number);
                }

                /* Tab content */
                .dd-tab-content {
                    display: none;
                }

                .dd-tab-content.active {
                    display: block;
                }

                /* Expandable stack trace items */
                .dd-trace-item-expandable {
                    margin-bottom: 4px;
                    background: var(--devtools-bg);
                    border: 1px solid var(--devtools-border);
                    border-radius: 2px;
                    overflow: hidden;
                    cursor: pointer;
                }

                .dd-trace-summary {
                    padding: 8px 12px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: background 0.1s;
                }

                .dd-trace-item-expandable:hover .dd-trace-summary {
                    background: var(--devtools-hover);
                }

                .dd-trace-arrow {
                    color: var(--devtools-text-dim);
                    font-size: 10px;
                    width: 12px;
                    display: inline-block;
                    transition: transform 0.2s;
                }

                .dd-trace-item-expandable.expanded .dd-trace-arrow {
                    transform: rotate(90deg);
                }

                .dd-trace-details {
                    display: none;
                    padding: 12px 16px 12px 44px;
                    background: var(--devtools-sidebar);
                    border-top: 1px solid var(--devtools-border);
                }

                .dd-trace-item-expandable.expanded .dd-trace-details {
                    display: block;
                }

                .dd-trace-detail-row {
                    display: grid;
                    grid-template-columns: 100px 1fr;
                    gap: 12px;
                    margin-bottom: 8px;
                    font-size: 11px;
                }

                .dd-trace-detail-row:last-child {
                    margin-bottom: 0;
                }

                .dd-trace-detail-label {
                    color: var(--devtools-text-dim);
                    font-weight: 600;
                }

                .dd-trace-detail-value {
                    color: var(--devtools-text);
                    font-family: Menlo, Monaco, monospace;
                }
            </style>
        </head>
        <body>
        <div class="dd-wrapper">
            <div class="dd-container">
                <!-- DevTools-style header tabs -->
                <div class="dd-header">
                    <div class="dd-header-tab active" data-tab="console">
                        <span class="dd-header-icon">🐛</span>
                        <span>Console</span>
                    </div>
                    <div class="dd-header-tab" data-tab="payload">
                        <span>Payload</span>
                    </div>
                    <div class="dd-header-tab" data-tab="stack">
                        <span>Stack Trace</span>
                    </div>
                </div>

                <!-- Toolbar with context info -->
                <div class="dd-toolbar">
                    <span class="dd-toolbar-label">File</span>
                    <span class="dd-toolbar-value"><?= htmlspecialchars($file) ?>:<?= $line ?></span>
                    <?php if ($context): ?>
                        <span class="dd-toolbar-separator"></span>
                        <span class="dd-toolbar-label">Context</span>
                        <span class="dd-toolbar-value"><?= htmlspecialchars($context) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Tab: Console (Variable Dumps) -->
                <div class="dd-content dd-tab-content active" data-tab-content="console">
                    <?php foreach ($args as $index => $arg): ?>
                        <div class="dd-arg-section">
                            <div class="dd-arg-header">
                                <span class="dd-arg-badge">#<?= $index + 1 ?></span>
                                <span class="dd-arg-type"><?= $this->getTypeLabel($arg) ?></span>
                            </div>
                            <div class="dd-dump">
                                <?php $this->dumpVarTree($arg, 0); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Tab: Payload (Raw JSON) -->
                <div class="dd-content dd-tab-content" data-tab-content="payload">
                    <div class="dd-arg-section">
                        <div class="dd-arg-header">
                            <span class="dd-arg-badge">RAW</span>
                            <span class="dd-arg-type">JSON Formatted</span>
                        </div>
                        <div class="dd-dump">
                        <pre style="margin: 0; color: var(--devtools-text); line-height: 1.6;"><?php
                            $payload = [];
                            foreach ($args as $index => $arg) {
                                $payload['arg_' . ($index + 1)] = $arg;
                            }
                            echo htmlspecialchars(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_NOQUOTES);
                            ?></pre>
                        </div>
                    </div>
                </div>

                <!-- Tab: Stack Trace -->
                <div class="dd-content dd-tab-content" data-tab-content="stack">
                    <?php if (!empty($trace)): ?>
                        <div class="dd-backtrace">
                            <div class="dd-backtrace-header">
                                <span>Call Stack</span>
                                <span style="color: var(--devtools-text-dim); font-weight: normal;"><?= count($trace) ?> frames</span>
                            </div>
                            <div class="dd-backtrace-content">
                                <?php foreach ($trace as $index => $frame): ?>
                                    <div class="dd-trace-item-expandable" onclick="this.classList.toggle('expanded')">
                                        <div class="dd-trace-summary">
                                            <span class="dd-trace-arrow">▸</span>
                                            <span class="dd-trace-number"><?= $index ?></span>
                                            <?php if (isset($frame['class'])): ?>
                                                <span class="dd-trace-class"><?= htmlspecialchars($frame['class']) ?></span>
                                                <span style="color: var(--devtools-text-dim);"><?= htmlspecialchars($frame['type'] ?? '::') ?></span>
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

                                        <!-- Expandable details -->
                                        <div class="dd-trace-details">
                                            <?php if (isset($frame['file'])): ?>
                                                <div class="dd-trace-detail-row">
                                                    <span class="dd-trace-detail-label">File:</span>
                                                    <span class="dd-trace-detail-value"><?= htmlspecialchars($traceFile ?? $frame['file']) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['line'])): ?>
                                                <div class="dd-trace-detail-row">
                                                    <span class="dd-trace-detail-label">Line:</span>
                                                    <span class="dd-trace-detail-value"><?= $frame['line'] ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['class'])): ?>
                                                <div class="dd-trace-detail-row">
                                                    <span class="dd-trace-detail-label">Class:</span>
                                                    <span class="dd-trace-detail-value"><?= htmlspecialchars($frame['class']) ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['function'])): ?>
                                                <div class="dd-trace-detail-row">
                                                    <span class="dd-trace-detail-label">Function:</span>
                                                    <span class="dd-trace-detail-value"><?= htmlspecialchars($frame['function']) ?>()</span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (isset($frame['args']) && count($frame['args']) > 0): ?>
                                                <div class="dd-trace-detail-row">
                                                    <span class="dd-trace-detail-label">Arguments:</span>
                                                    <div class="dd-trace-detail-value">
                                                        <?php foreach ($frame['args'] as $argIndex => $argValue): ?>
                                                            <div style="margin-top: 8px;">
                                                                <strong style="color: var(--devtools-property);">[<?= $argIndex ?>]</strong>
                                                                <div style="margin-left: 16px; margin-top: 4px;">
                                                                    <?php $this->dumpVarTree($argValue, 0); ?>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div style="padding: 20px; color: var(--devtools-text-dim); text-align: center;">
                            No stack trace available
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            // Tab switching
            document.querySelectorAll('.dd-header-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Update active tab
                    document.querySelectorAll('.dd-header-tab').forEach(t => t.classList.remove('active'));
                    this.classList.add('active');

                    // Show corresponding content
                    document.querySelectorAll('.dd-tab-content').forEach(content => {
                        content.classList.remove('active');
                    });
                    document.querySelector(`[data-tab-content="${targetTab}"]`).classList.add('active');
                });
            });

            // Tree expansion
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('dd-tree-arrow')) {
                    const item = e.target.closest('.dd-tree-item').parentElement;
                    item.classList.toggle('dd-tree-expanded');
                }
            });
        </script>
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
     * Dump variable in DevTools tree style
     */
    private function dumpVarTree(mixed $var, int $level = 0): void
    {
        $indent = str_repeat('<span class="dd-tree-indent" style="width: 12px; display: inline-block;"></span>', $level);
        $type = gettype($var);

        match ($type) {
            'boolean' => $this->echoTreeLine($indent, '', $var ? 'true' : 'false', 'bool'),
            'integer' => $this->echoTreeLine($indent, '', number_format($var), 'number'),
            'double' => $this->echoTreeLine($indent, '', (string)$var, 'number'),
            'string' => $this->echoTreeString($indent, $var),
            'NULL' => $this->echoTreeLine($indent, '', 'null', 'null'),
            'array' => $this->dumpArrayTree($var, $level, $indent),
            'object' => $this->dumpObjectTree($var, $level, $indent),
            'resource' => $this->echoTreeLine($indent, '', 'resource(' . get_resource_type($var) . ')', 'null'),
            default => $this->echoTreeLine($indent, '', $type, 'null'),
        };
    }

    /**
     * Echo a tree line with proper styling
     */
    private function echoTreeLine(string $indent, string $key, string $value, string $type): void
    {
        echo '<div class="dd-tree-item">';
        echo $indent;
        echo '<span class="dd-tree-arrow" style="visibility: hidden;"></span>';
        echo '<div class="dd-tree-content"><div class="dd-tree-line">';

        if ($key !== '') {
            echo '<span class="dd-prop-key">' . htmlspecialchars($key) . '</span>';
            echo '<span class="dd-prop-separator">:</span>';
        }

        echo '<span class="dd-prop-value dd-value-' . $type . '">' . htmlspecialchars($value) . '</span>';
        echo '</div></div></div>';
    }

    /**
     * Echo string with proper formatting
     */
    private function echoTreeString(string $indent, string $value): void
    {
        $len = strlen($value);
        $display = htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($len > 100) {
            $display = substr($display, 0, 100) . '...';
        }

        echo '<div class="dd-tree-item">';
        echo $indent;
        echo '<span class="dd-tree-arrow" style="visibility: hidden;"></span>';
        echo '<div class="dd-tree-content"><div class="dd-tree-line">';
        echo '<span class="dd-prop-value dd-value-string">"' . $display . '"</span>';
        echo '<span class="dd-type-badge">string(' . $len . ')</span>';
        echo '</div></div></div>';
    }

    /**
     * Dump array in tree style
     */
    private function dumpArrayTree(array $var, int $level, string $indent): void
    {
        $count = count($var);
        $isAssoc = $count > 0 && array_keys($var) !== range(0, $count - 1);

        if ($count === 0) {
            $this->echoTreeLine($indent, '', 'Array(0) []', 'array');
            return;
        }

        echo '<div class="dd-tree-expanded">';
        echo '<div class="dd-tree-item">';
        echo $indent;
        echo '<span class="dd-tree-arrow"></span>';
        echo '<div class="dd-tree-content"><div class="dd-tree-line">';
        echo '<span class="dd-value-array">Array(' . $count . ')</span>';
        echo '<span class="dd-type-badge">array</span>';
        echo '</div></div></div>';

        echo '<div class="dd-tree-children">';
        foreach ($var as $key => $value) {
            $displayKey = $isAssoc || is_string($key) ? (is_string($key) ? '"' . htmlspecialchars($key) . '"' : $key) : $key;

            if (is_array($value) || is_object($value)) {
                echo '<div class="dd-tree-expanded">';
                echo '<div class="dd-tree-item">';
                echo str_repeat('<span class="dd-tree-indent" style="width: 12px; display: inline-block;"></span>', $level + 1);
                echo '<span class="dd-tree-arrow"></span>';
                echo '<div class="dd-tree-content"><div class="dd-tree-line">';
                echo '<span class="dd-prop-key">' . $displayKey . '</span>';
                echo '<span class="dd-prop-separator">:</span>';
                echo '</div></div></div>';
                echo '<div class="dd-tree-children">';
                $this->dumpVarTree($value, $level + 2);
                echo '</div></div>';
            } else {
                $newIndent = str_repeat('<span class="dd-tree-indent" style="width: 12px; display: inline-block;"></span>', $level + 1);
                echo '<div class="dd-tree-item">';
                echo $newIndent;
                echo '<span class="dd-tree-arrow" style="visibility: hidden;"></span>';
                echo '<div class="dd-tree-content"><div class="dd-tree-line">';
                echo '<span class="dd-prop-key">' . $displayKey . '</span>';
                echo '<span class="dd-prop-separator">:</span>';
                $this->echoInlineValue($value);
                echo '</div></div></div>';
            }
        }
        echo '</div></div>';
    }

    /**
     * Dump object in tree style
     */
    private function dumpObjectTree(object $var, int $level, string $indent): void
    {
        $className = get_class($var);
        $reflection = new \ReflectionClass($var);
        $props = [];

        foreach ($reflection->getProperties() as $prop) {
            try {
                $props[$prop->getName()] = $prop->getValue($var);
            } catch (\Error $e) {
                // Skip uninitialized
            }
        }

        $count = count($props);

        if ($count === 0) {
            echo '<div class="dd-tree-item">';
            echo $indent;
            echo '<span class="dd-tree-arrow" style="visibility: hidden;"></span>';
            echo '<div class="dd-tree-content"><div class="dd-tree-line">';
            echo '<span class="dd-value-object">' . htmlspecialchars($className) . '</span>';
            echo '<span class="dd-type-badge">object</span>';
            echo ' {}';
            echo '</div></div></div>';
            return;
        }

        echo '<div class="dd-tree-expanded">';
        echo '<div class="dd-tree-item">';
        echo $indent;
        echo '<span class="dd-tree-arrow"></span>';
        echo '<div class="dd-tree-content"><div class="dd-tree-line">';
        echo '<span class="dd-value-object">' . htmlspecialchars($className) . '</span>';
        echo '<span class="dd-type-badge">object</span>';
        echo '</div></div></div>';

        echo '<div class="dd-tree-children">';
        foreach ($props as $key => $value) {
            if (is_array($value) || is_object($value)) {
                echo '<div class="dd-tree-expanded">';
                echo '<div class="dd-tree-item">';
                echo str_repeat('<span class="dd-tree-indent" style="width: 12px; display: inline-block;"></span>', $level + 1);
                echo '<span class="dd-tree-arrow"></span>';
                echo '<div class="dd-tree-content"><div class="dd-tree-line">';
                echo '<span class="dd-prop-key">' . htmlspecialchars($key) . '</span>';
                echo '<span class="dd-prop-separator">:</span>';
                echo '</div></div></div>';
                echo '<div class="dd-tree-children">';
                $this->dumpVarTree($value, $level + 2);
                echo '</div></div>';
            } else {
                $newIndent = str_repeat('<span class="dd-tree-indent" style="width: 12px; display: inline-block;"></span>', $level + 1);
                echo '<div class="dd-tree-item">';
                echo $newIndent;
                echo '<span class="dd-tree-arrow" style="visibility: hidden;"></span>';
                echo '<div class="dd-tree-content"><div class="dd-tree-line">';
                echo '<span class="dd-prop-key">' . htmlspecialchars($key) . '</span>';
                echo '<span class="dd-prop-separator">:</span>';
                $this->echoInlineValue($value);
                echo '</div></div></div>';
            }
        }
        echo '</div></div>';
    }

    /**
     * Echo inline value with proper color
     */
    private function echoInlineValue(mixed $value): void
    {
        $type = gettype($value);

        match ($type) {
            'boolean' => print('<span class="dd-value-bool">' . ($value ? 'true' : 'false') . '</span>'),
            'integer' => print('<span class="dd-value-number">' . number_format($value) . '</span><span class="dd-type-badge">int</span>'),
            'double' => print('<span class="dd-value-number">' . $value . '</span><span class="dd-type-badge">float</span>'),
            'string' => $this->echoInlineString($value),
            'NULL' => print('<span class="dd-value-null">null</span>'),
            default => print('<span>' . htmlspecialchars($type) . '</span>'),
        };
    }

    /**
     * Echo inline string value
     */
    private function echoInlineString(string $value): void
    {
        $len = strlen($value);
        $display = htmlspecialchars($value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8');

        if ($len > 100) {
            $display = substr($display, 0, 100) . '...';
        }

        echo '<span class="dd-value-string">"' . $display . '"</span>';
        echo '<span class="dd-type-badge">string(' . $len . ')</span>';
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
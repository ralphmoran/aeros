<?php

namespace Aeros\Src\Classes;

class File
{
    /**
     * Creates a file with the given content. This method validates if the file
     * exists, otherwise it's created. When it exists, the content is appended.
     *
     * @param string $filename
     * @param mixed $content
     * @param int $append
     * @return integer|false
     */
    public static function create(string $filename, mixed $content = '', int $append = FILE_APPEND): int|false
    {
        // Validate path before operation
        $filename = static::validatePath($filename);

        // Ensure directory exists
        $directory = dirname($filename);

        if (! is_dir($directory) &&! mkdir($directory, 0755, true)) {
            throw new \Exception("Failed to create directory: {$directory}");
        }

        return file_put_contents($filename, $content, $append);
    }

    /**
     * Gets the content of the file or a URL.
     *
     * @param string $filename
     * @return string|false
     */
    public static function getContent(string $filename): string|false
    {
        // Security: Only allow local file reading, no URLs
        if (filter_var($filename, FILTER_VALIDATE_URL)) {
            throw new \Exception("URL reading not allowed for security reasons");
        }

        // Validate path
        $filename = static::validatePath($filename);

        // Check file exists and is readable
        if (! file_exists($filename)) {
            throw new \Exception("File does not exist: {$filename}");
        }

        if (! is_readable($filename)) {
            throw new \Exception("File is not readable: {$filename}");
        }

        return file_get_contents($filename);
    }

    /**
     * Creates a new file from a template with the given tokens.
     *
     * @param string $filename
     * @param string $template
     * @param array $tokens
     * @return integer|false
     */
    public static function createFromTemplate(string $filename, string $template, array $tokens): int|false
    {
        // Framework operation - validate files exist but skip whitelist check
        if (! file_exists($template)) {
            throw new \Exception("{$template} does not exist");
        }

        // Check for null bytes only (basic security)
        if (strpos($template, "\0") !== false || strpos($filename, "\0") !== false) {
            throw new \Exception("Invalid path: null byte detected");
        }

        // Get template content directly (bypass validatePath for trusted framework operation)
        $templateContent = file_get_contents($template);

        if ($templateContent === false) {
            throw new \Exception("Failed to read template: {$template}");
        }

        foreach ($tokens as $tag => $value) {
            $templateContent = str_replace('{{' . $tag . '}}', $value, $templateContent);
        }

        // Create file directly without validation (framework writes to app directories)
        $directory = dirname($filename);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new \Exception("Failed to create directory: {$directory}");
        }

        return file_put_contents($filename, $templateContent, 0);
    }

    /**
     * Validates that a path is safe and within allowed directories
     * Prevents symlink attacks by checking resolved path
     *
     * @param string $path
     * @return string Normalized safe path
     * @throws \Exception
     */
    private static function validatePath(string $path): string
    {
        // Check for null bytes (security: null byte injection)
        if (strpos($path, "\0") !== false) {
            throw new \Exception("Invalid path: null byte detected");
        }

        // Get allowed base paths from config
        $allowedPaths = static::getAllowedBasePaths();

        // Normalize path (resolve .., ., //)
        $realPath = realpath($path);

        // If path doesn't exist yet (for creation), validate the directory
        if ($realPath === false) {
            $directory = dirname($path);
            $realDirectory = realpath($directory);

            if ($realDirectory === false) {
                throw new \Exception("Directory does not exist");
            }

            // SECURITY: Check if directory itself is a symlink pointing outside allowed paths
            if (is_link($directory)) {
                $symlinkTarget = readlink($directory);
                $isAllowed = false;

                foreach ($allowedPaths as $allowedPath) {
                    if (strpos(realpath($symlinkTarget), $allowedPath) === 0) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (! $isAllowed) {
                    throw new \Exception("Access denied: symlink points outside allowed directories");
                }
            }

            // Reconstruct path with validated directory
            $realPath = $realDirectory . DIRECTORY_SEPARATOR . basename($path);

            // SECURITY: Prevent creating symlinks
            if (is_link($path)) {
                throw new \Exception("Access denied: cannot create symlinks");
            }
        } else {
            // Path exists - check if it's a symlink
            if (is_link($path)) {

                // Get symlink target and resolve it
                $symlinkTarget = readlink($path);

                if ($symlinkTarget === false) {
                    throw new \Exception("Invalid symlink");
                }

                // Resolve symlink target to absolute path
                $resolvedTarget = realpath($symlinkTarget);

                if ($resolvedTarget === false) {
                    throw new \Exception("Symlink target does not exist");
                }

                // Check if symlink target is within allowed paths
                $isAllowed = false;

                foreach ($allowedPaths as $allowedPath) {
                    if (strpos($resolvedTarget, $allowedPath) === 0) {
                        $isAllowed = true;
                        break;
                    }
                }

                if (! $isAllowed) {
                    throw new \Exception("Access denied: symlink points outside allowed directories");
                }

                // Use resolved target for further validation
                $realPath = $resolvedTarget;
            }
        }

        // Verify path is within allowed directories
        $isAllowed = false;

        foreach ($allowedPaths as $allowedPath) {
            if (strpos($realPath, $allowedPath) === 0) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            throw new \Exception("Access denied: path outside allowed directories");
        }

        return $realPath;
    }

    /**
     * Get allowed base paths from storage config
     *
     * @return array
     */
    private static function getAllowedBasePaths(): array
    {
        $config = config('storage');
        $allowedPaths = [];

        // Add all configured storage paths
        if (isset($config['spaces'])) {
            foreach ($config['spaces'] as $space) {
                if (isset($space['path'])) {
                    $allowedPaths[] = realpath($space['path']);
                }
            }
        }

        // Add files upload path if configured
        if (isset($config['files']['upload_path'])) {
            $allowedPaths[] = realpath($config['files']['upload_path']);
        }

        if (isset($config['files']['public_path'])) {
            $allowedPaths[] = realpath($config['files']['public_path']);
        }

        // Add application directories
        $allowedPaths[] = realpath(app()->basedir);

        // Remove any false values and duplicates
        return array_unique(array_filter($allowedPaths));
    }

    /**
     * Check if a file exists
     *
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        $path = static::validatePath($path);

        return file_exists($path);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     * @throws \Exception
     */
    public static function delete(string $path): bool
    {
        $path = static::validatePath($path);

        if (! file_exists($path)) {
            throw new \Exception("File does not exist: {$path}");
        }

        if (! is_file($path)) {
            throw new \Exception("Path is not a file: {$path}");
        }

        if (! is_writable($path)) {
            throw new \Exception("File is not writable: {$path}");
        }

        return unlink($path);
    }

    /**
     * Move a file from one location to another
     *
     * @param string $from
     * @param string $to
     * @return bool
     * @throws \Exception
     */
    public static function move(string $from, string $to): bool
    {
        $from = static::validatePath($from);
        $to = static::validatePath($to);

        if (! file_exists($from)) {
            throw new \Exception("Source file does not exist: {$from}");
        }

        // Ensure destination directory exists
        $directory = dirname($to);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new \Exception("Failed to create directory: {$directory}");
        }

        // Check if destination already exists
        if (file_exists($to)) {
            throw new \Exception("Destination file already exists: {$to}");
        }

        return rename($from, $to);
    }

    /**
     * Copy a file from one location to another
     *
     * @param string $from
     * @param string $to
     * @return bool
     * @throws \Exception
     */
    public static function copy(string $from, string $to): bool
    {
        $from = static::validatePath($from);
        $to = static::validatePath($to);

        if (! file_exists($from)) {
            throw new \Exception("Source file does not exist: {$from}");
        }

        if (! is_readable($from)) {
            throw new \Exception("Source file is not readable: {$from}");
        }

        // Ensure destination directory exists
        $directory = dirname($to);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new \Exception("Failed to create directory: {$directory}");
        }

        return copy($from, $to);
    }

    /**
     * Get file size in bytes
     *
     * @param string $path
     * @return int
     * @throws \Exception
     */
    public static function size(string $path): int
    {
        $path = static::validatePath($path);

        if (! file_exists($path)) {
            throw new \Exception("File does not exist: {$path}");
        }

        $size = filesize($path);

        if ($size === false) {
            throw new \Exception("Failed to get file size: {$path}");
        }

        return $size;
    }

    /**
     * Get file extension (lowercase, without dot)
     *
     * @param string $path
     * @return string
     */
    public static function extension(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Sanitize filename to prevent security issues
     * Removes: path separators, null bytes, control characters, special chars
     *
     * @param string $filename
     * @return string
     */
    public static function sanitizeFilename(string $filename): string
    {
        // Get only the filename, remove any path
        $filename = basename($filename);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Remove control characters (ASCII 0-31)
        $filename = preg_replace('/[\x00-\x1F\x7F]/', '', $filename);

        // Remove path separators and dangerous characters
        $filename = str_replace(['/', '\\', '..', '<', '>', ':', '"', '|', '?', '*'], '', $filename);

        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);

        // Remove multiple dots (except the one before extension)
        $parts = explode('.', $filename);

        if (count($parts) > 2) {
            $extension = array_pop($parts);
            $basename = implode('_', $parts);
            $filename = $basename . '.' . $extension;
        }

        // Limit length (max 255 chars for most filesystems)
        if (strlen($filename) > 255) {
            $extension = static::extension($filename);
            $basename = substr($filename, 0, 255 - strlen($extension) - 1);
            $filename = $basename . '.' . $extension;
        }

        return $filename;
    }

    /**
     * Generate a unique filename in the given path
     *
     * @param string $path Directory path
     * @param string $extension File extension (without dot)
     * @param string $prefix Optional prefix
     * @return string Full path to unique file
     * @throws \Exception
     */
    public static function generateUniqueFilename(string $path, string $extension, string $prefix = ''): string
    {
        $path = static::validatePath($path);

        if (! is_dir($path)) {
            throw new \Exception("Path is not a directory: {$path}");
        }

        // Sanitize extension
        $extension = strtolower(trim($extension, '.'));

        // Generate unique filename using hash + timestamp
        do {
            $hash = bin2hex(random_bytes(16)); // 32 char random hash
            $timestamp = time();
            $filename = $prefix . $timestamp . '_' . $hash . '.' . $extension;
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
        } while (file_exists($fullPath));

        return $fullPath;
    }

    /**
     * Store an uploaded file securely
     *
     * @param array $uploadedFile The $_FILES array element (e.g., $_FILES['document'])
     * @param string|null $directory Target directory (null = use config default)
     * @param array $options Override options: ['name' => 'custom.jpg', 'overwrite' => false]
     * @return array File information ['path' => '', 'name' => '', 'size' => 0, 'mime' => '']
     * @throws \Exception
     */
    public static function store(array $uploadedFile, ?string $directory = null, array $options = []): array
    {
        // Validate upload array structure
        if (! isset($uploadedFile['tmp_name'], $uploadedFile['name'], $uploadedFile['size'], $uploadedFile['error'])) {
            throw new \Exception("Invalid upload file array structure");
        }

        // Check for upload errors
        static::validateUploadError($uploadedFile['error']);

        $tmpPath = $uploadedFile['tmp_name'];
        $originalName = $uploadedFile['name'];
        $fileSize = $uploadedFile['size'];

        // Security: Verify it's actually an uploaded file
        if (! is_uploaded_file($tmpPath)) {
            throw new \Exception("Security violation: File was not uploaded via HTTP POST");
        }

        // Get storage configuration
        $config = config('storage');
        $fileConfig = $config['files'] ?? [];

        // Validate file size
        $maxSize = $fileConfig['max_size'] ?? $config['files']['max_validation_size'];

        if ($fileSize > $maxSize) {
            throw new \Exception("File size exceeds maximum allowed: " . static::formatBytes($maxSize));
        }

        if ($fileSize === 0) {
            throw new \Exception("File is empty");
        }

        // Get actual MIME type from file content (not from upload data)
        $actualMimeType = static::getMimeTypeFromFile($tmpPath);

        if ($actualMimeType === false) {
            throw new \Exception("Failed to determine file MIME type");
        }

        // ADD THIS BLOCK - Check blocked types first
        $blockedTypes = $fileConfig['blocked_types'] ?? [];

        if (in_array($actualMimeType, $blockedTypes, true)) {
            throw new \Exception("File type is blocked for security reasons: {$actualMimeType}");
        }

        // Validate images for embedded scripts
        static::validateImageSecurity($tmpPath, $actualMimeType);

        // Validate MIME type against whitelist (if allowed_types is set)
        $allowedTypes = $fileConfig['allowed_types'] ?? [];

        if (! empty($allowedTypes) && !isset($allowedTypes[$actualMimeType])) {
            throw new \Exception("File type not allowed: {$actualMimeType}");
        }

        $sanitizedName = static::sanitizeFilename($originalName);

        $extension = static::extension($sanitizedName);

        static::validateMimeExtension($actualMimeType, $extension);

        if ($directory === null) {
            $directory = $fileConfig['upload_path'] ?? app()->basedir . '/storage/uploads';
        }

        if (! is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw new \Exception("Failed to create upload directory: {$directory}");
        }

        $directory = static::validatePath($directory);

        // Determine final filename based on naming strategy
        $namingStrategy = $fileConfig['naming_strategy'] ?? 'hash';
        $customName = $options['name'] ?? null;
        $allowOverwrite = $options['overwrite'] ?? false;

        if ($customName !== null) {
            // Use custom name if provided
            $finalName = static::sanitizeFilename($customName);
            $finalPath = $directory . DIRECTORY_SEPARATOR . $finalName;

            if (! $allowOverwrite && file_exists($finalPath)) {
                throw new \Exception("File already exists: {$finalName}");
            }
        } else {
            // Use configured naming strategy
            $finalPath = static::generateStoragePath($directory, $extension, $namingStrategy, $sanitizedName);
        }

        // Move uploaded file to final destination (atomic operation)
        if (! move_uploaded_file($tmpPath, $finalPath)) {
            throw new \Exception("Failed to move uploaded file to destination");
        }

        // Set secure permissions (readable but not executable)
        chmod($finalPath, 0644);

        // Return file information
        return [
            'path' => $finalPath,
            'name' => basename($finalPath),
            'original_name' => $originalName,
            'size' => $fileSize,
            'mime' => $actualMimeType,
            'extension' => $extension,
        ];
    }

    /**
     * Store multiple uploaded files securely
     *
     * @param array $uploadedFiles Array of $_FILES elements
     * @param string|null $directory Target directory (null = use config default)
     * @param array $options Options: ['stop_on_error' => true, 'per_file_options' => []]
     * @return array Results: ['success' => [...], 'failed' => [...], 'count' => 0]
     */
    public static function storeMultiple(array $uploadedFiles, ?string $directory = null, array $options = []): array
    {
        $stopOnError = $options['stop_on_error'] ?? true;
        $perFileOptions = $options['per_file_options'] ?? [];

        $results = [
            'success' => [],
            'failed' => [],
            'count' => 0,
        ];

        foreach ($uploadedFiles as $key => $file) {
            // Skip if not a valid file array
            if (! is_array($file) || ! isset($file['tmp_name'])) {
                continue;
            }

            try {
                // Check if there are specific options for this file
                $fileOptions = $perFileOptions[$key] ?? [];

                $result = static::store($file, $directory, $fileOptions);

                $results['success'][] = array_merge($result, ['key' => $key]);
                $results['count']++;

            } catch (\Exception $e) {
                $results['failed'][] = [
                    'key' => $key,
                    'original_name' => $file['name'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];

                // Stop processing if stop_on_error is true
                if ($stopOnError) {
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Validate upload error code
     *
     * @param int $errorCode
     * @return void
     * @throws \Exception
     */
    private static function validateUploadError(int $errorCode): void
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension',
        ];

        if ($errorCode !== UPLOAD_ERR_OK) {
            $message = $errors[$errorCode] ?? 'Unknown upload error';
            throw new \Exception("Upload error: {$message}");
        }
    }

    /**
     * Validate MIME type and extension against config
     *
     * @param string $mimeType
     * @param string $extension
     * @return void
     * @throws \Exception
     */
    private static function validateMimeExtension(string $mimeType, string $extension): void
    {
        $allowedTypes = config('storage')['files']['allowed_types'] ?? [];

        // If allowed_types is empty, skip extension validation (allow all)
        if (empty($allowedTypes)) {
            return;
        }

        // Check if MIME type is in allowed list
        if (! isset($allowedTypes[$mimeType])) {
            throw new \Exception("File type not allowed: {$mimeType}");
        }

        // Validate extension matches the MIME type
        if (! in_array($extension, $allowedTypes[$mimeType], true)) {
            throw new \Exception("File extension '{$extension}' does not match MIME type '{$mimeType}'");
        }
    }

    /**
     * Generate storage path based on naming strategy
     *
     * @param string $directory
     * @param string $extension
     * @param string $strategy 'hash', 'timestamp', or 'original'
     * @param string $originalName
     * @return string
     */
    private static function generateStoragePath(string $directory, string $extension, string $strategy, string $originalName): string
    {
        switch ($strategy) {
            case 'original':
                // Use original filename, add number if exists
                // Use pathinfo to get filename WITHOUT extension
                $baseName = pathinfo($originalName, PATHINFO_FILENAME);
                $filename = $baseName . '.' . $extension;
                $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;

                // If exists, append number
                $counter = 1;

                while (file_exists($fullPath)) {
                    $filename = $baseName . '_' . $counter . '.' . $extension;
                    $fullPath = $directory . DIRECTORY_SEPARATOR . $filename;
                    $counter++;
                }

                return $fullPath;

            case 'timestamp':
                // Timestamp + random suffix
                $timestamp = time();
                $random = bin2hex(random_bytes(8));
                $filename = $timestamp . '_' . $random . '.' . $extension;
                return $directory . DIRECTORY_SEPARATOR . $filename;

            case 'hash':
            default:
                // Cryptographically secure hash
                return static::generateUniqueFilename($directory, $extension);
        }
    }

    /**
     * Format bytes to human readable size
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get MIME type directly from file without path validation
     * Used for temporary uploaded files
     *
     * @param string $path
     * @return string|false
     * @throws \Exception
     */
    private static function getMimeTypeFromFile(string $path): string|false
    {
        if (! file_exists($path)) {
            throw new \Exception("File does not exist: {$path}");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new \Exception("Failed to open fileinfo database");
        }

        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mimeType;
    }

    /**
     * Validate image file for embedded scripts (security)
     * Scans entire file in chunks to prevent memory exhaustion
     *
     * @param string $path Path to image file
     * @param string $mimeType MIME type of file
     * @return void
     * @throws \Exception
     */
    private static function validateImageSecurity(string $path, string $mimeType): void
    {
        // Only check image files
        if (strpos($mimeType, 'image/') !== 0) {
            return;
        }

        // Get file size
        $fileSize = filesize($path);

        if ($fileSize === false) {
            throw new \Exception("Failed to get file size for security scan");
        }

        // Scan file in chunks for PHP tags
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \Exception("Failed to open file for security scan");
        }

        $chunkSize = 8192; // 8KB chunks
        $overlap = 512; // Overlap to catch split patterns
        $previousChunk = '';

        while (! feof($handle)) {
            $chunk = fread($handle, $chunkSize);

            if ($chunk === false) {
                fclose($handle);
                throw new \Exception("Failed to read file for security scan");
            }

            // Combine with previous chunk overlap to catch patterns split across chunks
            $scanContent = $previousChunk . $chunk;

            // Check for specific PHP/script execution patterns
            // Removed overly broad patterns that cause false positives in binary data
            $dangerousPatterns = [
                '<?php',           // PHP opening tag
                '<?=',             // PHP short echo tag
                '<script',         // JavaScript
                'eval(',           // PHP eval
                'system(',         // System command execution
                'exec(',           // Command execution
                'shell_exec(',     // Shell execution
                'passthru(',       // Pass through to shell
            ];

            foreach ($dangerousPatterns as $pattern) {
                if (stripos($scanContent, $pattern) !== false) {
                    fclose($handle);
                    throw new \Exception("Security violation: Potentially malicious code detected in image file");
                }
            }

            // Keep last bytes for overlap check
            $previousChunk = substr($chunk, -$overlap);
        }

        fclose($handle);

        $maxImageSize = config(
            'storage.files.max_validation_size',
            config('storage.files.max_size')
        );

        if ($fileSize > $maxImageSize) {
            throw new \Exception("Image file too large for validation: " . static::formatBytes($fileSize));
        }

        // Validate actual image format using GD (prevents corrupted/fake images)
        // This also ensures image can be safely processed
        switch ($mimeType) {
            case 'image/jpeg':
                $image = @imagecreatefromjpeg($path);

                break;

            case 'image/png':
                $image = @imagecreatefrompng($path);

                break;

            case 'image/gif':
                $image = @imagecreatefromgif($path);

                break;

            case 'image/webp':
                $image = @imagecreatefromwebp($path);

                break;

            case 'image/svg+xml':
                static::validateSvgSecurity($path);
                return; // SVG validated, no GD check needed

            default:

                if (! is_readable($path)) {
                    throw new \Exception("Image file is not readable");
                }

                return; // Skip GD validation for other image types
        }

        if ($image === false) {
            throw new \Exception("Invalid or corrupted image file");
        }

        imagedestroy($image);
    }

    /**
     * Validate SVG file for security threats
     * SVG files can contain JavaScript, XXE, and malicious event handlers
     *
     * @param string $path Path to SVG file
     * @return void
     * @throws \Exception
     */
    private static function validateSvgSecurity(string $path): void
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new \Exception("Failed to read SVG file");
        }

        $patterns = [
            ['type' => 'string', 'pattern' => '<script', 'message' => 'Script tags'],
            ['type' => 'string', 'pattern' => 'javascript:', 'message' => 'JavaScript protocol'],
            ['type' => 'string', 'pattern' => '<iframe', 'message' => 'iframes'],
            ['type' => 'string', 'pattern' => '<embed', 'message' => 'Embedded content'],
            ['type' => 'string', 'pattern' => '<object', 'message' => 'Object tags'],
            ['type' => 'string', 'pattern' => '<!ENTITY', 'message' => 'XML entities'],
            ['type' => 'string', 'pattern' => '<!DOCTYPE', 'message' => 'DOCTYPE declarations'],
            ['type' => 'string', 'pattern' => 'xlink:href', 'message' => 'External references'],
            ['type' => 'regex', 'pattern' => '/on\w+\s*=/i', 'message' => 'Event handlers'],
        ];

        foreach ($patterns as $config) {
            $found = $config['type'] === 'regex'
                ? preg_match($config['pattern'], $content)
                : stripos($content, $config['pattern']) !== false;

            if (! $found) {
                continue;
            }

            throw new \Exception("Security violation: {$config['message']} detected in SVG");
        }

        // Validate XML structure
        libxml_use_internal_errors(true);

        if (simplexml_load_string($content) === false) {
            throw new \Exception("Invalid SVG: malformed XML structure");
        }

        libxml_clear_errors();
    }
}

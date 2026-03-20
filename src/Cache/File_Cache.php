<?php

declare (strict_types=1);
namespace Fast_Route\Cache;

use function chmod;
use Closure;
use function dirname;
use Fast_Route\Cache;
use Fast_Route\Configure_Routes;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_writable;
use const LOCK_EX;
use function mkdir;
use function rename;
use function restore_error_handler;
use RuntimeException;
use function set_error_handler;
use function unlink;
use function var_export;
/** @phpstan-import-type ProcessedData from ConfigureRoutes */
final class File_Cache implements Cache
{
    private const DIRECTORY_PERMISSIONS = 0775;
    private const FILE_PERMISSIONS = 0664;
    /**
     * This is cached in a local static variable to avoid instantiating a closure each time we need an empty handler
     */
    private static Closure $empty_error_handler;
    public function __construct()
    {
        self::$empty_error_handler ??= static function (): void {
        };
    }
    /** @inheritdoc */
    public function get(string $key, callable $loader): array
    {
        $result = self::read_file_contents($key);
        if ($result !== null) {
            return $result;
        }
        $data = $loader();
        self::write_to_file($key, '<?php return ' . var_export($data, true) . ';');
        return $data;
    }
    /** @return ProcessedData|null */
    private static function read_file_contents(string $path): ?array
    {
        // error suppression is faster than calling `file_exists()` + `is_file()` + `is_readable()`, especially because there's no need to error here
        set_error_handler(self::$empty_error_handler);
        $value = include $path;
        restore_error_handler();
        if (!is_array($value)) {
            return null;
        }
        // @phpstan-ignore-next-line because we won´t be able to validate the array shape in a performant way
        return $value;
    }
    private static function write_to_file(string $path, string $content): void
    {
        $directory = dirname($path);
        if (!self::create_directory_if_needed($directory) || !is_writable($directory)) {
            throw new RuntimeException('The cache directory is not writable "' . $directory . '"');
        }
        set_error_handler(self::$empty_error_handler);
        $tmp_file = $path . '.tmp';
        if (file_put_contents($tmp_file, $content, LOCK_EX) === false) {
            restore_error_handler();
            return;
        }
        chmod($tmp_file, self::FILE_PERMISSIONS);
        if (!rename($tmp_file, $path)) {
            unlink($tmp_file);
        }
        restore_error_handler();
    }
    private static function create_directory_if_needed(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }
        set_error_handler(self::$empty_error_handler);
        $created = mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        restore_error_handler();
        return $created !== false || is_dir($directory);
    }
}
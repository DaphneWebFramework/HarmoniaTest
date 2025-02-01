<?php declare(strict_types=1);
/**
 * StreamMock.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

/**
 * A custom stream wrapper that emulates the behavior of `php://input` and
 * `php://output`.
 *
 * @link http://news-from-the-basement.blogspot.com/2011/07/mocking-phpinput.html
 */
class StreamMock
{
    public const ERROR_MODE_NONE = 0;
    public const ERROR_MODE_OPEN = 1;
    public const ERROR_MODE_READ = 2;

    public static ?string $PersistentStorage = null;
    public static int $ErrorMode = self::ERROR_MODE_NONE;

    private string $buffer;
    private int $length;
    private int $position;
    public $context = null; // Required for PHP 8.2+ to prevent dynamic property warnings

    public function __construct()
    {
        $this->buffer = is_string(self::$PersistentStorage) ? self::$PersistentStorage : '';
        $this->length = \strlen($this->buffer);
        $this->position = 0;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        if (self::$ErrorMode === self::ERROR_MODE_OPEN) {
            return false;
        }
        return true;
    }

    public function stream_write(string $data): int
    {
        $this->buffer .= $data;
        $written = \strlen($data);
        $this->length += $written;
        return $written;
    }

    public function stream_flush(): bool
    {
        self::$PersistentStorage = $this->buffer;
        return true;
    }

    public function stream_stat(): array
    {
        return [];
    }

    public function stream_read(int $count): string|false
    {
        if (self::$ErrorMode === self::ERROR_MODE_READ) {
            // Causes `stream_get_contents` or `file_get_contents` to return an
            // empty string.
            return false;
        }
        $length = \min($count, $this->length - $this->position);
        $data = \substr($this->buffer, $this->position, $length);
        $this->position += $length;
        return $data;
    }

    public function stream_eof(): bool
    {
        return $this->position >= $this->length;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $newPosition = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length - $offset,
            default => -1
        };
        if ($newPosition < 0 || $newPosition > $this->length) {
            return false;
        }
        $this->position = $newPosition;
        return true;
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_close(): void
    {
        // No action needed
    }
}

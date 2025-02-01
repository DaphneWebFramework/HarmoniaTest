<?php declare(strict_types=1);
/**
 * StreamMockManager.php
 *
 * (C) 2025 by Eylem Ugurel
 *
 * Licensed under a Creative Commons Attribution 4.0 International License.
 *
 * You should have received a copy of the license along with this work. If not,
 * see <http://creativecommons.org/licenses/by/4.0/>.
 */

require_once 'StreamMock.php';

/**
 * Manages the `StreamMock` class for testing PHP input/output streams.
 */
class StreamMockManager
{
    private const WRAPPER = 'php';

    public static function Create(): ?StreamMockManager
    {
        \stream_wrapper_unregister(self::WRAPPER);
        if (!\stream_wrapper_register(self::WRAPPER, StreamMock::class)) {
            \stream_wrapper_restore(self::WRAPPER);
            return null;
        }
        return new self();
    }

    public function __construct()
    {
        StreamMock::$PersistentStorage = null;
        StreamMock::$ErrorMode = StreamMock::ERROR_MODE_NONE;
    }

    public function __destruct()
    {
        \stream_wrapper_unregister(self::WRAPPER);
        \stream_wrapper_restore(self::WRAPPER);
    }

    public function Write(string $data): bool
    {
        return \file_put_contents('php://output', $data) === \strlen($data);
    }

    public function SimulateOpenError(): void
    {
        StreamMock::$ErrorMode = StreamMock::ERROR_MODE_OPEN;
    }

    public function SimulateReadError(): void
    {
        StreamMock::$ErrorMode = StreamMock::ERROR_MODE_READ;
    }
}

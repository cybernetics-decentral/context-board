<?php

/**
 * FlatfileStore — abstracts all file I/O operations.
 *
 * SDD Reference: Section 3.8
 */
class FlatfileStore
{
    private string $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = rtrim($dataDir, '/');
    }

    public function read(string $relativePath): array
    {
        return $this->readJson($relativePath);
    }

    public function readRaw(string $relativePath): string
    {
        $path = $this->resolvePath($relativePath);
        if (!file_exists($path)) {
            throw new \RuntimeException("File not found: {$relativePath}");
        }
        return file_get_contents($path);
    }

    public function exists(string $relativePath): bool
    {
        return file_exists($this->resolvePath($relativePath));
    }

    public function write(string $relativePath, array $data): void
    {
        $this->writeJson($relativePath, $data);
    }

    public function writeRaw(string $relativePath, string $content): void
    {
        $this->atomicWrite($this->resolvePath($relativePath), $content);
    }

    public function delete(string $relativePath): void
    {
        $path = $this->resolvePath($relativePath);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function deleteDirectory(string $relativePath): void
    {
        $path = $this->resolvePath($relativePath);
        if (!is_dir($path)) {
            return;
        }
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectoryByPath($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        rmdir($path);
    }

    public function createDirectory(string $relativePath): void
    {
        $path = $this->resolvePath($relativePath);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public function listDirectory(string $relativePath): array
    {
        $path = $this->resolvePath($relativePath);
        if (!is_dir($path)) {
            return [];
        }
        $items = scandir($path);
        return array_values(array_filter($items, fn($i) => $i !== '.' && $i !== '..'));
    }

    public function readJson(string $relativePath): array
    {
        $path = $this->resolvePath($relativePath);
        if (!file_exists($path)) {
            return [];
        }
        $raw = file_get_contents($path);
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[' . date('c') . '] [WARNING] Corrupt JSON in: ' . $path);
            return [];
        }
        return $data ?? [];
    }

    public function writeJson(string $relativePath, array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('JSON encoding failed: ' . json_last_error_msg());
        }
        $this->atomicWrite($this->resolvePath($relativePath), $json);
    }

    // --- Private helpers ---

    private function resolvePath(string $relativePath): string
    {
        return $this->dataDir . '/' . ltrim($relativePath, '/');
    }

    private function atomicWrite(string $targetPath, string $content): void
    {
        $tmpDir = $this->dataDir . '/tmp';
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }
        $tempPath = $tmpDir . '/' . basename($targetPath) . '.' . microtime(true) . '.' . bin2hex(random_bytes(4)) . '.tmp';

        $handle = fopen($tempPath, 'w');
        if ($handle === false) {
            throw new \RuntimeException("Could not open temp file for writing: {$tempPath}");
        }
        fwrite($handle, $content);
        fclose($handle);

        if (!rename($tempPath, $targetPath)) {
            @unlink($tempPath);
            throw new \RuntimeException("Atomic write failed for: {$targetPath}");
        }
    }

    private function deleteDirectoryByPath(string $path): void
    {
        $items = scandir($path);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $itemPath = $path . '/' . $item;
            if (is_dir($itemPath)) {
                $this->deleteDirectoryByPath($itemPath);
            } else {
                unlink($itemPath);
            }
        }
        rmdir($path);
    }
}

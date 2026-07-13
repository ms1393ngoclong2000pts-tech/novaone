<?php

declare(strict_types=1);

final class DataStore
{
    public function __construct(
        private readonly string $path,
        private readonly string $seedPath
    ) {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0777, true);
        }

        if (! file_exists($this->path)) {
            $this->reset();
        }
    }

    public function all(): array
    {
        $json = file_get_contents($this->path);
        $data = json_decode($json ?: '{}', true);

        if (is_array($data)) {
            return $data;
        }

        $this->backupCorruptData();
        $this->reset();

        $json = file_get_contents($this->path);
        $data = json_decode($json ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    public function get(string $key): array
    {
        return $this->all()[$key] ?? [];
    }

    public function put(string $key, array $items): void
    {
        $data = $this->all();
        $data[$key] = array_values($items);
        $this->save($data);
    }

    public function save(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Không thể mã hóa dữ liệu lưu trữ.');
        }

        $directory = dirname($this->path);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $temporary = tempnam($directory, 'novaone-data-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create a temporary data file.');
        }

        try {
            if (file_put_contents($temporary, $json, LOCK_EX) === false) {
                throw new RuntimeException('Unable to write application data.');
            }

            if (! rename($temporary, $this->path)) {
                throw new RuntimeException('Unable to replace application data.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    public function reset(): void
    {
        $seed = require $this->seedPath;
        $this->save($seed);
    }

    private function backupCorruptData(): void
    {
        if (! is_file($this->path)) {
            return;
        }

        copy($this->path, $this->path . '.corrupt-' . date('Ymd-His') . '.bak');
    }
}

<?php

namespace Larament\DotEnvEditor;

/**
 * DotEnvEditor class
 *
 * @author Raziul Islam <raziul.dev@gmail.com>
 *
 * @link https://raziul.dev
 */
class DotEnvEditor
{
    protected string $envRawData;

    protected array $env = [];

    protected array $newEnv = [];

    protected array $removedEnv = [];

    protected ?string $backupDirectory = null;

    protected int $backupCount = 5;

    /**
     * DotEnvEditor constructor.
     *
     * @param string $envFilePath   The path to the.env file
     * @param bool $keepBackup      Whether to keep a backup of the.env file
     */
    public function __construct(
        protected string $envFilePath,
        protected bool $keepBackup = true
    ) {
        if (! file_exists($this->envFilePath)) {
            throw new \InvalidArgumentException("The .env file does not exist at {$this->envFilePath}");
        }

        $this->envRawData = file_get_contents($this->envFilePath);
        $this->env = $this->parseEnv($this->envRawData);
    }

    /**
     * Set the directory to store the backup files
     */
    public function setBackupDir(string $dir): self
    {
        if (! is_dir($dir) && ! mkdir($dir, 0755, true)) {
            throw new \InvalidArgumentException("The backup directory does not exist at {$dir}");
        }

        $this->backupDirectory = $dir;

        return $this;
    }

    /**
     * Path to the backup directory
     */
    public function backupDir(): string
    {
        if ($this->backupDirectory) {
            return rtrim($this->backupDirectory, '/');
        }

        // default to the same directory as the .env file
        return dirname($this->envFilePath);
    }

    /**
     * Set the number of backups to keep
     */
    public function setBackupCount(int $count): self
    {
        $this->backupCount = $count;

        return $this;
    }

    /**
     * Load the .env file
     */
    public static function load(string $envFilePath, bool $keepBackup = true): self
    {
        return new static($envFilePath, $keepBackup);
    }

    /**
     * Get the value of an environment variable
     */
    public function get(string $key)
    {
        return $this->newEnv[$key] ?? $this->env[$key] ?? null;
    }

    /**
     * Get all environment variables
     */
    public function all(): array
    {
        return $this->newEnv + $this->env;
    }

    /**
     * Get only the environment variables specified
     */
    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    /**
     * Remove an environment variable
     */
    public function remove(string $key): self
    {
        $cleanKey = str_replace('.', '_', strtoupper($key));
        $this->removedEnv[$cleanKey] = true;
        unset($this->newEnv[$cleanKey]);
        unset($this->env[$cleanKey]);

        return $this;
    }

    /**
     * Set an environment variable or an array of environment variables
     */
    public function set(string|array $key, mixed $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

            return $this;
        }

        $this->newEnv[str_replace('.', '_', strtoupper($key))] = $value;

        return $this;
    }

    /**
     * Write the .env file
     */
    public function write(): bool
    {
        $env = $this->envRawData;

        // Remove keys
        foreach ($this->removedEnv as $key => $true) {
            $escapedKey = preg_quote($key, '/');
            $env = preg_replace("/^{$escapedKey}=.*\r?\n?/m", "", $env);
        }

        $append = [];

        foreach ($this->newEnv as $key => $value) {
            $value = $this->castValue($value);

            if (array_key_exists($key, $this->env)) {
                $escapedKey = preg_quote($key, '/');
                $env = preg_replace("/^{$escapedKey}=.*/m", "{$key}={$value}", $env);
            } else {
                $append[] = $key.'='.$value;
            }
        }

        if ($append) {
            if (! empty($env) && ! str_ends_with($env, "\n")) {
                $env .= "\n";
            }
            $env .= implode("\n", $append)."\n";
        }

        $this->keepBackup();
        $this->removedEnv = [];

        return file_put_contents($this->envFilePath, $env) !== false;
    }

    /**
     * Cast the value appropriately
     */
    private function castValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return '';
        }

        if (is_string($value) && (
            str_contains($value, ' ')
            || str_starts_with($value, '${') && str_ends_with($value, '}')
        )) {
            return '"'.$value.'"';
        }

        return $value;
    }

    /**
     * Parse the env data to an array
     */
    private function parseEnv(string $rawData): array
    {
        $env = [];
        foreach (explode("\n", $rawData) as $line) {
            $line = rtrim($line, "\r");
            if (str_starts_with($line, '#') || empty($line)) {
                continue;
            }
            $line = explode('=', $line, 2);
            $env[$line[0]] = $line[1] ?? null;
        }

        return $env;
    }

    /**
     * Clear old backups
     */
    public function clearOldBackups(): void
    {
        if (! $this->backupDirectory) {
            return;
        }

        $backups = array_filter(
            glob($this->backupDir().'/{,.}*', GLOB_BRACE),
            'is_file'
        );

        if (count($backups) < $this->backupCount) {
            return;
        }

        // sort by last modified
        usort($backups, fn ($a, $b) => filemtime($b) - filemtime($a));

        // keep latest backups
        array_splice($backups, 0, $this->backupCount);

        foreach ($backups as $backup) {
            unlink($backup);
        }
    }

    /**
     * Keep a backup of the .env file
     */
    private function keepBackup(): void
    {
        if (! $this->keepBackup) {
            return;
        }

        $microtime = explode(' ', microtime());
        $micro = substr($microtime[0], 2, 6);
        $path = sprintf(
            '%s/%s.%s',
            $this->backupDir(),
            basename($this->envFilePath),
            date('Y-m-d-H-i-s', $microtime[1]) . '-' . $micro
        );

        copy($this->envFilePath, $path);

        $this->clearOldBackups();
    }
}

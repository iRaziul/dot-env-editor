<?php

namespace Raziul\DotEnvEditor;

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

    protected ?string $backupDirectory = null;

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
        unset($this->newEnv[str_replace('.', '_', strtoupper($key))]);
        unset($this->env[str_replace('.', '_', strtoupper($key))]);

        return $this;
    }

    /**
     * Set an environment variable or an array of environment variables
     */
    public function set(string|array $key, string $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->set($k, $v);
            }

            return $this;
        }

        if (is_string($value) && str_contains($value, ' ')) {
            $value = '"'.$value.'"';
        }

        $this->newEnv[str_replace('.', '_', strtoupper($key))] = $value;

        return $this;
    }

    /**
     * Write the .env file
     */
    public function write(): bool
    {
        $replace = [];
        $append = [];

        // get keys for replacing and appending
        foreach ($this->newEnv as $key => $value) {
            if (array_key_exists($key, $this->env)) {
                $replace[$key.'='.$this->env[$key]] = $key.'='.$value;
            } else {
                $append[] = $key.'='.$value;
            }
        }

        $env = str_replace(
            array_keys($replace),
            array_values($replace),
            $this->envRawData
        );

        if ($append) {
            $env .= "\n".implode("\n", $append)."\n";
        }

        $this->keepBackup();

        return file_put_contents($this->envFilePath, $env) !== false;
    }

    /**
     * Parse the env data to an array
     */
    private function parseEnv(string $rawData): array
    {
        $env = [];
        foreach (explode("\n", $rawData) as $line) {
            if (str_starts_with($line, '#') || empty($line)) {
                continue;
            }
            $line = explode('=', $line, 2);
            $env[$line[0]] = $line[1] ?? null;
        }

        return $env;
    }

    /**
     * Keep a backup of the .env file
     */
    private function keepBackup(): void
    {
        if (! $this->keepBackup) {
            return;
        }

        if (! $this->backupDirectory) {
            $path = $this->envFilePath;
        } else {
            $path = rtrim($this->backupDirectory, '/').'/'.basename($this->envFilePath);
        }

        copy(
            $this->envFilePath,
            $path.'.'.date('Y-m-d-H-i-s')
        );
    }
}

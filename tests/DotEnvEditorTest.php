<?php

namespace Larament\DotEnvEditor\Tests;

use Larament\DotEnvEditor\DotEnvEditor;
use PHPUnit\Framework\TestCase;

class DotEnvEditorTest extends TestCase
{
    private string $tempEnvFile;
    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempEnvFile = tempnam(sys_get_temp_dir(), 'env_test_');
        $this->backupDir = sys_get_temp_dir() . '/env_test_backups_' . uniqid();
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempEnvFile)) {
            unlink($this->tempEnvFile);
        }
        if (is_dir($this->backupDir)) {
            array_map('unlink', glob("{$this->backupDir}/*"));
            rmdir($this->backupDir);
        }

        parent::tearDown();
    }

    public function test_it_loads_and_parses_env_variables()
    {
        file_put_contents($this->tempEnvFile, "APP_NAME=Laravel\nAPP_DEBUG=true\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, false);

        $this->assertEquals('Laravel', $editor->get('APP_NAME'));
        $this->assertEquals('true', $editor->get('APP_DEBUG'));
    }

    public function test_it_handles_windows_line_endings_without_trailing_cr()
    {
        file_put_contents($this->tempEnvFile, "APP_NAME=Laravel\r\nAPP_DEBUG=true\r\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, false);

        $this->assertEquals('Laravel', $editor->get('APP_NAME'));
        $this->assertEquals('true', $editor->get('APP_DEBUG'));
    }

    public function test_it_does_not_have_substring_replacement_collisions()
    {
        file_put_contents($this->tempEnvFile, "APP_DEBUG=true\nDEBUG=true\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, false);
        $editor->set('DEBUG', 'false');
        $editor->write();

        $updatedContent = file_get_contents($this->tempEnvFile);

        $this->assertStringContainsString("APP_DEBUG=true\n", $updatedContent);
        $this->assertStringContainsString("DEBUG=false\n", $updatedContent);
    }

    public function test_it_sets_normal_and_nested_keys()
    {
        file_put_contents($this->tempEnvFile, "DB_CONNECTION_HOST=localhost\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, false);
        
        // Test dot notation flattening
        $editor->set('DB_CONNECTION.host', '127.0.0.1');
        $editor->set('NEW_VARIABLE', 'hello');
        $editor->write();

        $this->assertEquals('127.0.0.1', $editor->get('DB_CONNECTION_HOST'));
        $this->assertEquals('hello', $editor->get('NEW_VARIABLE'));
    }

    public function test_it_removes_keys()
    {
        file_put_contents($this->tempEnvFile, "APP_NAME=Laravel\nAPP_DEBUG=true\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, false);
        $editor->remove('APP_DEBUG');
        $editor->write();

        $this->assertNull($editor->get('APP_DEBUG'));
        $this->assertStringNotContainsString('APP_DEBUG', file_get_contents($this->tempEnvFile));
    }

    public function test_it_manages_backups()
    {
        file_put_contents($this->tempEnvFile, "APP_NAME=Laravel\n");

        $editor = DotEnvEditor::load($this->tempEnvFile, true);
        $editor->setBackupDir($this->backupDir);
        $editor->setBackupCount(2);

        $editor->set('VAR1', '1')->write();
        $editor->set('VAR2', '2')->write();
        $editor->set('VAR3', '3')->write();

        $backups = array_filter(glob("{$this->backupDir}/*"), 'is_file');

        // It should keep exactly 2 latest backups
        $this->assertCount(2, $backups);
    }
}

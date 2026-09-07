<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Backup\CronCommandGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronCommandGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_cron_command_generator_uses_actual_base_path_by_default(): void
    {
        $generator = new CronCommandGenerator();
        $command = $generator->generate();

        $expectedBasePath = base_path();
        $this->assertStringContainsString("cd '{$expectedBasePath}'", $command);
        $this->assertStringContainsString('artisan schedule:run >> /dev/null 2>&1', $command);
    }

    public function test_cron_command_generator_supports_custom_base_path(): void
    {
        $generator = new CronCommandGenerator();
        $customPath = '/home/testuser/apps/kasme';
        $command = $generator->generate(basePath: $customPath);

        $this->assertStringContainsString("cd '{$customPath}'", $command);
    }

    public function test_cron_command_generator_supports_configurable_php_binary(): void
    {
        $generator = new CronCommandGenerator();
        $customPhp = '/opt/alt/php84/usr/bin/php';
        $command = $generator->generate(phpBinary: $customPhp);

        $this->assertStringContainsString("'{$customPhp}'", $command);
    }

    public function test_cron_command_generator_formats_extension_flags_correctly(): void
    {
        $generator = new CronCommandGenerator();
        $extensions = 'bcmath,dom,fileinfo,mbstring,zip';
        $command = $generator->generate(
            basePath: '/home/adezaivm/apps/kas',
            phpBinary: '/opt/alt/php84/usr/bin/php',
            extensions: $extensions
        );

        $expected = "* * * * * cd '/home/adezaivm/apps/kas' && '/opt/alt/php84/usr/bin/php' -d extension=bcmath.so -d extension=dom.so -d extension=fileinfo.so -d extension=mbstring.so -d extension=zip.so artisan schedule:run >> /dev/null 2>&1";
        $this->assertSame($expected, $command);
    }

    public function test_cron_command_generator_safely_rejects_and_ignores_invalid_extension_names(): void
    {
        $generator = new CronCommandGenerator();
        // Malicious or invalid entries with shell injection characters, spaces, semicolons
        $extensions = 'bcmath,evil;rm -rf /,zip,invalid name,ext_with-dash,`whoami`';
        $command = $generator->generate(extensions: $extensions);

        // Valid extensions should be included
        $this->assertStringContainsString('-d extension=bcmath.so', $command);
        $this->assertStringContainsString('-d extension=zip.so', $command);
        $this->assertStringContainsString('-d extension=ext_with-dash.so', $command);

        // Dangerous / invalid extensions must be completely absent
        $this->assertStringNotContainsString('rm', $command);
        $this->assertStringNotContainsString('whoami', $command);
        $this->assertStringNotContainsString('invalid name', $command);
        $this->assertStringNotContainsString(';', $command);
    }

    public function test_empty_extension_list_generates_valid_command_without_extension_flags(): void
    {
        $generator = new CronCommandGenerator();
        $command = $generator->generate(
            basePath: '/home/user/apps/kasme',
            phpBinary: '/usr/bin/php',
            extensions: ''
        );

        $expected = "* * * * * cd '/home/user/apps/kasme' && '/usr/bin/php' artisan schedule:run >> /dev/null 2>&1";
        $this->assertSame($expected, $command);
        $this->assertStringNotContainsString('-d extension', $command);
    }

    public function test_generated_command_does_not_contain_secrets_or_credentials(): void
    {
        config([
            'app.key' => 'base64:SecretAppKeyForTestingOnly1234567890=',
            'database.connections.mysql.password' => 'super_secret_db_password',
        ]);

        $generator = new CronCommandGenerator();
        $command = $generator->generate();

        $this->assertStringNotContainsString('SecretAppKey', $command);
        $this->assertStringNotContainsString('super_secret_db_password', $command);
        $this->assertStringNotContainsString('APP_KEY', $command);
        $this->assertStringNotContainsString('DB_PASSWORD', $command);
    }

    public function test_backup_dashboard_displays_generated_cron_command_for_owner(): void
    {
        config([
            'kasme.php_cli_binary' => '/opt/alt/php84/usr/bin/php',
            'kasme.php_cli_extensions' => 'bcmath,zip',
        ]);

        $owner = User::factory()->instanceOwner()->create();

        $response = $this->actingAs($owner)->get(route('backups.index'));
        $response->assertOk();
        $response->assertSee('Panduan cPanel Cron Job:');
        $response->assertSee('Tambahkan perintah berikut pada cPanel → Cron Jobs dan jalankan setiap menit.');
        $response->assertSee('/opt/alt/php84/usr/bin/php');
        $response->assertSee('-d extension=bcmath.so');
        $response->assertSee('-d extension=zip.so');
        $response->assertSee('Salin');
    }

    public function test_non_owner_cannot_access_backup_dashboard_or_see_cron_command(): void
    {
        $normalUser = User::factory()->create(['is_instance_owner' => false]);

        $response = $this->actingAs($normalUser)->get(route('backups.index'));
        $response->assertForbidden();
    }
}

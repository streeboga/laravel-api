<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $base = sys_get_temp_dir().'/streeboga-sync-skills-'.bin2hex(random_bytes(4));
    mkdir($base.'/vendor/composer', 0o755, true);
    mkdir($base.'/vendor/acme/demo/skills/demo-skill', 0o755, true);
    mkdir($base.'/.claude', 0o755, true);

    $this->base = $base;

    // Fake skill source inside fake vendor package.
    file_put_contents(
        $base.'/vendor/acme/demo/skills/demo-skill/SKILL.md',
        "---\nname: demo-skill\nversion: 1.0.0\nstreeboga_owned: true\n---\n\n# Demo\n",
    );
    file_put_contents(
        $base.'/vendor/acme/demo/skills/demo-skill/README.md',
        "hello world\n",
    );

    file_put_contents(
        $base.'/vendor/composer/installed.json',
        (string) json_encode([
            'packages' => [
                [
                    'name' => 'acme/demo',
                    'version' => '1.0.0',
                    'install-path' => '../acme/demo',
                    'extra' => [
                        'streeboga' => [
                            'skills' => [
                                'demo-skill' => [
                                    'path' => 'skills/demo-skill',
                                    'version' => '1.0.0',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], JSON_PRETTY_PRINT),
    );

    // Point the app base path at our fake root.
    $this->app->setBasePath($base);
});

afterEach(function () {
    if (isset($this->base) && is_string($this->base) && is_dir($this->base)) {
        File::deleteDirectory($this->base);
    }
});

it('copies skill into .claude/skills and writes manifest', function () {
    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    $destDir = $this->base.'/.claude/skills/streeboga-demo-demo-skill';
    expect($destDir)->toBeDirectory()
        ->and($destDir.'/SKILL.md')->toBeFile()
        ->and($destDir.'/README.md')->toBeFile();

    $manifestPath = $this->base.'/.claude/skills/.streeboga-manifest.json';
    expect($manifestPath)->toBeFile();

    /** @var array<string, array<string, string>> $manifest */
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    expect($manifest)->toHaveKey('streeboga-demo-demo-skill')
        ->and($manifest['streeboga-demo-demo-skill']['package'])->toBe('acme/demo')
        ->and($manifest['streeboga-demo-demo-skill']['skill'])->toBe('demo-skill')
        ->and($manifest['streeboga-demo-demo-skill']['hash'])->not->toBe('');
});

it('is idempotent — second run does not overwrite unchanged skill', function () {
    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    $destFile = $this->base.'/.claude/skills/streeboga-demo-demo-skill/SKILL.md';
    $manifestPath = $this->base.'/.claude/skills/.streeboga-manifest.json';
    $manifestBefore = (string) file_get_contents($manifestPath);

    // Force a distinguishable mtime to verify no-op behavior.
    touch($destFile, time() - 60);
    $mtimeMarker = filemtime($destFile);

    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    expect(filemtime($destFile))->toBe($mtimeMarker)
        ->and((string) file_get_contents($manifestPath))->toBe($manifestBefore);
});

it('skips user-modified skill and logs conflict', function () {
    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    $destFile = $this->base.'/.claude/skills/streeboga-demo-demo-skill/README.md';
    file_put_contents($destFile, "user-edited\n");

    // Change source to trigger a sync attempt on the next run.
    file_put_contents(
        $this->base.'/vendor/acme/demo/skills/demo-skill/README.md',
        "upstream-updated\n",
    );

    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    expect(file_get_contents($destFile))->toBe("user-edited\n")
        ->and($this->base.'/.claude/skills/.streeboga-conflicts.log')->toBeFile();
});

it('overwrites user-modified skill when --force is passed', function () {
    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    $destFile = $this->base.'/.claude/skills/streeboga-demo-demo-skill/README.md';
    file_put_contents($destFile, "user-edited\n");

    file_put_contents(
        $this->base.'/vendor/acme/demo/skills/demo-skill/README.md',
        "upstream-updated\n",
    );

    $this->artisan('streeboga:sync-skills', ['--force' => true])->assertSuccessful();

    expect(file_get_contents($destFile))->toBe("upstream-updated\n");
});

it('skips destination without streeboga_owned frontmatter', function () {
    $destDir = $this->base.'/.claude/skills/streeboga-demo-demo-skill';
    mkdir($destDir, 0o755, true);
    file_put_contents(
        $destDir.'/SKILL.md',
        "---\nname: demo-skill\n---\n\n# Pre-existing\n",
    );

    $this->artisan('streeboga:sync-skills')->assertSuccessful();

    expect(file_get_contents($destDir.'/SKILL.md'))->toContain('Pre-existing')
        ->and($this->base.'/.claude/skills/.streeboga-conflicts.log')->toBeFile();
});

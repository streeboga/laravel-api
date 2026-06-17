<?php

declare(strict_types=1);

namespace Streeboga\LaravelApi\Console;

use Illuminate\Console\Command;

/**
 * Syncs Claude Code skills shipped via composer packages into the host app's `.claude/skills/` directory.
 *
 * Discovery: reads `vendor/composer/installed.json` and, for each package, looks up
 * `extra.streeboga.skills` (dict of `skill_name => {path, version}`).
 *
 * Layout: each skill is copied to `.claude/skills/streeboga-{vendor-less-package-name}-{skill-name}/`.
 *
 * Overwrite rules: a destination SKILL.md is considered user-modified when its current content
 * hash differs from the hash recorded in `.claude/skills/.streeboga-manifest.json`. User-modified
 * skills are skipped and logged to `.claude/skills/.streeboga-conflicts.log` (unless --force is
 * supplied). Skills whose SKILL.md frontmatter lacks `streeboga_owned: true` are never overwritten.
 */
final class SyncSkillsCommand extends Command
{
    /** @var string */
    protected $signature = 'streeboga:sync-skills {--force : Overwrite destination files even if user-modified} {--quiet-output : Suppress normal output}';

    /** @var string */
    protected $description = 'Sync Claude Code skills shipped by streeboga composer packages into .claude/skills/';

    public function handle(): int
    {
        $basePath = $this->resolveBasePath();
        $installedJsonPath = $basePath.'/vendor/composer/installed.json';

        if (! is_file($installedJsonPath)) {
            $this->error(sprintf('installed.json not found at %s', $installedJsonPath));

            return self::FAILURE;
        }

        $rawJson = (string) file_get_contents($installedJsonPath);
        /** @var array{packages?: list<array<string, mixed>>} $decoded */
        $decoded = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
        $packages = $decoded['packages'] ?? [];

        $skillsDir = $basePath.'/.claude/skills';
        $manifestPath = $skillsDir.'/.streeboga-manifest.json';
        $conflictsLogPath = $skillsDir.'/.streeboga-conflicts.log';

        if (! is_dir($skillsDir) && ! mkdir($skillsDir, 0o755, true) && ! is_dir($skillsDir)) {
            $this->error(sprintf('Failed to create skills directory: %s', $skillsDir));

            return self::FAILURE;
        }

        $manifest = $this->loadManifest($manifestPath);
        $newManifest = [];
        $copiedCount = 0;
        $skippedCount = 0;
        $force = (bool) $this->option('force');
        $quiet = (bool) $this->option('quiet-output');

        foreach ($packages as $package) {
            $packageName = (string) ($package['name'] ?? '');
            if ($packageName === '') {
                continue;
            }

            /** @var array<string, mixed> $extra */
            $extra = is_array($package['extra'] ?? null) ? $package['extra'] : [];
            /** @var array<string, mixed> $streeboga */
            $streeboga = is_array($extra['streeboga'] ?? null) ? $extra['streeboga'] : [];
            /** @var array<string, mixed> $skills */
            $skills = is_array($streeboga['skills'] ?? null) ? $streeboga['skills'] : [];

            if ($skills === []) {
                continue;
            }

            $packageDir = $this->resolvePackageDir($basePath, $package);
            if ($packageDir === null) {
                continue;
            }

            $packageSlug = $this->vendorlessPackageName($packageName);

            foreach ($skills as $skillName => $skillMeta) {
                if (! is_array($skillMeta)) {
                    continue;
                }

                $sourcePath = (string) ($skillMeta['path'] ?? '');
                if ($sourcePath === '') {
                    continue;
                }

                $sourceDir = rtrim($packageDir.'/'.$sourcePath, '/');
                if (! is_dir($sourceDir)) {
                    if (! $quiet) {
                        $this->warn(sprintf('[%s] skill "%s": source directory missing (%s)', $packageName, $skillName, $sourceDir));
                    }

                    continue;
                }

                $destKey = sprintf('streeboga-%s-%s', $packageSlug, $skillName);
                $destDir = $skillsDir.'/'.$destKey;

                $destSkillMdPath = $destDir.'/SKILL.md';
                $sourceHash = $this->hashDirectory($sourceDir);

                if (is_dir($destDir) && ! $force) {
                    $destSkillMdExists = is_file($destSkillMdPath);
                    $isOwned = $destSkillMdExists && $this->hasStreebogaOwnedFrontmatter($destSkillMdPath);

                    if (! $isOwned) {
                        $this->logConflict($conflictsLogPath, $destKey, 'missing streeboga_owned: true in frontmatter');
                        if (! $quiet) {
                            $this->warn(sprintf('Skipped "%s" — not streeboga-owned (use --force to overwrite)', $destKey));
                        }
                        $skippedCount++;

                        continue;
                    }

                    $priorHash = $manifest[$destKey]['hash'] ?? null;
                    $currentDestHash = $this->hashDirectory($destDir);

                    if ($priorHash !== null && $priorHash !== $currentDestHash) {
                        $this->logConflict($conflictsLogPath, $destKey, 'local modification detected (hash mismatch)');
                        if (! $quiet) {
                            $this->warn(sprintf('Skipped "%s" — local modifications detected (use --force to overwrite)', $destKey));
                        }
                        $skippedCount++;
                        $newManifest[$destKey] = $manifest[$destKey];

                        continue;
                    }

                    if ($priorHash === $sourceHash) {
                        // Up to date — nothing to do, retain manifest entry.
                        $newManifest[$destKey] = $manifest[$destKey];

                        continue;
                    }
                }

                $this->copyDirectory($sourceDir, $destDir);
                $copiedCount++;
                $newManifest[$destKey] = [
                    'package' => $packageName,
                    'skill' => $skillName,
                    'version' => (string) ($skillMeta['version'] ?? ''),
                    'hash' => $sourceHash,
                ];

                if (! $quiet) {
                    $this->info(sprintf('Synced %s → .claude/skills/%s', $packageName.':'.$skillName, $destKey));
                }
            }
        }

        $this->saveManifest($manifestPath, $newManifest);

        if (! $quiet) {
            $this->info(sprintf('Done. Copied: %d, skipped: %d.', $copiedCount, $skippedCount));
        }

        return self::SUCCESS;
    }

    private function resolveBasePath(): string
    {
        return rtrim(base_path(), '/');
    }

    /**
     * @param  array<string, mixed>  $package
     */
    private function resolvePackageDir(string $basePath, array $package): ?string
    {
        $installPath = $package['install-path'] ?? null;
        if (! is_string($installPath) || $installPath === '') {
            $name = (string) ($package['name'] ?? '');

            return $name !== '' ? $basePath.'/vendor/'.$name : null;
        }

        $resolved = $basePath.'/vendor/composer/'.$installPath;
        $real = realpath($resolved);

        return $real === false ? $resolved : $real;
    }

    private function vendorlessPackageName(string $packageName): string
    {
        $slash = strpos($packageName, '/');
        if ($slash === false) {
            return $packageName;
        }

        return substr($packageName, $slash + 1);
    }

    /**
     * @return array<string, array{package: string, skill: string, version: string, hash: string}>
     */
    private function loadManifest(string $path): array
    {
        if (! is_file($path)) {
            return [];
        }

        $contents = (string) file_get_contents($path);
        if ($contents === '') {
            return [];
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (! is_array($decoded)) {
            return [];
        }

        /** @var array<string, array{package: string, skill: string, version: string, hash: string}> $decoded */
        return $decoded;
    }

    /**
     * @param  array<string, array{package: string, skill: string, version: string, hash: string}>  $manifest
     */
    private function saveManifest(string $path, array $manifest): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            return;
        }

        ksort($manifest);
        $json = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        file_put_contents($path, $json.PHP_EOL);
    }

    private function logConflict(string $path, string $destKey, string $reason): void
    {
        $dir = dirname($path);
        if (! is_dir($dir) && ! mkdir($dir, 0o755, true) && ! is_dir($dir)) {
            return;
        }

        $line = sprintf('[%s] %s: %s', date('c'), $destKey, $reason).PHP_EOL;
        file_put_contents($path, $line, FILE_APPEND);
    }

    private function hasStreebogaOwnedFrontmatter(string $skillMdPath): bool
    {
        $contents = (string) file_get_contents($skillMdPath);
        if ($contents === '') {
            return false;
        }

        if (! str_starts_with($contents, '---')) {
            return false;
        }

        $end = strpos($contents, "\n---", 3);
        if ($end === false) {
            return false;
        }

        $frontmatter = substr($contents, 3, $end - 3);

        return (bool) preg_match('/^\s*streeboga_owned\s*:\s*true\s*$/mi', $frontmatter);
    }

    private function hashDirectory(string $dir): string
    {
        if (! is_dir($dir)) {
            return '';
        }

        $files = [];
        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if (! $item instanceof \SplFileInfo) {
                continue;
            }
            if (! $item->isFile()) {
                continue;
            }
            $path = $item->getPathname();
            $relative = substr($path, strlen($dir) + 1);
            $hash = hash_file('sha256', $path);
            $files[$relative] = $hash === false ? '' : $hash;
        }

        ksort($files);
        $serialized = '';
        foreach ($files as $relative => $hash) {
            $serialized .= $relative.':'.$hash."\n";
        }

        return hash('sha256', $serialized);
    }

    private function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination) && ! mkdir($destination, 0o755, true) && ! is_dir($destination)) {
            throw new \RuntimeException(sprintf('Failed to create destination %s', $destination));
        }

        /** @var \RecursiveIteratorIterator<\RecursiveDirectoryIterator> $iterator */
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if (! $item instanceof \SplFileInfo) {
                continue;
            }
            $sourcePath = $item->getPathname();
            $relative = substr($sourcePath, strlen($source) + 1);
            $target = $destination.'/'.$relative;

            if ($item->isDir()) {
                if (! is_dir($target) && ! mkdir($target, 0o755, true) && ! is_dir($target)) {
                    throw new \RuntimeException(sprintf('Failed to create directory %s', $target));
                }

                continue;
            }

            $targetDir = dirname($target);
            if (! is_dir($targetDir) && ! mkdir($targetDir, 0o755, true) && ! is_dir($targetDir)) {
                throw new \RuntimeException(sprintf('Failed to create directory %s', $targetDir));
            }

            if (! copy($sourcePath, $target)) {
                throw new \RuntimeException(sprintf('Failed to copy %s → %s', $sourcePath, $target));
            }
        }
    }
}

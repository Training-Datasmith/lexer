<?php

declare(strict_types=1);

/**
 * Verify org cluster packages match Training-Datasmith composer-index SHAs and root aliases.
 */

$expected = [
    'phpstan/phpstan' => [
        'alias' => '2.2.99',
        'reference' => '63f848a4b1dbe2f2cb87acb3ff32596661e45417',
    ],
];

$installedPhpPath = __DIR__ . '/../vendor/composer/installed.php';
$installedJsonPath = __DIR__ . '/../vendor/composer/installed.json';

if (! is_readable($installedPhpPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.php not found; run composer update first.\n");
    exit(1);
}

if (! is_readable($installedJsonPath)) {
    fwrite(STDERR, "check-cluster-pins: vendor/composer/installed.json not found; run composer update first.\n");
    exit(1);
}

/** @var array{versions: array<string, array{reference?: string, aliases?: list<string>}>} $installedPhp */
$installedPhp = require $installedPhpPath;

$installedJsonRaw = file_get_contents($installedJsonPath);
if ($installedJsonRaw === false) {
    fwrite(STDERR, "check-cluster-pins: could not read installed.json.\n");
    exit(1);
}

/** @var array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>} $installedJson */
$installedJson = json_decode($installedJsonRaw, true, 512, JSON_THROW_ON_ERROR);

$referencesFromJson = referencesFromInstalledJson($installedJson);

/**
 * @param array<string, array{alias: string, reference: string}> $expectedMap
 * @param array{versions: array<string, array{reference?: string, aliases?: list<string>}>} $versions
 */
function checkInstalledPhp(array $expectedMap, array $versions, string $label): bool
{
    $failed = false;

    foreach ($expectedMap as $name => $want) {
        if (! isset($versions[$name])) {
            fwrite(STDERR, sprintf("check-cluster-pins%s: %s is not installed.\n", $label, $name));
            $failed = true;
            continue;
        }

        $pkg = $versions[$name];
        $aliases = $pkg['aliases'] ?? [];
        if (! in_array($want['alias'], $aliases, true)) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins%s: %s alias mismatch (expected %s in aliases %s).\n",
                $label,
                $name,
                $want['alias'],
                json_encode($aliases),
            ));
            $failed = true;
        }

        $reference = $pkg['reference'] ?? null;
        if (! is_string($reference) || $reference === '') {
            fwrite(STDERR, sprintf("check-cluster-pins%s: %s has no reference.\n", $label, $name));
            $failed = true;
            continue;
        }

        if (! hash_equals($want['reference'], $reference)) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins%s: %s reference mismatch (expected %s, got %s).\n",
                $label,
                $name,
                $want['reference'],
                $reference,
            ));
            $failed = true;
        }
    }

    return ! $failed;
}

/**
 * @param array{packages?: list<array<string, mixed>>, 'packages-dev'?: list<array<string, mixed>>} $installedJson
 *
 * @return array<string, string>
 */
function referencesFromInstalledJson(array $installedJson): array
{
    $references = [];
    $packages = array_merge($installedJson['packages'] ?? [], $installedJson['packages-dev'] ?? []);

    foreach ($packages as $package) {
        if (! isset($package['name'], $package['source']['reference'])) {
            continue;
        }

        $name = (string) $package['name'];
        $reference = strtolower((string) $package['source']['reference']);
        $references[$name] = $reference;
    }

    return $references;
}

/**
 * @param array<string, array{alias: string, reference: string}> $expectedMap
 * @param array<string, string> $references
 */
function checkInstalledJson(array $expectedMap, array $references, string $label): bool
{
    $failed = false;

    foreach ($expectedMap as $name => $want) {
        if (! isset($references[$name])) {
            fwrite(STDERR, sprintf("check-cluster-pins%s: %s is not present in installed.json.\n", $label, $name));
            $failed = true;
            continue;
        }

        $actual = $references[$name];
        $expectedRef = strtolower($want['reference']);
        if ($actual !== $expectedRef) {
            fwrite(STDERR, sprintf(
                "check-cluster-pins%s: %s installed.json reference mismatch (expected %s, got %s).\n",
                $label,
                $name,
                $expectedRef,
                $actual,
            ));
            $failed = true;
        }
    }

    return ! $failed;
}

function flipLastHex(string $sha): string
{
    $last = substr($sha, -1);
    $flipped = $last === '0' ? '1' : '0';

    return substr($sha, 0, -1) . $flipped;
}

$versionsPhp = $installedPhp['versions'] ?? [];

$mutatedPhpVersions = $versionsPhp;
$wrongPhpRef = flipLastHex($expected['phpstan/phpstan']['reference']);
$mutatedPhpVersions['phpstan/phpstan']['reference'] = $wrongPhpRef;
if (checkInstalledPhp($expected, $mutatedPhpVersions, ' demo')) {
    fwrite(STDERR, "check-cluster-pins demo: installed.php wrong-SHA assertion did not fail.\n");
    exit(1);
}

$mutatedJsonRefs = $referencesFromJson;
$wrongJsonRef = flipLastHex(strtolower($expected['phpstan/phpstan']['reference']));
if ($wrongJsonRef === $wrongPhpRef) {
    $wrongJsonRef = flipLastHex($wrongJsonRef);
}
$mutatedJsonRefs['phpstan/phpstan'] = $wrongJsonRef;
if (checkInstalledJson($expected, $mutatedJsonRefs, ' demo')) {
    fwrite(STDERR, "check-cluster-pins demo: installed.json wrong-SHA assertion did not fail.\n");
    exit(1);
}

if (! checkInstalledPhp($expected, $versionsPhp, '')) {
    exit(1);
}

if (! checkInstalledJson($expected, $referencesFromJson, '')) {
    exit(1);
}

fwrite(STDOUT, "check-cluster-pins: all cluster packages match expected SHAs and aliases.\n");
exit(0);

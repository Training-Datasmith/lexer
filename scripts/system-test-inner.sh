#!/usr/bin/env bash
set -euo pipefail

export COMPOSER_ALLOW_SUPERUSER=1

BASELINE_TESTS=17
BASELINE_ASSERTIONS=90
EXPECTED_PHP_VERSION="8.1.34"

COMPOSER_URL="https://github.com/composer/composer/releases/download/2.11.1/composer.phar"
COMPOSER_SHA256="6f1b891e1d4927d62740276c540fb490e47689e19e102c49e8da849794882f8c"

fail_deprecation_output() {
    local label="$1"
    local file="$2"
    local stripped
    stripped="$(mktemp)"
    sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g' "${file}" | tr '\r' '\n' >"${stripped}"
    if grep -E '^(PHP[[:space:]]+)?(Warning|Deprecated|Notice):' "${stripped}" >/dev/null; then
        echo "${label}: output contains Warning, Deprecated, or Notice lines" >&2
        grep -E '^(PHP[[:space:]]+)?(Warning|Deprecated|Notice):' "${stripped}" >&2 || true
        rm -f "${stripped}"
        exit 1
    fi
    rm -f "${stripped}"
}

strip_for_gates() {
    local in="$1"
    local out="$2"
    sed -E 's/\x1b\[[0-9;]*[[:alpha:]]//g' "${in}" | tr '\r' '\n' >"${out}"
}

php -v | tee /tmp/php-version.txt
php -r "
\$v = PHP_VERSION;
if (\$v !== '${EXPECTED_PHP_VERSION}') {
    fwrite(STDERR, \"PHP {\$v} does not match required ${EXPECTED_PHP_VERSION}\n\");
    exit(1);
}
"

for ext in ctype dom json mbstring phar simplexml tokenizer xml xmlwriter; do
    if ! php -m | grep -qi "^${ext}$"; then
        echo "Missing required PHP extension: ${ext}" >&2
        exit 1
    fi
done

php scripts/check-no-legacy-camelcase.php

curl -fsSL -o /tmp/composer.phar "${COMPOSER_URL}"
echo "${COMPOSER_SHA256}  /tmp/composer.phar" | sha256sum -c -

git config --global --add safe.directory /app

composer_out="$(mktemp)"
set +e
php /tmp/composer.phar update --no-interaction --no-ansi --prefer-dist --no-progress >"${composer_out}" 2>&1
composer_status=$?
set -e
cat "${composer_out}"
fail_deprecation_output "composer update" "${composer_out}"
if [[ ${composer_status} -ne 0 ]]; then
    rm -f "${composer_out}"
    exit "${composer_status}"
fi
rm -f "${composer_out}"

php scripts/check-cluster-pins.php

audit_out="$(mktemp)"
set +e
php /tmp/composer.phar audit --no-ansi --no-interaction >"${audit_out}" 2>&1
audit_status=$?
set -e
cat "${audit_out}"
fail_deprecation_output "composer audit" "${audit_out}"
if [[ ${audit_status} -ne 0 ]]; then
    exit "${audit_status}"
fi
audit_stripped="$(mktemp)"
strip_for_gates "${audit_out}" "${audit_stripped}"
if ! grep -Fxq 'No security vulnerability advisories found.' "${audit_stripped}"; then
    echo 'composer audit: missing exact success line' >&2
    exit 1
fi
if grep -E '^Found [0-9]+ security vulnerability' "${audit_stripped}" >/dev/null; then
    echo 'composer audit: security advisories reported' >&2
    exit 1
fi
if grep -E '^Found [0-9]+ abandoned package' "${audit_stripped}" >/dev/null; then
    echo 'composer audit: abandoned packages reported' >&2
    exit 1
fi
echo "composer audit gate line: No security vulnerability advisories found."
rm -f "${audit_out}" "${audit_stripped}"

phpunit_out="$(mktemp)"
set +e
vendor/bin/phpunit --colors=never --no-coverage >"${phpunit_out}" 2>&1
phpunit_status=$?
set -e
cat "${phpunit_out}"
fail_deprecation_output "phpunit" "${phpunit_out}"
if [[ ${phpunit_status} -ne 0 ]]; then
    exit "${phpunit_status}"
fi
phpunit_stripped="$(mktemp)"
strip_for_gates "${phpunit_out}" "${phpunit_stripped}"
if grep -E '^(FAILURES!|ERRORS!|OK, but)' "${phpunit_stripped}" >/dev/null; then
    echo 'phpunit: disallowed summary' >&2
    exit 1
fi
if ! grep -E '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${phpunit_stripped}" >/dev/null; then
    echo 'phpunit: missing strict OK success line' >&2
    exit 1
fi
ok_line="$(grep -E '^OK \([0-9]+ tests?, [0-9]+ assertions?\)$' "${phpunit_stripped}" | tail -n 1)"
if ! [[ "${ok_line}" =~ ^OK\ \(([0-9]+)\ tests?,\ ([0-9]+)\ assertions?\)$ ]]; then
    echo "phpunit: could not parse success line: ${ok_line}" >&2
    exit 1
fi
tests="${BASH_REMATCH[1]}"
assertions="${BASH_REMATCH[2]}"
if [[ "${tests}" -ne "${BASELINE_TESTS}" ]]; then
    echo "phpunit: expected ${BASELINE_TESTS} tests (pre-rename baseline), got ${tests}" >&2
    exit 1
fi
if [[ "${assertions}" -ne "${BASELINE_ASSERTIONS}" ]]; then
    echo "phpunit: expected ${BASELINE_ASSERTIONS} assertions (pre-rename baseline), got ${assertions}" >&2
    exit 1
fi
echo "phpunit gate line: ${ok_line}"
rm -f "${phpunit_out}" "${phpunit_stripped}"

phpstan_out="$(mktemp)"
set +e
vendor/bin/phpstan analyse --memory-limit=1G --no-progress --no-ansi >"${phpstan_out}" 2>&1
phpstan_status=$?
set -e
cat "${phpstan_out}"
fail_deprecation_output "phpstan" "${phpstan_out}"
if [[ ${phpstan_status} -ne 0 ]]; then
    exit "${phpstan_status}"
fi
phpstan_stripped="$(mktemp)"
strip_for_gates "${phpstan_out}" "${phpstan_stripped}"
if ! grep -E '^ \[OK\] No errors *$' "${phpstan_stripped}" >/dev/null; then
    echo 'phpstan: missing [OK] No errors line' >&2
    exit 1
fi
phpstan_line="$(grep -E '^ \[OK\] No errors *$' "${phpstan_stripped}" | tail -n 1)"
echo "phpstan gate line: ${phpstan_line}"
rm -f "${phpstan_out}" "${phpstan_stripped}"

vendor/bin/phpcbf --no-colors >/dev/null 2>&1 || true

phpcs_out="$(mktemp)"
set +e
vendor/bin/phpcs --no-colors >"${phpcs_out}" 2>&1
phpcs_status=$?
set -e
cat "${phpcs_out}"
fail_deprecation_output "phpcs" "${phpcs_out}"
if [[ ${phpcs_status} -ne 0 ]]; then
    exit "${phpcs_status}"
fi
phpcs_stripped="$(mktemp)"
strip_for_gates "${phpcs_out}" "${phpcs_stripped}"
if grep -E '^ERROR:' "${phpcs_stripped}" >/dev/null; then
    echo 'phpcs: ERROR line present' >&2
    exit 1
fi
if grep -E '^A TOTAL OF [1-9]' "${phpcs_stripped}" >/dev/null; then
    echo 'phpcs: violations reported' >&2
    exit 1
fi
if ! grep -E '^Time: ([0-9]+ms|[0-9]+(\.[0-9]+)? secs|[0-9]+ mins(, [0-9]+(\.[0-9]+)? secs)?); Memory: [0-9]+(\.[0-9]+)?MB$' "${phpcs_stripped}" >/dev/null; then
    echo 'phpcs: missing Time/Memory success line' >&2
    exit 1
fi
phpcs_line="$(grep -E '^Time: ([0-9]+ms|[0-9]+(\.[0-9]+)? secs|[0-9]+ mins(, [0-9]+(\.[0-9]+)? secs)?); Memory: [0-9]+(\.[0-9]+)?MB$' "${phpcs_stripped}" | tail -n 1)"
echo "phpcs gate line: ${phpcs_line}"
rm -f "${phpcs_out}" "${phpcs_stripped}"

echo "check-cluster-pins gate line: check-cluster-pins: all cluster packages match expected SHAs and aliases."
echo "system-test: all steps passed (baseline ${BASELINE_TESTS} tests, ${BASELINE_ASSERTIONS} assertions)."

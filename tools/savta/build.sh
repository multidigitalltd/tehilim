#!/usr/bin/env bash
#
# Minifies the theme's CSS/JS and, with --zip, packages it for upload.
#
#   tools/savta/build.sh          # minify only
#   tools/savta/build.sh --zip    # minify, then package savta-al-hasafsal.zip
#
# The theme serves the readable sources when SCRIPT_DEBUG is on, so they stay
# in the package for debugging.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
THEME="$ROOT/savta-al-hasafsal"
ESBUILD="${ESBUILD:-npx --yes esbuild}"

echo "Minifying CSS…"
for file in "$THEME"/assets/css/*.css; do
	case "$file" in *.min.css) continue ;; esac
	out="${file%.css}.min.css"
	$ESBUILD "$file" --minify --loader:.css=css --outfile="$out" --log-level=warning
	printf '  %-12s %6s -> %6s\n' "$(basename "$file")" "$(wc -c < "$file")" "$(wc -c < "$out")"
done

echo "Minifying JS…"
for file in "$THEME"/assets/js/*.js; do
	case "$file" in *.min.js) continue ;; esac
	out="${file%.js}.min.js"
	$ESBUILD "$file" --minify --target=es2018 --outfile="$out" --log-level=warning
	printf '  %-12s %6s -> %6s\n' "$(basename "$file")" "$(wc -c < "$file")" "$(wc -c < "$out")"
done

if [ "${1:-}" = "--zip" ]; then
	echo "Packaging…"
	rm -f "$ROOT/savta-al-hasafsal.zip"
	( cd "$ROOT" && zip -rq savta-al-hasafsal.zip savta-al-hasafsal -x '*.DS_Store' -x 'savta-al-hasafsal/phpcs.xml.dist' )
	printf '  savta-al-hasafsal.zip  %s bytes\n' "$(wc -c < "$ROOT/savta-al-hasafsal.zip")"
fi

echo "Done."

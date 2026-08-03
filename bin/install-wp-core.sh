#!/usr/bin/env bash
set -euo pipefail

VERSION="${1:-latest}"
DEST="${2:-${RUNNER_TEMP:-/tmp}/wordpress}"

if [[ "${VERSION}" == "latest" ]]; then
	ARCHIVE_URL="https://wordpress.org/latest.tar.gz"
else
	ARCHIVE_URL="https://wordpress.org/wordpress-${VERSION}.tar.gz"
fi

if [[ -f "${DEST}/wp-settings.php" ]]; then
	echo "${DEST}"
	exit 0
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "${TMP_DIR}"' EXIT
curl -fsSL "${ARCHIVE_URL}" -o "${TMP_DIR}/wordpress.tar.gz"
mkdir -p "${DEST}"
tar -xzf "${TMP_DIR}/wordpress.tar.gz" -C "${DEST}" --strip-components=1
test -f "${DEST}/wp-settings.php"
echo "${DEST}"

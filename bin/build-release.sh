#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="quick-adsense"
ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
OUT_DIR="${1:-${ROOT}/build}"
VERSION="$( grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "${ROOT}/quick-adsense.php" )"
README_TAG="$( grep -oP '^Stable tag:\s*\K[0-9.]+' "${ROOT}/readme.txt" )"

if [[ "${VERSION}" != "${README_TAG}" ]]; then
	echo "Version mismatch: plugin header ${VERSION} vs readme ${README_TAG}" >&2
	exit 1
fi

mkdir -p "${OUT_DIR}"
ZIP_PATH="${OUT_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
git -C "${ROOT}" archive --format=zip --prefix="${PLUGIN_SLUG}/" -o "${ZIP_PATH}" HEAD
echo "Built ${ZIP_PATH}"

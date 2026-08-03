#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="quick-adsense"
ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
OUT_DIR="${1:-${ROOT}/build}"
VERSION="$( grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "${ROOT}/quick-adsense.php" )"
README_TAG="$( grep -oP '^Stable tag:\s*\K[0-9.]+' "${ROOT}/readme.txt" )"
STAGING_ROOT="$( mktemp -d )"

cleanup() {
	rm -rf "${STAGING_ROOT}"
}
trap cleanup EXIT

if [[ "${VERSION}" != "${README_TAG}" ]]; then
	echo "Version mismatch: plugin header ${VERSION} vs readme ${README_TAG}" >&2
	exit 1
fi

mkdir -p "${OUT_DIR}"
ZIP_PATH="${OUT_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
PACKAGE_ROOT="${STAGING_ROOT}/${PLUGIN_SLUG}"

mkdir -p "${PACKAGE_ROOT}"
rsync -a --exclude-from="${ROOT}/.distignore" "${ROOT}/" "${PACKAGE_ROOT}/"
rm -f "${ZIP_PATH}"
( cd "${STAGING_ROOT}" && zip -rq "${ZIP_PATH}" "${PLUGIN_SLUG}" )

ZIP_VERSION="$( unzip -p "${ZIP_PATH}" "${PLUGIN_SLUG}/quick-adsense.php" | grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' )"
ZIP_README_TAG="$( unzip -p "${ZIP_PATH}" "${PLUGIN_SLUG}/readme.txt" | grep -oP '^Stable tag:\s*\K[0-9.]+' )"

if [[ "${ZIP_VERSION}" != "${VERSION}" || "${ZIP_README_TAG}" != "${VERSION}" ]]; then
	echo "Built zip version mismatch: plugin header ${ZIP_VERSION} vs readme ${ZIP_README_TAG} vs expected ${VERSION}" >&2
	exit 1
fi

echo "Built ${ZIP_PATH}"

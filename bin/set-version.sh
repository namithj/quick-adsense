#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-}"
if [[ "${MODE}" == "--check" ]]; then
	VERSION="${2:-}"
else
	VERSION="${1:-}"
fi

if [[ ! "${VERSION}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Version must use x.y.z format." >&2
	exit 1
fi

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
HEADER="$( grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "${ROOT}/quick-adsense.php" )"
README="$( grep -oP '^Stable tag:\s*\K[0-9.]+' "${ROOT}/readme.txt" )"

if [[ "${MODE}" == "--check" ]]; then
	[[ "${VERSION}" == "${HEADER}" && "${VERSION}" == "${README}" ]]
	exit
fi

sed -i "s/^ \* Version: .*/ * Version: ${VERSION}/" "${ROOT}/quick-adsense.php"
sed -i "s/^Stable tag: .*/Stable tag: ${VERSION}/" "${ROOT}/readme.txt"

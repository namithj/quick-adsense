#!/usr/bin/env bash
set -euo pipefail

MODE="${1:-}"
if [[ "${MODE}" == "--check" ]]; then
	VERSION="${2:-}"
elif [[ "${MODE}" == "--check-current" ]]; then
	VERSION=''
else
	VERSION="${1:-}"
fi

if [[ "${MODE}" != "--check-current" && ! "${VERSION}" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Version must use x.y.z format." >&2
	exit 1
fi

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
HEADER="$( grep -oP '^\s*\*\s*Version:\s*\K[0-9.]+' "${ROOT}/quick-adsense.php" )"
README="$( grep -oP '^Stable tag:\s*\K[0-9.]+' "${ROOT}/readme.txt" )"

replace_in_file() {
	local pattern="$1"
	local file="$2"
	local tmp

	tmp="$( mktemp )"
	sed "${pattern}" "${file}" > "${tmp}"
	cat "${tmp}" > "${file}"
	rm -f "${tmp}"
}

check_synced_versions() {
	local expected_version="$1"

	if [[ -n "${expected_version}" && ( "${expected_version}" != "${HEADER}" || "${expected_version}" != "${README}" ) ]]; then
		printf 'Version mismatch: tag=%s, plugin header=%s, readme stable tag=%s\n' \
			"${expected_version}" "${HEADER}" "${README}" >&2
		exit 1
	fi

	if [[ "${HEADER}" != "${README}" ]]; then
		printf 'Version mismatch: plugin header=%s, readme stable tag=%s\n' \
			"${HEADER}" "${README}" >&2
		exit 1
	fi
}

if [[ "${MODE}" == "--check" ]]; then
	check_synced_versions "${VERSION}"
	exit 0
fi

if [[ "${MODE}" == "--check-current" ]]; then
	check_synced_versions ''
	exit 0
fi

replace_in_file "s/^ \\* Version: .*/ * Version: ${VERSION}/" "${ROOT}/quick-adsense.php"
replace_in_file "s/^Stable tag: .*/Stable tag: ${VERSION}/" "${ROOT}/readme.txt"
bash "${BASH_SOURCE[0]}" --check "${VERSION}"

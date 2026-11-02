# Quick AdSense characterization tests

Run the dependency-free compatibility suite from the plugin directory:

```sh
php tests/characterization.php
```

It executes the plugin's real settings-control and frontend rendering code with a
minimal WordPress shim. The shim deliberately treats the representative
compatible payload as KSES-safe: this suite characterizes plugin flow and payload
fidelity, not WordPress core sanitization.
HTML, JavaScript, quotes, and backslashes through the header, footer, quicktag,
automatic insertion, and widget paths.

## User-config regression suite (R00)

Twenty realistic site configurations are run through the plugin and compared
with golden-master snapshots taken on v2.9.4:

```sh
composer test:configs   # or: bin/test-user-configs.sh
```

It installs a throwaway WordPress core into `.cache/wp-core/` and never touches
the real site. See [user-configs/README.md](user-configs/README.md) for the
config format, the coverage table and the snapshot policy.

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

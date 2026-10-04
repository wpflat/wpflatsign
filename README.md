# BizyDoc

Contracts and agreements with e-signature, sealed PDFs, an audit trail and a verification page, for the Bizy suite.

Needs BizyCore.

Part of the **Bizy** suite (BizyCore, BizyTasks, BizyMoney, BizyDoc, BizyFiles). Each plugin has its own repository and installs on its own; the modules need BizyCore.

## Install

Download or clone this repository, put the `bizydoc` folder in `wp-content/plugins/`, and activate it (activate BizyCore first — this plugin refuses to start without it). The built scripts in `build/` are committed, so nothing needs compiling to use it.

## Develop

```
npm install
npm run build      # rebuilds build/ from ui/
```

PHP lives in `includes/`, the Vue source in `ui/`. The shared test suites and deploy tools live in the Bizy workspace (`bizy/` next to this folder).

License: GPL-2.0-or-later.

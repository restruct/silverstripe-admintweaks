# README screenshots

`readme.shot.ts` writes the PNGs in `docs/images/` that the module README shows. It is not a test
suite: it runs with its own config, `playwright.config.ts` in this directory, which the browser
tests and CI never load (they run `../playwright.config.ts`, whose `testDir` is `specs/`), so a
normal test run never rewrites the images. Never edit the PNGs by hand; regenerate them.

What it needs:

- a Silverstripe 6 host with this module installed and the browser fixtures from
  `tests/browser/fixtures/` loaded (PHP classes in `app/src/`, `fixtures/_config/*.yml` in
  `app/_config/`), then the database built. The fixtures supply the content: the system switcher
  config (`Example SITE`, `DOCS`, `LMS`), the fixture ModelAdmins and `AtBRecord` with its
  CopyTextField. Pages and groups are the ones a fresh `recipe-cms` install creates;
- `BROWSER_TARGET_URLS` pointing at that host (only the `ss6` target is used, else the first).

The shots hide what only the test host has (the fixture admins' menu entries and permissions, the
switcher's escaping test entry); nothing about the module's own UI is changed.

The shared runner in the maintainers' workspace does all of that:

```bash
BROWSER_ONLY=ss6 tools/browser/run.sh silverstripe-admintweaks --config=screenshots/playwright.config.ts
```

Against a host you started yourself, from `tests/browser/`:

```bash
npm ci
BROWSER_TARGET_URLS="ss6=http://127.0.0.1:8862" npx playwright test --config=screenshots/playwright.config.ts
```

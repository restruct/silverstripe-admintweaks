# Browser-test targets for this module, sourced by the shared runner
# (~/Sites/0_ss-mods-maintenance/tools/browser/run.sh) and by .github/workflows/browser-tests.yml.
# Plain bash assignments only. CI tests only the targets with an empty SS<n>_SRC_REF (= this
# checkout).
# Ports are assigned in ~/Sites/0_ss-mods-maintenance/tools/browser/PORTS.md; take new ones there.

BROWSER_PACKAGE="restruct/silverstripe-admintweaks"
BROWSER_TARGETS="ss5"

# This branch (v3, 3.x) requires framework ^4 | ^5. SS5 is its browser target; SS4 is past EOL and
# not a target. SS6 is served by main (4.x), which carries its own copy of these tests.
SS5_RECIPE="^5"
SS5_PHP="8.3"
SS5_PORT="8861"
SS5_SRC_REF=""

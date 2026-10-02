# Browser-test targets for this module, sourced by the shared runner
# (~/Sites/0_ss-mods-maintenance/tools/browser/run.sh) and by .github/workflows/browser-tests.yml.
# Plain bash assignments only. CI tests only the targets with an empty SS<n>_SRC_REF (= this
# checkout).
# Ports are assigned in ~/Sites/0_ss-mods-maintenance/tools/browser/PORTS.md; take new ones there.

BROWSER_PACKAGE="restruct/silverstripe-admintweaks"
BROWSER_TARGETS="ss5 ss6"

# main (4.x) requires framework ^6, so it serves SS6 only. SS5 is served by the v3 line
# (3.x, framework ^4 | ^5), which carries its own copy of these tests with both refs empty; from
# here the runner tests it through a worktree of origin/v3, and CI on main skips it.
SS5_RECIPE="^5"
SS5_PHP="8.3"
SS5_PORT="8861"
SS5_SRC_REF="origin/v3"

SS6_RECIPE="^6"
SS6_PHP="8.3"
SS6_PORT="8862"
SS6_SRC_REF=""

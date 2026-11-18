# Tasks

Build tasks for repairing a migrated asset store, and two developer tools. They run as
`sake dev/tasks/<name>` (or at `/dev/tasks/<name>` in a browser, as an admin), with options as
`name=value` arguments. On the 4.x line (Silverstripe 6) the invocations changed to
`sake tasks:<name> --option`.

## Repair tasks for a site migrated to Silverstripe 5

Three tasks that fix asset artifacts left behind by a migration from Silverstripe 3 or 4. All are
**dry-run by default**; pass `apply=1` to write. Run them in this order:

| # | Task | Fixes |
|---|------|-------|
| 1 | `sake dev/tasks/fix-folder-filefilename` | `/admin/assets` dies with **`HashFileIDHelper::buildFileID requires an $hash value`**. Folder rows must have an EMPTY `FileFilename` (a folder derives its path from the parent chain); a populated value makes the folder's `visibility` lookup build a file ID with no hash. Also reports empty-`FileHash` File rows, which trip the same exception. |
| 2 | `sake dev/tasks/fix-misclassified-images` | Image files carried over with `ClassName = File` instead of `Image`. Blank tiles in the asset-admin grid, but more importantly, a `has_one` **Image** relation pointing at such a record renders **nothing on the front end**, because `Fill()`/`FitMax()`/`ScaleWidth()` don't exist on `File`. Resolves the target class via `File::get_class_for_file_extension()`, so `.svg` correctly becomes your registered SVG image class rather than `Image`. |
| 3 | `sake dev/tasks/generate-cms-thumbnails` | Blank tiles in the asset-admin file grid. The grid is served by GraphQL and asset-admin deliberately injects a **non-generating** thumbnail generator (`ThumbnailGenerator.graphql` -> `Generates: false`), so it emits the `__FitMax[...]` URL but never creates the variant. Normal uploads generate variants on save; migrated files never went through SS5, so their variants don't exist and the `<img>` 404s. SS4 ran `ImageThumbnailHelper` inside `MigrateFileTask`; **SS5 removed the task but kept the helper**. This runs it. |

```bash
sake dev/tasks/fix-folder-filefilename            # report only
sake dev/tasks/fix-folder-filefilename apply=1    # write
```

Order matters: (2) before (3), because the thumbnail helper skips anything whose `getIsImage()` is
false; a file still stuck on `ClassName = File` would be passed over.

Prerequisite for (3): the files must actually be migrated (a populated `FileHash`). On a database
where `FileHash` is empty there are no stored bytes to make a thumbnail from, and the task will
correctly report that it generated nothing.

Task (3) requires `silverstripe/asset-admin`; it hides itself if that isn't installed.

## Developer tasks

### orm-query: read-only ORM lookups

Runs on the command line in dev mode only.

```bash
sake dev/tasks/orm-query class=Member limit=5
sake dev/tasks/orm-query class=Member "filter[Email:PartialMatch]=example" fields=ID,Email
sake dev/tasks/orm-query class=Page sql=1
sake dev/tasks/orm-query class=File schema=1
```

Arguments: `class`, `filter[...]`, `exclude[...]`, `where`, `fields`, `sort`, `limit`, `count`,
`groupBy`, `sum`, `avg`, `min`, `max`, `sql=1` (show the query) and `schema=1` (show the class's
fields, relations and extensions). The class docblock in `src/Dev/OrmQueryTask.php` has more
examples.

### qjob-profile: what does a queued job accumulate?

```bash
sake dev/tasks/qjob-profile class='My\Jobs\SyncJob' maxcalls=50
```

Runs a queued job in-process and reports memory, step, jobData size and message count per
`process()` call. Dev only: it really runs the job. Deprecated: it moved to
[queuedjobs-enhancements](https://github.com/restruct/silverstripe-queuedjobs-enhancements) 1.7.0
under the same name, and this copy switches itself off when that is installed.

### FocusPointInvertYaxisTask

```bash
sake dev/tasks/FocusPointInvertYaxisTask
```

Inverts all FocusPoint Y values (FocusPoint v2 to v3), for when the FocusPoint module's own
migration did not run or went wrong. It has no dry run: it writes straight away, to the draft,
live and versions tables, and running it twice inverts the values back. Without the FocusPoint
columns it reports that there is nothing to do.

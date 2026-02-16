# Fix FileLocalPathExtension::getLocalPath() — Use Framework Resolution

## Problem

`FileLocalPathExtension::getLocalPath()` manually constructed filesystem paths by combining the protected/public root with `dirname`, `hash prefix (10 chars)`, and `basename` from the File record. This hardcoded approach didn't match what the framework actually does to find files on disk.

The framework uses `FileIDHelperResolutionStrategy::searchForTuple()` which tries multiple `FileIDHelper` implementations (Hash, Natural) and performs DB lookups when hashes are missing. The manual construction only covered the simple `{dir}/{hash10}/{basename}` pattern.

**Symptom:** `getLocalPath()` returned `null` for files that the framework (via `$file->getString()`, admin file preview, etc.) could read and serve without issue.

**Root cause:** Path resolution logic was replicated manually instead of delegating to the framework's resolution system which handles all edge cases.

## Solution

Rewrote `getLocalPath()` to use the framework's own `FlysystemAssetStore` → `FileResolutionStrategy::searchForTuple()` → `LocalFilesystemAdapter::prefixPath()` chain. This is the same pattern used by `FlysystemAssetStore::getAsURL()`.

### Key Framework API Chain

```
FlysystemAssetStore (AssetStore singleton)
├── getPublicFilesystem() → Filesystem
│   ├── getAdapter() → PublicAssetAdapter (extends LocalFilesystemAdapter)
│   │   └── prefixPath(fileID) → absolute path
│   └── has(fileID) → bool
├── getProtectedFilesystem() → Filesystem
│   ├── getAdapter() → ProtectedAssetAdapter (extends LocalFilesystemAdapter)
│   │   └── prefixPath(fileID) → absolute path
│   └── has(fileID) → bool
├── getPublicResolutionStrategy() → FileIDHelperResolutionStrategy
│   └── searchForTuple(ParsedFileID, Filesystem) → ParsedFileID|null
└── getProtectedResolutionStrategy() → FileIDHelperResolutionStrategy
    └── searchForTuple(ParsedFileID, Filesystem) → ParsedFileID|null
```

`LocalFilesystemAdapter::prefixPath()` is a public method added by SilverStripe (not in upstream Flysystem) at `vendor/silverstripe/assets/src/Flysystem/LocalFilesystemAdapter.php:26`.

### What was removed

- `resolveProtectedAssetsPath()` — no longer needed; the framework knows where its own filesystems are rooted
- `$has_warned_relative_path` static — the relative path warning is no longer our concern
- All manual candidate path construction

### What was added

- `resolveOnFilesystem()` private helper — delegates to `FileResolutionStrategy::searchForTuple()` and `LocalFilesystemAdapter::prefixPath()`
- Checks protected filesystem first, then public (protected is more common for CLI tool use cases)
- Graceful null return for non-Flysystem stores (e.g. mocks in tests) and non-local adapters (e.g. S3)

## Related Issue: signed-asset-urls

`computeOnDiskPath()` in `restruct/silverstripe-signed-asset-urls` has the same hardcoded path construction issue. It builds `{dirname}/{hashPrefix}/{basename}` for web server handoff (X-Sendfile/X-Accel-Redirect). The PHP streaming fallback uses `$store->getAsStream()` which goes through framework resolution and works correctly — only the web server handoff path is affected.

The fix would mirror this approach: use framework resolution to get the resolved fileID, then `prefixPath()` for the absolute path.

## When to use getLocalPath() vs alternatives

| Method | Use when |
|--------|----------|
| `$file->getLocalPath()` | Need absolute path for CLI tools (cpdf, pdftotext, etc.) |
| `$file->getString()` | Need file content as string (reading, processing) |
| `$file->getStream()` | Need streaming access (large files, HTTP response) |
| `$file->getURL()` | Need web-accessible URL |

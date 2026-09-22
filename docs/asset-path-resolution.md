# Asset Path Resolution in SilverStripe

How SilverStripe stores files on disk, how the framework resolves them, and how `getLocalPath()` hooks into the framework's resolution system.

## How SS Stores Files on Disk

SilverStripe uses two filesystems for asset storage:

### Protected store (default for new uploads)
Files not yet published, or files that should remain protected (e.g. restricted_assets).

```
{protected_root}/{dirname}/{hash10}/{basename}
```

Example:
```
restricted_assets/Uploads/33b1d9f841/document.pdf
```

### Public store
Files that have been published to the public webroot.

```
public/assets/{dirname}/{hash10}/{basename}   (hash path)
public/assets/{dirname}/{basename}             (natural/legacy path)
```

### File ID Helpers

The framework uses `FileIDHelper` implementations to generate and parse file IDs:

| Helper | Pattern | Example |
|--------|---------|---------|
| `HashFileIDHelper` | `{dir}/{hash10}/{basename}` | `Uploads/33b1d9f841/doc.pdf` |
| `NaturalFileIDHelper` | `{dir}/{basename}` | `Uploads/doc.pdf` |

## How the Framework Resolves Files

When SilverStripe needs to find a file on disk, it uses `FileResolutionStrategy::searchForTuple()`:

```
FlysystemAssetStore
├── getProtectedFilesystem() → Filesystem
├── getPublicFilesystem() → Filesystem
├── getProtectedResolutionStrategy() → FileIDHelperResolutionStrategy
└── getPublicResolutionStrategy() → FileIDHelperResolutionStrategy
```

`FileIDHelperResolutionStrategy::searchForTuple()` does:
1. Takes a `ParsedFileID` (filename + hash)
2. Tries each configured `FileIDHelper` to generate candidate file IDs
3. Checks if the candidate exists on the filesystem (`$filesystem->has()`)
4. If hash is missing, performs a DB lookup to find it
5. Returns a `ParsedFileID` with the resolved `fileID`, or `null`

### Getting the absolute path

Once you have a resolved `fileID`, use `LocalFilesystemAdapter::prefixPath()` to get the absolute filesystem path:

```php
$adapter = $filesystem->getAdapter();
// $adapter is LocalFilesystemAdapter (PublicAssetAdapter or ProtectedAssetAdapter)
$absolutePath = $adapter->prefixPath($resolved->getFileID());
```

`prefixPath()` is a public method added by SilverStripe's `LocalFilesystemAdapter` (not in upstream Flysystem).

## How getLocalPath() Works

`FileLocalPathExtension::getLocalPath()` delegates entirely to the framework:

```php
$store = Injector::inst()->get(AssetStore::class);
$parsedFileID = new ParsedFileID($filename, $hash);

// Try protected filesystem first, then public
$resolved = $strategy->searchForTuple($parsedFileID, $filesystem);
$path = $adapter->prefixPath($resolved->getFileID());
```

This handles all storage layouts automatically — hash paths, natural paths, and any future FileIDHelper implementations.

## When to Use What

| Method | Use case | Notes |
|--------|----------|-------|
| `$file->getLocalPath()` | Need absolute path for CLI tools | cpdf, pdftotext, wkhtmltopdf, etc. |
| `$file->getString()` | Need file content as string | Reads through AssetStore abstraction |
| `$file->getStream()` | Need streaming access | Large files, HTTP responses |
| `$file->getURL()` | Need web-accessible URL | For browser/frontend use |
| `$file->getAbsoluteURL()` | Need full URL with domain | External links, emails |

### Why getLocalPath() exists

The framework's `AssetStore` interface provides `getString()` and `getStream()` for reading file content. But some use cases need the actual filesystem path:

- **External CLI tools** that take file paths as arguments (not stdin)
- **File operations** that need direct filesystem access (e.g. `filesize()`, `filemtime()`)
- **Web server handoff** (X-Sendfile, X-Accel-Redirect) where the web server reads the file directly

For all other cases, prefer `getString()` or `getStream()` — they work with any AssetStore backend (local, S3, etc.).

## Testing getLocalPath()

There are no PHPUnit tests for `getLocalPath()` — it requires a fully bootstrapped SilverStripe environment with actual filesystem adapters. Use the manual checklist below after making changes.

### Manual Testing Checklist

#### 1. Build verification

```bash
vendor/bin/sake dev/build flush=1
```

Should complete without errors.

#### 2. Verify resolution for known files

```bash
# Find files with known hashes
vendor/bin/sake dev/tasks/orm-query class=File "filter[FileHash:not]=" limit=5 fields=ID,FileFilename,FileHash

# Check which storage layout is used on disk
ls {protected_folder}/{dirname}/{hash10}/{basename}   # hash path
ls {protected_folder}/{dirname}/{basename}             # natural path
ls public/assets/{dirname}/{hash10}/{basename}         # public hash path
ls public/assets/{dirname}/{basename}                  # public natural path
```

`getLocalPath()` should return the correct absolute path regardless of which layout exists on disk.

#### 3. Test in PHP (sake or controller)

```php
$file = File::get()->filter('FileHash:not', '')->first();
$path = $file->getLocalPath();

// Should return absolute path like:
// /path/to/project/restricted_assets/Uploads/abc1234567/document.pdf
// Should return null for files not on local filesystem
```

#### 4. Verify both stores are checked

`getLocalPath()` tries protected first, then public. To verify both paths work:

- Find a protected file (in `restricted_assets/` or `.protected/`) — should resolve
- Find a published file (in `public/assets/`) — should also resolve
- A file that exists in neither store — should return `null`

### What is NOT covered by automated tests

`getLocalPath()` delegates to `FileResolutionStrategy::searchForTuple()` + `LocalFilesystemAdapter::prefixPath()`. These framework methods are tested by SilverStripe's own test suite. The extension's role is simply to wire them together and check both filesystems, which is verified manually.

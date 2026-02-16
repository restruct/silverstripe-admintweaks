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

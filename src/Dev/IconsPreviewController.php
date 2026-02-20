<?php

namespace Restruct\Silverstripe\AdminTweaks\Dev;

use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\HTTPResponse;

/**
 * Preview all curated Bootstrap Icons available in the admin.
 *
 * Access at: /admin/admintweaks-icons
 * Hidden from CMS menu — dev tool only.
 */
class IconsPreviewController extends LeftAndMain
{
    private static $url_segment = 'admintweaks-icons';

    private static $menu_title = 'Bootstrap Icons';

    private static $allowed_actions = [
        'index' => 'CMS_ACCESS_CMSMain',
    ];

    # Hide from CMS menu — dev tool only
    private static $ignore_menuitem = true;

    public function index($request): HTTPResponse
    {
        return HTTPResponse::create($this->renderIconsPage())
            ->addHeader('Content-Type', 'text/html; charset=utf-8');
    }

    protected function renderIconsPage(): string
    {
        $icons = $this->getIconCategories();

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bootstrap Icons - SilverStripe Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
            color: #333;
        }
        h1 { margin: 0 0 5px; }
        .subtitle { color: #666; margin-bottom: 20px; }
        .subtitle a { color: #0d6efd; }
        h2 {
            margin: 25px 0 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #0d6efd;
            color: #0d6efd;
            font-size: 1.1rem;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 8px;
        }
        .icon-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px 6px;
            text-align: center;
            transition: all 0.15s;
            cursor: pointer;
        }
        .icon-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(13, 110, 253, 0.15);
        }
        .icon-item i {
            font-size: 22px;
            display: block;
            margin-bottom: 6px;
            color: #333;
        }
        .icon-item:hover i { color: #0d6efd; }
        .icon-item code {
            font-size: 9px;
            color: #666;
            word-break: break-all;
            display: block;
        }
        .search-box {
            position: sticky;
            top: 0;
            background: #f5f5f5;
            padding: 10px 0 15px;
            z-index: 100;
        }
        .search-box input {
            width: 100%;
            max-width: 350px;
            padding: 8px 12px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 5px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #0d6efd;
        }
        .count { color: #888; font-size: 13px; font-weight: normal; }
        .hidden { display: none !important; }
        .copy-toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #198754;
            color: white;
            padding: 10px 16px;
            border-radius: 5px;
            opacity: 0;
            transition: opacity 0.2s;
            pointer-events: none;
            font-size: 14px;
        }
        .copy-toast.show { opacity: 1; }
        .back-link { margin-bottom: 15px; }
        .back-link a { color: #0d6efd; text-decoration: none; font-size: 14px; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="back-link"><a href="/admin">&larr; Back to Admin</a></div>
    <h1>Bootstrap Icons <span class="count" id="total-count"></span></h1>
    <p class="subtitle">Curated selection for SilverStripe Admin &bull; Click to copy class &bull; <a href="https://icons.getbootstrap.com/" target="_blank">Full browser</a></p>

    <div class="search-box">
        <input type="text" id="search" placeholder="Search icons..." autofocus>
    </div>

    <div id="icons-container"></div>
    <div class="copy-toast" id="toast">Copied!</div>

    <script>
        const icons = {$this->getIconsJson()};

        const container = document.getElementById('icons-container');
        const searchInput = document.getElementById('search');
        const toast = document.getElementById('toast');
        const totalCount = document.getElementById('total-count');
        let total = 0;

        for (const [category, iconList] of Object.entries(icons)) {
            total += iconList.length;
            const section = document.createElement('div');
            section.className = 'category';
            section.innerHTML = '<h2>' + category + ' <span class="count">(' + iconList.length + ')</span></h2>';

            const grid = document.createElement('div');
            grid.className = 'grid';

            for (const icon of iconList) {
                const item = document.createElement('div');
                item.className = 'icon-item';
                item.dataset.name = icon;
                item.innerHTML = '<i class="bi bi-' + icon + '"></i><code>bi bi-' + icon + '</code>';
                item.onclick = function() { copyToClipboard('bi bi-' + icon); };
                grid.appendChild(item);
            }

            section.appendChild(grid);
            container.appendChild(section);
        }

        totalCount.textContent = '(' + total + ' icons)';

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase();
            document.querySelectorAll('.icon-item').forEach(function(item) {
                item.classList.toggle('hidden', !item.dataset.name.includes(query));
            });
            document.querySelectorAll('.category').forEach(function(cat) {
                const visible = cat.querySelectorAll('.icon-item:not(.hidden)').length;
                cat.classList.toggle('hidden', visible === 0);
            });
        });

        function copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 1500);
        }
    </script>
</body>
</html>
HTML;

        return $html;
    }

    protected function getIconsJson(): string
    {
        return json_encode($this->getIconCategories(), JSON_PRETTY_PRINT);
    }

    protected function getIconCategories(): array
    {
        return [
            "Actions - Add/Remove" => [
                "plus", "plus-lg", "plus-circle", "plus-circle-fill", "plus-square", "plus-square-fill",
                "dash", "dash-lg", "dash-circle", "dash-circle-fill", "dash-square",
                "x", "x-lg", "x-circle", "x-circle-fill", "x-square", "x-square-fill"
            ],
            "Actions - Confirm/Check" => [
                "check", "check-lg", "check-circle", "check-circle-fill", "check-square", "check-square-fill",
                "check-all", "check2", "check2-all", "check2-circle", "check2-square",
                "patch-check", "patch-check-fill"
            ],
            "Edit/CRUD" => [
                "pencil", "pencil-fill", "pencil-square", "pen", "pen-fill", "vector-pen",
                "brush", "brush-fill", "eraser", "eraser-fill",
                "trash", "trash-fill", "trash2", "trash2-fill", "trash3", "trash3-fill"
            ],
            "Clipboard/Copy" => [
                "copy", "clipboard", "clipboard-fill", "clipboard-check", "clipboard-check-fill",
                "clipboard-data", "clipboard-data-fill", "clipboard-plus", "clipboard-plus-fill",
                "clipboard-minus", "clipboard-minus-fill", "clipboard-x", "clipboard-x-fill",
                "clipboard2", "clipboard2-check", "clipboard2-check-fill", "clipboard2-data", "clipboard2-data-fill",
                "clipboard-pulse", "clipboard-heart", "clipboard-heart-fill"
            ],
            "Navigation - Arrows" => [
                "arrow-left", "arrow-right", "arrow-up", "arrow-down",
                "arrow-left-circle", "arrow-right-circle", "arrow-up-circle", "arrow-down-circle",
                "arrow-left-square", "arrow-right-square", "arrow-up-square", "arrow-down-square",
                "arrow-bar-left", "arrow-bar-right", "arrow-bar-up", "arrow-bar-down",
                "arrow-return-left", "arrow-return-right", "arrow-repeat", "arrow-clockwise", "arrow-counterclockwise",
                "arrows-move", "arrows-expand", "arrows-collapse", "arrows-fullscreen",
                "arrows-angle-expand", "arrows-angle-contract", "arrow-left-right", "arrow-down-up", "arrows"
            ],
            "Navigation - Chevrons/Carets" => [
                "chevron-left", "chevron-right", "chevron-up", "chevron-down",
                "chevron-double-left", "chevron-double-right", "chevron-double-up", "chevron-double-down",
                "chevron-expand", "chevron-contract",
                "chevron-bar-left", "chevron-bar-right", "chevron-bar-up", "chevron-bar-down",
                "caret-left", "caret-left-fill", "caret-right", "caret-right-fill",
                "caret-up", "caret-up-fill", "caret-down", "caret-down-fill"
            ],
            "Files - Generic" => [
                "file", "file-fill", "file-earmark", "file-earmark-fill",
                "file-text", "file-text-fill", "file-earmark-text", "file-earmark-text-fill",
                "file-richtext", "file-richtext-fill", "file-earmark-richtext", "file-earmark-richtext-fill",
                "file-plus", "file-plus-fill", "file-earmark-plus", "file-earmark-plus-fill",
                "file-minus", "file-minus-fill", "file-earmark-minus", "file-earmark-minus-fill",
                "file-check", "file-check-fill", "file-earmark-check", "file-earmark-check-fill",
                "file-x", "file-x-fill", "file-earmark-x", "file-earmark-x-fill",
                "file-diff", "file-diff-fill", "file-earmark-diff", "file-earmark-diff-fill",
                "files", "files-alt"
            ],
            "Files - Types" => [
                "file-pdf", "file-pdf-fill", "file-earmark-pdf", "file-earmark-pdf-fill",
                "file-image", "file-image-fill", "file-earmark-image", "file-earmark-image-fill",
                "file-code", "file-code-fill", "file-earmark-code", "file-earmark-code-fill",
                "file-music", "file-music-fill", "file-earmark-music", "file-earmark-music-fill",
                "file-play", "file-play-fill", "file-earmark-play", "file-earmark-play-fill",
                "file-zip", "file-zip-fill", "file-earmark-zip", "file-earmark-zip-fill",
                "file-word", "file-word-fill", "file-earmark-word", "file-earmark-word-fill",
                "file-excel", "file-excel-fill", "file-earmark-excel", "file-earmark-excel-fill",
                "file-ppt", "file-ppt-fill", "file-earmark-ppt", "file-earmark-ppt-fill",
                "file-spreadsheet", "file-spreadsheet-fill", "file-earmark-spreadsheet", "file-earmark-spreadsheet-fill",
                "file-binary", "file-binary-fill", "file-earmark-binary", "file-earmark-binary-fill",
                "file-font", "file-font-fill", "file-earmark-font", "file-earmark-font-fill"
            ],
            "Folders" => [
                "folder", "folder-fill", "folder2", "folder2-open",
                "folder-plus", "folder-minus", "folder-check", "folder-x",
                "folder-symlink", "folder-symlink-fill"
            ],
            "Media - Images" => [
                "image", "image-fill", "image-alt", "images", "card-image",
                "camera", "camera-fill", "camera2", "camera-video", "camera-video-fill"
            ],
            "Media - Video/Audio" => [
                "film", "play", "play-fill", "play-btn", "play-btn-fill", "play-circle", "play-circle-fill",
                "pause", "pause-fill", "pause-btn", "pause-btn-fill", "pause-circle", "pause-circle-fill",
                "stop", "stop-fill", "stop-btn", "stop-btn-fill", "stop-circle", "stop-circle-fill",
                "skip-start", "skip-start-fill", "skip-end", "skip-end-fill",
                "skip-backward", "skip-backward-fill", "skip-forward", "skip-forward-fill",
                "volume-up", "volume-up-fill", "volume-down", "volume-down-fill",
                "volume-mute", "volume-mute-fill", "volume-off", "volume-off-fill",
                "music-note", "music-note-beamed", "music-note-list"
            ],
            "View - Zoom/Fullscreen" => [
                "zoom-in", "zoom-out", "fullscreen", "fullscreen-exit",
                "aspect-ratio", "aspect-ratio-fill", "pip", "pip-fill",
                "easel", "easel-fill", "easel2", "easel2-fill", "easel3", "easel3-fill"
            ],
            "Search/Filter/Sort" => [
                "search", "search-heart", "search-heart-fill",
                "filter", "filter-circle", "filter-circle-fill", "filter-square", "filter-square-fill",
                "funnel", "funnel-fill",
                "sort-down", "sort-down-alt", "sort-up", "sort-up-alt",
                "sort-alpha-down", "sort-alpha-down-alt", "sort-alpha-up", "sort-alpha-up-alt",
                "sort-numeric-down", "sort-numeric-down-alt", "sort-numeric-up", "sort-numeric-up-alt"
            ],
            "List/Grid/Layout" => [
                "list", "list-ul", "list-ol", "list-check", "list-task", "list-nested",
                "list-columns", "list-columns-reverse", "list-stars",
                "grid", "grid-fill", "grid-3x2", "grid-3x2-gap", "grid-3x2-gap-fill",
                "grid-3x3", "grid-3x3-gap", "grid-3x3-gap-fill", "grid-1x2", "grid-1x2-fill",
                "layout-text-sidebar", "layout-text-sidebar-reverse", "layout-text-window", "layout-text-window-reverse",
                "layout-sidebar", "layout-sidebar-reverse", "layout-split", "layout-three-columns",
                "columns", "columns-gap", "table", "kanban", "kanban-fill"
            ],
            "Menu/Options" => [
                "three-dots", "three-dots-vertical", "grip-horizontal", "grip-vertical",
                "border-all", "border-center", "border-inner", "border-outer", "border-width"
            ],
            "Status - Info/Warning/Error" => [
                "info", "info-lg", "info-circle", "info-circle-fill", "info-square", "info-square-fill",
                "question", "question-lg", "question-circle", "question-circle-fill", "question-square", "question-square-fill",
                "exclamation", "exclamation-lg", "exclamation-circle", "exclamation-circle-fill",
                "exclamation-square", "exclamation-square-fill", "exclamation-triangle", "exclamation-triangle-fill",
                "exclamation-octagon", "exclamation-octagon-fill", "exclamation-diamond", "exclamation-diamond-fill",
                "x-octagon", "x-octagon-fill", "x-diamond", "x-diamond-fill",
                "slash-circle", "slash-circle-fill", "ban", "ban-fill"
            ],
            "Notifications" => [
                "bell", "bell-fill", "bell-slash", "bell-slash-fill", "app-indicator", "activity"
            ],
            "People/Users" => [
                "person", "person-fill", "person-circle", "person-square",
                "person-plus", "person-plus-fill", "person-dash", "person-dash-fill",
                "person-x", "person-x-fill", "person-check", "person-check-fill",
                "person-gear", "person-lock", "person-badge", "person-badge-fill",
                "person-lines-fill", "person-vcard", "person-vcard-fill",
                "people", "people-fill", "person-walking", "person-workspace"
            ],
            "Settings/Tools" => [
                "gear", "gear-fill", "gear-wide", "gear-wide-connected", "gears",
                "sliders", "sliders2", "sliders2-vertical",
                "wrench", "wrench-adjustable", "wrench-adjustable-circle", "wrench-adjustable-circle-fill",
                "tools", "hammer", "screwdriver", "nut", "nut-fill"
            ],
            "Communication - Email" => [
                "envelope", "envelope-fill", "envelope-open", "envelope-open-fill",
                "envelope-at", "envelope-at-fill", "envelope-plus", "envelope-plus-fill",
                "envelope-check", "envelope-check-fill", "envelope-x", "envelope-x-fill",
                "envelope-exclamation", "envelope-exclamation-fill", "envelope-paper", "envelope-paper-fill"
            ],
            "Communication - Chat" => [
                "chat", "chat-fill", "chat-dots", "chat-dots-fill",
                "chat-left", "chat-left-fill", "chat-left-dots", "chat-left-dots-fill",
                "chat-left-text", "chat-left-text-fill", "chat-right", "chat-right-fill",
                "chat-right-dots", "chat-right-dots-fill", "chat-square", "chat-square-fill",
                "chat-square-dots", "chat-square-dots-fill", "chat-square-text", "chat-square-text-fill",
                "chat-text", "chat-text-fill", "chat-quote", "chat-quote-fill",
                "send", "send-fill", "reply", "reply-fill", "reply-all", "reply-all-fill", "forward", "forward-fill"
            ],
            "Communication - Phone" => [
                "telephone", "telephone-fill", "telephone-plus", "telephone-plus-fill",
                "telephone-minus", "telephone-minus-fill", "telephone-x", "telephone-x-fill",
                "telephone-inbound", "telephone-inbound-fill", "telephone-outbound", "telephone-outbound-fill",
                "telephone-forward", "telephone-forward-fill"
            ],
            "Links/External/Share" => [
                "link", "link-45deg", "unlink",
                "box-arrow-up-right", "box-arrow-up-left", "box-arrow-down-right", "box-arrow-down-left",
                "box-arrow-right", "box-arrow-left", "box-arrow-up", "box-arrow-down",
                "box-arrow-in-up-right", "box-arrow-in-down-left", "box-arrow-in-right", "box-arrow-in-left",
                "share", "share-fill", "at", "qr-code", "qr-code-scan", "upc-scan", "barcode"
            ],
            "Cloud/Upload/Download" => [
                "download", "upload", "cloud", "cloud-fill",
                "cloud-download", "cloud-download-fill", "cloud-upload", "cloud-upload-fill",
                "cloud-check", "cloud-check-fill", "cloud-plus", "cloud-plus-fill",
                "cloud-minus", "cloud-minus-fill", "cloud-slash", "cloud-slash-fill",
                "cloud-arrow-up", "cloud-arrow-up-fill", "cloud-arrow-down", "cloud-arrow-down-fill"
            ],
            "Database/Storage/Server" => [
                "database", "database-fill", "database-check", "database-fill-check",
                "database-add", "database-fill-add", "database-dash", "database-fill-dash",
                "database-x", "database-fill-x", "database-up", "database-fill-up",
                "database-down", "database-fill-down", "database-gear", "database-fill-gear",
                "database-lock", "database-fill-lock", "database-exclamation", "database-fill-exclamation",
                "server", "hdd", "hdd-fill", "hdd-stack", "hdd-stack-fill",
                "hdd-network", "hdd-network-fill", "device-hdd", "device-hdd-fill", "device-ssd", "device-ssd-fill"
            ],
            "Security" => [
                "lock", "lock-fill", "unlock", "unlock-fill",
                "shield", "shield-fill", "shield-check", "shield-fill-check",
                "shield-plus", "shield-fill-plus", "shield-minus", "shield-fill-minus",
                "shield-x", "shield-fill-x", "shield-exclamation", "shield-fill-exclamation",
                "shield-lock", "shield-lock-fill", "shield-slash", "shield-slash-fill",
                "key", "key-fill", "fingerprint", "incognito",
                "safe", "safe-fill", "safe2", "safe2-fill",
                "pass", "pass-fill", "passkey", "passkey-fill"
            ],
            "Time/Calendar" => [
                "calendar", "calendar-fill", "calendar2", "calendar2-fill", "calendar3", "calendar3-fill", "calendar4",
                "calendar-plus", "calendar-plus-fill", "calendar-minus", "calendar-minus-fill",
                "calendar-check", "calendar-check-fill", "calendar-x", "calendar-x-fill",
                "calendar-event", "calendar-event-fill", "calendar-date", "calendar-date-fill",
                "calendar-day", "calendar-day-fill", "calendar-week", "calendar-week-fill",
                "calendar-month", "calendar-month-fill", "calendar-range", "calendar-range-fill",
                "calendar-heart", "calendar-heart-fill",
                "clock", "clock-fill", "clock-history", "alarm", "alarm-fill",
                "stopwatch", "stopwatch-fill", "hourglass", "hourglass-top", "hourglass-bottom", "hourglass-split"
            ],
            "Ratings/Rewards" => [
                "star", "star-fill", "star-half", "stars",
                "heart", "heart-fill", "heart-half", "heart-pulse", "heart-pulse-fill", "suit-heart", "suit-heart-fill",
                "award", "award-fill", "trophy", "trophy-fill",
                "patch-exclamation", "patch-exclamation-fill", "patch-minus", "patch-minus-fill",
                "patch-plus", "patch-plus-fill", "patch-question", "patch-question-fill",
                "hand-thumbs-up", "hand-thumbs-up-fill", "hand-thumbs-down", "hand-thumbs-down-fill",
                "emoji-smile", "emoji-smile-fill", "emoji-frown", "emoji-frown-fill", "emoji-neutral", "emoji-neutral-fill"
            ],
            "Education/Learning" => [
                "mortarboard", "mortarboard-fill", "book", "book-fill", "book-half",
                "journal", "journal-text", "journal-check", "journal-plus", "journal-minus", "journal-x",
                "journal-bookmark", "journal-bookmark-fill", "journals",
                "journal-album", "journal-arrow-down", "journal-arrow-up", "journal-medical", "journal-richtext"
            ],
            "Bookmarks" => [
                "bookmark", "bookmark-fill", "bookmark-check", "bookmark-check-fill",
                "bookmark-plus", "bookmark-plus-fill", "bookmark-dash", "bookmark-dash-fill",
                "bookmark-x", "bookmark-x-fill", "bookmark-star", "bookmark-star-fill",
                "bookmark-heart", "bookmark-heart-fill", "bookmarks", "bookmarks-fill"
            ],
            "Text/Typography" => [
                "blockquote-left", "blockquote-right", "quote", "text-paragraph",
                "text-left", "text-center", "text-right", "type",
                "type-bold", "type-italic", "type-underline", "type-strikethrough",
                "type-h1", "type-h2", "type-h3"
            ],
            "Tags/Labels" => [
                "tag", "tag-fill", "tags", "tags-fill",
                "pin", "pin-fill", "pin-angle", "pin-angle-fill", "flag", "flag-fill"
            ],
            "View/Visibility" => [
                "eye", "eye-fill", "eye-slash", "eye-slash-fill", "eyeglasses", "binoculars", "binoculars-fill"
            ],
            "Print" => ["printer", "printer-fill"],
            "Location/Navigation" => [
                "house", "house-fill", "house-door", "house-door-fill",
                "building", "buildings", "buildings-fill", "globe", "globe2",
                "geo", "geo-alt", "geo-alt-fill", "geo-fill", "pin-map", "pin-map-fill",
                "signpost", "signpost-fill", "signpost-2", "signpost-2-fill", "signpost-split", "signpost-split-fill",
                "compass", "compass-fill", "map", "map-fill"
            ],
            "Containers/Packaging" => [
                "box", "box-fill", "box2", "box2-fill", "boxes", "box-seam", "box-seam-fill",
                "archive", "archive-fill", "inbox", "inbox-fill", "inboxes", "inboxes-fill"
            ],
            "Power/System" => [
                "power", "toggles", "toggles2", "toggle-on", "toggle-off", "toggle2-on", "toggle2-off"
            ],
            "Code/Development" => [
                "code", "code-slash", "code-square", "terminal", "terminal-fill",
                "braces", "braces-asterisk", "bug", "bug-fill", "git", "github", "gitlab", "regex"
            ],
            "API/Integration" => [
                "plug", "plug-fill", "usb-plug", "usb-plug-fill",
                "broadcast", "broadcast-pin", "rss", "rss-fill",
                "wifi", "wifi-1", "wifi-2", "wifi-off",
                "reception-0", "reception-1", "reception-2", "reception-3", "reception-4",
                "ethernet", "diagram-2", "diagram-2-fill", "diagram-3", "diagram-3-fill",
                "bezier", "bezier2", "hr"
            ],
            "AI/Automation/Charts" => [
                "robot", "magic", "lightbulb", "lightbulb-fill", "lightbulb-off", "lightbulb-off-fill",
                "cpu", "cpu-fill", "gpu-card", "memory", "motherboard", "motherboard-fill",
                "speedometer", "speedometer2", "graph-up", "graph-down", "graph-up-arrow", "graph-down-arrow",
                "bar-chart", "bar-chart-fill", "bar-chart-line", "bar-chart-line-fill", "bar-chart-steps",
                "pie-chart", "pie-chart-fill"
            ],
            "Relations/Hierarchy" => [
                "repeat", "repeat-1", "shuffle", "collection", "collection-fill",
                "stack", "stack-overflow", "layers", "layers-fill", "layers-half",
                "node-plus", "node-plus-fill", "node-minus", "node-minus-fill"
            ],
            "Commerce" => [
                "cash", "cash-coin", "cash-stack", "coin", "currency-dollar", "currency-euro",
                "credit-card", "credit-card-fill", "credit-card-2-front", "credit-card-2-front-fill",
                "credit-card-2-back", "credit-card-2-back-fill",
                "cart", "cart-fill", "cart-plus", "cart-plus-fill", "cart-check", "cart-check-fill",
                "cart-dash", "cart-dash-fill", "cart-x", "cart-x-fill",
                "bag", "bag-fill", "bag-check", "bag-check-fill", "bag-plus", "bag-plus-fill",
                "bag-dash", "bag-dash-fill", "bag-x", "bag-x-fill",
                "basket", "basket-fill", "basket2", "basket2-fill", "basket3", "basket3-fill",
                "receipt", "receipt-cutoff"
            ],
            "Window/UI Chrome" => [
                "window", "window-dash", "window-desktop", "window-fullscreen", "window-plus",
                "window-sidebar", "window-split", "window-stack", "window-x",
                "app", "bounding-box", "bounding-box-circles",
                "cursor", "cursor-fill", "cursor-text",
                "hand-index", "hand-index-fill", "hand-index-thumb", "hand-index-thumb-fill"
            ]
        ];
    }
}

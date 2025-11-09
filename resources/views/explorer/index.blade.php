<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>File Explorer</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen flex flex-col bg-gray-100 text-gray-900 font-sans">
    <div class="bg-blue-600 text-white px-4 py-2 flex items-center gap-2 shadow-md">
        <button onclick="navigateBack()" title="@lang('messages.back')" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">◄</button>
        <button onclick="navigateForward()" title="@lang('messages.forward')" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">►</button>
        <button onclick="navigateUp()" title="@lang('messages.up')" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">▲</button>
        <button onclick="refresh()" title="@lang('messages.refresh')" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">⟳</button>
        <input type="text" id="addressBar" value="{{ $currentPath }}" class="flex-1 bg-white border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-900" onkeydown="handleAddressBarKeydown(event)">
    </div>

    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex gap-4 items-center">
        <div class="flex gap-2">
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all active" onclick="setViewMode('grid')" title="@lang('messages.grid_view')">⊞</button>
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all hover:border-blue-600" onclick="setViewMode('list')" title="@lang('messages.list_view')">☰</button>
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all hover:border-blue-600" onclick="setViewMode('detail')" title="@lang('messages.detail_view')">▦</button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        <div class="w-60 bg-gray-50 border-r border-gray-200 p-4 overflow-y-auto">
            <div class="mb-4">
                <div class="quick-access-label px-4 font-semibold text-xs text-gray-600 uppercase mb-2">@lang('messages.quick_access')</div>
                @foreach($quickAccessPaths as $key => $pathInfo)
                    <div class="px-4 py-2 cursor-pointer transition-colors hover:bg-gray-200 rounded quick-access-item" data-path="{{ json_encode($pathInfo['path'], JSON_HEX_TAG | JSON_HEX_APOS) }}" onclick="navigateTo({{ json_encode($pathInfo['path'], JSON_HEX_TAG | JSON_HEX_APOS) }}, viewMode, currentLanguage)">
                        {{ $pathInfo['label'] }}
                    </div>
                @endforeach
            </div>

            <div class="mb-4">
                <div class="folders-label px-4 font-semibold text-xs text-gray-600 uppercase mb-2">@lang('messages.folders')</div>
                <div id="treeContainer" class="text-sm"></div>
            </div>
        </div>

        <div class="flex-1 overflow-auto p-4" id="content">
            @if(count($items) === 0)
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <div class="text-8xl mb-4 opacity-50">📁</div>
                    <p>@lang('messages.empty_folder')</p>
                </div>
            @else
                <div id="detailHeader" class="detail-header hidden">
                    <div class="header-cell sortable" data-sort="name">@lang('messages.name') <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="modified">@lang('messages.modified') <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="type">@lang('messages.type') <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="size">@lang('messages.size') <span class="sort-icon">⇅</span></div>
                </div>
                <div class="grid-view" id="itemsContainer">
                    @foreach($items as $item)
                        <a href="#" class="item-card grid-card" title="{{ $item['name'] }}" data-type="{{ $item['isDirectory'] ? 'folder' : $item['type'] }}" data-path="{{ json_encode($item['path'], JSON_HEX_TAG | JSON_HEX_APOS) }}" data-is-directory="{{ $item['isDirectory'] ? 'true' : 'false' }}" data-metadata="{{ json_encode($item['metadata'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS) }}">
                            <div class="item-icon">
                                @php
                                    if ($item['isDirectory']) {
                                        echo '📁';
                                    } else {
                                        $typeMap = [
                                            'text' => '📄',
                                            'pdf' => '📕',
                                            'word' => '📘',
                                            'excel' => '📗',
                                            'image' => '🖼️',
                                            'video' => '🎬',
                                            'audio' => '🎵',
                                            'archive' => '📦',
                                        ];
                                        echo $typeMap[$item['type']] ?? '📄';
                                    }
                                @endphp
                            </div>
                            <div class="item-name" title="{{ $item['name'] }}">
                                {{ $item['name'] }}
                            </div>
                        </a>
                    @endforeach
                </div>

                <style>
                    /* Basic styles */
                    .hidden {
                        display: none !important;
                    }

                    .grid-view {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                        gap: 1rem;
                    }

                    .item-card {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        gap: 0.5rem;
                        padding: 0.75rem;
                        background-color: white;
                        border: 1px solid #e5e7eb;
                        border-radius: 0.5rem;
                        cursor: pointer;
                        transition: all 0.2s;
                        text-decoration: none;
                        color: inherit;
                        overflow: hidden;
                    }

                    .item-card:hover {
                        border-color: #2563eb;
                        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
                        transform: translateY(-2px);
                    }

                    .item-icon {
                        font-size: 2rem;
                        height: 2.5rem;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    }

                    .item-name {
                        font-size: 0.75rem;
                        text-align: center;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        display: -webkit-box;
                        -webkit-line-clamp: 2;
                        -webkit-box-orient: vertical;
                        word-break: break-word;
                        width: 100%;
                        padding: 0 0.25rem;
                    }

                    /* Styles for list view */
                    .list-view {
                        display: flex;
                        flex-direction: column;
                        gap: 0;
                        width: 100%;
                    }

                    .list-view .item-card {
                        display: flex;
                        flex-direction: row;
                        justify-content: flex-start;
                        align-items: center;
                        gap: 0.75rem;
                        padding: 0.625rem 0.75rem;
                        border-radius: 0;
                        border: none;
                        border-bottom: 1px solid #e5e7eb;
                        text-align: left;
                    }

                    .list-view .item-card:hover {
                        background-color: #f3f4f6;
                        border-bottom-color: #e5e7eb;
                        transform: none;
                        box-shadow: none;
                    }

                    .list-view .item-icon {
                        font-size: 1.125rem;
                        height: auto;
                        width: 1.5rem;
                        flex-shrink: 0;
                    }

                    .list-view .item-name {
                        font-size: 0.875rem;
                        text-align: left;
                        -webkit-line-clamp: unset;
                        word-break: normal;
                        padding: 0;
                        flex: 1;
                    }

                    /* Styles for detail view */
                    .detail-view {
                        display: flex;
                        flex-direction: column;
                        gap: 0;
                        width: 100%;
                    }

                    .detail-view .item-card {
                        display: grid;
                        grid-template-columns: 2fr 1.2fr 1fr 1fr;
                        align-items: center;
                        gap: 0.75rem;
                        padding: 0.625rem 0.75rem;
                        border-radius: 0;
                        border: none;
                        border-bottom: 1px solid #e5e7eb;
                        text-align: left;
                    }

                    .detail-view .item-card:hover {
                        background-color: #f3f4f6;
                        border-bottom-color: #e5e7eb;
                        transform: none;
                        box-shadow: none;
                    }

                    .detail-view .item-name-cell {
                        display: flex;
                        align-items: center;
                        gap: 0.5rem;
                        min-width: 0;
                    }

                    .detail-view .item-icon {
                        font-size: 1rem;
                        height: auto;
                        width: 1.25rem;
                        flex-shrink: 0;
                    }

                    .detail-view .item-name {
                        font-size: 0.875rem;
                        text-align: left;
                        -webkit-line-clamp: unset;
                        word-break: normal;
                        padding: 0;
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    /* Header styles */
                    .detail-header {
                        display: grid;
                        grid-template-columns: 2fr 1.2fr 1fr 1fr;
                        gap: 0.75rem;
                        padding: 0.625rem 0.75rem;
                        background-color: #f0f0f0;
                        border-bottom: 1px solid #d0d0d0;
                        font-weight: 600;
                        font-size: 0.8125rem;
                        color: #666;
                        user-select: none;
                    }

                    .header-cell {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }

                    .header-cell.sortable {
                        cursor: pointer;
                        padding: 0.25rem 0.5rem;
                        border-radius: 0.25rem;
                        transition: background-color 0.2s;
                    }

                    .header-cell.sortable:hover {
                        background-color: #e0e0e0;
                    }

                    .header-cell.sortable.active {
                        background-color: #2563eb;
                        color: white;
                    }

                    .sort-icon {
                        display: inline-block;
                        margin-left: 0.25rem;
                        font-size: 0.75rem;
                        opacity: 0.5;
                    }

                    .sort-icon.asc::after {
                        content: '▲';
                        opacity: 1;
                    }

                    .sort-icon.desc::after {
                        content: '▼';
                        opacity: 1;
                    }
                </style>
            @endif
        </div>
    </div>

    <script>
        let viewMode = @json($viewMode ?? 'grid');
        const userPath = @json($currentPath);
        const defaultUserPath = @json($quickAccessPaths['home']['path'] ?? null);
        const currentLanguage = document.documentElement.lang;

        // Multilingual text (can be dynamically updated)
        let messages = {
            'folder': '@lang('messages.folder')',
            'text_file': '@lang('messages.text_file')',
            'pdf': '@lang('messages.pdf')',
            'word_document': '@lang('messages.word_document')',
            'excel_file': '@lang('messages.excel_file')',
            'image': '@lang('messages.image')',
            'video': '@lang('messages.video')',
            'audio': '@lang('messages.audio')',
            'archive': '@lang('messages.archive')',
            'file': '@lang('messages.file')',
        };

        /**
         * Unified navigation function
         * @param {string} path - Destination path
         * @param {string} mode - Display mode ('grid', 'list', 'detail')
         * @param {string} language - Display language ('en', 'ja')
         */
        function navigateTo(path, mode = viewMode, language = currentLanguage) {
            const url = new URL('/explorer', window.location.origin);
            url.searchParams.set('path', path);
            url.searchParams.set('mode', mode);
            url.searchParams.set('language', language);
            window.location.href = url.toString();
        }

        const DRIVE_STATE_KEY = 'explorer_drive_states';
        const OS_TYPE = '{{ PHP_OS_FAMILY }}';  // 'Darwin' for Mac, 'Windows' for Windows

        function getDriveStates() {
            try {
                const stored = localStorage.getItem(DRIVE_STATE_KEY);
                return stored ? JSON.parse(stored) : {};
            } catch (e) {
                return {};
            }
        }

        function saveDriveStates(states) {
            try {
                localStorage.setItem(DRIVE_STATE_KEY, JSON.stringify(states));
            } catch (e) {
                console.error('Failed to save drive states:', e);
            }
        }

        function shouldDriveBeExpanded(drivePath, isCurrentDrive) {
            const states = getDriveStates();
            if (drivePath in states) {
                return states[drivePath];
            }
            return isCurrentDrive;
        }

        function applyViewMode(mode) {
            const container = document.getElementById('itemsContainer');
            const detailHeader = document.getElementById('detailHeader');

            container.className = mode + '-view';

            const buttons = document.querySelectorAll('button[onclick*="setViewMode"]');
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                btn.classList.add('bg-white', 'text-gray-900', 'border-gray-300');
            });

            buttons.forEach(btn => {
                if (btn.textContent.includes(mode === 'grid' ? '⊞' : mode === 'list' ? '☰' : '▦')) {
                    btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.remove('bg-white', 'text-gray-900', 'border-gray-300');
                }
            });

            detailHeader.classList.add('hidden');
            if (mode === 'detail') {
                detailHeader.classList.remove('hidden');
                attachSortHandlers();
            }

            updateItemsLayout(mode);
        }

        function setViewMode(mode) {
            navigateTo(userPath, mode, currentLanguage);
        }

        function attachSortHandlers() {
            const headers = document.querySelectorAll('.detail-header .header-cell.sortable');
            headers.forEach(header => {
                header.removeEventListener('click', handleSort);
                header.addEventListener('click', handleSort);
            });
        }

        let sortConfig = { field: 'name', direction: 'asc' };

        function handleSort(e) {
            const field = e.currentTarget.getAttribute('data-sort');
            const headers = document.querySelectorAll('.detail-header .header-cell.sortable');

            headers.forEach(h => h.classList.remove('active'));

            if (sortConfig.field === field) {
                sortConfig.direction = sortConfig.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortConfig.field = field;
                sortConfig.direction = 'asc';
            }

            e.currentTarget.classList.add('active');
            sortItems();
        }

        function sortItems() {
            const container = document.getElementById('itemsContainer');
            const items = Array.from(document.querySelectorAll('.item-card'));

            items.sort((a, b) => {
                let aVal, bVal;

                if (sortConfig.field === 'name') {
                    aVal = a.getAttribute('title') || '';
                    bVal = b.getAttribute('title') || '';
                } else if (sortConfig.field === 'type') {
                    aVal = a.getAttribute('data-type') || '';
                    bVal = b.getAttribute('data-type') || '';
                } else if (sortConfig.field === 'size') {
                    const aMeta = JSON.parse(a.getAttribute('data-metadata') || '{}');
                    const bMeta = JSON.parse(b.getAttribute('data-metadata') || '{}');
                    aVal = aMeta.size || 0;
                    bVal = bMeta.size || 0;
                } else if (sortConfig.field === 'modified') {
                    const aMeta = JSON.parse(a.getAttribute('data-metadata') || '{}');
                    const bMeta = JSON.parse(b.getAttribute('data-metadata') || '{}');
                    aVal = aMeta.modified || 0;
                    bVal = bMeta.modified || 0;
                }

                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                    return sortConfig.direction === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                } else {
                    return sortConfig.direction === 'asc' ? aVal - bVal : bVal - aVal;
                }
            });

            // DOMを再構築
            items.forEach(item => container.appendChild(item));
        }

        function updateItemsLayout(mode) {
            const items = document.querySelectorAll('.item-card');
            items.forEach(item => {
                const metadata = JSON.parse(item.getAttribute('data-metadata') || '{}');
                const type = item.getAttribute('data-type');
                const isDirectory = item.getAttribute('data-is-directory') === 'true';

                item.innerHTML = '';

                if (mode === 'grid') {
                    const iconDiv = document.createElement('div');
                    iconDiv.className = 'item-icon';
                    iconDiv.textContent = getIcon(isDirectory, type);

                    const nameDiv = document.createElement('div');
                    nameDiv.className = 'item-name';
                    nameDiv.textContent = item.getAttribute('title') || item.title;
                    nameDiv.title = nameDiv.textContent;

                    item.appendChild(iconDiv);
                    item.appendChild(nameDiv);
                } else if (mode === 'list') {
                    const nameDiv = document.createElement('div');
                    nameDiv.style.display = 'flex';
                    nameDiv.style.alignItems = 'center';
                    nameDiv.style.gap = '0.5rem';
                    nameDiv.style.flex = '1';
                    nameDiv.style.minWidth = '0';

                    const icon = document.createElement('span');
                    icon.className = 'item-icon';
                    icon.textContent = getIcon(isDirectory, type);

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'item-name';
                    nameSpan.textContent = item.getAttribute('title') || item.title;
                    nameSpan.title = nameSpan.textContent;

                    nameDiv.appendChild(icon);
                    nameDiv.appendChild(nameSpan);
                    item.appendChild(nameDiv);
                } else if (mode === 'detail') {
                    const nameCell = document.createElement('div');
                    nameCell.className = 'item-name-cell';

                    const icon = document.createElement('span');
                    icon.className = 'item-icon';
                    icon.textContent = getIcon(isDirectory, type);

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'item-name';
                    nameSpan.textContent = item.getAttribute('title') || item.title;
                    nameSpan.title = nameSpan.textContent;

                    nameCell.appendChild(icon);
                    nameCell.appendChild(nameSpan);
                    item.appendChild(nameCell);

                    const modSpan = document.createElement('span');
                    modSpan.className = 'item-modified';
                    if (metadata.modified) {
                        modSpan.textContent = formatDate(metadata.modified * 1000);
                    } else {
                        modSpan.textContent = '-';
                    }
                    item.appendChild(modSpan);

                    const typeSpan = document.createElement('span');
                    typeSpan.className = 'item-type';
                    typeSpan.textContent = isDirectory ? messages.folder : getTypeLabel(type);
                    item.appendChild(typeSpan);

                    // サイズセル
                    const sizeSpan = document.createElement('span');
                    sizeSpan.className = 'item-size';
                    if (isDirectory) {
                        sizeSpan.textContent = '-';
                    } else {
                        sizeSpan.textContent = formatFileSize(metadata.size || 0);
                    }
                    item.appendChild(sizeSpan);
                }
            });
        }

        function getIcon(isDirectory, type) {
            if (isDirectory) return '📁';
            const iconMap = {
                'text': '📄',
                'pdf': '📕',
                'word': '📘',
                'excel': '📗',
                'image': '🖼️',
                'video': '🎬',
                'audio': '🎵',
                'archive': '📦',
            };
            return iconMap[type] || '📄';
        }

        function getTypeLabel(type) {
            const typeMap = {
                'text': messages.text_file,
                'pdf': messages.pdf,
                'word': messages.word_document,
                'excel': messages.excel_file,
                'image': messages.image,
                'video': messages.video,
                'audio': messages.audio,
                'archive': messages.archive,
                'file': messages.file,
            };
            return typeMap[type] || messages.file;
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function formatDate(ms) {
            const date = new Date(ms);
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}/${month}/${day} ${hours}:${minutes}`;
        }

        function handleItemDblClick(path, isDirectory) {
            if (isDirectory) {
                navigateTo(path, viewMode, currentLanguage);
            } else {
                openFile(path);
            }
        }

        function openFile(filePath) {
            fetch('/api/file/open', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    path: filePath,
                }),
            })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (data.success) {
                    console.log('File opened:', filePath);
                } else {
                    console.error('Error opening file:', data.message);
                    alert('Error: ' + data.message);
                }
            })
            .catch((err) => {
                console.error('Unexpected error opening file:', err);
                alert('Failed to open file: ' + err.message);
            });
        }

        function getCsrfToken() {
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? token.getAttribute('content') : '';
        }

        function handleAddressBarKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const path = document.getElementById('addressBar').value.trim();
                if (path) {
                    navigateTo(path, viewMode, currentLanguage);
                }
            }
        }

        function navigateBack() {
            history.back();
        }

        function navigateForward() {
            history.forward();
        }

        function navigateUp() {
            const path = userPath;
            const isWindowsPath = path.includes('\\');
            const separator = isWindowsPath ? '\\' : '/';
            const lastSepIndex = path.lastIndexOf(separator);

            if (lastSepIndex > 0) {
                if (isWindowsPath && lastSepIndex === 2 && path[1] === ':') {
                    return;
                }
                const parentPath = path.substring(0, lastSepIndex);
                navigateTo(parentPath, viewMode, currentLanguage);
            }
        }

        function refresh() {
            location.reload();
        }

        function renderDirectoryTree() {
            const drivesData = @json($rootDrives);
            const currentPath = userPath;
            const container = document.getElementById('treeContainer');
            const driveStates = getDriveStates();

            container.innerHTML = '';

            if (drivesData && drivesData.length > 0) {
                const currentDrive = findDriveForPath(drivesData, currentPath);

                drivesData.forEach(drive => {
                    const driveElement = document.createElement('div');
                    driveElement.className = 'mb-3';
                    driveElement.setAttribute('data-drive-path', drive.path);

                    const isCurrentDrive = currentDrive && currentDrive.path === drive.path;
                    const isExpanded = shouldDriveBeExpanded(drive.path, isCurrentDrive);

                    const driveHeader = document.createElement('div');
                    driveHeader.className = 'px-2 py-1 cursor-pointer hover:bg-gray-200 rounded transition-colors flex items-center gap-1';
                    driveHeader.onclick = (e) => {
                        e.stopPropagation();
                        navigateTo(drive.path, viewMode, currentLanguage);
                    };

                    const driveToggle = document.createElement('span');
                    driveToggle.className = 'tree-toggle text-sm select-none';
                    driveToggle.style.width = '16px';
                    driveToggle.style.display = 'inline-block';
                    driveToggle.style.textAlign = 'center';
                    driveToggle.textContent = isExpanded ? '▼' : '▶';

                    const driveIcon = document.createElement('span');
                    driveIcon.textContent = '💾';
                    driveIcon.style.marginRight = '0.25rem';

                    const driveName = document.createElement('span');
                    driveName.textContent = drive.name;
                    driveName.style.flex = '1';
                    driveName.style.fontWeight = drive.path === currentPath ? 'bold' : 'normal';
                    driveName.style.color = drive.path === currentPath ? '#2563eb' : 'inherit';

                    driveHeader.appendChild(driveToggle);
                    driveHeader.appendChild(driveIcon);
                    driveHeader.appendChild(driveName);

                    driveElement.appendChild(driveHeader);

                    if (drive.children && drive.children.length > 0) {
                        const childrenContainer = document.createElement('div');
                        childrenContainer.className = 'tree-children' + (isExpanded ? '' : ' hidden');

                        // Calculate the depth of the current path under the drive
                        // Compute relative depth from the drive path and apply to tree node depth
                        const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
                        const driveNorm = normalizePath(drive.path.toLowerCase());
                        const currentNorm = normalizePath(currentPath.toLowerCase());

                        const treeHtml = renderTreeNodes(drive.children, drive.path, currentPath, 1);
                        childrenContainer.innerHTML = treeHtml;

                        driveElement.appendChild(childrenContainer);
                    }

                    container.appendChild(driveElement);
                });

                attachTreeEventListeners();
            }
        }

        function findDriveForPath(drives, currentPath) {
            const currentLower = currentPath.toLowerCase();

            const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
            const currentNorm = normalizePath(currentLower);

            // Check for exact match
            for (let drive of drives) {
                const driveNorm = normalizePath(drive.path.toLowerCase());
                if (currentNorm === driveNorm) {
                    return drive;
                }
            }

            // Check parent path inclusion relationship
            for (let drive of drives) {
                const driveNorm = normalizePath(drive.path.toLowerCase());

                if (currentNorm.startsWith(driveNorm + '/')) {
                    return drive;
                }
            }

            return null;
        }

        function renderTreeNodes(nodes, parentPath, currentPath, depth = 0) {
            let html = '<ul class="list-none pl-0" style="margin: 0; padding: 0;">';

            nodes.forEach(node => {
                const isExpanded = isPathUnderNode(currentPath, node.path);
                const hasChildren = node.children && node.children.length > 0;
                const nodeId = 'tree-' + node.path.replace(/[^a-zA-Z0-9]/g, '_');
                const indentPx = depth * 16;

                html += `<li class="tree-node" data-path="${node.path}">`;
                html += `<div class="tree-item flex items-center gap-1 px-2 py-1 cursor-pointer hover:bg-gray-200 rounded transition-colors" style="margin-left: ${indentPx}px;">`;

                if (hasChildren) {
                    html += `<span class="tree-toggle text-sm select-none" style="width: 16px; display: inline-block; text-align: center;">`;
                    html += isExpanded ? '▼' : '▶';
                    html += `</span>`;
                } else {
                    html += `<span style="width: 16px; display: inline-block;"></span>`;
                }

                html += '<span class="text-base">📁</span>';

                const isCurrentNode = node.path === currentPath;
                const className = isCurrentNode ? 'font-semibold text-blue-600 flex-1' : 'flex-1';
                html += `<span class="${className} truncate tree-node-name">${node.name}</span>`;

                html += '</div>';

                if (hasChildren) {
                    const childrenClass = isExpanded ? '' : 'hidden';
                    html += `<div class="tree-children ${childrenClass}">`;
                    html += renderTreeNodes(node.children, node.path, currentPath, depth + 1);
                    html += '</div>';
                }

                html += '</li>';
            });

            html += '</ul>';
            return html;
        }

        function isPathUnderNode(currentPath, nodePath) {
            const currentLower = currentPath.toLowerCase();
            const nodeLower = nodePath.toLowerCase();

            const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
            const currentNorm = normalizePath(currentLower);
            const nodeNorm = normalizePath(nodeLower);

            if (currentNorm === nodeNorm) {
                return true;
            }

            // Check if current path is under the node path
            if (currentNorm.startsWith(nodeNorm + '/')) {
                return true;
            }

            return false;
        }

        function attachTreeEventListeners() {
            const toggles = document.querySelectorAll('.tree-toggle');

            toggles.forEach(toggle => {
                toggle.removeEventListener('click', handleToggleClick);
                toggle.addEventListener('click', handleToggleClick);
            });

            const treeItems = document.querySelectorAll('.tree-item');

            treeItems.forEach(item => {
                item.removeEventListener('click', handleTreeItemClick);
                item.addEventListener('click', handleTreeItemClick);
            });
        }

        function handleToggleClick(e) {
            e.stopPropagation();

            const toggle = e.currentTarget;
            const liParent = toggle.closest('li');

            if (!liParent) {
                const driveElement = toggle.closest('[data-drive-path]');

                if (driveElement) {
                    const childrenContainer = driveElement.querySelector(':scope > .tree-children');

                    if (childrenContainer) {
                        const isNowHidden = childrenContainer.classList.toggle('hidden');
                        toggle.textContent = isNowHidden ? '▶' : '▼';

                        const drivePath = driveElement.getAttribute('data-drive-path');
                        const driveStates = getDriveStates();
                        driveStates[drivePath] = !isNowHidden;
                        saveDriveStates(driveStates);
                    }
                }
            } else {
                const childrenContainer = liParent.querySelector(':scope > .tree-children');
                if (childrenContainer) {
                    const isNowHidden = childrenContainer.classList.toggle('hidden');
                    toggle.textContent = isNowHidden ? '▶' : '▼';
                }
            }
        }

        function handleTreeItemClick(e) {
            // Ignore toggle clicks
            if (e.target.classList.contains('tree-toggle')) {
                return;
            }

            e.stopPropagation();
            const treeNode = this.closest('.tree-node');
            if (treeNode) {
                let nodePath = treeNode.getAttribute('data-path');
                nodePath = nodePath.replace(/\\\\/g, '\\');
                if (nodePath) {
                    navigateTo(nodePath, viewMode, currentLanguage);
                }
            } else {
                // Handle drive header click
                const driveElement = this.closest('[data-drive-path]');
                if (driveElement) {
                    const drivePath = driveElement.getAttribute('data-drive-path');
                    navigateTo(drivePath, viewMode, currentLanguage);
                }
            }
        }

        function changeLanguage(language) {
            const currentPath = document.getElementById('addressBar').value;
            navigateTo(currentPath, viewMode, language);
        }

        // Listen for menu item clicks from native application
        Native.on('Native\\Desktop\\Events\\Menu\\MenuItemClicked', (payload, _event) => {
            if (payload.item.id === 'change_language_en') {
                changeLanguage('en');
            } else if (payload.item.id === 'change_language_ja') {
                changeLanguage('ja');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            renderDirectoryTree();
            applyViewMode(viewMode);

            const itemCards = document.querySelectorAll('[data-type]');
            itemCards.forEach(card => {
                card.addEventListener('dblclick', function(e) {
                    e.preventDefault();
                    const path = JSON.parse(this.getAttribute('data-path'));
                    const isDirectory = this.getAttribute('data-is-directory') === 'true';
                    handleItemDblClick(path, isDirectory);
                });
            });

            // Clear localStorage when the application closes
            if (window.Native) {
                Native.on('Native\\Desktop\\Events\\Windows\\WindowClosed', () => {
                    localStorage.clear();
                });
            }
        });
    </script>
</body>
</html>

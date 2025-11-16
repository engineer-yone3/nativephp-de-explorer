<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ファイルエクスプローラー</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-screen flex flex-col bg-gray-100 text-gray-900 font-sans">
    <div class="bg-blue-600 text-white px-4 py-2 flex items-center gap-2 shadow-md">
        <button onclick="navigateBack()" title="戻る" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">◄</button>
        <button onclick="navigateForward()" title="進む" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">►</button>
        <button onclick="navigateUp()" title="上へ" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">▲</button>
        <button onclick="refresh()" title="更新" class="bg-white/20 border border-white/40 text-white px-3 py-1.5 rounded hover:bg-white/30 transition-colors">⟳</button>
        <input type="text" id="addressBar" value="{{ $currentPath }}" class="flex-1 bg-white border border-gray-300 rounded px-3 py-1.5 text-sm text-gray-900" onkeydown="handleAddressBarKeydown(event)">
    </div>

    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex gap-4 items-center">
        <div class="flex gap-2">
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all active" onclick="setViewMode('grid')" title="グリッド表示">⊞</button>
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all hover:border-blue-600" onclick="setViewMode('list')" title="リスト表示">☰</button>
            <button class="bg-white border border-gray-300 px-2.5 py-1.5 rounded cursor-pointer transition-all hover:border-blue-600" onclick="setViewMode('detail')" title="詳細表示">▦</button>
        </div>
    </div>

    <div class="flex flex-1 overflow-hidden">
        <div class="w-60 bg-gray-50 border-r border-gray-200 p-4 overflow-y-auto">
            <div class="mb-4">
                <div class="px-4 font-semibold text-xs text-gray-600 uppercase mb-2">クイックアクセス</div>
                @foreach($quickAccessPaths as $key => $pathInfo)
                    <div class="px-4 py-2 cursor-pointer transition-colors hover:bg-gray-200 rounded quick-access-item" data-path="{{ json_encode($pathInfo['path']) }}" onclick="navigateTo({{ json_encode($pathInfo['path']) }})">
                        {{ $pathInfo['label'] }}
                    </div>
                @endforeach
            </div>

            <div class="mb-4">
                <div class="px-4 font-semibold text-xs text-gray-600 uppercase mb-2">フォルダー</div>
                <div id="treeContainer" class="text-sm"></div>
            </div>
        </div>

        <div class="flex-1 overflow-auto p-4" id="content">
            @if(count($items) === 0)
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <div class="text-8xl mb-4 opacity-50">📁</div>
                    <p>このフォルダは空です</p>
                </div>
            @else
                <div id="detailHeader" class="detail-header hidden">
                    <div class="header-cell sortable" data-sort="name">名前 <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="modified">更新日時 <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="type">種類 <span class="sort-icon">⇅</span></div>
                    <div class="header-cell sortable" data-sort="size">サイズ <span class="sort-icon">⇅</span></div>
                </div>
                <div class="grid-view" id="itemsContainer">
                    @foreach($items as $item)
                        <a href="#" class="item-card grid-card" title="{{ $item['name'] }}" data-type="{{ $item['isDirectory'] ? 'folder' : $item['type'] }}" data-path="{{ json_encode($item['path']) }}" data-is-directory="{{ $item['isDirectory'] ? 'true' : 'false' }}" data-metadata="{{ json_encode($item['metadata'] ?? []) }}">
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
                    /* 基本スタイル */
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

                    /* リスト表示時のスタイル */
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

                    /* 詳細表示用のスタイル */
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

                    /* ヘッダースタイル */
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

    <div class="bg-gray-50 border-t border-gray-200 px-4 py-2 text-xs text-gray-600 flex justify-between">
        <span id="itemCount">アイテム: {{ count($items) }}</span>
        <span>ディスク: C: (使用可能な容量)</span>
    </div>

    <script>
        let viewMode = 'grid';
        const userPath = @json($currentPath);
        const defaultUserPath = @json($quickAccessPaths['home']['path'] ?? null);
        
        // ドライブの展開/折畳み状態をローカルストレージで管理
        const DRIVE_STATE_KEY = 'explorer_drive_states';
        
        // ドライブ状態を取得
        function getDriveStates() {
            try {
                const stored = localStorage.getItem(DRIVE_STATE_KEY);
                return stored ? JSON.parse(stored) : {};
            } catch (e) {
                return {};
            }
        }
        
        // ドライブ状態を保存
        function saveDriveStates(states) {
            try {
                localStorage.setItem(DRIVE_STATE_KEY, JSON.stringify(states));
            } catch (e) {
                console.error('Failed to save drive states:', e);
            }
        }
        
        // ドライブの展開状態を取得（デフォルトは現在のパスが含まれるドライブのみ展開）
        function shouldDriveBeExpanded(drivePath, isCurrentDrive) {
            const states = getDriveStates();
            
            // ストレージに状態が保存されていたらそれを使用
            if (drivePath in states) {
                return states[drivePath];
            }
            
            // 初回は現在のパスが含まれるドライブのみ展開
            return isCurrentDrive;
        }

        function setViewMode(mode) {
            viewMode = mode;
            const container = document.getElementById('itemsContainer');
            const detailHeader = document.getElementById('detailHeader');
            const buttons = document.querySelectorAll('button[onclick*="setViewMode"]');
            
            // ボタンのアクティブ状態を更新
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                btn.classList.add('bg-white', 'text-gray-900', 'border-gray-300');
            });
            event.target.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
            event.target.classList.remove('bg-white', 'text-gray-900', 'border-gray-300');
            
            // コンテナのクラスを切り替える
            container.className = mode + '-view';
            detailHeader.classList.add('hidden');
            
            if (mode === 'grid') {
                updateItemsLayout('grid');
            } else if (mode === 'list') {
                updateItemsLayout('list');
            } else if (mode === 'detail') {
                detailHeader.classList.remove('hidden');
                updateItemsLayout('detail');
                attachSortHandlers();
            }
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
            
            // 前回のソート状態をクリア
            headers.forEach(h => h.classList.remove('active'));
            
            // ソート方向を切り替える
            if (sortConfig.field === field) {
                sortConfig.direction = sortConfig.direction === 'asc' ? 'desc' : 'asc';
            } else {
                sortConfig.field = field;
                sortConfig.direction = 'asc';
            }
            
            // ヘッダーをアクティブ化
            e.currentTarget.classList.add('active');
            
            // アイテムをソート
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
                    // グリッド表示用に構造を戻す
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
                    // リスト表示用に構造を更新
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
                    // 詳細表示用に構造を更新
                    // 名前セル
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
                    
                    // 更新日時セル
                    const modSpan = document.createElement('span');
                    modSpan.className = 'item-modified';
                    if (metadata.modified) {
                        modSpan.textContent = formatDate(metadata.modified * 1000);
                    } else {
                        modSpan.textContent = '-';
                    }
                    item.appendChild(modSpan);
                    
                    // 種類セル
                    const typeSpan = document.createElement('span');
                    typeSpan.className = 'item-type';
                    typeSpan.textContent = isDirectory ? 'フォルダ' : getTypeLabel(type);
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
                'text': 'テキストファイル',
                'pdf': 'PDF',
                'word': 'Wordドキュメント',
                'excel': 'Excelファイル',
                'image': '画像',
                'video': 'ビデオ',
                'audio': 'オーディオ',
                'archive': '圧縮ファイル',
                'file': 'ファイル',
            };
            return typeMap[type] || 'ファイル';
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
                navigateTo(path);
            } else {
                // ファイルをOSのデフォルトアプリケーションで開く
                openFile(path);
            }
        }

        function openFile(filePath) {
            // PHP側のControllerにファイルパスを送信してOS側で開く
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
                    console.log('ファイルを開きました:', filePath);
                } else {
                    console.error('ファイルを開く際にエラーが発生しました:', data.message);
                    alert('エラー: ' + data.message);
                }
            })
            .catch((err) => {
                console.error('ファイルを開く際に予期しないエラーが発生しました:', err);
                alert('ファイルを開く際にエラーが発生しました: ' + err.message);
            });
        }

        function getCsrfToken() {
            // メタタグからCSRFトークンを取得
            const token = document.querySelector('meta[name="csrf-token"]');
            return token ? token.getAttribute('content') : '';
        }

        function navigateTo(path) {
            window.location.href = `/explorer?path=${encodeURIComponent(path)}&mode=${viewMode}`;
        }

        function handleAddressBarKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                const path = document.getElementById('addressBar').value.trim();
                if (path) {
                    navigateTo(path);
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
            // Windowsパス（C:\Users\...）またはUnixパス（/home/...）に対応
            const isWindowsPath = path.includes('\\');
            const separator = isWindowsPath ? '\\' : '/';
            const lastSepIndex = path.lastIndexOf(separator);
            
            if (lastSepIndex > 0) {
                // Windowsドライブレター（C:）の場合は処理しない
                if (isWindowsPath && lastSepIndex === 2 && path[1] === ':') {
                    return;
                }
                const parentPath = path.substring(0, lastSepIndex);
                navigateTo(parentPath);
            }
        }

        function refresh() {
            location.reload();
        }

        // ツリーを初期化してレンダリング
        function renderDirectoryTree() {
            const drivesData = @json($rootDrives);
            const currentPath = userPath;
            const container = document.getElementById('treeContainer');
            const driveStates = getDriveStates();
            
            container.innerHTML = '';
            
            if (drivesData && drivesData.length > 0) {
                // 現在のパスが含まれるドライブを判定
                const currentDrive = findDriveForPath(drivesData, currentPath);
                
                // ドライブごとにツリーを作成
                drivesData.forEach(drive => {
                    const driveElement = document.createElement('div');
                    driveElement.className = 'mb-3';
                    
                    // このドライブが現在のパスを含むか判定
                    const isCurrentDrive = currentDrive && currentDrive.path === drive.path;
                    
                    // ドライブの展開状態を判定（保存状態 or 初回は現在のドライブのみ）
                    const isExpanded = shouldDriveBeExpanded(drive.path, isCurrentDrive);
                    
                    // ドライブ名
                    const driveHeader = document.createElement('div');
                    driveHeader.className = 'px-2 py-1 cursor-pointer hover:bg-gray-200 rounded transition-colors flex items-center gap-1';
                    driveHeader.onclick = (e) => {
                        e.stopPropagation();
                        navigateTo(drive.path);
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
                    
                    // ドライブの子ツリー
                    if (drive.children && drive.children.length > 0) {
                        const childrenContainer = document.createElement('div');
                        childrenContainer.className = 'tree-children' + (isExpanded ? '' : ' hidden');
                        
                        // カレントパスがドライブ配下のどの深さにあるかを計算
                        // ドライブパスから相対的な深さを算出して、ツリーノードの depth に反映
                        const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
                        const driveNorm = normalizePath(drive.path.toLowerCase());
                        const currentNorm = normalizePath(currentPath.toLowerCase());
                        
                        // ドライブ直下のノードは depth=1 から開始
                        const treeHtml = renderTreeNodes(drive.children, drive.path, currentPath, 1);
                        childrenContainer.innerHTML = treeHtml;
                        
                        driveElement.appendChild(childrenContainer);
                        
                        // トグル機能
                        driveToggle.addEventListener('click', (e) => {
                            e.stopPropagation();
                            const isNowHidden = childrenContainer.classList.toggle('hidden');
                            driveToggle.textContent = isNowHidden ? '▶' : '▼';
                            
                            // 状態を保存
                            driveStates[drive.path] = !isNowHidden;
                            saveDriveStates(driveStates);
                        });
                    }
                    
                    container.appendChild(driveElement);
                });
                
                attachTreeEventListeners();
            }
        }

        // 指定パスが含まれるドライブを検索
        function findDriveForPath(drives, currentPath) {
            const currentLower = currentPath.toLowerCase();
            
            // パスセパレータを / に統一する関数
            const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
            const currentNorm = normalizePath(currentLower);
            
            // 現在のパスと一致するドライブを最初に探す
            for (let drive of drives) {
                const driveNorm = normalizePath(drive.path.toLowerCase());
                if (currentNorm === driveNorm) {
                    return drive;
                }
            }
            
            // 現在のパスがドライブ配下にあるかチェック
            for (let drive of drives) {
                const driveNorm = normalizePath(drive.path.toLowerCase());
                
                // ドライブパスが / で終わらないように統一してから比較
                if (currentNorm.startsWith(driveNorm + '/')) {
                    return drive;
                }
            }
            
            return null;
        }

        // ツリーノードのHTMLを再帰的に生成
        function renderTreeNodes(nodes, parentPath, currentPath, depth = 0) {
            let html = '<ul class="list-none pl-0" style="margin: 0; padding: 0;">';
            
            nodes.forEach(node => {
                const isExpanded = isPathUnderNode(currentPath, node.path);
                const hasChildren = node.children && node.children.length > 0;
                const nodeId = 'tree-' + node.path.replace(/[^a-zA-Z0-9]/g, '_');
                const indentPx = depth * 16; // 階層ごとに16pxインデント
                
                html += `<li class="tree-node" data-path="${node.path}">`;
                html += `<div class="tree-item flex items-center gap-1 px-2 py-1 cursor-pointer hover:bg-gray-200 rounded transition-colors" style="margin-left: ${indentPx}px;">`;
                
                // 展開/折畳みボタン
                if (hasChildren) {
                    html += `<span class="tree-toggle text-sm select-none" style="width: 16px; display: inline-block; text-align: center;">`;
                    html += isExpanded ? '▼' : '▶';
                    html += `</span>`;
                } else {
                    html += `<span style="width: 16px; display: inline-block;"></span>`;
                }
                
                // アイコン
                html += '<span class="text-base">📁</span>';
                
                // ノード名
                const isCurrentNode = node.path === currentPath;
                const className = isCurrentNode ? 'font-semibold text-blue-600 flex-1' : 'flex-1';
                html += `<span class="${className} truncate tree-node-name">${node.name}</span>`;
                
                html += '</div>';
                
                // 子ノード
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

        // 現在のパスがノード配下かどうかを判定
        function isPathUnderNode(currentPath, nodePath) {
            // 大文字小文字を区別しない比較（Windowsパス対応）
            const currentLower = currentPath.toLowerCase();
            const nodeLower = nodePath.toLowerCase();
            
            // パスセパレータを / に統一し、末尾の / を削除
            const normalizePath = (path) => path.replace(/\\/g, '/').replace(/\/$/, '');
            const currentNorm = normalizePath(currentLower);
            const nodeNorm = normalizePath(nodeLower);
            
            if (currentNorm === nodeNorm) {
                return true;
            }
            
            // ノード配下のパスか判定
            if (currentNorm.startsWith(nodeNorm + '/')) {
                return true;
            }
            
            return false;
        }

        // ツリーイベントリスナーをアタッチ
        function attachTreeEventListeners() {
            const toggles = document.querySelectorAll('.tree-toggle');
            toggles.forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const treeItem = this.closest('.tree-item');
                    const childrenContainer = treeItem.parentElement.querySelector('.tree-children');
                    
                    if (childrenContainer) {
                        childrenContainer.classList.toggle('hidden');
                        this.textContent = childrenContainer.classList.contains('hidden') ? '▶' : '▼';
                    }
                });
            });
            
            // ツリーアイテムのクリックイベントリスナー
            const treeItems = document.querySelectorAll('.tree-item');
            treeItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const treeNode = this.closest('.tree-node');
                    let nodePath = treeNode.getAttribute('data-path');
                    // HTMLエスケープされたバックスラッシュをアンエスケープ
                    nodePath = nodePath.replace(/\\\\/g, '\\');
                    if (nodePath) {
                        navigateTo(nodePath);
                    }
                });
            });
        }

        // ダブルクリックイベントリスナーの設定
        document.addEventListener('DOMContentLoaded', function() {
            // URL パラメータからビューモードを復元
            const urlParams = new URLSearchParams(window.location.search);
            const savedMode = urlParams.get('mode');
            
            // ツリーをレンダリング
            renderDirectoryTree();
            
            // 保存されたモードがあれば適用
            if (savedMode && ['grid', 'list', 'detail'].includes(savedMode)) {
                // 適切なボタンをシミュレートするために、mode を設定
                viewMode = savedMode;
                const container = document.getElementById('itemsContainer');
                const detailHeader = document.getElementById('detailHeader');
                
                // コンテナクラスを設定
                container.className = savedMode + '-view';
                
                // ボタンのアクティブ状態を更新
                const buttons = document.querySelectorAll('button[onclick*="setViewMode"]');
                buttons.forEach(btn => {
                    btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                    btn.classList.add('bg-white', 'text-gray-900', 'border-gray-300');
                });
                
                // アクティブなボタンを見つけて強調表示
                buttons.forEach(btn => {
                    if (btn.textContent.includes(savedMode === 'grid' ? '⊞' : savedMode === 'list' ? '☰' : '▦')) {
                        btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                        btn.classList.remove('bg-white', 'text-gray-900', 'border-gray-300');
                    }
                });
                
                // ヘッダーの表示制御
                detailHeader.classList.add('hidden');
                if (savedMode === 'detail') {
                    detailHeader.classList.remove('hidden');
                    attachSortHandlers();
                }
                
                // アイテムレイアウトを更新
                updateItemsLayout(savedMode);
            } else {
                // デフォルトはグリッド表示
                const detailHeader = document.getElementById('detailHeader');
                if (detailHeader) detailHeader.classList.add('hidden');
            }
            
            const itemCards = document.querySelectorAll('[data-type]');
            itemCards.forEach(card => {
                card.addEventListener('dblclick', function(e) {
                    e.preventDefault();
                    const path = JSON.parse(this.getAttribute('data-path'));
                    const isDirectory = this.getAttribute('data-is-directory') === 'true';
                    handleItemDblClick(path, isDirectory);
                });
            });
            
            // アプリケーション終了時にローカルストレージをクリア
            window.addEventListener('beforeunload', function() {
                localStorage.removeItem(DRIVE_STATE_KEY);
            });
        });
    </script>
</body>
</html>

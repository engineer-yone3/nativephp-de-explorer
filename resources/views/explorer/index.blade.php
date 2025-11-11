<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    <div class="px-4 py-2 cursor-pointer transition-colors hover:bg-gray-200 rounded" onclick="navigateTo({{ json_encode($pathInfo['path']) }})">
                        {{ $pathInfo['label'] }}
                    </div>
                @endforeach
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
                // ファイルをデフォルトプログラムで開く
                console.log('ファイルを開く:', path);
            }
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

        // ダブルクリックイベントリスナーの設定
        document.addEventListener('DOMContentLoaded', function() {
            // URL パラメータからビューモードを復元
            const urlParams = new URLSearchParams(window.location.search);
            const savedMode = urlParams.get('mode');
            
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
        });
    </script>
</body>
</html>

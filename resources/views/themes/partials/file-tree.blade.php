@if(empty($files))
    <div class="empty-state">
        <svg viewBox="0 0 20 20" class="icon">
            <path d="M10 3a7 7 0 1 0 7 7h-1.5a5.5 5.5 0 1 1-1.17-3.36l.17-.14H12v5l6.5-4-6.5-4v3.5h-.52A7 7 0 0 0 10 3z"></path>
        </svg>
        <p>Nenhum arquivo encontrado</p>
    </div>
@else
    <ul class="file-tree">
        @foreach($files as $file)
            @if(isset($file['children']))
                <li class="folder">
                    <div class="file-item" data-path="{{ $file['path'] ?? '' }}">
                        @if($file['type'] === 'directory')
                            @if(str_contains($file['path'] ?? '', 'assets/images') || str_contains($file['path'] ?? '', 'assets/img'))
                                <svg viewBox="0 0 20 20" class="icon">
                                    <path d="M1 3a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3zm16 0H3v14h14V3zM8 5a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm2 0h6v2h-6V5zm-2 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm2 0h6v2h-6V9zm-2 4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm2 0h6v2h-6v-2z"></path>
                                </svg>
                            @else
                                <svg viewBox="0 0 20 20" class="icon">
                                    <path d="M17 5v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2z"></path>
                                </svg>
                            @endif
                            <span>{{ $file['name'] }}</span>
                        @else
                            <!-- Código existente para arquivos -->
                            @php
                                $extension = pathinfo($file['path'], PATHINFO_EXTENSION);
                            @endphp
                            <!-- ... ícones para arquivos ... -->
                            <span>{{ $file['name'] }}</span>
                        @endif
                    </div>
                    <div class="folder-content">
                        @include('themes.partials.file-tree', ['files' => $file['children']])
                    </div>
                </li>
            @else
                <li class="file">
                    <div class="file-item" data-path="{{ $file['path'] ?? '' }}">
                        @php
                            $extension = pathinfo($file['path'] ?? '', PATHINFO_EXTENSION);
                        @endphp
                        
                        @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']))
                            <svg viewBox="0 0 20 20" class="icon">
                                <path d="M1 3a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V3zm16 0H3v14h14V3zM5 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm10 8a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path>
                            </svg>
                        @elseif($extension === 'css')
                            <svg viewBox="0 0 20 20" class="icon">
                                <path d="M3 3h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 2v10h14V5H3zm2 2h10v2H5V7zm0 4h6v2H5v-2z"></path>
                            </svg>
                        @elseif($extension === 'js')
                            <svg viewBox="0 0 20 20" class="icon">
                                <path d="M3 3h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 2v10h14V5H3zm7 2h2v6h-2V7zm-3 1h2v5H7V8zm6 1h2v4h-2V9z"></path>
                            </svg>
                        @elseif($extension === 'liquid')
                        <svg viewBox="0 0 20 20" focusable="false" class="icon">
                            <path d="M12.221 4.956a.75.75 0 0 0-1.442-.412l-3 10.5a.75.75 0 1 0 1.442.412l3-10.5Z"></path>
                            <path d="M7.03 6.22a.75.75 0 0 1 0 1.06l-2.72 2.72 2.72 2.72a.75.75 0 0 1-1.06 1.06l-3.25-3.25a.75.75 0 0 1 0-1.06l3.25-3.25a.75.75 0 0 1 1.06 0Z"></path>
                            <path d="M12.97 13.78a.75.75 0 0 1 0-1.06l2.72-2.72-2.72-2.72a.75.75 0 0 1 1.06-1.06l3.25 3.25a.75.75 0 0 1 0 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0Z"></path>
                          </svg>
                        @elseif($extension === 'json')
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon">
                                <path fill-rule="evenodd" d="M14 4.5V11h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5zM4.151 15.29a1.2 1.2 0 0 1-.111-.449h.764a.58.58 0 0 0 .255.384q.105.073.25.114.142.041.319.041.245 0 .413-.07a.56.56 0 0 0 .255-.193.5.5 0 0 0 .084-.29.39.39 0 0 0-.152-.326q-.152-.12-.463-.193l-.618-.143a1.7 1.7 0 0 1-.539-.214 1 1 0 0 1-.352-.367 1.1 1.1 0 0 1-.123-.524q0-.366.19-.639.192-.272.528-.422.337-.15.777-.149.456 0 .779.152.326.153.5.41.18.255.2.566h-.75a.56.56 0 0 0-.12-.258.6.6 0 0 0-.246-.181.9.9 0 0 0-.37-.068q-.324 0-.512.152a.47.47 0 0 0-.185.384q0 .18.144.3a1 1 0 0 0 .404.175l.621.143q.326.075.566.211a1 1 0 0 1 .375.358q.135.222.135.56 0 .37-.188.656a1.2 1.2 0 0 1-.539.439q-.351.158-.858.158-.381 0-.665-.09a1.4 1.4 0 0 1-.478-.252 1.1 1.1 0 0 1-.29-.375m-3.104-.033a1.3 1.3 0 0 1-.082-.466h.764a.6.6 0 0 0 .074.27.5.5 0 0 0 .454.246q.285 0 .422-.164.137-.165.137-.466v-2.745h.791v2.725q0 .66-.357 1.005-.355.345-.985.345a1.6 1.6 0 0 1-.568-.094 1.15 1.15 0 0 1-.407-.266 1.1 1.1 0 0 1-.243-.39m9.091-1.585v.522q0 .384-.117.641a.86.86 0 0 1-.322.387.9.9 0 0 1-.47.126.9.9 0 0 1-.47-.126.87.87 0 0 1-.32-.387 1.55 1.55 0 0 1-.117-.641v-.522q0-.386.117-.641a.87.87 0 0 1 .32-.387.87.87 0 0 1 .47-.129q.265 0 .47.129a.86.86 0 0 1 .322.387q.117.255.117.641m.803.519v-.513q0-.565-.205-.973a1.46 1.46 0 0 0-.59-.63q-.38-.22-.916-.22-.534 0-.92.22a1.44 1.44 0 0 0-.589.628q-.205.407-.205.975v.513q0 .562.205.973.205.407.589.626.386.217.92.217.536 0 .917-.217.384-.22.589-.626.204-.41.205-.973m1.29-.935v2.675h-.746v-3.999h.662l1.752 2.66h.032v-2.66h.75v4h-.656l-1.761-2.676z"/>
                          </svg>
                        @else
                            <svg viewBox="0 0 20 20" class="icon">
                                <path d="M17 5v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2z"></path>
                            </svg>
                        @endif
                        <span>{{ basename($file['path'] ?? '') }}</span>
                    </div>
                </li>
            @endif
        @endforeach
    </ul>
@endif

<style>
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: #919eab;
}

.empty-state .icon {
    width: 60px;
    height: 60px;
    font-weight: bold;
    margin-bottom: 10px;
    fill: currentColor;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

.folder-content {
    padding-left: 20px;
    display: none;
}

.folder.open .folder-content {
    display: block;
}

.file-item {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    cursor: pointer;
}

.file-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.file-item .icon {
    width: 16px;
    height: 16px;
    margin-right: 8px;
    fill: currentColor;
    flex-shrink: 0;
}

.file-item span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.file-item.active {
    background-color: #3b4d5e;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle folders
    document.querySelectorAll('.folder > .file-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (e.target === this || e.target.closest('.file-item')) {
                this.parentElement.classList.toggle('open');
            }
        });
    });

    // Highlight active file
    const urlParams = new URLSearchParams(window.location.search);
    const activeFile = urlParams.get('file');
    if (activeFile) {
        const fileItem = document.querySelector(`.file-item[data-path="${activeFile}"]`);
        if (fileItem) {
            fileItem.classList.add('active');
            
            // Open parent folders
            let parent = fileItem.closest('.folder-content');
            while (parent) {
                parent.style.display = 'block';
                parent.previousElementSibling.parentElement.classList.add('open');
                parent = parent.closest('.folder-content');
            }
        }
    }
});
</script>
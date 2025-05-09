<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
      <div class="theme-editor-container">
          <!-- Sidebar -->
          <div class="theme-sidebar">
              <div class="sidebar-header">
                  <div class="theme-info">
                      <h3>{{ $theme->name }}</h3>
                      <span class="theme-version">v{{ $theme->version }}</span>
                      <div class="theme-status-badge">{{ $theme->role === 'main' ? 'Tema Principal' : 'Tema de Desenvolvimento' }}</div>
                  </div>
                  <div class="search-box">
                      <input type="text" id="file-filter" placeholder="Filtrar arquivos...">
                      <svg class="search-icon" viewBox="0 0 20 20">
                          <path d="M14.386 14.386l4.088 4.088-4.088-4.088A7.533 7.533 0 1 1 3.733 3.733a7.533 7.533 0 0 1 10.653 10.653z"></path>
                      </svg>
                  </div>
              </div>
              <div class="file-tree-container">
                  <div class="file-tree" id="file-tree">
                      @include('themes.partials.file-tree', ['files' => $files])
                  </div>
              </div>
          </div>
  
          <!-- Editor Principal -->
          <div class="theme-editor">
              <div class="editor-toolbar">
                  <div class="file-path" id="current-file-path">
                      <span class="file-icon">
                          <svg viewBox="0 0 20 20">
                              <path d="M17 5v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 2h5a2 2 0 0 1 2 2z"></path>
                          </svg>
                      </span>
                      <span class="path-text">Selecione um arquivo</span>
                  </div>
                  <div class="editor-actions">
                      <button class="btn btn-secondary" id="refresh-btn">
                          <svg viewBox="0 0 20 20" class="icon">
                              <path d="M10 3a7 7 0 1 0 7 7h-1.5a5.5 5.5 0 1 1-1.17-3.36l.17-.14H12v5l6.5-4-6.5-4v3.5h-.52A7 7 0 0 0 10 3z"></path>
                          </svg>
                          Atualizar
                      </button>
                      <button class="btn btn-secondary" id="preview-btn" disabled>
                          <svg viewBox="0 0 20 20" class="icon">
                              <path d="M10 12a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0-8.5c4.6 0 8.5 3.36 8.5 7.5 0 4.14-3.9 7.5-8.5 7.5S1.5 15.14 1.5 11c0-4.14 3.9-7.5 8.5-7.5z"></path>
                          </svg>
                          Pré-visualizar
                      </button>
                      <button class="btn btn-primary" id="save-btn" disabled>
                          <svg viewBox="0 0 20 20" class="icon">
                              <path d="M17 3h-2.5V1.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5V3H3a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V4a1 1 0 0 0-1-1zm-10-1h7V3H7V2zm8 15H5V5h2v1.5a.5.5 0 0 0 .5.5h5a.5.5 0 0 0 .5-.5V5h2v12z"></path>
                          </svg>
                          Salvar
                      </button>
                  </div>
              </div>
              <div id="code-editor" class="editor-content"></div>
              <div id="editor-status" class="editor-status-bar"></div>
          </div>
      </div>
  
      <!-- CodeMirror Assets -->
      <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css" rel="stylesheet">
      <link href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/shopify.min.css" rel="stylesheet">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closetag.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/closebrackets.min.js"></script>
  
      <style>
      :root {
          --sidebar-bg: #202e3e;
          --sidebar-text: #f5f5f7;
          --sidebar-hover: #2c3e50;
          --sidebar-active: #3b4d5e;
          --editor-bg: #ffffff;
          --toolbar-bg: #f9fafb;
          --toolbar-border: #dfe3e8;
          --primary: #5c6ac4;
          --primary-hover: #3f4dae;
          --secondary: #919eab;
          --success: #50b83c;
          --error: #de3618;
          --warning: #ffea8a;
      }
  
      .theme-editor-container {
          display: flex;
          height: calc(100vh - 60px);
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
      }
  
      /* Sidebar Styles */
      .theme-sidebar {
          width: 280px;
          background-color: var(--sidebar-bg);
          color: var(--sidebar-text);
          display: flex;
          flex-direction: column;
          border-right: 1px solid #1a2431;
      }
  
      .sidebar-header {
          padding: 16px;
          border-bottom: 1px solid #1a2431;
      }
  
      .theme-info {
          margin-bottom: 16px;
      }
  
      .theme-info h3 {
          margin: 0 0 4px 0;
          font-size: 16px;
          font-weight: 600;
      }
  
      .theme-version {
          font-size: 13px;
          color: var(--secondary);
          display: block;
          margin-bottom: 8px;
      }
  
      .theme-status-badge {
          display: inline-block;
          padding: 4px 8px;
          background-color: #2d3748;
          border-radius: 3px;
          font-size: 12px;
          font-weight: 500;
      }
  
      .search-box {
          position: relative;
      }
  
      .search-box input {
          width: 100%;
          padding: 8px 8px 8px 32px;
          background-color: #1a2431;
          border: 1px solid #1a2431;
          border-radius: 3px;
          color: white;
          font-size: 13px;
      }
  
      .search-box input:focus {
          outline: none;
          border-color: var(--primary);
      }
  
      .search-icon {
          position: absolute;
          left: 10px;
          top: 50%;
          transform: translateY(-50%);
          width: 14px;
          height: 14px;
          fill: var(--secondary);
      }
  
      .file-tree-container {
          flex: 1;
          overflow-y: auto;
          padding: 8px 0;
      }
  
      .file-tree {
          list-style: none;
          padding: 0;
          margin: 0;
      }
  
      .file-item {
          padding: 8px 16px;
          cursor: pointer;
          font-size: 13px;
          display: flex;
          align-items: center;
      }
  
      .file-item:hover {
          background-color: var(--sidebar-hover);
      }
  
      .file-item.active {
          background-color: var(--sidebar-active);
          font-weight: 500;
      }
  
      .file-item .icon {
          width: 16px;
          height: 16px;
          margin-right: 8px;
          fill: currentColor;
      }
  
      /* Editor Styles */
      .theme-editor {
          flex: 1;
          display: flex;
          flex-direction: column;
          background-color: var(--editor-bg);
      }
  
      .editor-toolbar {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 8px 16px;
          background-color: var(--toolbar-bg);
          border-bottom: 1px solid var(--toolbar-border);
      }
  
      .file-path {
          display: flex;
          align-items: center;
          font-family: 'SF Mono', 'Roboto Mono', monospace;
          font-size: 13px;
          color: #212b36;
      }
  
      .file-icon {
          margin-right: 8px;
      }
  
      .file-icon svg {
          width: 16px;
          height: 16px;
          fill: #637381;
      }
  
      .editor-actions {
          display: flex;
          gap: 8px;
      }
  
      .btn {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          padding: 8px 12px;
          border-radius: 3px;
          font-size: 13px;
          font-weight: 500;
          cursor: pointer;
          border: 1px solid transparent;
          transition: all 0.2s ease;
      }
  
      .btn .icon {
          width: 16px;
          height: 16px;
          margin-right: 6px;
      }
  
      .btn-primary {
          background-color: var(--primary);
          color: white;
      }
  
      .btn-primary:hover {
          background-color: var(--primary-hover);
      }
  
      .btn-primary:disabled {
          background-color: #c4cdd5;
          cursor: not-allowed;
      }
  
      .btn-secondary {
          background-color: white;
          border-color: #c4cdd5;
          color: #212b36;
      }
  
      .btn-secondary:hover {
          background-color: #f9fafb;
      }
  
      .btn-secondary:disabled {
          opacity: 0.6;
          cursor: not-allowed;
      }
  
      .editor-content {
          flex: 1;
          overflow: hidden;
      }
  
      .CodeMirror {
          height: 100% !important;
          font-family: 'SF Mono', 'Roboto Mono', monospace;
          font-size: 13px;
          line-height: 1.5;
      }
  
      .editor-status-bar {
          padding: 8px 16px;
          font-size: 12px;
          border-top: 1px solid var(--toolbar-border);
          background-color: var(--toolbar-bg);
      }
  
      /* Custom CodeMirror Theme */
      .cm-s-shopify .CodeMirror-gutters {
          background-color: #f4f6f8;
          border-right: 1px solid #dfe3e8;
      }
  
      .cm-s-shopify .CodeMirror-linenumber {
          color: #919eab;
      }
  
      /* Responsive */
      @media (max-width: 768px) {
          .theme-editor-container {
              flex-direction: column;
          }
          .theme-sidebar {
              width: 100%;
              height: 200px;
          }
      }
      </style>
  
      <script>
      document.addEventListener('DOMContentLoaded', function() {
          // Inicialização do Editor
          const editor = CodeMirror(document.getElementById('code-editor'), {
                  lineNumbers: true,
                  theme: 'shopify', // Certifique-se de ter este tema carregado
                  lineWrapping: true,
                  autoCloseTags: true,
                  autoCloseBrackets: true,
                  indentUnit: 4,
                  tabSize: 4,
                  readOnly: true,
                  styleActiveLine: true, // Destaque na linha ativa
                  matchBrackets: true, // Destaque para brackets correspondentes
                  extraKeys: {
                        'Ctrl-S': saveFile,
                        'Cmd-S': saveFile,
                        'Ctrl-F': 'findPersistent', // Busca persistente
                        'Ctrl-D': 'selectNextOccurrence', // Selecionar próxima ocorrência
                  },
                  // Configurações de estilo adicionais
                  gutters: ["CodeMirror-linenumbers", "CodeMirror-foldgutter"],
                  foldGutter: true,
                  highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true},
                  lint: true, // Habilita verificação de sintaxe se tiver um linter configurado
                  // Estilo personalizado para o cursor e seleção
                  styleSelectedText: true,
                  cursorBlinkRate: 530,
                  cursorHeight: 0.9,
                  // Configurações para Liquid (se estiver usando)
                  mode: 'htmlmixed' // Ou 'htmlmixed' se preferir
                  });

                  const style = document.createElement('style');
                        style.textContent = `
                        .CodeMirror {
                              font-family: 'Fira Code', 'Consolas', 'Monaco', 'Courier New', monospace;
                              font-size: 14px;
                              height: 100%;
                              background: #f8f9fa;
                              color: #212529;
                              border-radius: 4px;
                              box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }
                        
                        .CodeMirror-gutters {
                              background: #f8f9fa;
                              border-right: 1px solid #dee2e6;
                        }
                        
                        .CodeMirror-linenumber {
                              color: #6c757d;
                              padding: 0 8px;
                        }
                        
                        .CodeMirror-activeline-background {
                              background: rgba(0,0,0,0.03);
                        }
                        
                        .CodeMirror-focused .CodeMirror-activeline-background {
                              background: rgba(0,123,255,0.05);
                        }
                        
                        .CodeMirror-selected {
                              background: #d4e6ff;
                        }
                        
                        .CodeMirror-matchingbracket {
                              color: #0b7285 !important;
                              background: rgba(11, 114, 133, 0.1);
                              font-weight: bold;
                        }
                        
                        .CodeMirror-foldmarker {
                              background: #dee2e6;
                              color: #495057;
                              border-radius: 2px;
                              padding: 0 4px;
                        }
                        
                        .cm-liquid-tag {
                              color: #d63384;
                        }
                        
                        .cm-liquid-variable {
                              color: #0d6efd;
                        }
                        
                        .cm-liquid-filter {
                              color: #fd7e14;
                        }
                        `;
                        document.head.appendChild(style);
  
          // Estado do Editor
          const state = {
              currentFile: null,
              originalContent: '',
              fileModes: {
                  'liquid': 'htmlmixed',
                  'css': 'css',
                  'scss': 'css',
                  'js': 'javascript',
                  'json': 'application/json',
                  'html': 'htmlmixed',
                  'xml': 'xml'
              }
          };
  
          // Elementos da UI
          const ui = {
              currentFilePath: document.getElementById('current-file-path'),
              saveBtn: document.getElementById('save-btn'),
              previewBtn: document.getElementById('preview-btn'),
              refreshBtn: document.getElementById('refresh-btn'),
              statusEl: document.getElementById('editor-status'),
              fileFilter: document.getElementById('file-filter')
          };
  
         // Carregar Arquivo
document.querySelectorAll('.file-item').forEach(item => {
    item.addEventListener('click', () => {
        const filePath = item.getAttribute('data-path');
        // Verificação mais robusta para pastas
        const isDirectory = item.classList.contains('folder') || 
                           item.getAttribute('data-type') === 'directory' || 
                           !filePath.includes('.');
        
        if (filePath) loadFile(filePath, isDirectory);
    });
});

async function loadFile(filePath, isDirectory = false) {
    // Se for um diretório, apenas atualiza a UI sem fazer requisição
    if (isDirectory) {
        // Marca a pasta como ativa
        document.querySelectorAll('.file-item').forEach(el => el.classList.remove('active'));
        const activeItem = document.querySelector(`.file-item[data-path="${filePath}"]`);
        if (activeItem) activeItem.classList.add('active');
        
        // Atualiza o caminho exibido
        ui.currentFilePath.querySelector('.path-text').textContent = filePath;
        
        // Desativa botões que não fazem sentido para pastas
        ui.saveBtn.disabled = true;
        ui.previewBtn.disabled = true;
        
        // Limpa o editor
        editor.setValue('');
        editor.setOption('readOnly', true);
        
        updateStatus('Pasta selecionada', 'info');
        return;
    }

    // Verificação adicional para garantir que é um arquivo
    if (!filePath.includes('.')) {
        updateStatus('Item selecionado não é um arquivo válido', 'warning');
        return;
    }

    try {
        const response = await fetch(`/api/themes/{{ $theme->shopify_theme_id }}/files?path=${encodeURIComponent(filePath)}`);
        
        if (!response.ok) throw new Error(`Erro ${response.status}: ${response.statusText}`);
        
        const data = await response.json();
        
        state.currentFile = filePath;
        state.originalContent = data.content;
        
        // Atualizar UI
        ui.currentFilePath.querySelector('.path-text').textContent = filePath;
        ui.saveBtn.disabled = false;
        ui.previewBtn.disabled = false;
        
        // Configurar Editor
        editor.setValue(data.content);
        editor.setOption('readOnly', false);
        
        // Definir modo de linguagem
        const ext = filePath.split('.').pop().toLowerCase();
        editor.setOption('mode', state.fileModes[ext] || 'htmlmixed');
        
        // Marcar arquivo ativo
        document.querySelectorAll('.file-item').forEach(el => el.classList.remove('active'));
        const activeFile = document.querySelector(`.file-item[data-path="${filePath}"]`);
        if (activeFile) activeFile.classList.add('active');
        
        updateStatus('Arquivo carregado', 'success');
    } catch (error) {
        console.error('Erro ao carregar arquivo:', error);
        updateStatus(`Erro: ${error.message}`, 'error');
    }
}
  
          // Salvar Arquivo
          async function saveFile() {
              if (!state.currentFile) return;
              
              const content = editor.getValue();
              
              try {
                  const response = await fetch(`/api/themes/{{ $theme->shopify_theme_id }}/files`, {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      },
                      body: JSON.stringify({
                          path: state.currentFile,
                          content: content
                      })
                  });
  
                  if (!response.ok) throw new Error(`Erro ${response.status}: ${response.statusText}`);
                  
                  const data = await response.json();
                  state.originalContent = content;
                  updateStatus('Alterações salvas com sucesso', 'success');
              } catch (error) {
                  console.error('Erro ao salvar arquivo:', error);
                  updateStatus(`Erro ao salvar: ${error.message}`, 'error');
              }
          }
  
          // Atualizar Status
          function updateStatus(message, type = 'info') {
              ui.statusEl.textContent = message;
              ui.statusEl.className = `editor-status-bar ${type}`;
              setTimeout(() => ui.statusEl.textContent = '', 5000);
          }
  
          // Event Listeners
          document.querySelectorAll('.file-item').forEach(item => {
            item.addEventListener('click', () => {
                  const filePath = item.getAttribute('data-path');
                  const isDirectory = item.closest('.folder') !== null;
                  
                  if (filePath) loadFile(filePath, isDirectory);
            });
      });
  
          ui.saveBtn.addEventListener('click', saveFile);
          ui.refreshBtn.addEventListener('click', () => {
              if (state.currentFile) loadFile(state.currentFile);
          });
  
          ui.fileFilter.addEventListener('input', (e) => {
              const term = e.target.value.toLowerCase();
              document.querySelectorAll('.file-item').forEach(item => {
                  const fileName = item.getAttribute('data-path').toLowerCase();
                  item.style.display = fileName.includes(term) ? 'flex' : 'none';
              });
          });
  
          editor.on('change', () => {
              ui.saveBtn.disabled = editor.getValue() === state.originalContent;
          });
  
          // Hotkeys
          document.addEventListener('keydown', (e) => {
              if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                  e.preventDefault();
                  saveFile();
              }
          });
      });
      </script>
          </body>
          </html>
          
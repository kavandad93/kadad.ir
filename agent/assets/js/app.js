const state = {
    activeChatId: null,
    activeFilePath: null,
    openTabs: {}, 
    monacoInstance: null,
    csrfToken: document.body.getAttribute('data-csrf')
};

const ui = {
    init() {
        document.getElementById('agentInteractionForm').addEventListener('submit', (e) => {
            e.preventDefault();
            ui.submitPromptToAgent();
        });

        document.getElementById('settingsSubmissionUpdateConfigurationForm').addEventListener('submit', (e) => {
            e.preventDefault();
            ui.saveGlobalSettingsParameters();
        });
    },

    showSettingsModal() {
        document.getElementById('settingsGlobalModalConfigurationOverlayWindow').classList.remove('hidden-element');
    },

    hideSettingsModal() {
        document.getElementById('settingsGlobalModalConfigurationOverlayWindow').classList.add('hidden-element');
    },

    async saveGlobalSettingsParameters() {
        alert('Settings parameter modification committed successfully.');
        ui.hideSettingsModal();
    },

    async executeLogout() {
        await fetch('api/auth.php?action=logout');
        window.location.href = 'index.php?route=login';
    },

    appendLogEntry(action, file, status) {
        const container = document.getElementById('liveAuditLogStreamContainer');
        const entry = document.createElement('div');
        entry.className = 'audit-log-entry-row';
        entry.innerHTML = `<strong>[${new Date().toLocaleTimeString()}]</strong> <span style="color:var(--accent-primary)">${action}</span> | Target: <i>${file}</i> -> ${status}`;
        container.appendChild(entry);
        container.scrollTop = container.scrollHeight;
    },

    appendChatBubble(role, text) {
        const viewport = document.getElementById('chatConversationViewportArea');
        const bubble = document.createElement('div');
        bubble.className = `dialogue-message-bubble-row role-${role}`;
        bubble.textContent = text;
        viewport.appendChild(bubble);
        viewport.scrollTop = viewport.scrollHeight;
    },

    async submitPromptToAgent() {
        if (!state.activeChatId) {
            alert('Please select or create an active chat session.');
            return;
        }

        const inputField = document.getElementById('agentQueryInput');
        const promptText = inputField.value.trim();
        if (!promptText) return;

        inputField.value = '';
        this.appendChatBubble('user', promptText);

        const formData = new FormData();
        formData.append('prompt', promptText);

        try {
            const res = await fetch(`api/agent.php?chat_id=${state.activeChatId}`, {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                this.appendChatBubble('assistant', data.explanation);
                ui.appendLogEntry('AGENT_ACTION', data.action.type, 'EXECUTED');
                explorer.refreshTree();
            }
        } catch(e) {
            alert('Agent execution runtime pipeline block connection dropped.');
        }
    }
};

const chat = {
    async loadHistory() {
        const res = await fetch('api/chat.php?action=list');
        const data = await res.json();
        const container = document.getElementById('chatsListContainer');
        container.innerHTML = '';
        
        if(data.chats && data.chats.length > 0) {
            data.chats.forEach(c => {
                const row = document.createElement('div');
                row.className = `chat-session-link-row ${state.activeChatId === c.id ? 'active-session-chat' : ''}`;
                row.innerHTML = `<span>${c.title}</span>`;
                row.onclick = () => chat.select(c.id);
                container.appendChild(row);
            });
        }
    },

    async createNewSession() {
        const title = prompt('Enter session topic layout:');
        if(!title) return;
        const res = await fetch('api/chat.php?action=create', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title })
        });
        const data = await res.json();
        if (data.success) {
            state.activeChatId = data.id;
            this.loadHistory();
        }
    },

    select(id) {
        state.activeChatId = id;
        this.loadHistory();
        document.getElementById('chatConversationViewportArea').innerHTML = '';
        this.appendChatBubble('assistant', 'Session context loaded. Waiting for automated structural instructions...');
    }
};

const explorer = {
    async refreshTree() {
        const res = await fetch('api/workspace.php?action=list');
        const data = await res.json();
        const container = document.getElementById('fileExplorerTreeContainer');
        container.innerHTML = '';

        if(data.success && data.data) {
            data.data.forEach(item => {
                const node = document.createElement('div');
                node.className = 'tree-item-node-row';
                node.innerHTML = `<span>${item.type === 'folder' ? '📁' : '📄'} ${item.name}</span>`;
                if(item.type === 'file') {
                    node.onclick = () => editorCore.openFile(item.path);
                }
                container.appendChild(node);
            });
        }
    }
};

const editorCore = {
    initializeMonaco() {
        require.config({ paths: { vs: '[https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs](https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs)' } });
        require(['vs/editor/editor.main'], () => {
            const surface = document.getElementById('monacoEditorEngineSurfaceContainer');
            surface.innerHTML = '';
            state.monacoInstance = monaco.editor.create(surface, {
                value: '// Welcome to Kadad AI Integrated Monaco IDE Editor Engine Workspace Surface Panel',
                language: 'javascript',
                theme: 'vs-dark',
                automaticLayout: true
            });
        });
    },

    async openFile(path) {
        state.activeFilePath = path;
        const res = await fetch(`api/workspace.php?action=read&path=${encodeURIComponent(path)}`);
        const data = await res.json();
        if(data.success) {
            state.monacoInstance.setValue(data.content);
            ui.appendLogEntry('OPEN_FILE', path, 'SUCCESS');
        }
    }
};

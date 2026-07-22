<?php
// templates/dashboard.php
$csrf = Security::generateCSRFToken();

$stmt = $pdo->query("SELECT key_name, key_value FROM system_config");
$sysConfig = [];
foreach ($stmt->fetchAll() as $r) {
    $sysConfig[$r['key_name']] = $r['key_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Kadad AI Agent Console</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="[https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js](https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.39.0/min/vs/loader.min.js)"></script>
</head>
<body class="dark-theme-active app-layout-grid-base" data-csrf="<?php echo $csrf; ?>">

    <header class="app-header-navigation-bar">
        <div class="brand-system-meta-zone">
            <span class="logo-text-em">Kadad AI Agent</span>
            <span class="version-tag">v2.4 Engine</span>
        </div>
        <div class="workspace-selection-context-selector-box">
            <label style="font-size:0.8rem; margin-right:5px;">Active Workspace:</label>
            <input type="text" id="topWorkspaceDisplay" value="<?php echo Security::sanitizeHtml($sysConfig['current_workspace'] ?? ''); ?>" readonly placeholder="/workspaces/default">
        </div>
        <div class="session-management-actions-zone">
            <button class="btn-secondary-action" onclick="ui.showSettingsModal()">Global Settings</button>
            <button class="btn-danger-action" onclick="ui.executeLogout()">Logout</button>
        </div>
    </header>

    <main class="dashboard-workspace-viewport-matrix-grid">
        <section class="left-wing-panel-sidebar flex-column-layout">
            <div class="chats-history-panel-component flex-column-layout layout-segment-half">
                <div class="sidebar-header-section-bar">
                    <h3>Conversations</h3>
                    <button class="btn-icon-add" onclick="chat.createNewSession()">+ New Session</button>
                </div>
                <div id="chatsListContainer" class="scrollable-content-list-wrapper"></div>
            </div>
            <div class="project-explorer-panel-component flex-column-layout layout-segment-half">
                <div class="sidebar-header-section-bar">
                    <h3>Workspace Tree</h3>
                    <button class="btn-icon-add" onclick="explorer.createNewFolderPrompt()">+ Folder</button>
                    <button class="btn-icon-add" onclick="explorer.createNewFilePrompt()">+ File</button>
                </div>
                <div id="fileExplorerTreeContainer" class="scrollable-content-list-wrapper"></div>
            </div>
        </section>

        <section class="center-wing-workspace-viewport flex-column-layout">
            <div id="chatConversationViewportArea" class="chat-conversation-viewport-area scrollable-content-list-wrapper">
                <div class="system-welcome-message-banner" style="padding:20px; background:var(--bg-surface); border-radius:6px; margin-bottom:15px;">
                    <h2>Kadad AI Agent Automation Console</h2>
                    <p style="font-size:0.9rem; margin-top:10px; color:var(--text-main);">Provide instructional processing command intents using standard interaction inputs below.</p>
                </div>
            </div>
            <div class="chat-input-interaction-dock-bar">
                <form id="agentInteractionForm" class="flex-row-layout">
                    <textarea id="agentQueryInput" placeholder="Instruct the coding agent... (e.g., 'Analyze our project database definitions file')" required></textarea>
                    <button type="submit" class="btn-submit-interaction-trigger">Execute</button>
                </form>
            </div>
        </section>

        <section class="right-wing-workspace-preview-viewport flex-column-layout">
            <div class="tabs-navigation-management-bar-strip" id="editorTabsNavigationBarStrip"></div>
            <div id="monacoEditorEngineSurfaceContainer" class="monaco-editor-engine-surface-container flex-grow-layout-element" style="height: calc(100% - 195px);">
                <div class="editor-surface-placeholder-screen">
                    <p>Select a file from the Explorer Tree to open the editor surface viewport.</p>
                </div>
            </div>
            <div class="live-action-audit-log-tracker-component">
                <div class="audit-log-header-strip">
                    <h4>Agent Structural Actions Audit Log</h4>
                </div>
                <div id="liveAuditLogStreamContainer" class="live-audit-log-stream-container"></div>
            </div>
        </section>
    </main>

    <div id="settingsGlobalModalConfigurationOverlayWindow" class="modal-layout-infrastructure-structure-component-panel-overlay-window hidden-element">
        <div class="modal-card-box-content-wrapper">
            <h3>Global Environment Configuration Settings</h3>
            <hr style="border-color:var(--border-color); margin: 15px 0;">
            <form id="settingsSubmissionUpdateConfigurationForm">
                <div class="form-group-item">
                    <label>DeepSeek API Security Key Token</label>
                    <input type="password" id="cfg_api_key" value="<?php echo Security::sanitizeHtml($sysConfig['api_key'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Base Endpoint URL Route Gateway Destination</label>
                    <input type="text" id="cfg_base_url" value="<?php echo Security::sanitizeHtml($sysConfig['base_url'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Default LLM Model Identifier Sequence</label>
                    <input type="text" id="cfg_default_model" value="<?php echo Security::sanitizeHtml($sysConfig['default_model'] ?? ''); ?>">
                </div>
                <div class="form-group-item">
                    <label>Temperature Sampling Metric Factor</label>
                    <input type="number" step="0.1" min="0" max="2" id="cfg_temperature" value="<?php echo Security::sanitizeHtml($sysConfig['temperature'] ?? '0.2'); ?>">
                </div>
                <div class="form-group-item">
                    <label>Max Tokens Length Response Size Value</label>
                    <input type="number" id="cfg_max_tokens" value="<?php echo Security::sanitizeHtml($sysConfig['max_tokens'] ?? '4096'); ?>">
                </div>
                <div class="form-group-item">
                    <label>Active Project Workspace Absolute Path Target</label>
                    <input type="text" id="cfg_current_workspace" value="<?php echo Security::sanitizeHtml($sysConfig['current_workspace'] ?? ''); ?>">
                </div>
                <div class="modal-actions-footer-row-bar">
                    <button type="button" class="btn-secondary-action" onclick="ui.hideSettingsModal()">Cancel</button>
                    <button type="submit" class="btn-primary-action">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>

<div x-data="workflowEditor({{ json_encode($nodes) }}, {{ $workflowId }})" x-init="initEditor()" class="workflow-visual-editor" style="max-width: 960px; margin: 0 auto; padding: 32px 24px 64px; background: #F7F8FC; min-height: 600px;">
    
    <!-- HEADER -->
    <div class="header">
        <div>
            <div class="header-title">Éditeur de workflow</div>
            <div class="header-sub">Configuration des processus de prospection</div>
        </div>
        <div class="header-meta">
            <button @click="addCase()" class="badge-entry">+ Nouveau cas</button>
        </div>
    </div>

    <!-- WORKFLOW CONTENT -->
    <div id="workflow-content" class="workflow-content"></div>
    
    <!-- TOOLBAR -->
    <div class="workflow-toolbar flex items-center justify-between p-3 bg-white border rounded-lg mt-6 shadow-sm">
        <div class="flex items-center gap-2">
            <button @click="addNode('task')" class="px-3 py-1.5 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                + Tâche
            </button>
            <button @click="addNode('condition')" class="px-3 py-1.5 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
                + Condition
            </button>
            <button @click="addNode('action')" class="px-3 py-1.5 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
                + Action
            </button>
        </div>
        <div class="flex items-center gap-2">
            <button @click="saveWorkflow()" class="px-3 py-1.5 bg-indigo-500 text-white rounded hover:bg-indigo-600 text-sm">
                Sauvegarder
            </button>
        </div>
    </div>
    
    @if(session('workflow-saved'))
        <div class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg">
            Workflow sauvegardé avec succès!
        </div>
    @endif
</div>

<script>
function workflowEditor(nodes = [], workflowId = null) {
    return {
        nodes: nodes,
        cases: [],
        selectedNode: null,
        workflowId: workflowId,
        
        initEditor() {
            this.groupNodesByCases();
            this.renderWorkflow();
            this.$wire.on('workflow-saved-successfully', () => {
                alert('Workflow sauvegardé avec succès!');
            });
        },
        
        groupNodesByCases() {
            // Grouper les nœuds par cas basé sur leur config ou créer des cas automatiquement
            this.cases = [];
            let currentCase = null;
            let caseNumber = 1;
            
            this.nodes.forEach((node) => {
                const caseId = node.config?.case_id || null;
                
                if (caseId !== currentCase) {
                    currentCase = caseId || `case_${caseNumber}`;
                    this.cases.push({
                        id: currentCase,
                        number: caseNumber,
                        title: node.config?.case_title || `Cas ${caseNumber}`,
                        subtitle: node.config?.case_subtitle || '',
                        steps: []
                    });
                    caseNumber++;
                }
                
                this.cases[this.cases.length - 1].steps.push(node);
            });
        },
        
        renderWorkflow() {
            const container = document.getElementById('workflow-content');
            if (!container) return;
            
            container.innerHTML = '';
            
            this.cases.forEach((caseData) => {
                const caseCard = this.createCaseCard(caseData);
                container.appendChild(caseCard);
            });
        },
        
        createCaseCard(caseData) {
            const card = document.createElement('div');
            card.className = 'case-card';
            
            card.innerHTML = `
                <div class="case-card-header">
                    <div class="case-number">${caseData.number}</div>
                    <div>
                        <div class="case-title">${caseData.title}</div>
                        <div class="case-subtitle">${caseData.subtitle}</div>
                    </div>
                </div>
                <div class="flow">
                    ${caseData.steps.map((step, index) => this.renderStep(step, index)).join('')}
                </div>
            `;
            
            return card;
        },
        
        renderStep(step, index) {
            const stepColors = {
                task: 'teal',
                condition: 'purple',
                action: 'coral',
                notification: 'mint',
                approval: 'gray'
            };
            
            const stepIcons = {
                task: '🔍',
                condition: '🗣️',
                action: '📋',
                notification: '⏱',
                approval: '❓'
            };
            
            const color = stepColors[step.type] || 'gray';
            const icon = stepIcons[step.type] || '📝';
            
            let html = `
                <div class="flow-step ${color}">
                    <div class="flow-step-icon">${icon}</div>
                    <div class="flow-step-body">
                        <div class="flow-step-title">${step.label}</div>
                        <div class="flow-step-detail">${step.config?.description || ''}</div>
                    </div>
                </div>
            `;
            
            // Ajouter connecteur si pas le dernier
            if (index < this.nodes.length - 1) {
                html += `<div class="connector">↓</div>`;
            }
            
            // Ajouter branches si c'est une condition
            if (step.type === 'condition' && step.config?.branches) {
                html += this.renderBranches(step.config.branches);
            }
            
            return html;
        },
        
        renderBranches(branches) {
            const branchCount = branches.length;
            const gridClass = branchCount === 3 ? 'branches-3' : 'branches';
            
            let html = `<div class="${gridClass}">`;
            
            branches.forEach((branch) => {
                const branchColor = branch.type === 'yes' ? 'bb-yes' : 
                                   branch.type === 'no' ? 'bb-no' : 
                                   branch.color || 'bb-amber';
                
                html += `
                    <div class="branch-box ${branchColor}">
                        <div class="branch-box-label">${branch.label}</div>
                        <div class="branch-box-content">${branch.content}</div>
                        <div class="branch-box-detail">${branch.detail}</div>
                        ${branch.tag ? `<div class="tag-inline tag-${branch.tagColor || 'gray'}">${branch.tag}</div>` : ''}
                    </div>
                `;
            });
            
            html += '</div>';
            return html;
        },
        
        addNode(type) {
            this.$wire.addNode(type).then(() => {
                this.$wire.loadNodes();
            });
        },
        
        addCase() {
            const caseNumber = this.cases.length + 1;
            this.$wire.addNode('task', {
                config: {
                    case_id: `case_${caseNumber}`,
                    case_title: `Nouveau cas ${caseNumber}`,
                    case_subtitle: 'Description du cas'
                }
            }).then(() => {
                this.$wire.loadNodes();
            });
        },
        
        saveWorkflow() {
            this.$wire.saveWorkflow({ nodes: this.nodes });
        }
    };
}
</script>

<style>
:root {
    --purple:   #6C5CE7;
    --purple-l: #EDE9FC;
    --green:    #00B894;
    --green-l:  #D4F5EE;
    --amber:    #FDCB6E;
    --amber-l:  #FFF5DC;
    --amber-d:  #8B6914;
    --red:      #D63031;
    --red-l:    #FDECEA;
    --teal:     #0984E3;
    --teal-l:   #E3F2FD;
    --coral:    #E17055;
    --coral-l:  #FDEEE9;
    --pink:     #E84393;
    --pink-l:   #FCEAF4;
    --mint:     #00CEC9;
    --mint-l:   #E0FAF9;
    --gray:     #636E72;
    --gray-l:   #F1F2F6;
    --ink:      #1a1a2e;
    --border:   #DFE6E9;
    --white:    #ffffff;
    --radius:   8px;
    --shadow:   0 1px 4px rgba(0,0,0,0.08);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

.workflow-visual-editor {
    font-family: 'Inter', system-ui, sans-serif;
    background: #F7F8FC;
    color: #1a1a2e;
    font-size: 13px;
    line-height: 1.5;
}

.header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding-bottom: 20px;
    border-bottom: 2px solid #1a1a2e;
    margin-bottom: 28px;
}

.header-title {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: #1a1a2e;
    letter-spacing: -0.5px;
}

.header-sub {
    font-size: 12px;
    color: #636E72;
    margin-top: 4px;
}

.header-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
}

.badge-entry {
    background: #1a1a2e;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    white-space: nowrap;
    cursor: pointer;
    border: none;
}

.case-card {
    background: #ffffff;
    border: 1.5px solid #DFE6E9;
    border-radius: 12px;
    padding: 18px 18px 14px;
    margin-bottom: 14px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}

.case-card.updated {
    border-color: #00CEC9;
    border-width: 2px;
}

.case-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
    padding-bottom: 10px;
    border-bottom: 1px solid #DFE6E9;
}

.case-number {
    font-family: 'Inter Tight', 'Inter', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
    background: #1a1a2e;
    width: 34px; height: 34px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.case-title { font-weight: 700; font-size: 14px; color: #1a1a2e; }
.case-subtitle { font-size: 11px; color: #636E72; margin-top: 2px; }

.flow { display: flex; flex-direction: column; gap: 0; align-items: stretch; }

.flow-step {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 8px;
    border: 1.5px solid transparent;
}

.flow-step-icon { font-size: 14px; margin-top: 1px; flex-shrink: 0; width: 20px; text-align: center; }
.flow-step-body { flex: 1; }
.flow-step-title { font-weight: 600; font-size: 12px; }
.flow-step-detail { font-size: 11px; color: #636E72; margin-top: 2px; line-height: 1.4; }

.connector { text-align: center; color: #636E72; font-size: 14px; line-height: 1.8; }

.branches {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 4px;
}

.branches-3 {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 10px;
    margin-top: 4px;
}

.branch-box {
    border-radius: 8px;
    padding: 10px 12px;
    border: 1.5px solid transparent;
}

.branch-box-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.branch-box-content { font-size: 11.5px; font-weight: 500; }
.branch-box-detail  { font-size: 11px; color: inherit; opacity: 0.75; margin-top: 3px; line-height: 1.35; }

.bb-yes    { background: #D4F5EE;  border-color: #00B894; }
.bb-yes .branch-box-label   { color: #006b5a; }
.bb-yes .branch-box-content { color: #006b5a; }

.bb-no     { background: #FDECEA;    border-color: #D63031; }
.bb-no .branch-box-label    { color: #8b1a1a; }
.bb-no .branch-box-content  { color: #8b1a1a; }

.bb-amber  { background: #FFF5DC;  border-color: #FDCB6E; }
.bb-amber .branch-box-label   { color: #8B6914; }
.bb-amber .branch-box-content { color: #8B6914; }

.bb-purple { background: #EDE9FC; border-color: #6C5CE7; }
.bb-purple .branch-box-label   { color: #3d2e9c; }
.bb-purple .branch-box-content { color: #3d2e9c; }

.bb-teal   { background: #E3F2FD;   border-color: #0984E3; }
.bb-teal .branch-box-label    { color: #045f9c; }
.bb-teal .branch-box-content  { color: #045f9c; }

.bb-mint   { background: #E0FAF9;   border-color: #00CEC9; }
.bb-mint .branch-box-label    { color: #006b68; }
.bb-mint .branch-box-content  { color: #006b68; }

.teal   { background: #E3F2FD;   border-color: #0984E3; }
.teal .flow-step-title   { color: #045f9c; }
.coral  { background: #FDEEE9;  border-color: #E17055; }
.coral .flow-step-title  { color: #7d3520; }
.purple { background: #EDE9FC; border-color: #6C5CE7; }
.purple .flow-step-title { color: #3d2e9c; }
.gray   { background: #F1F2F6;   border-color: #DFE6E9; }
.gray .flow-step-title   { color: #2d3436; }
.mint   { background: #E0FAF9;   border-color: #00CEC9; }
.mint .flow-step-title   { color: #006b68; }

.tag-inline {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    font-family: monospace;
    padding: 1px 6px;
    border-radius: 4px;
    margin-left: 4px;
}

.tag-mint   { background: #E0FAF9;  color: #006b68; border: 1px solid #00CEC9; }
.tag-green  { background: #D4F5EE; color: #006b5a; border: 1px solid #00B894; }
.tag-amber  { background: #FFF5DC; color: #8B6914; border: 1px solid #FDCB6E; }
.tag-coral  { background: #FDEEE9; color: #7d3520; border: 1px solid #E17055; }
.tag-red    { background: #FDECEA;   color: #8b1a1a; border: 1px solid #D63031; }
.tag-teal   { background: #E3F2FD;  color: #045f9c; border: 1px solid #0984E3; }
.tag-gray   { background: #F1F2F6;  color: #2d3436; border: 1px solid #DFE6E9; }
.tag-purple { background: #EDE9FC;color: #3d2e9c; border: 1px solid #6C5CE7; }
</style>

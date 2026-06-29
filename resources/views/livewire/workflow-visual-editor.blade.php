<div x-data="workflowEditor({{ json_encode($nodes) }}, {{ $workflowId }})" x-init="initEditor()" class="workflow-visual-editor" style="height: 600px; width: 100%; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden;">
    <div class="workflow-toolbar flex items-center justify-between p-3 bg-gray-50 border-b">
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
            <button @click="addNode('notification')" class="px-3 py-1.5 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm">
                + Notification
            </button>
            <button @click="addNode('approval')" class="px-3 py-1.5 bg-red-500 text-white rounded hover:bg-red-600 text-sm">
                + Validation
            </button>
        </div>
        <div class="flex items-center gap-2">
            <button @click="autoLayout()" class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 text-sm">
                Auto-layout
            </button>
            <button @click="saveWorkflow()" class="px-3 py-1.5 bg-indigo-500 text-white rounded hover:bg-indigo-600 text-sm">
                Sauvegarder
            </button>
        </div>
    </div>
    
    <div id="workflow-canvas" class="workflow-canvas" style="height: calc(100% - 50px);"></div>
    
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
        edges: [],
        selectedNode: null,
        workflowId: workflowId,
        
        initEditor() {
            this.renderCanvas();
            this.$wire.on('workflow-saved-successfully', () => {
                alert('Workflow sauvegardé avec succès!');
            });
        },
        
        renderCanvas() {
            const canvas = document.getElementById('workflow-canvas');
            if (!canvas) return;
            
            canvas.innerHTML = '';
            
            // Créer un SVG simple pour les connexions
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.style.position = 'absolute';
            svg.style.top = '0';
            svg.style.left = '0';
            svg.style.width = '100%';
            svg.style.height = '100%';
            svg.style.pointerEvents = 'none';
            canvas.appendChild(svg);
            
            // Rendre les nœuds
            this.nodes.forEach((node, index) => {
                const nodeEl = this.createNodeElement(node, index);
                canvas.appendChild(nodeEl);
            });
            
            // Rendre les connexions
            this.renderConnections(svg);
        },
        
        createNodeElement(node, index) {
            const colors = {
                task: 'bg-blue-100 border-blue-500',
                condition: 'bg-yellow-100 border-yellow-500',
                action: 'bg-green-100 border-green-500',
                notification: 'bg-purple-100 border-purple-500',
                approval: 'bg-red-100 border-red-500'
            };
            
            const div = document.createElement('div');
            div.className = `workflow-node absolute p-4 rounded-lg border-2 shadow-sm cursor-move ${colors[node.type] || 'bg-gray-100 border-gray-500'}`;
            div.style.left = `${node.x || 50 + (index * 200)}px`;
            div.style.top = `${node.y || 50}px`;
            div.style.minWidth = '180px';
            div.dataset.id = node.id;
            
            div.innerHTML = `
                <div class="font-semibold text-sm">${node.label}</div>
                <div class="text-xs text-gray-600 mt-1">${node.type}</div>
                <div class="text-xs text-gray-500">Ordre: ${node.ordre}</div>
                <button @click="deleteNode(${node.id})" class="mt-2 text-xs text-red-600 hover:text-red-8 00">Supprimer</button>
            `;
            
            // Rendre le nœud déplaçable
            this.makeDraggable(div);
            
            // Sélection au clic
            div.addEventListener('click', (e) => {
                e.stopPropagation();
                this.selectNode(node);
            });
            
            return div;
        },
        
        makeDraggable(element) {
            let isDragging = false;
            let startX, startY, initialX, initialY;
            
            element.addEventListener('mousedown', (e) => {
                isDragging = true;
                startX = e.clientX;
                startY = e.clientY;
                initialX = parseInt(element.style.left) || 0;
                initialY = parseInt(element.style.top) || 0;
                element.style.zIndex = '1000';
            });
            
            document.addEventListener('mousemove', (e) => {
                if (!isDragging) return;
                
                const dx = e.clientX - startX;
                const dy = e.clientY - startY;
                
                element.style.left = `${initialX + dx}px`;
                element.style.top = `${initialY + dy}px`;
            });
            
            document.addEventListener('mouseup', () => {
                if (isDragging) {
                    isDragging = false;
                    element.style.zIndex = '';
                    this.updateNodePosition(element);
                }
            });
        },
        
        updateNodePosition(element) {
            const nodeId = element.dataset.id;
            const node = this.nodes.find(n => n.id == nodeId);
            if (node) {
                node.x = parseInt(element.style.left);
                node.y = parseInt(element.style.top);
            }
        },
        
        renderConnections(svg) {
            svg.innerHTML = '';
            
            for (let i = 0; i < this.nodes.length - 1; i++) {
                const from = this.nodes[i];
                const to = this.nodes[i + 1];
                
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', (from.x || 50 + (i * 200)) + 90);
                line.setAttribute('y1', (from.y || 50) + 50);
                line.setAttribute('x2', (to.x || 50 + ((i + 1) * 200)) + 90);
                line.setAttribute('y2', (to.y || 50) + 50);
                line.setAttribute('stroke', '#9ca3af');
                line.setAttribute('stroke-width', '2');
                line.setAttribute('marker-end', 'url(#arrowhead)');
                
                svg.appendChild(line);
            }
            
            // Ajouter la flèche
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
            marker.setAttribute('id', 'arrowhead');
            marker.setAttribute('markerWidth', '10');
            marker.setAttribute('markerHeight', '7');
            marker.setAttribute('refX', '9');
            marker.setAttribute('refY', '3.5');
            marker.setAttribute('orient', 'auto');
            
            const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
            polygon.setAttribute('points', '0 0, 10 3.5, 0 7');
            polygon.setAttribute('fill', '#9ca3af');
            
            marker.appendChild(polygon);
            defs.appendChild(marker);
            svg.appendChild(defs);
        },
        
        addNode(type) {
            this.$wire.addNode(type).then(() => {
                this.$wire.loadNodes();
            });
        },
        
        deleteNode(nodeId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette étape?')) {
                this.$wire.deleteNode(nodeId).then(() => {
                    this.$wire.loadNodes();
                });
            }
        },
        
        selectNode(node) {
            this.selectedNode = node;
            // Émettre un événement pour mettre à jour le formulaire Filament
            this.$dispatch('node-selected', { node: node });
        },
        
        autoLayout() {
            this.nodes.forEach((node, index) => {
                node.x = 50 + (index * 200);
                node.y = 50;
            });
            this.renderCanvas();
        },
        
        saveWorkflow() {
            this.$wire.saveWorkflow({ nodes: this.nodes });
        }
    };
}
</script>

<style>
.workflow-visual-editor {
    background-color: #f9fafb;
    background-image: 
        radial-gradient(circle, #d1d5db 1px, transparent 1px);
    background-size: 20px 20px;
}

.workflow-node {
    transition: box-shadow 0.2s, transform 0.1s;
}

.workflow-node:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transform: translateY(-1px);
}
</style>

// Drag and Drop utilities for Filament CRM
class DragDropManager {
    constructor() {
        this.draggedElement = null;
        this.dragSource = null;
        this.dragTarget = null;
        this.init();
    }

    init() {
        // Initialize drag and drop for sortable lists
        this.initSortableLists();
        this.initKanbanBoards();
    }

    initSortableLists() {
        const sortableLists = document.querySelectorAll('[data-sortable-list]');
        
        sortableLists.forEach(list => {
            this.makeSortable(list);
        });
    }

    makeSortable(element) {
        let draggedItem = null;

        element.addEventListener('dragstart', (e) => {
            draggedItem = e.target;
            e.target.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });

        element.addEventListener('dragend', (e) => {
            e.target.style.opacity = '1';
            draggedItem = null;
            
            // Save new order
            this.saveOrder(element);
        });

        element.addEventListener('dragover', (e) => {
            e.preventDefault();
            const afterElement = this.getDragAfterElement(element, e.clientY);
            if (afterElement == null) {
                element.appendChild(draggedItem);
            } else {
                element.insertBefore(draggedItem, afterElement);
            }
        });
    }

    getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll('[data-draggable]:not(.dragging)')];

        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    initKanbanBoards() {
        const kanbanColumns = document.querySelectorAll('[data-kanban-column]');
        
        kanbanColumns.forEach(column => {
            this.makeKanbanColumn(column);
        });
    }

    makeKanbanColumn(column) {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', (e) => {
            column.classList.remove('drag-over');
        });

        column.addEventListener('drop', (e) => {
            e.preventDefault();
            column.classList.remove('drag-over');
            
            const card = document.querySelector('.dragging');
            if (card) {
                column.appendChild(card);
                this.saveKanbanMove(card, column);
            }
        });
    }

    saveOrder(container) {
        const resourceId = container.dataset.resource;
        const orderedIds = [...container.children].map(child => child.dataset.id);
        
        if (resourceId && orderedIds.length > 0) {
            fetch(`/api/drag-drop/reorder/${resourceId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ordered_ids: orderedIds }),
            });
        }
    }

    saveKanbanMove(card, column) {
        const resourceId = card.dataset.resource;
        const recordId = card.dataset.id;
        const groupField = column.dataset.groupField;
        const groupValue = column.dataset.groupValue;
        
        if (resourceId && recordId && groupField && groupValue) {
            fetch(`/api/drag-drop/move/${resourceId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    id: recordId,
                    group_field: groupField,
                    group_value: groupValue,
                }),
            });
        }
    }
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    window.dragDropManager = new DragDropManager();
});

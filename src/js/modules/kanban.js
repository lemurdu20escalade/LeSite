/**
 * Kanban Board Module
 *
 * Drag and drop task management for bureau members.
 * Uses native HTML5 drag and drop API.
 *
 * @package Lemur
 */

/**
 * Task Modal Manager
 */
class TaskModal {
    constructor() {
        this.modal = document.getElementById('taskModal');
        if (!this.modal) return;

        this.backdrop = this.modal.querySelector('.task-modal__backdrop');
        this.closeBtn = this.modal.querySelector('.task-modal__close');
        this.title = this.modal.querySelector('.task-modal__title');
        this.priority = this.modal.querySelector('.task-modal__priority');
        this.status = this.modal.querySelector('.task-modal__status');
        this.recurring = this.modal.querySelector('.task-modal__recurring');
        this.dueDate = this.modal.querySelector('.task-modal__due-date');
        this.dueDateText = this.modal.querySelector('.task-modal__due-date-text');
        this.assigned = this.modal.querySelector('.task-modal__assigned');
        this.assignedAvatars = this.modal.querySelector('.task-modal__assigned-avatars');
        this.assignedNames = this.modal.querySelector('.task-modal__assigned-names');
        this.description = this.modal.querySelector('.task-modal__description');
        this.descriptionText = this.modal.querySelector('.task-modal__description-text');
        this.checklist = this.modal.querySelector('.task-modal__checklist');
        this.checklistProgress = this.modal.querySelector('.task-modal__checklist-progress');
        this.checklistItems = this.modal.querySelector('.task-modal__checklist-items');
        this.editBtn = this.modal.querySelector('.task-modal__edit-btn');

        this.currentTaskId = null;
        this.currentChecklist = [];

        this.bindEvents();
    }

    bindEvents() {
        if (this.closeBtn) {
            this.closeBtn.addEventListener('click', () => this.close());
        }
        if (this.backdrop) {
            this.backdrop.addEventListener('click', () => this.close());
        }

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.hidden) {
                this.close();
            }
        });
    }

    /**
     * Open modal with task data
     * @param {HTMLElement} card - The kanban card element
     */
    open(card) {
        const data = card.dataset;
        this.currentTaskId = data.taskId;

        // Title
        this.title.textContent = data.title || '';

        // Priority
        this.priority.dataset.priority = data.priority || 'medium';
        this.priority.textContent = data.priorityLabel || '';

        // Status
        this.status.textContent = data.statusLabel || '';
        this.status.dataset.status = data.status || '';

        // Recurring
        if (data.isRecurring === 'true') {
            this.recurring.hidden = false;
        } else {
            this.recurring.hidden = true;
        }

        // Due date
        if (data.dueDate) {
            this.dueDate.hidden = false;
            let dueDateHtml = data.dueDateFormatted || data.dueDate;
            if (data.isOverdue === 'true') {
                this.dueDate.classList.add('task-modal__due-date--overdue');
                dueDateHtml += ` <span class="task-modal__overdue-badge">${window.lemurKanban?.i18n?.overdue || 'En retard'}</span>`;
            } else {
                this.dueDate.classList.remove('task-modal__due-date--overdue');
            }
            this.dueDateText.innerHTML = dueDateHtml;
        } else {
            this.dueDate.hidden = true;
        }

        // Assigned users
        const assignedUsers = JSON.parse(data.assigned || '[]');
        if (assignedUsers.length > 0) {
            this.assigned.hidden = false;
            this.assignedAvatars.innerHTML = assignedUsers
                .map(u => `<img src="${u.avatar}" alt="${u.name}" class="task-modal__avatar" width="32" height="32">`)
                .join('');
            this.assignedNames.textContent = assignedUsers.map(u => u.name).join(', ');
        } else {
            this.assigned.hidden = true;
        }

        // Description
        if (data.description) {
            this.description.hidden = false;
            this.descriptionText.textContent = data.description;
        } else {
            this.description.hidden = true;
        }

        // Checklist
        this.currentChecklist = JSON.parse(data.checklist || '[]');
        if (this.currentChecklist.length > 0) {
            this.checklist.hidden = false;
            this.renderChecklist();
        } else {
            this.checklist.hidden = true;
        }

        // Edit button
        if (this.editBtn) {
            this.editBtn.href = `${window.lemurKanban?.editUrl || '/wp-admin/post.php?post='}${this.currentTaskId}&action=edit`;
        }

        // Show modal
        this.modal.hidden = false;
        document.body.style.overflow = 'hidden';

        // Focus trap
        this.closeBtn?.focus();
    }

    /**
     * Render checklist items
     */
    renderChecklist() {
        const done = this.currentChecklist.filter(i => i.done).length;
        const total = this.currentChecklist.length;
        this.checklistProgress.textContent = `${done}/${total}`;

        const canEdit = window.lemurKanban?.canEdit === true;

        this.checklistItems.innerHTML = this.currentChecklist.map((item, index) => `
            <li class="task-modal__checklist-item ${item.done ? 'task-modal__checklist-item--done' : ''}">
                <label class="task-modal__checklist-label">
                    <input type="checkbox"
                           class="task-modal__checklist-checkbox"
                           data-index="${index}"
                           ${item.done ? 'checked' : ''}
                           ${canEdit ? '' : 'disabled'}>
                    <span class="task-modal__checklist-text">${this.escapeHtml(item.item)}</span>
                </label>
            </li>
        `).join('');

        // Bind checkbox events if can edit
        if (canEdit) {
            this.checklistItems.querySelectorAll('.task-modal__checklist-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', (e) => this.toggleChecklistItem(e));
            });
        }
    }

    /**
     * Toggle checklist item
     * @param {Event} e - Change event
     */
    async toggleChecklistItem(e) {
        const index = parseInt(e.target.dataset.index, 10);
        const isChecked = e.target.checked;

        // Update local state
        this.currentChecklist[index].done = isChecked;

        // Update UI immediately
        const listItem = e.target.closest('.task-modal__checklist-item');
        if (isChecked) {
            listItem.classList.add('task-modal__checklist-item--done');
        } else {
            listItem.classList.remove('task-modal__checklist-item--done');
        }

        // Update progress
        const done = this.currentChecklist.filter(i => i.done).length;
        const total = this.currentChecklist.length;
        this.checklistProgress.textContent = `${done}/${total}`;

        // Update the card's data attribute
        const card = document.querySelector(`.kanban__card[data-task-id="${this.currentTaskId}"]`);
        if (card) {
            card.dataset.checklist = JSON.stringify(this.currentChecklist);

            // Update card's checklist badge
            const badge = card.querySelector('.kanban__card-checklist');
            const badgeCount = card.querySelector('.kanban__card-checklist-count');
            if (badge) {
                badge.title = `${done}/${total}`;
            }
            if (badgeCount) {
                badgeCount.textContent = `${done}/${total}`;
            }
        }

        // Save to server
        try {
            const response = await fetch(`${window.lemurKanban?.restUrl || '/wp-json/lemur/v1/'}tasks/${this.currentTaskId}/checklist`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': window.lemurKanban?.nonce || '',
                },
                body: JSON.stringify({ checklist: this.currentChecklist }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            if (import.meta.env.DEV) {
                console.log('[Lemur Kanban] Checklist updated');
            }
        } catch (error) {
            console.error('[Lemur Kanban] Checklist update failed:', error);
            // Revert on error
            this.currentChecklist[index].done = !isChecked;
            e.target.checked = !isChecked;
            listItem.classList.toggle('task-modal__checklist-item--done');
        }
    }

    /**
     * Close modal
     */
    close() {
        this.modal.hidden = true;
        document.body.style.overflow = '';
        this.currentTaskId = null;
    }

    /**
     * Escape HTML to prevent XSS
     * @param {string} text
     * @returns {string}
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

export class KanbanBoard {
    /**
     * @param {HTMLElement} container - The kanban board container
     */
    constructor(container) {
        this.container = container;
        this.canEdit = container.dataset.canEdit === 'true';
        this.restUrl = window.lemurKanban?.restUrl || '/wp-json/lemur/v1/';
        this.nonce = window.lemurKanban?.nonce || '';

        this.cards = container.querySelectorAll('.kanban__card');
        this.columns = container.querySelectorAll('.kanban__items');

        this.draggedCard = null;
        this.sourceColumn = null;
        this.isDragging = false;

        this.init();
    }

    /**
     * Initialize drag and drop
     */
    init() {
        this.bindCardEvents();

        if (this.canEdit) {
            this.bindColumnEvents();
        }

        if (import.meta.env.DEV) {
            console.log('[Lemur Kanban] Initialized with', this.cards.length, 'cards', this.canEdit ? '(editable)' : '(read-only)');
        }
    }

    /**
     * Bind events to cards
     */
    bindCardEvents() {
        this.cards.forEach(card => {
            // Click to open modal (only if not dragging and not clicking edit button)
            card.addEventListener('click', (e) => {
                // Don't open modal if clicking edit button
                if (e.target.closest('.kanban__card-edit')) {
                    return;
                }
                // Don't open modal if we were dragging
                if (this.isDragging) {
                    return;
                }
                taskModal?.open(card);
            });

            // Drag events only if editable
            if (this.canEdit && card.getAttribute('draggable') === 'true') {
                card.addEventListener('dragstart', this.handleDragStart.bind(this));
                card.addEventListener('dragend', this.handleDragEnd.bind(this));
            }
        });
    }

    /**
     * Bind events to columns
     */
    bindColumnEvents() {
        this.columns.forEach(column => {
            column.addEventListener('dragover', this.handleDragOver.bind(this));
            column.addEventListener('dragenter', this.handleDragEnter.bind(this));
            column.addEventListener('dragleave', this.handleDragLeave.bind(this));
            column.addEventListener('drop', this.handleDrop.bind(this));
        });
    }

    /**
     * Handle drag start
     * @param {DragEvent} e
     */
    handleDragStart(e) {
        this.isDragging = true;
        this.draggedCard = e.target.closest('.kanban__card');
        this.sourceColumn = this.draggedCard.closest('.kanban__items');

        this.draggedCard.classList.add('kanban__card--dragging');

        // Store task ID in dataTransfer
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.draggedCard.dataset.taskId);

        // Delay to allow CSS transition
        setTimeout(() => {
            this.draggedCard.style.opacity = '0.5';
        }, 0);
    }

    /**
     * Handle drag end
     * @param {DragEvent} e
     */
    handleDragEnd(e) {
        this.draggedCard.classList.remove('kanban__card--dragging');
        this.draggedCard.style.opacity = '';

        // Remove all drag-over states
        this.columns.forEach(col => {
            col.classList.remove('kanban__items--drag-over');
        });

        this.draggedCard = null;
        this.sourceColumn = null;

        // Reset dragging flag after a short delay (to prevent click from triggering)
        setTimeout(() => {
            this.isDragging = false;
        }, 100);
    }

    /**
     * Handle drag over
     * @param {DragEvent} e
     */
    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        // Get the element after which the card should be inserted
        const afterElement = this.getClosestCard(e.currentTarget, e.clientY);
        const card = this.draggedCard;

        if (afterElement === null) {
            e.currentTarget.appendChild(card);
        } else {
            e.currentTarget.insertBefore(card, afterElement);
        }

        // Update empty states immediately during drag
        this.updateColumnCounts();
    }

    /**
     * Handle drag enter
     * @param {DragEvent} e
     */
    handleDragEnter(e) {
        e.preventDefault();
        e.currentTarget.classList.add('kanban__items--drag-over');
    }

    /**
     * Handle drag leave
     * @param {DragEvent} e
     */
    handleDragLeave(e) {
        // Only remove class if leaving the column entirely
        if (!e.currentTarget.contains(e.relatedTarget)) {
            e.currentTarget.classList.remove('kanban__items--drag-over');
        }
    }

    /**
     * Handle drop
     * @param {DragEvent} e
     */
    async handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('kanban__items--drag-over');

        const taskId = e.dataTransfer.getData('text/plain');
        const newStatus = e.currentTarget.dataset.status;
        const oldStatus = this.sourceColumn?.dataset.status;

        // If status changed, update via API
        if (newStatus !== oldStatus) {
            await this.updateTaskStatus(taskId, newStatus);
        }

        // Update column counts
        this.updateColumnCounts();
    }

    /**
     * Get closest card element for insertion
     * @param {HTMLElement} column
     * @param {number} y - Mouse Y position
     * @returns {HTMLElement|null}
     */
    getClosestCard(column, y) {
        const cards = [...column.querySelectorAll('.kanban__card:not(.kanban__card--dragging)')];

        return cards.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element || null;
    }

    /**
     * Update task status via REST API
     * @param {string} taskId
     * @param {string} newStatus
     */
    async updateTaskStatus(taskId, newStatus) {
        try {
            const response = await fetch(`${this.restUrl}tasks/${taskId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.nonce,
                },
                body: JSON.stringify({ status: newStatus }),
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            if (import.meta.env.DEV) {
                console.log('[Lemur Kanban] Task updated:', data);
            }
        } catch (error) {
            console.error('[Lemur Kanban] Update failed:', error);

            // Revert visual change
            if (this.sourceColumn && this.draggedCard) {
                this.sourceColumn.appendChild(this.draggedCard);
            }

            // Show error to user
            this.showError('La mise à jour a échoué. Veuillez réessayer.');
        }
    }

    /**
     * Update column counts and empty states after drag
     */
    updateColumnCounts() {
        this.columns.forEach(column => {
            const cards = column.querySelectorAll('.kanban__card');
            const count = cards.length;
            const countEl = column.closest('.kanban__column')?.querySelector('.kanban__column-count');
            const emptyEl = column.querySelector('.kanban__empty');

            // Update count badge
            if (countEl) {
                countEl.textContent = count;
            }

            // Show/hide empty placeholder
            if (emptyEl) {
                emptyEl.style.display = count === 0 ? '' : 'none';
            }
        });
    }

    /**
     * Show error message
     * @param {string} message
     */
    showError(message) {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = 'kanban__toast kanban__toast--error';
        toast.textContent = message;
        toast.setAttribute('role', 'alert');

        this.container.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
}

// Global task modal instance
let taskModal = null;

/**
 * Initialize Kanban boards on page load
 */
export function initKanban() {
    const boards = document.querySelectorAll('.member-kanban:not([data-kanban-initialized])');

    if (boards.length === 0) {
        return;
    }

    // Initialize modal (once)
    if (!taskModal) {
        taskModal = new TaskModal();
    }

    boards.forEach(board => {
        board.setAttribute('data-kanban-initialized', 'true');
        new KanbanBoard(board);
    });

    if (import.meta.env.DEV) {
        console.log('[Lemur] Kanban initialized for', boards.length, 'board(s)');
    }
}

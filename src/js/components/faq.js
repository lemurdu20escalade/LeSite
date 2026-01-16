/**
 * FAQ Component
 *
 * Alpine.js components for FAQ search and category filtering.
 *
 * @package Lemur
 */

/**
 * FAQ Search component
 *
 * Provides real-time search filtering of FAQ items.
 *
 * @returns {object} Alpine.js component data
 */
export function faqSearch() {
    return {
        searchQuery: '',
        filteredCount: 0,

        /**
         * Filter FAQ items based on search query
         */
        filterFaqs() {
            const query = this.searchQuery.toLowerCase().trim();
            const faqItems = document.querySelectorAll('.faq-item');
            let count = 0;

            faqItems.forEach((item) => {
                const questionEl = item.querySelector('.faq-item__question-text');
                const answerEl = item.querySelector('.faq-item__answer-content');

                if (!questionEl) return;

                const question = questionEl.textContent.toLowerCase();
                const answer = answerEl ? answerEl.textContent.toLowerCase() : '';

                if (query.length < 3) {
                    item.style.display = '';
                    item.classList.remove('faq-item--highlighted');
                } else if (question.includes(query) || answer.includes(query)) {
                    item.style.display = '';
                    item.classList.add('faq-item--highlighted');
                    count++;
                } else {
                    item.style.display = 'none';
                    item.classList.remove('faq-item--highlighted');
                }
            });

            this.filteredCount = count;

            // Show/hide empty categories
            document.querySelectorAll('.faq-category').forEach((category) => {
                const visibleItems = category.querySelectorAll('.faq-item:not([style*="display: none"])');
                category.style.display = visibleItems.length > 0 ? '' : 'none';
            });
        },
    };
}

/**
 * Initialize FAQ category filters
 *
 * Sets up click handlers for category filter buttons.
 */
export function initFaqFilters() {
    const filterButtons = document.querySelectorAll('.faq-filter');
    const categories = document.querySelectorAll('.faq-category');

    if (filterButtons.length === 0) return;

    filterButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;

            // Update button states
            filterButtons.forEach((btn) => {
                btn.classList.remove('faq-filter--active');
                btn.setAttribute('aria-pressed', 'false');
            });
            button.classList.add('faq-filter--active');
            button.setAttribute('aria-pressed', 'true');

            // Show/hide categories
            categories.forEach((category) => {
                if (filter === 'all' || category.dataset.category === filter) {
                    category.style.display = '';
                } else {
                    category.style.display = 'none';
                }
            });

            // Reset search when changing filter
            const searchInput = document.getElementById('faq-search-input');
            if (searchInput && searchInput.value) {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            }
        });
    });
}

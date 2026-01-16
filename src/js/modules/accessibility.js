/**
 * Accessibility Module - WCAG 2.1 AA Compliance
 *
 * @package Lemur
 */

class LemurAccessibility {
  constructor() {
    this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.isKeyboardNavigating = false;
  }

  /**
   * Initialize all accessibility features
   */
  init() {
    this.initKeyboardNavigation();
    this.initFocusManagement();
    this.initAriaLive();
    this.initReducedMotion();
    this.initSkipLinks();

    if (import.meta.env.DEV) {
      this.initAccessibilityChecker();
    }
  }

  /**
   * Keyboard navigation detection
   */
  initKeyboardNavigation() {
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Tab') {
        document.body.classList.add('keyboard-navigation');
        this.isKeyboardNavigating = true;
      }
    });

    document.addEventListener('mousedown', () => {
      document.body.classList.remove('keyboard-navigation');
      this.isKeyboardNavigating = false;
    });

    // Arrow navigation in menus
    const menus = document.querySelectorAll('[role="menubar"], [role="menu"]');
    menus.forEach((menu) => this.setupMenuKeyboard(menu));

    // Grid navigation
    const grids = document.querySelectorAll('[role="grid"]');
    grids.forEach((grid) => this.setupGridKeyboard(grid));
  }

  /**
   * Menu keyboard navigation
   */
  setupMenuKeyboard(menu) {
    const items = menu.querySelectorAll('[role="menuitem"]');

    items.forEach((item, index) => {
      item.addEventListener('keydown', (e) => {
        let targetIndex = index;

        switch (e.key) {
          case 'ArrowDown':
          case 'ArrowRight':
            e.preventDefault();
            targetIndex = index < items.length - 1 ? index + 1 : 0;
            break;
          case 'ArrowUp':
          case 'ArrowLeft':
            e.preventDefault();
            targetIndex = index > 0 ? index - 1 : items.length - 1;
            break;
          case 'Home':
            e.preventDefault();
            targetIndex = 0;
            break;
          case 'End':
            e.preventDefault();
            targetIndex = items.length - 1;
            break;
          default:
            return;
        }

        items[targetIndex].focus();
      });
    });
  }

  /**
   * Grid keyboard navigation (roving tabindex)
   */
  setupGridKeyboard(grid) {
    const cells = grid.querySelectorAll('[role="gridcell"]');
    const columns = parseInt(grid.dataset.columns) || 3;

    cells.forEach((cell, index) => {
      cell.setAttribute('tabindex', index === 0 ? '0' : '-1');

      cell.addEventListener('keydown', (e) => {
        let targetIndex = index;
        const row = Math.floor(index / columns);
        const col = index % columns;

        switch (e.key) {
          case 'ArrowRight':
            e.preventDefault();
            targetIndex = col < columns - 1 && index < cells.length - 1 ? index + 1 : index;
            break;
          case 'ArrowLeft':
            e.preventDefault();
            targetIndex = col > 0 ? index - 1 : index;
            break;
          case 'ArrowDown':
            e.preventDefault();
            targetIndex = index + columns < cells.length ? index + columns : index;
            break;
          case 'ArrowUp':
            e.preventDefault();
            targetIndex = index - columns >= 0 ? index - columns : index;
            break;
          case 'Home':
            e.preventDefault();
            targetIndex = e.ctrlKey ? 0 : row * columns;
            break;
          case 'End':
            e.preventDefault();
            targetIndex = e.ctrlKey ? cells.length - 1 : Math.min((row + 1) * columns - 1, cells.length - 1);
            break;
          default:
            return;
        }

        cells.forEach((c) => c.setAttribute('tabindex', '-1'));
        cells[targetIndex].setAttribute('tabindex', '0');
        cells[targetIndex].focus();
      });
    });
  }

  /**
   * Focus trap for modals
   */
  initFocusManagement() {
    document.addEventListener('keydown', (e) => {
      if (e.key !== 'Tab') return;

      const trapElement = document.querySelector('[data-focus-trap="active"]');
      if (!trapElement) return;

      const focusableElements = trapElement.querySelectorAll(
        'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
      );

      if (focusableElements.length === 0) return;

      const firstElement = focusableElements[0];
      const lastElement = focusableElements[focusableElements.length - 1];

      if (e.shiftKey && document.activeElement === firstElement) {
        e.preventDefault();
        lastElement.focus();
      } else if (!e.shiftKey && document.activeElement === lastElement) {
        e.preventDefault();
        firstElement.focus();
      }
    });

    // Restore focus after modal close
    document.addEventListener('modal-close', (e) => {
      const trigger = e.detail?.trigger;
      if (trigger && typeof trigger.focus === 'function') {
        trigger.focus();
      }
    });
  }

  /**
   * Create ARIA live regions for announcements
   */
  initAriaLive() {
    // Polite announcements
    if (!document.getElementById('aria-live-region')) {
      const liveRegion = document.createElement('div');
      liveRegion.id = 'aria-live-region';
      liveRegion.className = 'sr-only';
      liveRegion.setAttribute('aria-live', 'polite');
      liveRegion.setAttribute('aria-atomic', 'true');
      document.body.appendChild(liveRegion);
    }

    // Urgent alerts
    if (!document.getElementById('aria-alert-region')) {
      const alertRegion = document.createElement('div');
      alertRegion.id = 'aria-alert-region';
      alertRegion.className = 'sr-only';
      alertRegion.setAttribute('role', 'alert');
      alertRegion.setAttribute('aria-live', 'assertive');
      document.body.appendChild(alertRegion);
    }
  }

  /**
   * Announce message to screen readers
   */
  static announce(message, priority = 'polite') {
    const regionId = priority === 'assertive' ? 'aria-alert-region' : 'aria-live-region';
    const region = document.getElementById(regionId);

    if (region) {
      region.textContent = '';
      setTimeout(() => {
        region.textContent = message;
      }, 100);
    }
  }

  /**
   * Handle prefers-reduced-motion
   */
  initReducedMotion() {
    if (this.prefersReducedMotion) {
      document.documentElement.classList.add('reduced-motion');
    }

    window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
      document.documentElement.classList.toggle('reduced-motion', e.matches);
      this.prefersReducedMotion = e.matches;
    });
  }

  /**
   * Enhanced skip links
   */
  initSkipLinks() {
    const skipLinks = document.querySelectorAll('.skip-link');

    skipLinks.forEach((link) => {
      link.addEventListener('click', (e) => {
        const targetId = link.getAttribute('href')?.slice(1);
        if (!targetId) return;

        const target = document.getElementById(targetId);
        if (target) {
          e.preventDefault();
          target.setAttribute('tabindex', '-1');
          target.focus();
          target.removeAttribute('tabindex');
        }
      });
    });
  }

  /**
   * Development accessibility checker
   */
  initAccessibilityChecker() {
    window.addEventListener('load', () => {
      const issues = [];

      // Check images without alt
      document.querySelectorAll('img:not([alt])').forEach((img) => {
        issues.push({
          type: 'error',
          element: img,
          message: 'Image sans attribut alt',
        });
      });

      // Check links without accessible text
      document.querySelectorAll('a').forEach((link) => {
        const text = link.textContent?.trim();
        const ariaLabel = link.getAttribute('aria-label');
        const title = link.getAttribute('title');

        if (!text && !ariaLabel && !title) {
          issues.push({
            type: 'error',
            element: link,
            message: 'Lien sans texte accessible',
          });
        }
      });

      // Check buttons without accessible text
      document.querySelectorAll('button').forEach((button) => {
        const text = button.textContent?.trim();
        const ariaLabel = button.getAttribute('aria-label');

        if (!text && !ariaLabel) {
          issues.push({
            type: 'error',
            element: button,
            message: 'Bouton sans texte accessible',
          });
        }
      });

      // Check heading hierarchy
      let lastLevel = 0;
      document.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach((heading) => {
        const level = parseInt(heading.tagName[1]);

        if (level > lastLevel + 1 && lastLevel > 0) {
          issues.push({
            type: 'warning',
            element: heading,
            message: `Saut de niveau de titre (h${lastLevel} → h${level})`,
          });
        }
        lastLevel = level;
      });

      // Check form fields without labels
      document.querySelectorAll('input, textarea, select').forEach((field) => {
        if (field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
          return;
        }

        const id = field.getAttribute('id');
        const ariaLabel = field.getAttribute('aria-label');
        const ariaLabelledBy = field.getAttribute('aria-labelledby');

        if (id) {
          const label = document.querySelector(`label[for="${id}"]`);
          if (!label && !ariaLabel && !ariaLabelledBy) {
            issues.push({
              type: 'error',
              element: field,
              message: 'Champ de formulaire sans label associé',
            });
          }
        }
      });

      // Display results
      if (issues.length > 0) {
        console.group('🔍 Audit accessibilité Lemur');
        issues.forEach((issue) => {
          const method = issue.type === 'error' ? 'error' : 'warn';
          console[method](issue.message, issue.element);
        });
        console.groupEnd();
      } else {
        console.log('✅ Aucun problème d\'accessibilité détecté');
      }
    });
  }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  window.lemurAccessibility = new LemurAccessibility();
  window.lemurAccessibility.init();
});

// Export for use in other modules
export default LemurAccessibility;

/**
 * Lazy Images Module
 *
 * Enhances native lazy loading with fade-in effects and error handling.
 * Note: Actual lazy loading is handled by the browser via loading="lazy".
 *
 * @package Lemur
 */

/**
 * Initialize lazy image enhancements
 *
 * Adds fade-in effect to lazy-loaded images.
 * Images remain visible by default (CSS fallback) if JS fails.
 */
export function initLazyImages() {
  // Get all lazy images
  const images = document.querySelectorAll('img[loading="lazy"]');

  images.forEach((img) => {
    setupLazyImage(img);
  });
}

/**
 * Handle image load errors
 *
 * @param {HTMLImageElement} img - The image element that failed to load
 */
function handleImageError(img) {
  // Skip if already handled (prevent infinite loop)
  if (img.dataset.errorHandled) {
    return;
  }
  img.dataset.errorHandled = 'true';

  // Add error class for styling
  img.classList.add('error');
  img.classList.remove('loaded');

  // Skip if fallback is disabled
  if (img.dataset.noFallback) {
    return;
  }

  // Generate placeholder dimensions from attributes or defaults (ensure integers)
  const width = parseInt(img.width || img.getAttribute('width'), 10) || 400;
  const height = parseInt(img.height || img.getAttribute('height'), 10) || 300;

  // Replace with placeholder SVG
  img.src = generatePlaceholder(width, height);
  img.classList.add('loaded');
}

/**
 * Generate a placeholder SVG data URI
 *
 * @param {number} width - Width in pixels
 * @param {number} height - Height in pixels
 * @param {string} color - Background color (hex)
 * @returns {string} Base64-encoded SVG data URI
 */
function generatePlaceholder(width, height, color = '#e5e5e5') {
  // Validate hex color to prevent SVG injection
  if (!/^#[0-9a-fA-F]{3,8}$/.test(color)) {
    color = '#e5e5e5';
  }

  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}"><rect width="100%" height="100%" fill="${color}"/></svg>`;

  return 'data:image/svg+xml;base64,' + btoa(svg);
}

/**
 * Observe new images added to the DOM
 *
 * Useful for dynamically loaded content (e.g., infinite scroll, AJAX).
 */
export function observeNewImages() {
  // Create a MutationObserver to watch for new images
  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        // Check if the added node is an image
        if (node.nodeName === 'IMG' && node.loading === 'lazy') {
          setupLazyImage(node);
        }

        // Check for images within added elements
        if (node.querySelectorAll) {
          const images = node.querySelectorAll('img[loading="lazy"]');
          images.forEach(setupLazyImage);
        }
      });
    });
  });

  // Start observing the document
  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  return observer;
}

/**
 * Set up lazy loading enhancements for a single image
 *
 * @param {HTMLImageElement} img - The image element
 */
function setupLazyImage(img) {
  // Skip if already processed
  if (img.classList.contains('lazy-fade')) {
    return;
  }

  // Add the fade class to enable CSS transitions
  img.classList.add('lazy-fade');

  // If already loaded, mark as loaded immediately
  if (img.complete && img.naturalHeight !== 0) {
    img.classList.add('loaded');
    return;
  }

  // Add loaded class when image loads
  img.addEventListener('load', () => {
    img.classList.add('loaded');
  });

  // Handle load errors
  img.addEventListener('error', () => {
    handleImageError(img);
  });
}

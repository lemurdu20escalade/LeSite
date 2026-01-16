/**
 * Email Obfuscator Module
 *
 * Click-to-reveal email protection with scramble animation.
 *
 * @package Lemur
 */

/**
 * Characters used for scramble animation
 */
const SCRAMBLE_CHARS = '!@#$%^&*()_+-=[]{}|;:,.<>?0123456789';

/**
 * Default configuration
 */
const DEFAULT_CONFIG = {
    delay: 1500,
    classPrefix: 'lemur-email',
    i18n: {
        decoding: 'Décodage...',
        copy: 'Copier',
        copied: 'Copié !',
        clickHint: 'Cliquez pour envoyer',
        copyFailed: 'Erreur de copie',
    },
};

/**
 * Get configuration from window or use defaults
 * @returns {Object} Configuration object
 */
function getConfig() {
    return window.lemurEmailConfig || DEFAULT_CONFIG;
}

/**
 * Decode base64 string
 * @param {string} encoded - Base64 encoded string
 * @returns {string} Decoded string
 */
function decode(encoded) {
    try {
        return atob(encoded);
    } catch {
        return '';
    }
}

/**
 * Generate random character from scramble set
 * @returns {string} Random character
 */
function randomChar() {
    return SCRAMBLE_CHARS[Math.floor(Math.random() * SCRAMBLE_CHARS.length)];
}

/**
 * Scramble text animation
 * @param {HTMLElement} element - Element to animate
 * @param {string} targetText - Final text to reveal
 * @param {number} duration - Animation duration in ms
 * @returns {Promise<void>}
 */
function scrambleAnimation(element, targetText, duration) {
    return new Promise((resolve) => {
        const startTime = performance.now();
        const originalLength = element.textContent?.length || 0;
        const targetLength = targetText.length;

        // Pad or trim to match target length gradually
        const maxLength = Math.max(originalLength, targetLength);

        function animate(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // Easing function (ease-out)
            const easeProgress = 1 - Math.pow(1 - progress, 3);

            let result = '';

            for (let i = 0; i < maxLength; i++) {
                // Calculate if this character should be revealed yet
                const charProgress = (i / maxLength) * 0.7 + progress * 0.3;

                if (charProgress < easeProgress && i < targetLength) {
                    // Character is revealed
                    result += targetText[i];
                } else if (i < targetLength) {
                    // Character is still scrambling
                    result += randomChar();
                }
                // Characters beyond target length fade out naturally
            }

            element.textContent = result;

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                element.textContent = targetText;
                resolve();
            }
        }

        requestAnimationFrame(animate);
    });
}

/**
 * Create revealed email element
 * @param {string} email - Decoded email address
 * @param {boolean} showCopyButton - Whether to show copy button
 * @param {boolean} isCompact - Whether using compact mode
 * @returns {HTMLElement} Revealed element
 */
function createRevealedElement(email, showCopyButton, isCompact = false) {
    const config = getConfig();
    const classPrefix = config.classPrefix;

    const container = document.createElement('span');
    container.className = `${classPrefix} ${classPrefix}--revealed${isCompact ? ` ${classPrefix}--compact` : ''}`;

    // Email link
    const link = document.createElement('a');
    link.href = `mailto:${email}`;
    link.className = `${classPrefix}__link`;

    // Icon
    const icon = document.createElement('span');
    icon.className = `${classPrefix}__icon`;
    icon.setAttribute('aria-hidden', 'true');
    icon.innerHTML = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`;

    // Email text
    const text = document.createElement('span');
    text.className = `${classPrefix}__email`;
    text.textContent = email;

    link.appendChild(icon);
    link.appendChild(text);
    container.appendChild(link);

    // Copy button
    if (showCopyButton) {
        const copyBtn = document.createElement('button');
        copyBtn.type = 'button';
        copyBtn.className = `${classPrefix}__copy`;
        copyBtn.setAttribute('aria-label', config.i18n.copy);
        copyBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;

        copyBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            try {
                await navigator.clipboard.writeText(email);

                // Visual feedback
                copyBtn.classList.add(`${classPrefix}__copy--success`);
                copyBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`;

                // Reset after delay
                setTimeout(() => {
                    copyBtn.classList.remove(`${classPrefix}__copy--success`);
                    copyBtn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>`;
                }, 2000);
            } catch {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = email;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();

                try {
                    document.execCommand('copy');
                    copyBtn.classList.add(`${classPrefix}__copy--success`);
                    setTimeout(() => {
                        copyBtn.classList.remove(`${classPrefix}__copy--success`);
                    }, 2000);
                } catch {
                    if (import.meta.env?.DEV) {
                        console.warn('[Lemur Email] Copy failed');
                    }
                }

                document.body.removeChild(textArea);
            }
        });

        container.appendChild(copyBtn);
    }

    // Hint
    const hint = document.createElement('span');
    hint.className = `${classPrefix}__hint`;
    hint.textContent = config.i18n.clickHint;
    container.appendChild(hint);

    return container;
}

/**
 * Handle email button click
 * @param {HTMLButtonElement} button - Button element
 */
async function handleClick(button) {
    const config = getConfig();
    const classPrefix = config.classPrefix;

    // Prevent double-click
    if (button.classList.contains(`${classPrefix}--decoding`) ||
        button.classList.contains(`${classPrefix}--revealed`)) {
        return;
    }

    // Get encoded data
    const encodedUser = button.dataset.u;
    const encodedDomain = button.dataset.d;
    const showCopy = button.dataset.copy !== 'false';
    const isCompact = button.classList.contains(`${classPrefix}--compact`);

    if (!encodedUser || !encodedDomain) {
        return;
    }

    // Decode email
    const user = decode(encodedUser);
    const domain = decode(encodedDomain);

    if (!user || !domain) {
        return;
    }

    const email = `${user}@${domain}`;

    // Set decoding state
    button.classList.add(`${classPrefix}--decoding`);
    button.disabled = true;

    // Get text element
    const textElement = button.querySelector(`.${classPrefix}__text`);
    const hintElement = button.querySelector(`.${classPrefix}__hint`);

    if (textElement && !isCompact) {
        // Update hint
        if (hintElement) {
            hintElement.textContent = config.i18n.decoding;
        }

        // Get the element to animate (obfuscated text or label)
        const animateTarget = textElement.querySelector(`.${classPrefix}__obfuscated`) ||
                              textElement.querySelector(`.${classPrefix}__label`) ||
                              textElement;

        // Run scramble animation
        await scrambleAnimation(animateTarget, email, config.delay);
    } else {
        // Compact mode or no text element - shorter delay
        if (hintElement) {
            hintElement.textContent = config.i18n.decoding;
        }
        await new Promise((resolve) => setTimeout(resolve, isCompact ? 800 : config.delay));
    }

    // Create revealed element
    const revealedElement = createRevealedElement(email, showCopy, isCompact);

    // Replace button with revealed element
    button.replaceWith(revealedElement);

    // Add reveal animation class
    requestAnimationFrame(() => {
        revealedElement.classList.add(`${classPrefix}--animate-in`);
    });
}

/**
 * Initialize email obfuscator
 */
export function initEmailObfuscator() {
    const config = getConfig();
    const selector = `.${config.classPrefix}:not(.${config.classPrefix}--revealed)`;

    // Find all email buttons
    const buttons = document.querySelectorAll(selector);

    buttons.forEach((button) => {
        // Skip if already initialized
        if (button.dataset.initialized === 'true') {
            return;
        }

        button.dataset.initialized = 'true';

        button.addEventListener('click', () => {
            handleClick(button);
        });

        // Keyboard support
        button.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleClick(button);
            }
        });
    });

    if (import.meta.env?.DEV) {
        console.log(`[Lemur Email] Initialized ${buttons.length} email(s)`);
    }
}

/**
 * Observe DOM for dynamically added emails
 */
export function observeEmails() {
    const config = getConfig();
    const selector = `.${config.classPrefix}:not(.${config.classPrefix}--revealed)`;

    const observer = new MutationObserver((mutations) => {
        let hasNewEmails = false;

        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.matches?.(selector) || node.querySelector?.(selector)) {
                        hasNewEmails = true;
                    }
                }
            });
        });

        if (hasNewEmails) {
            initEmailObfuscator();
        }
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
}

export default {
    init: initEmailObfuscator,
    observe: observeEmails,
};

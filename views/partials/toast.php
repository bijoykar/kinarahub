<?php
/**
 * views/partials/toast.php — Toast notification container + full JS implementation.
 *
 * Include this file once per page, ideally near the </body> tag.
 * After inclusion the following globals become available:
 *
 *   window.showToast(message, type, duration)
 *     @param {string}  message   — Text to display (HTML is escaped internally).
 *     @param {string}  type      — 'success' | 'error' | 'warning' | 'info' | 'undo'
 *     @param {number}  duration  — Auto-dismiss after N ms. Default: 4000. 0 = sticky.
 *     @returns {HTMLElement}    The created toast DOM element.
 *
 *   window.showUndoToast(message, onUndo, duration)
 *     @param {string}   message   — Toast body text.
 *     @param {Function} onUndo    — Callback invoked when the "Undo" button is clicked.
 *     @param {number}   duration  — Window (ms) the user has to click Undo. Default: 5000.
 *     @returns {HTMLElement}
 *
 *   window.dismissToast(toastEl)
 *     @param {HTMLElement} toastEl — The toast element to programmatically dismiss.
 *
 * Toast types and their colour schemes:
 *   success → green    (bg-green-50 / text-green-800)
 *   error   → red      (bg-red-50 / text-red-800)
 *   warning → amber    (bg-amber-50 / text-amber-800)
 *   info    → blue     (bg-blue-50 / text-blue-800)
 *   undo    → amber    (bg-amber-50 / text-amber-800) + Undo button
 *
 * PHP flash message bridge:
 *   If $_SESSION['_flash'] is set, this partial will emit calls to showToast()
 *   for each flash message and then clear the flash bag.
 *   Flash bag format: [['type' => 'success', 'message' => '...'], ...]
 */

// ---------------------------------------------------------------------------
// PHP flash message bridge
// ---------------------------------------------------------------------------
$flashMessages = [];
if (isset($_SESSION['_flash']) && is_array($_SESSION['_flash'])) {
    $flashMessages = $_SESSION['_flash'];
    unset($_SESSION['_flash']);
}
?>

<!-- Toast container -->
<div
    id="toast-container"
    class="fixed top-4 right-4 z-50 flex flex-col gap-2 pointer-events-none"
    aria-live="polite"
    aria-label="Notifications"
    role="region"
>
    <!-- Toasts are injected here by JS -->
</div>

<script>
(function (window) {
    'use strict';

    // -----------------------------------------------------------------------
    // Configuration
    // -----------------------------------------------------------------------
    var CONTAINER_ID   = 'toast-container';
    var DEFAULT_DURATION = 4000;  // ms before auto-dismiss
    var ANIMATION_OUT    = 300;   // ms for exit animation

    // Colour + icon map per type
    var TOAST_TYPES = {
        success: {
            wrapper: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-700/50 text-green-800 dark:text-green-200',
            icon:    'text-green-500 dark:text-green-400',
            bar:     'bg-green-400 dark:bg-green-500',
            svg: '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>',
        },
        error: {
            wrapper: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-700/50 text-red-800 dark:text-red-200',
            icon:    'text-red-500 dark:text-red-400',
            bar:     'bg-red-400 dark:bg-red-500',
            svg: '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>',
        },
        warning: {
            wrapper: 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700/50 text-amber-800 dark:text-amber-200',
            icon:    'text-amber-500 dark:text-amber-400',
            bar:     'bg-amber-400 dark:bg-amber-500',
            svg: '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>',
        },
        info: {
            wrapper: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700/50 text-blue-800 dark:text-blue-200',
            icon:    'text-blue-500 dark:text-blue-400',
            bar:     'bg-blue-400 dark:bg-blue-500',
            svg: '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd"/></svg>',
        },
        undo: {
            wrapper: 'bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700/50 text-amber-800 dark:text-amber-200',
            icon:    'text-amber-500 dark:text-amber-400',
            bar:     'bg-amber-400 dark:bg-amber-500',
            svg: '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.793 2.232a.75.75 0 01-.025 1.06L3.622 7.25h10.003a5.375 5.375 0 010 10.75H10.75a.75.75 0 010-1.5h2.875a3.875 3.875 0 000-7.75H3.622l4.146 3.957a.75.75 0 01-1.036 1.085l-5.5-5.25a.75.75 0 010-1.085l5.5-5.25a.75.75 0 011.06.025z" clip-rule="evenodd"/></svg>',
        },
    };

    // -----------------------------------------------------------------------
    // Internal helper: get or create the container
    // -----------------------------------------------------------------------
    function getContainer() {
        return document.getElementById(CONTAINER_ID);
    }

    // -----------------------------------------------------------------------
    // Internal helper: escape HTML for safe text content
    // -----------------------------------------------------------------------
    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    // -----------------------------------------------------------------------
    // window.dismissToast(toastEl)
    // Plays the exit animation then removes the element.
    // -----------------------------------------------------------------------
    window.dismissToast = function (toastEl) {
        if (!toastEl || toastEl._dismissing) return;
        toastEl._dismissing = true;

        toastEl.classList.remove('toast-enter');
        toastEl.classList.add('toast-exit');

        setTimeout(function () {
            if (toastEl.parentNode) toastEl.parentNode.removeChild(toastEl);
        }, ANIMATION_OUT);
    };

    // -----------------------------------------------------------------------
    // window.showToast(message, type, duration)
    // -----------------------------------------------------------------------
    window.showToast = function (message, type, duration) {
        type     = (type in TOAST_TYPES) ? type : 'info';
        duration = (duration === undefined) ? DEFAULT_DURATION : duration;

        var config    = TOAST_TYPES[type];
        var container = getContainer();
        if (!container) return null;

        // Build toast element
        var toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.className = [
            'pointer-events-auto relative flex w-80 max-w-sm items-start gap-3',
            'rounded-xl border px-4 py-3 shadow-lg shadow-black/10',
            'toast-enter',
            config.wrapper,
        ].join(' ');

        // Progress bar (only for timed toasts)
        var progressHTML = '';
        if (duration > 0) {
            progressHTML = '<div class="absolute bottom-0 left-0 h-0.5 rounded-bl-xl ' + config.bar + '" style="width:100%;transition:width ' + (duration / 1000) + 's linear"></div>';
        }

        toast.innerHTML = [
            progressHTML,
            '<span class="mt-0.5 ' + config.icon + '">' + config.svg + '</span>',
            '<p class="flex-1 text-sm font-medium leading-snug">' + escHtml(message) + '</p>',
            '<button',
            '  type="button"',
            '  class="ml-auto flex-shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-current"',
            '  aria-label="Dismiss notification"',
            '>',
            '  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">',
            '    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>',
            '  </svg>',
            '</button>',
        ].join('');

        // Dismiss on close button click
        var closeBtn = toast.querySelector('button');
        closeBtn.addEventListener('click', function () {
            window.dismissToast(toast);
        });

        container.appendChild(toast);

        // Start progress bar animation (next frame so transition fires)
        requestAnimationFrame(function () {
            var bar = toast.querySelector('div[style]');
            if (bar) bar.style.width = '0%';
        });

        // Auto-dismiss
        var timer = null;
        if (duration > 0) {
            timer = setTimeout(function () {
                window.dismissToast(toast);
            }, duration);
        }

        // Pause auto-dismiss on hover
        toast.addEventListener('mouseenter', function () {
            if (timer) clearTimeout(timer);
            var bar = toast.querySelector('div[style]');
            if (bar) bar.style.transitionDuration = '0s';
        });

        toast.addEventListener('mouseleave', function () {
            if (duration > 0) {
                timer = setTimeout(function () {
                    window.dismissToast(toast);
                }, 1200); // short grace period after hover
                var bar = toast.querySelector('div[style]');
                if (bar) {
                    bar.style.transitionDuration = '1.2s';
                    bar.style.width = '0%';
                }
            }
        });

        // Swipe right to dismiss (touch)
        var touchStartX = null;
        toast.addEventListener('touchstart', function (e) {
            touchStartX = e.touches[0].clientX;
        }, { passive: true });
        toast.addEventListener('touchend', function (e) {
            if (touchStartX === null) return;
            var delta = e.changedTouches[0].clientX - touchStartX;
            if (delta > 60) window.dismissToast(toast);
            touchStartX = null;
        }, { passive: true });

        return toast;
    };

    // -----------------------------------------------------------------------
    // window.showUndoToast(message, onUndo, duration)
    // Shows an amber toast with an "Undo" button.
    // Calls onUndo() if the user clicks the button within `duration` ms.
    // -----------------------------------------------------------------------
    window.showUndoToast = function (message, onUndo, duration) {
        duration = (duration === undefined) ? 5000 : duration;

        var config    = TOAST_TYPES['undo'];
        var container = getContainer();
        if (!container) return null;

        var toast = document.createElement('div');
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.className = [
            'pointer-events-auto relative flex w-80 max-w-sm items-start gap-3',
            'rounded-xl border px-4 py-3 shadow-lg shadow-black/10',
            'toast-enter',
            config.wrapper,
        ].join(' ');

        toast.innerHTML = [
            '<div class="absolute bottom-0 left-0 h-0.5 rounded-bl-xl ' + config.bar + '" style="width:100%;transition:width ' + (duration / 1000) + 's linear"></div>',
            '<span class="mt-0.5 ' + config.icon + '">' + config.svg + '</span>',
            '<div class="flex flex-1 flex-col gap-2">',
            '  <p class="text-sm font-medium leading-snug">' + escHtml(message) + '</p>',
            '  <button',
            '    type="button"',
            '    id="undo-btn"',
            '    class="self-start rounded-md bg-amber-100 dark:bg-amber-800/40 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-800/60 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"',
            '  >',
            '    Undo',
            '  </button>',
            '</div>',
            '<button',
            '  type="button"',
            '  id="close-btn"',
            '  class="ml-auto flex-shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 transition-opacity focus:outline-none focus-visible:ring-2 focus-visible:ring-current"',
            '  aria-label="Dismiss"',
            '>',
            '  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">',
            '    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>',
            '  </svg>',
            '</button>',
        ].join('');

        var undoBtn  = toast.querySelector('#undo-btn');
        var closeBtn = toast.querySelector('#close-btn');
        var timer    = null;
        var acted    = false;

        function finish(runUndo) {
            if (acted) return;
            acted = true;
            if (timer) clearTimeout(timer);
            if (runUndo && typeof onUndo === 'function') {
                try { onUndo(); } catch(e) { console.error('showUndoToast onUndo error:', e); }
            }
            window.dismissToast(toast);
        }

        undoBtn.addEventListener('click',  function () { finish(true);  });
        closeBtn.addEventListener('click', function () { finish(false); });

        timer = setTimeout(function () { finish(false); }, duration);

        // Pause on hover
        toast.addEventListener('mouseenter', function () {
            if (timer) clearTimeout(timer);
            var bar = toast.querySelector('div[style]');
            if (bar) bar.style.transitionDuration = '0s';
        });
        toast.addEventListener('mouseleave', function () {
            var bar = toast.querySelector('div[style]');
            if (bar) {
                bar.style.transitionDuration = '1.5s';
                bar.style.width = '0%';
            }
            timer = setTimeout(function () { finish(false); }, 1500);
        });

        container.appendChild(toast);

        requestAnimationFrame(function () {
            var bar = toast.querySelector('div[style]');
            if (bar) bar.style.width = '0%';
        });

        return toast;
    };

    // -----------------------------------------------------------------------
    // PHP flash message bridge — auto-fire toasts for session flash messages
    // -----------------------------------------------------------------------
    <?php if (!empty($flashMessages)): ?>
    (function () {
        var flashes = <?= json_encode($flashMessages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;
        // Slight delay so the page renders first
        setTimeout(function () {
            flashes.forEach(function (flash) {
                if (flash && flash.message) {
                    window.showToast(flash.message, flash.type || 'info', flash.duration || <?= DEFAULT_DURATION ?? 4000 ?>);
                }
            });
        }, 200);
    })();
    <?php endif; ?>

})(window);
</script>

<style>
    @keyframes slideIn {
        from { transform: translateX(110%); opacity: 0; }
        to   { transform: translateX(0);    opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0);    opacity: 1; }
        to   { transform: translateX(110%); opacity: 0; }
    }
    .toast-enter { animation: slideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    .toast-exit  { animation: slideOut 0.3s ease-in forwards; }
</style>

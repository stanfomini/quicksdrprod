import './bootstrap';

// Quick Claimstaker dialer JS — package files imported from vendor/.
// Vite resolves these at build time after `composer install` has populated vendor/.
import DialerSession from '../../vendor/stanfomini/quick-claimstaker/resources/js/dialer-session.js';
import SoftphoneManager from '../../vendor/stanfomini/quick-claimstaker/resources/js/softphone.js';

// Expose as globals so Livewire/Alpine components in the package's blade views
// can reference `new window.DialerSession(...)` and `new window.SoftphoneManager()`.
window.DialerSession = DialerSession;
window.SoftphoneManager = SoftphoneManager;

/**
 * Workaround for a Chromium + Livewire-morph interaction where a focused
 * native <select> (especially when wired up with wire:model.live) can
 * have its picker randomly reopen after unrelated clicks on the page.
 *
 * Reproducer: click a <flux:select>, pick an option, then click anywhere
 * neutral on the page. At some point Chromium re-opens the native picker
 * because the select is still document.activeElement and Livewire's morph
 * preserves focus across updates. Clicking into an <input> moves focus
 * away and the behavior stops.
 *
 * Fix: as soon as a native <select> receives a real user change event,
 * blur it (deferred by one tick so wire:model.live sees the value first).
 * That way Livewire's next morph never lands on a focused select and the
 * picker can't be spuriously re-opened.
 */
document.addEventListener('change', (event) => {
    const target = event.target;
    if (target instanceof HTMLSelectElement && event.isTrusted) {
        // Defer so Alpine/Livewire's change handler fires first with the
        // select still focused (they read document.activeElement in some
        // code paths). Blurring on the next tick is invisible to the user
        // but enough to keep Chromium from reopening the picker.
        setTimeout(() => {
            if (document.activeElement === target) {
                target.blur();
            }
        }, 0);
    }
}, true);

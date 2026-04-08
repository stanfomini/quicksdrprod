import './bootstrap';

// Quick Claimstaker dialer JS — package files imported from vendor/.
// Vite resolves these at build time after `composer install` has populated vendor/.
import DialerSession from '../../vendor/stanfomini/quick-claimstaker/resources/js/dialer-session.js';
import SoftphoneManager from '../../vendor/stanfomini/quick-claimstaker/resources/js/softphone.js';

// Expose as globals so Livewire/Alpine components in the package's blade views
// can reference `new window.DialerSession(...)` and `new window.SoftphoneManager()`.
window.DialerSession = DialerSession;
window.SoftphoneManager = SoftphoneManager;

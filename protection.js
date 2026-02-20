(function() {
    // Basic string obfuscation
    const _0x5f4e = ['innerHTML', 'documentElement', 'preventDefault', 'keydown', 'ctrlKey', 'key', 'view-source:', 'href', 'location', 'indexOf'];
    
    const protectionMessage = `
        <style>
            body {
                background: #050810;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 100vh;
                margin: 0;
                font-family: 'Plus Jakarta Sans', sans-serif;
                color: #8B5CF6;
                text-align: center;
                line-height: 1.6;
                cursor: none !important;
            }
            h1 { font-size: 2.5rem; margin-bottom: 1rem; }
            p { font-size: 1.2rem; color: #E2E8F0; opacity: 0.8; }
            .cursor {
                width: 20px;
                height: 20px;
                border: 2px solid var(--primary-color);
                border-radius: 50%;
                position: fixed;
                pointer-events: none;
                transform: translate(-50%, -50%);
                transition: all 0.2s ease;
                z-index: 9999;
                background: rgba(139, 92, 246, 0.1);
            }
            .cursor.clicking {
                transform: translate(-50%, -50%) scale(0.8);
                background: rgba(139, 92, 246, 0.2);
            }
        </style>
        <h1>Looking for Something? 🤔</h1>
        <p>This content is protected.</p>
        <div class="cursor"></div>
    `;

    const hideSourceCode = () => {
        // Clear document content
        document.documentElement.innerHTML = protectionMessage;
        
        // Restore cursor functionality
        const cursor = document.querySelector('.cursor');
        if (cursor) {
            document.addEventListener('mousemove', (e) => {
                cursor.style.left = e.clientX + 'px';
                cursor.style.top = e.clientY + 'px';
            });

            document.addEventListener('mousedown', () => {
                cursor.classList.add('clicking');
            });

            document.addEventListener('mouseup', () => {
                cursor.classList.remove('clicking');
            });
        }

        // Prevent source viewing
        Object.defineProperty(document, 'documentElement', {
            get: function() {
                return {
                    innerHTML: protectionMessage,
                    outerHTML: protectionMessage
                };
            }
        });

        // Override document methods
        document.getElementById = () => null;
        document.getElementsByClassName = () => [];
        document.getElementsByTagName = () => [];
        document.querySelector = () => null;
        document.querySelectorAll = () => [];
        
        // Hide window properties
        Object.defineProperty(window, 'sourceURL', {
            get: function() { return undefined; }
        });
        
        // Disable view source
        window.addEventListener('keydown', function(e) {
            if (e.ctrlKey && (e.key === 'u' || e.key === 'U' || e.key === 's' || e.key === 'S')) {
                e.preventDefault();
                return false;
            }
        }, true);

        // Clear all intervals and timeouts
        let id = window.setTimeout(() => {}, 0);
        while (id--) {
            window.clearTimeout(id);
            window.clearInterval(id);
        }

        // Prevent page restore
        history.pushState(null, '', window.location.href);
        window.onpopstate = function() {
            history.pushState(null, '', window.location.href);
        };
    };

    const detectDevTools = () => {
        const threshold = 160;
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if (widthThreshold || heightThreshold || window.Firebug || window.console.firebug) {
            hideSourceCode();
            return true;
        }
        return false;
    };

    const initProtection = () => {
        // Initial check
        if (detectDevTools()) return;

        // Monitor for DevTools
        const devtools = {
            isOpen: false,
            orientation: undefined
        };

        // Check continuously
        setInterval(() => {
            const widthThreshold = window.outerWidth - window.innerWidth > 160;
            const heightThreshold = window.outerHeight - window.innerHeight > 160;
            
            if (widthThreshold || heightThreshold || 
                window.Firebug || 
                window.console.firebug ||
                window.chrome?.webstore?.onInstallStaged ||
                (/./[Symbol.for('Symbol.prototype.toString')] + [])[14] === '+') {
                
                if (!devtools.isOpen) {
                    hideSourceCode();
                }
                devtools.isOpen = true;
            }
        }, 100);

        // Protect against right-click and keyboard shortcuts
        document.addEventListener('contextmenu', e => e.preventDefault());
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey && (e.key === 'u' || e.key === 's' || e.key === 'i')) || e.key === 'F12') {
                e.preventDefault();
                hideSourceCode();
                return false;
            }
        }, true);

        // Override console methods
        const methods = ['log', 'debug', 'info', 'warn', 'error', 'clear', 'dir'];
        methods.forEach(method => {
            console[method] = () => hideSourceCode();
        });

        // Protect against source viewing
        setInterval(() => {
            if (window.location.href.indexOf('view-source:') === 0) {
                window.location.href = 'about:blank';
            }
        }, 100);

        // Additional protection against debugger
        setInterval(() => {
            debugger;
        }, 50);

        // Preserve cursor functionality
        const cursor = document.querySelector('.cursor');
        if (cursor) {
            document.addEventListener('mousemove', (e) => {
                if (devtools.isOpen) return;
                cursor.style.left = e.clientX + 'px';
                cursor.style.top = e.clientY + 'px';
            });

            document.addEventListener('mousedown', () => {
                if (devtools.isOpen) return;
                cursor.classList.add('clicking');
            });

            document.addEventListener('mouseup', () => {
                if (devtools.isOpen) return;
                cursor.classList.remove('clicking');
            });
        }
    };

    // Start protection immediately
    try {
        initProtection();
        
        // Additional event listeners
        window.addEventListener('load', () => {
            document.addEventListener('selectstart', e => e.preventDefault());
            document.addEventListener('copy', e => e.preventDefault());
            document.addEventListener('cut', e => e.preventDefault());
        });
        
        // Protect against page source viewing
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey && (e.key === 'u' || e.key === 'U')) || e.key === 'F12') {
                hideSourceCode();
                e.preventDefault();
                return false;
            }
        }, true);
    } catch(e) {
        hideSourceCode();
        window.location.reload();
    }
})(); 
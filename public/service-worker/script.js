const divInstall = document.getElementById('pwa-installer-container');
const btnInstall = document.getElementById('pwa-installer-btn');
const btnClose = document.getElementById('pwa-installer-close');
const pwaStatus = sessionStorage.getItem("pwaStatus");

/* Put code here */

let installPromptEvent;

if(!pwaStatus) {
    window.addEventListener('beforeinstallprompt', (event) => {
        console.log('👍', 'beforeinstallprompt', event);
        // Prevent Chrome <= 67 from automatically showing the prompt
        event.preventDefault();
        // Stash the event so it can be triggered later.
        installPromptEvent = event;
        // Remove the 'hidden' class from the install button container
        divInstall.classList.toggle('d-none', false);
    });
}

btnInstall.addEventListener('click', () => {
    // Update the install UI to remove the install button
    divInstall.classList.toggle('d-none', true);
    // Show the modal add to home screen dialog
    installPromptEvent.prompt();
    // Wait for the user to respond to the prompt
    installPromptEvent.userChoice.then((choice) => {
        if (choice.outcome === 'accepted') {
            console.log('User accepted the A2HS prompt');
        } else {
            console.log('User dismissed the A2HS prompt');
        }
        // Clear the saved prompt since it can't be used again
        installPromptEvent = null;
    });
});

btnClose.addEventListener('click', () => {
    divInstall.remove();
    sessionStorage.setItem("pwaStatus", "disabled");
});

window.addEventListener('appinstalled', (event) => {
    console.log('👍', 'appinstalled', event);
    // Clear the deferredPrompt so it can be garbage collected
    window.deferredPrompt = null;
});

// butInstall.addEventListener('click', async () => {
//     console.log('👍', 'butInstall-clicked');
//     const promptEvent = window.deferredPrompt;
//     if (!promptEvent) {
//         // The deferred prompt isn't available.
//         return;
//     }
//     // Show the install prompt.
//     promptEvent.prompt();
//     // Log the result
//     const result = await promptEvent.userChoice;
//     console.log('👍', 'userChoice', result);
//     // Reset the deferred prompt variable, since
//     // prompt() can only be called once.
//     window.deferredPrompt = null;
//     // Hide the install button.
//     divInstall.classList.toggle('d-none', true);
// });
//
// window.addEventListener('appinstalled', (event) => {
//     console.log('👍', 'appinstalled', event);
//     // Clear the deferredPrompt so it can be garbage collected
//     window.deferredPrompt = null;
// });

/* Only register a service worker if it's supported */
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/service-worker.js');
}

/**
 * Warn the page must be served over HTTPS
 * The `beforeinstallprompt` event won't fire if the page is served over HTTP.
 * Installability requires a service worker with a fetch event handler, and
 * if the page isn't served over HTTPS, the service worker won't load.
 */
if (window.location.protocol === 'http:') {
    const requireHTTPS = document.getElementById('requireHTTPS');
    const link = requireHTTPS.querySelector('a');
    link.href = window.location.href.replace('http://', 'https://');
    requireHTTPS.classList.add('show');
}
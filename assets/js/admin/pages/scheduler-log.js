/**
 * Scheduled task failure detail.
 *
 * Loads the tail of a command log into a modal so the admin can see why a task
 * failed or got locked. Fetched on demand to keep the dashboard render light.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const triggers = document.querySelectorAll('.scheduler-detail');
    if (triggers.length === 0) {
        return;
    }

    const modalEl = document.getElementById('schedulerLogModal');
    const titleEl = document.getElementById('schedulerLogModalTitle');
    const bodyEl = document.getElementById('schedulerLogModalBody');
    if (!modalEl || !bodyEl) {
        return;
    }

    import('../bootstrap/dist/modal').then(({default: Modal}) => {

        const modal = Modal.getOrCreateInstance(modalEl);

        triggers.forEach(trigger => {
            trigger.addEventListener('click', () => {

                const url = trigger.dataset.logUrl;
                if (!url) {
                    return;
                }

                if (titleEl && trigger.dataset.taskName) {
                    titleEl.textContent = trigger.dataset.taskName;
                }
                bodyEl.innerHTML = '<p class="scheduler-log-loading">Chargement du journal…</p>';
                modal.show();

                fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('HTTP ' + response.status);
                        }
                        return response.text();
                    })
                    .then(html => {
                        bodyEl.innerHTML = html;
                    })
                    .catch(() => {
                        bodyEl.innerHTML = '<p class="scheduler-log-error">Impossible de charger le journal de cette tâche.</p>';
                    });
            });
        });

    }).catch(error => console.error(error.message));
}

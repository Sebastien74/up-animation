/**
 * Modal
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
export default function () {

    import('../dist/modal').then(({default: Modal}) => {
        setModals(Modal);
    }).catch(error => console.error(error.message));

    let setModals = function (Modal) {

        let body = document.body;

        /** To get modals data  */
        let getData = function (modal, slug, defaultValue = true, onlyValue = false) {
            let attributeExist = modal.hasAttribute(slug);
            let attribute = attributeExist ? modal.getAttribute(slug) : defaultValue;
            let backdropStatus = attribute === true || attribute === 'true' || attribute === '1' || attribute === 1;
            if (onlyValue) {
                return attribute;
            }
            return backdropStatus;
        }

        /** To get element position  */
        let getOffset = function (el) {
            const rect = el.getBoundingClientRect();
            return {
                left: rect.left + window.scrollX,
                top: rect.top + window.scrollY
            }
        }

        /** Modals with timer */
        let timerModals = body.querySelectorAll('[data-modal-timer]');
        if (timerModals.length > 0) {
            timerModals.forEach((modal) => {
                import('js-cookie').then(({default: Cookies}) => {
                    let cookieName = modal.dataset.cookieName;
                    let cookieDelay = parseInt(modal.dataset.cookieDelay);
                    let cookie = Cookies.get(cookieName);
                    let timerModal = new Modal(modal, {
                        keyboard: false
                    });
                    if (!cookie) {
                        setTimeout(function () {
                            timerModal.show();
                        }, parseInt(modal.dataset.modalTimer));
                    }
                    let closeBtn = modal.querySelector('.btn-close');
                    let secure = location.protocol !== "http:";
                    let domainName = window.location.host;
                    let domain = domainName.replace('www.', '');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', (e) => {
                            Cookies.set(cookieName, true, {
                                expires: cookieDelay,
                                path: '/',
                                domain: domain,
                                secure: secure
                            });
                        });
                    }
                    modal.addEventListener('click', (e) => {
                        Cookies.set(cookieName, true, {
                            expires: cookieDelay,
                            path: '/',
                            domain: domain,
                            secure: secure
                        });
                    });
                }).catch(error => console.error(error.message));
            });
        }

        /** Modals show  */
        let modals = body.getElementsByClassName('modal');

        if (modals.length > 0) {

            for (let i = 0; i < modals.length; i++) {

                let modal = modals[i];
                let modalDialog = modal.querySelector('.modal-dialog');
                let button = document.querySelector('[data-bs-target="#' + modal.getAttribute('id') + '"]');
                let backdropStatus = getData(modal, 'data-backdrop');
                let modalPosition = getData(modal, 'data-position', 'initial', true);

                if (modalPosition === 'button' && !modalDialog.classList.contains('position-fixed') && button) {
                    let blockOffsets = getOffset(modal.closest('.layout-block'));
                    let buttonOffsets = getOffset(button);
                    let top = parseInt(buttonOffsets.top) - parseInt(blockOffsets.top);
                    let left = parseInt(buttonOffsets.left) - parseInt(blockOffsets.left);
                    modalDialog.classList.add('position-fixed');
                    modalDialog.style.top = top + "px";
                    modalDialog.style.left = left + "px";
                }

                modal.addEventListener('show.bs.modal', function (event) {
                    if (!backdropStatus) {
                        document.body.classList.add("no-backdrop");
                    }
                })

                modal.addEventListener('hidden.bs.modal', function (event) {
                    if (!backdropStatus) {
                        let backdrops = modal.getElementsByClassName('modal-backdrop');
                        for (let j = 0; j < backdrops.length; j++) {
                            backdrops[j].remove();
                        }
                        document.body.classList.remove("no-backdrop");
                    }
                })
            }
        }
    }
}
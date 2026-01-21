/**
 * Prototype
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    const prototypesBtn = document.querySelectorAll('.add-to-collection');
    let body = document.body;

    if (prototypesBtn && prototypesBtn.length > 0) {

        for (let i = 0; i < prototypesBtn.length; i++) {
            prototypesBtn[i].onclick = function (e) {
                e.preventDefault();
                addFormToCollection(prototypesBtn[i]);
            }
        }

        function addFormToCollection(btn) {

            const collectionHolder = document.getElementById(btn.dataset.collectionTarget);
            const index = collectionHolder.dataset.index;

            const prototypeHTML = collectionHolder
                .dataset
                .prototype
                .replace(/__name__/g, index);

            const temp = document.createElement('div');
            temp.innerHTML = prototypeHTML.trim();

            const newItem = temp.firstElementChild; // .appointment-item
            collectionHolder.appendChild(newItem);
            collectionHolder.dataset.index++;

            import('./form-package').then(({ default: formPackage }) => {
                new formPackage(false);
            }).catch(error => console.error(error.message));

            deleteFormToCollection();
        }

        const deleteFormToCollection = () => {

            document.querySelectorAll('.collection-delete').forEach(button => {

                let itemCollection = button.closest('.item-collection');
                let loader = itemCollection.getElementsByClassName('modal-loader')[0];

                button.onclick = function (e) {

                    e.preventDefault();

                    loader.classList.remove('d-none');

                    let href = button.getAttribute('href');
                    if (href) {

                        let form = itemCollection.closest('form');
                        let formId = form.getAttribute('id');
                        let formAction = form.getAttribute('action');
                        let urlDelete = formAction + '?delete=true';
                        let urlGet = formAction + '?ajax=true';
                        if (formAction.match(/\?./)) {
                            urlDelete = formAction + '&delete=true';
                            urlGet = formAction + '&ajax=true';
                        }

                        /** POST Form */
                        let xHttpPost = new XMLHttpRequest();
                        xHttpPost.open("POST", urlDelete, true);
                        xHttpPost.send(new FormData(form));
                        xHttpPost.onload = function (e) {
                            if (this.readyState === 4 && this.status === 200) {
                                /** DELETE Item */
                                let xHttpDelete = new XMLHttpRequest();
                                xHttpDelete.open("DELETE", href, true);
                                xHttpDelete.send();
                                xHttpDelete.onload = function (e) {
                                    if (this.readyState === 4 && this.status === 200) {
                                        /** GET Form */
                                        let xHttpGet = new XMLHttpRequest();
                                        xHttpGet.open("GET", urlGet, true);
                                        xHttpGet.setRequestHeader("Content-Type", "application/json; charset=utf-8");
                                        xHttpGet.send();
                                        xHttpGet.onload = function (e) {
                                            if (this.readyState === 4 && this.status === 200) {
                                                let response = JSON.parse(this.response);
                                                let html = document.createElement('div');
                                                html.innerHTML = response.html;
                                                let form = html.getElementsByClassName('form-ajax')[0];
                                                let container = document.getElementById(formId).parentNode;
                                                container.innerHTML = form.closest('.form-container').innerHTML;
                                                let body = document.body;
                                                document.querySelectorAll('.loader').forEach(loader => {
                                                    loader.classList.add('d-none');
                                                });
                                                document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                                                    backdrop.remove();
                                                });
                                                body.classList.remove('modal-open');
                                                body.style.cssText = 'overflow: initial; padding-right: 0';
                                                deleteFormToCollection();
                                                import('./form-package').then(({default: formPackage}) => {
                                                    new formPackage();
                                                }).catch(error => console.error(error.message));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        itemCollection.remove();
                        body.classList.remove('modal-open');
                        body.style.cssText = 'overflow: initial; padding-right: 0';
                        loader.classList.add('d-none');
                        document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
                            backdrop.remove();
                        });
                    }
                }
            });
        }

        deleteFormToCollection();
    }
}
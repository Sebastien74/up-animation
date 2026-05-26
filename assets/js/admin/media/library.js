import displayAlert from "../core/alert";
import dropifyJS from "../form/dropify";
import resetModal from "../../vendor/components/reset-modal";
import route from "../../vendor/components/routing";
import select2 from "../../vendor/plugins/select2";
import '../../../scss/admin/pages/library.scss';
import '../../../scss/admin/lib/sweetalert.scss';

import '../lib/sweetalert/sweetalert.min';
import Modal from '../bootstrap/dist/modal';
import Tooltip from '../bootstrap/dist/tooltip';
import '../media/cache-resolve';
import '../media/cache-clear';

let folderModalEl = document.getElementById('new-modal-folder');
if (folderModalEl) {
    folderModalEl.addEventListener('show.bs.modal', function () {
        let form = folderModalEl.querySelector('form');
        if (form) form.reset();
        let select = folderModalEl.querySelector('#folder_parent');
        if (select) {
            select.querySelectorAll("option").forEach(option => option.removeAttribute("selected"));
            select.dispatchEvent(new Event('change'));
        }
    });
}

document.body.addEventListener('click', function (e) {
    let reorderBtn = e.target.closest('#reorder-medias-btn');
    if (reorderBtn) {
        let trans = document.getElementById('data-translation');
        return swal({
            title: trans.dataset.swalDeleteTitle,
            text: trans.dataset.swalDeleteText,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans.dataset.swalDeleteConfirmText,
            cancelButtonText: trans.dataset.swalDeleteCancelText,
            closeOnConfirm: false
        }, function () {
            import(/* webpackPreload: true */ '../media/reorder-medias').then(({default: reorder}) => {
                reorder();
            }).catch(error => console.error(error.message));
        });
    }

    let editBtn = e.target.closest('.open-media-edit');
    if (editBtn) {
        e.preventDefault();
        let loader = document.getElementById('medias-preloader');
        let loaderParent = loader ? loader.parentElement : null;

        if (loader) loader.classList.remove('d-none');
        if (loaderParent) loaderParent.classList.remove('d-none');

        fetch(editBtn.getAttribute('href') + "?ajax=1", {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {
                if (response.html) {
                    document.body.insertAdjacentHTML('beforeend', response.html);
                    let modalEl = document.getElementById('media-edition-modal');
                    let modal = new Modal(modalEl);
                    modal.show();

                    dropifyJS();

                    modalEl.querySelectorAll('[data-toggle="tooltip"]').forEach(el => new Tooltip(el));
                    modalEl.addEventListener('hidden.bs.modal', function () {
                        modalEl.remove();
                    });
                }
                if (loader) loader.classList.add('d-none');
                if (loaderParent) loaderParent.classList.add('d-none');
            })
            .catch(errors => {
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopImmediatePropagation();
        return false;
    }
});

activeSearch();

if (document.querySelector('hx\\:include')) {
    import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
        mediaLoader();
    }).catch(error => console.error(error.message));
}

document.body.addEventListener('click', function (e) {

    let packLabel = e.target.closest('.check-pack-media-label');
    if (packLabel) {
        let inModal = packLabel.closest('#medias-library-modal');
        if (!inModal) {
            let file = packLabel.closest('.file');
            file.classList.toggle('active');
            let inputsChecked = document.querySelectorAll(".file.active");
            let btnWrapper = document.getElementById("media-management-buttons");
            if (btnWrapper) {
                if (inputsChecked.length > 0) {
                    btnWrapper.classList.remove('d-none');
                    btnWrapper.classList.add('d-inline-block');
                } else {
                    btnWrapper.classList.add('d-none');
                    btnWrapper.classList.remove('d-inline-block');
                }
            }
        }
    }

    /** Show move to folder modal */
    let selectFolderBtn = e.target.closest('#select-folder-btn');
    if (selectFolderBtn) {
        let loader = document.getElementById('medias-preloader');
        let path = selectFolderBtn.dataset.path;
        let url = path + (path.indexOf('?') > -1 ? '&ajax=true' : '?ajax=true');

        if (loader) {
            loader.classList.toggle('d-none');
            loader.parentElement.classList.toggle('d-none');
        }

        fetch(url, {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => response.json())
            .then(response => {
                document.body.insertAdjacentHTML('beforeend', response.html);
                let modalEl = document.body.lastElementChild.querySelector('.modal');
                if (!modalEl && document.body.lastElementChild.classList.contains('modal')) {
                    modalEl = document.body.lastElementChild;
                }
                if (modalEl) {
                    let modal = new Modal(modalEl);
                    modal.show();
                    select2();
                    modalEl.addEventListener("hidden.bs.modal", function () {
                        resetModal(modalEl, true);
                        let wrapper = modalEl.closest('.modal-wrapper');
                        if (wrapper) wrapper.remove();
                        else modalEl.remove();
                    });
                }
                if (loader) {
                    loader.classList.toggle('d-none');
                    loader.parentElement.classList.toggle('d-none');
                }
            });

        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }

    /** To move media in folder */
    let folderSaveBtn = e.target.closest('#select_folder_save');
    if (folderSaveBtn) {
        e.preventDefault();
        let form = folderSaveBtn.closest('form');
        let select = form.querySelector('select');
        let folder = select.value;
        let modalEl = document.getElementById('select-folder');

        resetModal(modalEl, true);

        let mgmtBtns = document.getElementById('media-management-buttons');
        if (mgmtBtns) {
            mgmtBtns.classList.add('d-none');
            mgmtBtns.classList.remove('d-inline-block');
        }

        document.querySelectorAll('.file.active').forEach(function (file) {
            ajaxManagement(file, route('admin_folder_media_move', {
                "website": document.body.dataset.id,
                "media": file.dataset.id,
                "folderId": folder
            }));
        });
    }

    /** To compress images */
    let compressBtn = e.target.closest('#media-compress-btn');
    if (compressBtn) {
        let loader = document.querySelector('#medias-card #medias-preloader');
        if (loader) {
            loader.classList.remove('d-none');
            loader.parentElement.classList.remove('d-none');
        }

        let activeFiles = document.querySelectorAll('.file.active');
        activeFiles.forEach(function (file) {
            let tooHeavyFile = file.classList.contains('too-heavy-file');
            ajaxManagement(file, route('admin_media_compress', {
                "website": document.body.dataset.id,
                "media": file.dataset.id
            }), tooHeavyFile);
            let restoreBtn = file.querySelector('.media-compress-restore');
            if (restoreBtn) restoreBtn.classList.remove('d-none');
        });

        if (activeFiles.length === 0) {
            if (loader) {
                loader.classList.add('d-none');
                loader.parentElement.classList.add('d-none');
            }
        }
    }

    /** To restore original media */
    let restoreBtn = e.target.closest('.media-compress-restore');
    if (restoreBtn) {
        e.preventDefault();
        let loader = document.querySelector('#medias-card #medias-preloader');

        if (loader) {
            loader.classList.remove('d-none');
            loader.parentElement.classList.remove('d-none');
        }

        fetch(restoreBtn.getAttribute('href'), {
            method: "GET",
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(response => {
                restoreBtn.classList.add('d-none');
                if (loader) {
                    loader.classList.add('d-none');
                    loader.parentElement.classList.add('d-none');
                }
            })
            .catch(errors => {
                import('../core/errors').then(({default: displayErrors}) => {
                    new displayErrors(errors);
                }).catch(error => console.error(error.message));
            });

        e.stopImmediatePropagation();
        return false;
    }

    /** Warning Message delete */
    let warningDeleteBtn = e.target.closest('.sa-warning-delete-medias');
    if (warningDeleteBtn) {
        let trans = document.getElementById('data-translation');
        let managementBtns = document.getElementById('media-management-buttons');
        if (managementBtns) {
            managementBtns.classList.add('d-none');
            managementBtns.classList.remove('d-inline-block');
        }

        swal({
            title: trans.dataset.swalTitle,
            text: '',
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: trans.dataset.swalConfirmText,
            cancelButtonText: trans.dataset.swalDeleteCancelText,
            closeOnConfirm: false
        }, function () {

            document.querySelectorAll('.alert').forEach(alert => alert.remove());

            document.querySelectorAll('.sa-button-container .confirm, .sa-button-container .cancel').forEach(btn => btn.setAttribute('disabled', ''));

            document.querySelectorAll('.file.active').forEach(function (file) {
                ajaxManagement(file, route('admin_media_remove', {
                    "website": document.body.dataset.id,
                    "media": file.dataset.id
                }), true, 'DELETE');
            });

            swal(trans.dataset.deletionCompleted, "", "success");

            setTimeout(function () {
                swal.close();
            }, 1500);
        });
    }
});

let ajaxManagement = function (file, url, remove = true, type = "GET") {
    fetch(url, {
        method: type,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(response => {
            if (response.success) {
                if (remove) {
                    file.style.transition = 'opacity 0.2s';
                    file.style.opacity = '0';
                    setTimeout(() => file.remove(), 200);
                } else {
                    file.classList.remove('active');
                }
            } else {
                displayAlert(response.message, 'danger', null, false);
            }

            if (document.querySelectorAll('.file.active').length === 0) {
                let loader = document.querySelector('#medias-card #medias-preloader');
                if (loader && !loader.classList.contains('d-none')) {
                    loader.classList.add('d-none');
                    loader.parentElement.classList.add('d-none');
                }
            }
        })
        .catch(errors => {
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        });
};

export default function activeSearch() {

    let showMoreMedias = function () {
        let paginationWrap = document.getElementById('medias-pagination');
        if (paginationWrap) {
            let pagination = paginationWrap.querySelector('.pagination-nav');
            let next = pagination ? pagination.dataset.next : null;
            if (next) {
                let btn = paginationWrap.querySelector('.show-more');
                btn.onclick = function () {
                    let loader = paginationWrap.querySelector('.spinner-wrap');
                    loader.classList.remove('d-none');
                    btn.classList.add('d-none');
                    let xHttp = new XMLHttpRequest();
                    xHttp.open("GET", next + '&ajax=true', true);
                    xHttp.send();
                    xHttp.onload = function () {
                        if (this.readyState === 4 && this.status === 200) {
                            let response = JSON.parse(this.response);
                            let html = document.createElement('div');
                            html.innerHTML = response.html;
                            let results = html.querySelector('#medias-results');
                            if (!results && html.querySelector('#medias-results-container')) {
                                results = html;
                            }
                            let responsePagination = html.querySelector('.pagination-nav');
                            if (responsePagination) {
                                pagination.dataset.next = responsePagination.dataset.next;
                            } else {
                                pagination.dataset.next = '';
                                paginationWrap.remove();
                            }
                            if (results) {
                                let container = document.querySelector('#medias-results-container');
                                let files = results.querySelectorAll('.file');
                                if (container) {
                                    files.forEach((file) => {
                                        let col = file.closest('[class*="col-"]');
                                        if (col) {
                                            container.appendChild(col);
                                        } else {
                                            container.appendChild(file);
                                        }
                                    });
                                }
                            }
                        }
                        if (loader) {
                            loader.classList.add('d-none');
                            btn.classList.remove('d-none');
                        }
                        import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                            mediaLoader();
                        }).catch(error => console.error(error.message));
                        // btn.scrollIntoView({
                        //     behavior: 'smooth', // 'auto' or 'smooth'
                        //     block: 'start',     // 'start', 'center', 'end', or 'nearest'
                        //     inline: 'nearest'   // 'start', 'center', 'end', or 'nearest'
                        // });
                        showMoreMedias();
                    }
                }
            } else {
                paginationWrap.remove();
            }
        }
    }

    showMoreMedias();

    let searchForm = document.getElementById('search-medias-form');
    if (searchForm) {
        searchForm.addEventListener('keydown', function (e) {
            let keyCode = e.keyCode || e.which;
            if (keyCode === 13) {
                e.preventDefault();
                return false;
            }
        });
    }

    /** Refresh medias on search */
    let searchField = document.getElementById('searchMedia');
    if (searchField) {

        const form = searchField.closest('form');
        const clearBtn = form ? form.querySelector('.search-clear') : null;

        const syncFilteringState = () => {
            if (!form) return;
            form.classList.toggle('is-filtering', searchField.value.trim().length > 0);
        };

        function submitFilter() {
            let loader = null;
            let mediaCard = document.getElementById('medias-card');
            if (mediaCard) {
                loader = mediaCard.querySelector('#medias-preloader');
            } else {
                loader = document.body.querySelector('#library-preloader');
            }
            if (loader) {
                loader.classList.remove('d-none');
                loader.parentNode.classList.remove('d-none');
            }
            let formPost = searchField.closest('form');
            let uri = '?' + new URLSearchParams(Array.from(new FormData(formPost))).toString();
            let xHttp = new XMLHttpRequest();
            xHttp.open("GET", formPost.getAttribute('action') + uri, true);
            xHttp.send();
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let response = JSON.parse(this.response);
                    let html = document.createElement('div');
                    html.innerHTML = response.html;
                    html.querySelector('.main-subtitle').remove();
                    let ajaxContent = document.querySelector('#medias-results');
                    ajaxContent.innerHTML = html.innerHTML
                    if (loader) {
                        loader.classList.add('d-none');
                    }
                    showMoreMedias();
                    import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                        mediaLoader();
                    }).catch(error => console.error(error.message));
                }
            }
        }

        let timer;
        const waitTime = 500;
        searchField.addEventListener('keyup', event => {
            syncFilteringState();
            clearTimeout(timer);
            timer = setTimeout(() => {
                doneTyping(event.target.value);
            }, waitTime);
        });

        searchField.addEventListener('input', syncFilteringState);

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                searchField.value = '';
                syncFilteringState();
                clearTimeout(timer);
                submitFilter();
                searchField.focus();
            });
        }

        syncFilteringState();

        function doneTyping() {
            submitFilter();
        }
    }
}
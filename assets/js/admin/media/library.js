import displayAlert from "../core/alert";
import dropifyJS from "../form/dropify";
import resetModal from "../../vendor/components/reset-modal";
import route from "../../vendor/components/routing";
import select2 from "../../vendor/plugins/select2";

import '../lib/sweetalert/sweetalert.min';
import '../bootstrap/dist/modal';
import '../media/cache-resolve';
import '../media/cache-clear';

import '../../../scss/admin/pages/library.scss';
import '../../../scss/admin/lib/sweetalert.scss';

let body = $('body');

let folderModal = $('#new-modal-folder');
folderModal.on('show.bs.modal', function () {
    folderModal.find('form')[0].reset();
    let select = folderModal.find('#folder_parent');
    select.find("option").removeAttr("selected");
    select.trigger("change");
});

body.on('click', '#reorder-medias-btn', function () {
    let trans = $('#data-translation');
    return swal({
        title: trans.data('swal-delete-title'),
        text: trans.data('swal-delete-text'),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.data('swal-delete-confirm-text'),
        cancelButtonText: trans.data('swal-delete-cancel-text'),
        closeOnConfirm: false
    }, function () {
        import(/* webpackPreload: true */ '../media/reorder-medias').then(({default: reorder}) => {
            new reorder();
        }).catch(error => console.error(error.message));
    });
});

body.on('click', '.open-media-edit', function (e) {

    e.preventDefault();

    let el = $(this);
    let loader = $('#medias-preloader');

    $.ajax({
        url: el.attr('href') + "?ajax=1",
        type: "GET",
        processData: false,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            loader.removeClass('d-none');
            loader.parent().removeClass('d-none');
        },
        success: function (response) {
            if (response.html) {

                let body = $('body');
                body.append(response.html);
                let modal = body.find('#media-edition-modal');
                modal.modal('show');

                dropifyJS();
                /** Touch spin */
                import('../../vendor/plugins/touchspin').then(({default: touchSpin}) => {
                    new touchSpin();
                }).catch(error => console.error(error.message));

                $('[data-toggle="tooltip"]').tooltip();
                modal.on('hidden.bs.modal', function () {
                    modal.remove();
                });

                import('./../../vendor/components/ai').then(({default: ai}) => {
                    new ai()
                }).catch(error => console.error(error.message));
            }
            loader.addClass('d-none');
            loader.parent().addClass('d-none');

            import('./../../vendor/components/ai').then(({default: ai}) => {
                new ai();
            }).catch(error => console.error(error.message));
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        }
    });

    e.stopImmediatePropagation();
    return false;
});

activeSearch();

body.on('click', '.check-pack-media-label', function () {

    let el = $(this);
    let inModal = el.closest('#medias-library-modal');

    if (inModal.length === 0) {

        let file = el.closest('.file');

        file.toggleClass('active');

        let body = $('body');
        let inputsChecked = body.find(".file.active");
        let brnWrapper = body.find("#media-management-buttons");

        if (inputsChecked.length > 0) {
            brnWrapper.removeClass('d-none').addClass('d-inline-block');
        } else {
            brnWrapper.addClass('d-none').removeClass('d-inline-block');
        }
    }
});

/** Show move to folder modal */
body.on('click', '#select-folder-btn', function (e) {

    let btn = $(this);
    let loader = body.find('#medias-preloader');
    let path = btn.data('path');
    let url = path + '?ajax=true';
    if (path.indexOf('?') > -1) {
        url = path + '&ajax=true'
    }

    $.ajax({
        url: url,
        type: "GET",
        processData: false,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            loader.toggleClass('d-none');
            loader.parent().toggleClass('d-none');
        },
        success: function (response) {

            let html = response.html;
            let modal = $(html).find('.modal');
            let container = $('body');

            container.append(response.html);

            let modalEl = container.find('#' + modal.attr('id'));

            modalEl.modal('show');
            loader.toggleClass('d-none');
            loader.parent().toggleClass('d-none');

            select2();

            modalEl.on("hide.bs.modal", function () {
                resetModal(modalEl, true);
                $('.modal-wrapper').remove();
            });
        },
        error: function (errors) {
        }
    });

    e.stopImmediatePropagation();
    return false;
});

/** To move media in folder */
body.on('click', '#select_folder_save', function (e) {

    e.preventDefault();

    let el = $(this);
    let select = el.closest('form').find('select');
    let folder = select.val();
    let modal = body.find('#select-folder');

    resetModal(modal, true);

    $('#media-management-buttons').addClass('d-none').removeClass('d-inline-block');

    body.find('.file.active').each(function () {
        let file = $(this);
        ajaxManagement(file, route('admin_folder_media_move', {
            "website": body.data('id'),
            "media": file.data('id'),
            "folderId": folder
        }));
    });
});

/** To compress images */
body.on('click', '#media-compress-btn', function () {

    let loader = $('#medias-card').find('#medias-preloader');
    if (loader) {
        loader.removeClass('d-none');
        loader.parent().removeClass('d-none');
    }

    let activeFiles = body.find('.file.active');
    activeFiles.each(function () {
        let file = $(this);
        let tooHeavyFile = !!file.hasClass('too-heavy-file');
        ajaxManagement(file, route('admin_media_compress', {
            "website": body.data('id'),
            "media": file.data('id'),
        }), tooHeavyFile);
        file.find('.media-compress-restore').removeClass('d-none');
    });

    if (activeFiles.length === 0) {
        loader.addClass('d-none');
        loader.parent().addClass('d-none');
    }
});

/** To restore original media */
body.on('click', '.media-compress-restore', function (e) {

    e.preventDefault();

    let el = $(this);
    let loader = $('#medias-card').find('#medias-preloader');

    $.ajax({
        url: el.attr('href'),
        type: "GET",
        processData: false,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
            loader.removeClass('d-none');
            loader.parent().removeClass('d-none');
        },
        success: function () {
            el.addClass('d-none');
            loader.addClass('d-none');
            loader.parent().addClass('d-none');
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        }
    });

    e.stopImmediatePropagation();
    return false;
});

/** Warning Message delete */
body.on('click', '.sa-warning-delete-medias', function () {

    let trans = $('#data-translation');

    body.find('#media-management-buttons').addClass('d-none').removeClass('d-inline-block');

    swal({
        title: trans.data('swal-title'),
        text: '',
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: trans.data('swal-confirm-text'),
        cancelButtonText: trans.data('swal-delete-cancel-text'),
        closeOnConfirm: false
    }, function () {

        $('.alert').remove();

        body.find('.sa-button-container .confirm').attr('disabled', '');
        body.find('.sa-button-container .cancel').attr('disabled', '');

        body.find('.file.active').each(function () {
            let file = $(this);
            ajaxManagement(file, route('admin_media_remove', {
                "website": body.data('id'),
                "media": file.data('id')
            }), true, 'DELETE');
        });

        swal(trans.data('deletion-completed'), "", "success");

        setTimeout(function () {
            swal.close();
        }, 1500);
    });
});

let ajaxManagement = function (file, url, remove = true, type = "GET") {
    $.ajax({
        url: url,
        type: type,
        processData: false,
        contentType: false,
        dataType: 'json',
        beforeSend: function () {
        },
        success: function (response) {
            if (response.success) {
                if (remove) {
                    file.fadeOut(200).remove();
                }
            } else {
                displayAlert(response.message, 'danger', null, false);
            }
            file.removeClass('active');
            if (body.find('.file.active').length === 0) {
                let loader = $('#medias-card').find('#medias-preloader');
                if (loader && !loader.hasClass('d-none')) {
                    loader.addClass('d-none');
                    loader.parent().addClass('d-none');
                }
            }
        },
        error: function (errors) {
            /** Display errors */
            import('../core/errors').then(({default: displayErrors}) => {
                new displayErrors(errors);
            }).catch(error => console.error(error.message));
        }
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
                            let responsePagination = html.querySelector('.pagination-nav');
                            pagination.dataset.next = responsePagination.dataset.next;
                            if (results) {
                                let container = document.querySelector('#medias-results-container');
                                let files = results.querySelectorAll('.file');
                                if (container) {
                                    files.forEach((file) => {
                                        container.appendChild(file);
                                    });
                                }
                            }
                        }
                        if (loader) {
                            loader.classList.add('d-none');
                            btn.classList.remove('d-none');
                        }
                        import(/* webpackPreload: true */ '../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                            new mediaLoader();
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

    $('#search-medias-form').on('keyup keypress', function (e) {
        let keyCode = e.keyCode || e.which;
        if (keyCode === 13) {
            e.preventDefault();
            return false;
        }
    });

    /** Refresh medias on search */
    let searchField = document.getElementById('searchMedia');
    if (searchField) {

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
                    html.querySelector('.card-subtitle').remove();
                    let ajaxContent = document.querySelector('#medias-results');
                    ajaxContent.innerHTML = html.innerHTML
                    if (loader) {
                        loader.classList.add('d-none');
                    }
                    showMoreMedias();
                    import('../../vendor/components/medias-loader').then(({default: mediaLoader}) => {
                        new mediaLoader();
                    }).catch(error => console.error(error.message));
                }
            }
        }

        let timer;
        const waitTime = 500;
        searchField.addEventListener('keyup', event => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                doneTyping(event.target.value);
            }, waitTime);
        });

        function doneTyping() {
            submitFilter();
        }
    }
}
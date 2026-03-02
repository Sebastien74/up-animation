import route from "../../vendor/components/routing";
import '../../../scss/admin/pages/translation.scss';

const body = document.body;
const extractButtons = body.querySelectorAll('.translation-extract-btn');
const generator = body.querySelector('#translation-generator');
const progressBlock = body.querySelector('#progress-block');
const indexEl = body.querySelector('#translations-domains-index');
const website = body.dataset.id;

extractButtons.forEach(function (button) {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const domain = button.dataset.domain;
        generator.classList.remove('d-none');
        if (indexEl) {
            indexEl.classList.add('d-none');
        }
        const item = body.querySelector('#translation-generator-locales li.undo');
        extract(website, generator, item, domain);
    });
});

let extract = function (website, generator, item, domain) {
    const locale = item.dataset.locale;
    fetch(route('admin_translation_extract', {website: website, locale: locale}), {
        method: "GET",
        headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
        .then(response => {
            if (response.ok) {
                item.classList.remove('undo');
                const progressItem = body.querySelector('#translation-generator-locales li.undo');
                if (progressItem) {
                    const progressLocale = progressItem.dataset.locale;
                    const flag = generator.querySelector('.extraction-title img');
                    if (flag) {
                        flag.setAttribute('src', '/medias/icons/flags/' + progressLocale + '.svg');
                    }
                    extract(website, generator, progressItem, domain);
                } else {
                    progress(website, generator, domain);
                }
            }
        });
};

let progress = function (website, generator, domain) {
    const urlArgs = typeof domain != 'undefined' ? {website: website, domain: domain} : {website: website};
    fetch(route('admin_translation_progress', urlArgs), {
        method: "GET",
        headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
        .then(response => response.json())
        .then(response => {
            generator.classList.add('d-none');
            progressBlock.innerHTML = response.html;
            const domainsList = progressBlock.querySelectorAll('.translation-list');
            domainsList.forEach(function (list) {
                generateTranslation(list, website, generator);
            });
        });
};

let generateTranslation = function (list, website, generator) {

    const translationsData = JSON.parse(list.querySelector('.domain-translations').textContent);
    const batchUrl = list.dataset.batchUrl;
    const mainCounter = body.querySelector('#main-counter');
    const total = parseInt(mainCounter.dataset.total);
    const listId = list.getAttribute('id');

    fetch(batchUrl, {
        method: "POST",
        headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({translations: translationsData})
    })
        .then(response => {
            if (response.ok) {
                list.classList.remove('undo');
                let count = 0;
                body.querySelectorAll('.translation-list:not(.undo)').forEach(function (listEl) {
                    count += parseInt(listEl.dataset.count);
                });
                mainCounter.dataset.count = count.toString();
                mainCounter.textContent = count.toString();

                const progressBlockEl = document.getElementById(listId).closest('.progress-block');
                const progressBar = progressBlockEl.querySelector('.progress-bar');
                list.dataset.progress = list.dataset.count;
                progressBlockEl.querySelector('.counter').textContent = list.dataset.count;
                progressBar.setAttribute('aria-valuenow', "100");
                progressBar.style.width = "100%";
                progressBar.classList.add('bg-info');

                if (count === total) {
                    generateYaml(website, generator);
                }
            }
        });
};

let generateYaml = function (website, generator) {
    progressBlock.remove();
    generator.querySelector('.extraction-title').classList.add('d-none');
    generator.querySelector('.yaml-title').classList.remove('d-none');
    generator.classList.remove('d-none');
    fetch(route('admin_translation_generate_files', {website: website}), {
        method: "GET",
        headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
        .then(response => {
            if (response.ok) {
                clearCache(website, generator);
            }
        });
};

let clearCache = function (website, generator) {
    generator.querySelector('.yaml-title').classList.add('d-none');
    generator.querySelector('.cache-title').classList.remove('d-none');
    fetch(route('cache_clear') + '?translations=true', {
        method: "GET",
        headers: {
            "Content-Type": "application/json; charset=utf-8",
            "X-Requested-With": "XMLHttpRequest"
        }
    })
        .then(response => {
            if (response.ok) {
                generator.querySelector('.cache-title').classList.add('d-none');
                generator.querySelector('.cache-generate-title').classList.remove('d-none');
                location.reload();
            }
        });
};

let saveEls = document.querySelectorAll('.save-row-trans');
saveEls.forEach(function (btn) {

    btn.addEventListener('click', function (e) {

        e.preventDefault();

        let row = btn.closest('tr');
        let form = document.getElementById(btn.dataset.formId);
        let formGroups = row.querySelectorAll('.form-group');
        let formControls = row.querySelectorAll('.form-control');

        formGroups.forEach(function (formGroup) {
            formGroup.classList.remove('has-success');
            let addons = row.querySelectorAll('.addon');
            addons.forEach(function (addon) {
                addon.classList.remove('bg-success', 'border-success');
            });
        });
        formControls.forEach(function (formControl) {
            formControl.classList.remove('form-control-success');
        });

        btn.querySelector('i.icm-save').classList.add('d-none');
        btn.querySelector('i.icm-spinner-third').classList.remove('d-none');

        fetch(form.getAttribute('action') + '?refresh=true', {
            method: "POST",
            body: new FormData(form)
        })
            .then(response => {
                if (response.ok) {
                    formGroups.forEach(function (formGroup) {
                        formGroup.classList.add('has-success');
                        let addons = row.querySelectorAll('.addon');
                        addons.forEach(function (addon) {
                            addon.classList.add('bg-success', 'border-success');
                        });
                    });
                    formControls.forEach(function (formControl) {
                        formControl.classList.add('form-control-success');
                    });
                    btn.querySelector('i.icm-save').classList.remove('d-none');
                    btn.querySelector('i.icm-spinner-third').classList.add('d-none');
                }
            });
    });
});
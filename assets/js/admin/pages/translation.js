import route from "../../vendor/components/routing";
import '../../../scss/admin/pages/translation.scss';

const body = document.body;
const extractButtons = body.querySelectorAll('.translation-extract-btn');
const loader = body.querySelector('#main-preloader');
const generator = body.querySelector('#translation-generator');
const progressBlock = body.querySelector('#progress-block');
const indexEl = body.querySelector('#translations-domains-index');
const website = body.dataset.id;

extractButtons.forEach(function (button) {
    button.addEventListener('click', function (e) {
        e.preventDefault();
        const domain = button.dataset.domain;
        loader.classList.remove('d-none');
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
    const xHttp = new XMLHttpRequest()
    xHttp.open("GET", route('admin_translation_extract', {website: website, locale: locale}), true);
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
    xHttp.send();
    xHttp.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            item.classList.remove('undo');
            const progressItem = document.querySelector('#translation-generator-locales li.undo');
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
    };
};

let progress = function (website, generator, domain) {
    const urlArgs = typeof domain != 'undefined' ? {website: website, domain: domain} : {website: website};
    const xHttp = new XMLHttpRequest();
    xHttp.open("GET", route('admin_translation_progress', urlArgs), true);
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
    xHttp.send();
    xHttp.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            let response = this.response;
            response = '{' + response.substring(response.indexOf("{") + 1, response.lastIndexOf("}")) + '}';
            response = JSON.parse(response);
            generator.classList.add('d-none');
            progressBlock.innerHTML = response.html;
            const domainsList = progressBlock.querySelectorAll('.translation-list');
            domainsList.forEach(function (list) {
                generateTranslation(list, website, generator);
            });
        }
    };
};

let generateTranslation = function (list, website, generator) {

    let translation = list.querySelector('li.translation.undo');

    const mainCounter = body.querySelector('#main-counter');
    const translations = list.querySelectorAll('li');
    const listId = list.getAttribute('id');
    const total = parseInt(mainCounter.dataset.total);

    if (translation) {
        const xHttp = new XMLHttpRequest()
        xHttp.open("GET", translation.dataset.href, true);
        xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
        xHttp.send();
        xHttp.onload = function (e) {
            if (this.readyState === 4 && this.status === 200) {
                translation.classList.remove('undo');
                translation = list.querySelector('li.translation.undo');
                const count = document.querySelectorAll('li.translation:not(.undo)').length;
                mainCounter.dataset.count = count.toString();
                mainCounter.textContent = count.toString();
                if (count + 1 === total) {
                    generateYaml(website, generator);
                } else {
                    const progress = parseInt(list.dataset.progress) + 1;
                    const progressBlock = document.getElementById(listId).closest('.progress-bloc');
                    const progressBar = progressBlock.querySelector('.progress-bar');
                    const percent = (progress * 100) / parseInt(translations.length);
                    list.dataset.progress = progress.toString();
                    progressBlock.querySelector('.counter').textContent = progress.toString();
                    progressBar.setAttribute('aria-valuenow', percent.toString());
                    progressBar.style.width = percent + "%";
                    if (percent === 100) {
                        progressBar.classList.add('bg-info');
                    }
                    generateTranslation(list, website, generator);
                }
            }
        };
    }
};

let generateYaml = function (website, generator) {
    progressBlock.remove();
    generator.querySelector('.extraction-title').classList.add('d-none');
    generator.querySelector('.yaml-title').classList.remove('d-none');
    generator.classList.remove('d-none');
    const xHttp = new XMLHttpRequest();
    xHttp.open("GET", route('admin_translation_generate_files', {website: website}), true);
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
    xHttp.send();
    xHttp.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            clearCache(website, generator);
        }
    };
};

let clearCache = function (website, generator) {
    generator.querySelector('.yaml-title').classList.add('d-none');
    generator.querySelector('.cache-title').classList.remove('d-none');
    const xHttp = new XMLHttpRequest();
    xHttp.open("GET", route('cache_clear') + '?translations=true', true);
    xHttp.setRequestHeader("Content-Type", "application/json; charset=utf-8");
    xHttp.send();
    xHttp.onload = function (e) {
        if (this.readyState === 4 && this.status === 200) {
            generator.querySelector('.cache-title').classList.add('d-none');
            generator.querySelector('.cache-generate-title').classList.remove('d-none');
            location.reload();
        }
    };
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

        btn.querySelector('svg.save-svg').classList.add('d-none');
        btn.querySelector('svg.spinner-svg').classList.remove('d-none');

        let xHttp = new XMLHttpRequest();
        xHttp.open("POST", form.getAttribute('action') + '?refresh=true', true);
        xHttp.send(new FormData(form));
        xHttp.onload = function () {
            if (this.readyState === 4 && this.status === 200) {
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
                btn.querySelector('svg.save-svg').classList.remove('d-none');
                btn.querySelector('svg.spinner-svg').classList.add('d-none');
            }
        }
    });
});
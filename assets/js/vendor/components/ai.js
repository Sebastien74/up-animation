import Utils from 'fullib-js/src/js/Utils/Utils';
import Tooltip from "bootstrap/js/src/tooltip";
import jsCookie from 'js-cookie';

import '../../../scss/vendor/components/_ai.scss';

export default function () {

    let utils = new Utils();
    let chatgptButtons = document.querySelectorAll('.btn-chatgpt');
    if (chatgptButtons) {
        chatgptButtons.forEach(chatgptBtn => {
            if (!chatgptBtn.classList.contains('loaded')) {
                chatgptBtn.classList.add('loaded');
                chatgptBtn.addEventListener('click', async ev => {
                    //SPINNER HANDLER
                    spinnerHandler(chatgptBtn, 'show-spinner')
                    //REQUEST
                    ev.preventDefault();
                    await requestApi(chatgptBtn);
                })
                if (chatgptBtn.classList.contains('auto-run')) {
                    chatgptBtn.click();
                }
            }
        })
    }

    //default open chatbot
    if (jsCookie.get('open-chatbot')) {
        let chatBot = document.querySelector('#chat-bot');
        if (chatBot) {
            chatBot.classList.remove('reduce')
        }
    }

    let choice = false;
    let searchInputs = document.querySelectorAll('.search-engine-form #search');
    let searchApiContainer = document.querySelector('.search-engine-form .autocomplete');

    if (searchInputs && searchApiContainer) {
        import('./../../front/default/components/search').then(({default: search}) => {
            new search()
        }).catch(error => console.error(error.message));
    }

    async function requestApi(chatgptBtn) {

        return new Promise((resolve, reject) => {

            let xHttp = new XMLHttpRequest();
            let url = chatgptBtn.getAttribute('data-felix-api');
            let method = chatgptBtn.getAttribute('data-method') ? chatgptBtn.getAttribute('data-method') : 'GET'
            xHttp.open(method, url, true);
            if (method === 'POST') {
                xHttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                if (chatgptBtn.classList.contains('tool-5')) {
                    let inputForm = chatgptBtn.parentNode.querySelector('input, textarea');
                    if (inputForm) {
                        let instanceTinyMce = tinymce.get(inputForm.getAttribute('id'));
                        let inputValue = inputForm.value;
                        if (instanceTinyMce) {
                            inputValue = instanceTinyMce.getContent();
                        }
                        if (inputValue) {
                            xHttp.send('userMessage=' + encodeURIComponent(inputValue));
                        }
                    }
                } else {
                    xHttp.send();
                }
            } else {
                xHttp.send();
            }

            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let response = JSON.parse(this.response);
                    if (response.httpCode === 200) {
                        let data = response.response;
                        if (response.tool === 1) {
                            handleChatbotTool(chatgptBtn, data);
                        } else if (response.tool === 2) {
                            handleSeoTool(chatgptBtn, data);
                        } else if (response.tool === 3) {
                            handleAnalysePageTool(chatgptBtn, data);
                        } else if (response.tool === 4) {
                            handleAnalyseImageTool(chatgptBtn, data);
                        } else if (response.tool === 5) {
                            handleSpellingCorrectionTool(chatgptBtn, data);
                        }
                        //SPINNER HANDLER
                        spinnerHandler(chatgptBtn, 'hide-spinner')

                    } else {
                        //SPINNER HANDLER
                        spinnerHandler(chatgptBtn, 'hide-spinner')
                        // alert(response.message);
                        console.log(response.message);
                    }
                    resolve();
                } else {
                    reject("Request failed with status " + this.status);
                }
            };

            xHttp.onerror = function () {
                reject("Network error occurred");
            };
        });
    }

    function handleSeoTool(chatgptBtn, data) {

        const seoContainer = document.querySelector('#seo-form-container');

        // GLOBAL VARS
        let seoMetaTitle = document.querySelector('#seo_metaTitle');
        let seoMetaDescription = document.querySelector('#seo_metaDescription');
        let seoMetaOgTitle = document.querySelector('#seo_metaOgTitle');
        let seoMetaOgDescription = document.querySelector('#seo_metaOgDescription');

        //SEO PAGE
        if (seoContainer) {
            if (!seoMetaTitle) {
                seoMetaTitle = chatgptBtn.parentNode.querySelector('[id*="metaTitle"]')
            }
            if (!seoMetaDescription) {
                seoMetaDescription = chatgptBtn.parentNode.querySelector('[id*="metaDescription"]')
            }
            if (seoMetaTitle) {
                seoMetaTitle.value = data.metaTitle;
            }
            if (seoMetaOgTitle) {
                seoMetaOgTitle.value = data.metaTitle;
            }
            if (seoMetaDescription) {
                seoMetaDescription.value = data.metaDescription;
            }
            if (seoMetaOgDescription) {
                seoMetaOgDescription.value = data.metaDescription;
            }

            import('./../../admin/pages/seo/preview').then(({default: preview}) => {
                new preview()
            }).catch(error => console.error(error.message));

        } else {

            //EDIT PAGE
            if (!seoMetaTitle) {
                seoMetaTitle = chatgptBtn.parentNode.parentNode.querySelector('.meta-title');
                if (seoMetaTitle) {
                    seoMetaTitle.value = data.metaTitle;
                }
            }
            if (!seoMetaDescription) {
                seoMetaDescription = chatgptBtn.parentNode.parentNode.querySelector('.meta-description');
                if (seoMetaDescription) {
                    seoMetaDescription.value = data.metaDescription;
                }
            }
        }

        import('./../../admin/form/counter').then(({default: counter}) => {
            new counter(true)
        }).catch(error => console.error(error.message));
    }

    function handleAnalysePageTool(chatgptBtn, data) {
        document.querySelector(chatgptBtn.getAttribute('data-container-result')).innerHTML = data.text;
    }

    function handleChatbotTool(chatgptBtn, data) {

        let chatbotBox = document.querySelector('#chat-bot');
        let contentChatbotBox = document.querySelector('#chat-bot .content');
        let reduceChatbotBox = document.querySelector('#chat-bot #reduce-chat-bot');
        let inputChatbotBox = document.querySelector('#chat-bot #input-chat-bot');
        let submitChatbotBox = document.querySelector('#chat-bot #submit-chat-bot');
        let bodyChatbotBox = document.querySelector('#chat-bot .body');
        let loader = document.querySelector('.loader-chatbot');

        bodyChatbotBox.addEventListener('scroll', ev => {
            jsCookie.set('scrollposition-chatbot', bodyChatbotBox.scrollTop);
        })

        //COOKIE
        if (data.historic) {
            reduceChatbotBox.addEventListener('click', ev => {
                chatbotBox.classList.toggle('reduce');

                if (chatbotBox.classList.contains('reduce')) {
                    jsCookie.remove('open-chatbot')
                } else {
                    jsCookie.set('open-chatbot', true);
                }
            })
        }

        // HISTORIC MSG
        if (data.historic && data.historic !== 'undefined' && data.historic.length > 0) {
            let htmlHistoricMessage = '';
            data.historic.forEach(message => {
                if (message && message !== 'undefined') {
                    htmlHistoricMessage += `<div class="message ${message.role}"><span>${message.text}</span></div>`;
                }
            })
            contentChatbotBox.innerHTML = htmlHistoricMessage;
            if (jsCookie.get('scrollposition-chatbot')) {
                bodyChatbotBox.scrollTop = jsCookie.get('scrollposition-chatbot');
            }
        }

        // HANDLE SEARCH
        inputChatbotBox.addEventListener("keypress", function (event) {
            if (event.key === "Enter") {
                submitChatbotBox.click();
            }
        })
        submitChatbotBox.addEventListener('click', ev => {
            if (!loader.classList.contains('loading')) {
                loader.classList.add('loading');
                let currentUrlAttr = chatgptBtn.getAttribute('data-felix-api')
                chatgptBtn.setAttribute('data-felix-api', currentUrlAttr + '&userMessage=' + inputChatbotBox.value);
                chatgptBtn.click();
                chatgptBtn.setAttribute('data-felix-api', currentUrlAttr);
            }
        })

        //HANDLE MSG RESPONSE
        if (data.text && data.text !== 'undefined') {
            let htmlMessage = `<div class="message user"><span>${inputChatbotBox.value}</span></div><div class="message assistant"><span>${data.text}</span></div>`;
            contentChatbotBox.insertAdjacentHTML(
                'beforeend',
                htmlMessage,
            );
            inputChatbotBox.value = '';
            bodyChatbotBox.scrollTop = bodyChatbotBox.scrollHeight;
        }


        loader.classList.remove('loading')
    }

    async function handleAnalyseImageTool(chatgptBtn, data) {
        if (data.medias) {

            let currentIndex = 0;
            let totalIndex = data.count;
            let progressBar = document.querySelector('#generate-alts-progress');
            let progress = progressBar ? progressBar.querySelector('.progress-bar') : false;
            let oldAttribute = chatgptBtn.getAttribute('data-felix-api');

            if (progressBar) {
                progressBar.classList.remove('d-none');
                progress.classList.remove('d-none');
                progress.style.width = '0%';
            }

            for (const locale of data.locales) {
                for (const media of data.medias) {
                    chatgptBtn.setAttribute(
                        'data-felix-api',
                        `${chatgptBtn.getAttribute('data-api-run')}&locale=${locale}&mediaPath=${media.fullPath}&mediaId=${media.id}`
                    );
                    await requestApi(chatgptBtn);
                    currentIndex++;
                    if (progressBar) {
                        let currentPercentage = (currentIndex / totalIndex) * 100;
                        progress.style.width = `${currentPercentage}%`;
                        progress.setAttribute('aria-valuenow', currentPercentage.toFixed(2));
                    }
                }
            }

            chatgptBtn.setAttribute('data-felix-api', oldAttribute)

            // Masque la barre de progression une fois terminé
            if (progressBar) {
                progressBar.classList.add('d-none');
            }
        }

        let inputIntl = chatgptBtn.parentNode.querySelector('[id*="placeholder"]');

        if (inputIntl && data.alt) {
            inputIntl.value = data.alt;
        }
    }

    async function handleGenerateKeywordTool(chatgptBtn, data) {

        if (data && data.urls) {

            let currentIndex = 0;
            let totalIndex = data.count;
            let progressBar = document.querySelector('#generate-keywords-progress');
            let progress = progressBar ? progressBar.querySelector('.progress-bar') : false;
            let oldAttribute = chatgptBtn.getAttribute('data-felix-api');

            if (progressBar) {
                progressBar.classList.remove('d-none');
                progress.classList.remove('d-none');
                progress.style.width = '0%';
            }

            for (const url of data.urls) {
                chatgptBtn.setAttribute(
                    'data-felix-api',
                    `${chatgptBtn.getAttribute('data-api-run')}&locale=${url.locale}&urlPath=${url.url}`
                );
                await requestApi(chatgptBtn);
                currentIndex++;
                if (progressBar) {
                    let currentPercentage = (currentIndex / totalIndex) * 100;
                    progress.style.width = `${currentPercentage}%`;
                    progress.setAttribute('aria-valuenow', currentPercentage.toFixed(2));
                }
            }

            chatgptBtn.setAttribute('data-felix-api', oldAttribute)

            // Masque la barre de progression une fois terminé
            if (progressBar) {
                progressBar.classList.add('d-none');
            }

            window.location.reload();
        }
    }


    async function handleSpellingCorrectionTool(chatgptBtn, data) {

        if (data.correction) {

            let correctionHTML = data.correctionHTML;
            let instanceTinyMce = false;
            let oldValue = false;
            let noCorrectionHtml = data.correctionHTML;
            let inputForm = chatgptBtn.parentNode.querySelector('input, textarea');

            if (inputForm) {

                instanceTinyMce = tinymce.get(inputForm.getAttribute('id'));
                if (instanceTinyMce) {
                    oldValue = instanceTinyMce.getContent();
                } else {
                    oldValue = inputForm.value;
                }

                data.highlight.forEach(spellingWrong => {
                    noCorrectionHtml = noCorrectionHtml.replace(`<span id="${spellingWrong.id}">${spellingWrong.after}</span>`, `<span id="${spellingWrong.id}">${spellingWrong.before}</span>`)
                })

                inputForm.parentNode.classList.add('waiting-spelling-correction');

                let spellingCorrectionContainer = utils.addElement('div', 'spelling-correction-container', {addTo: inputForm.parentNode})

                if (data.correction == oldValue || data.highlight.length === 0) {
                    let resumeSpellingCorrection = utils.addElement('div', 'resume-spelling-correction', {
                        addTo: spellingCorrectionContainer,
                        text: `Aucune faute n'a été signalée.`
                    })
                } else {

                    let resumeSpellingCorrection = utils.addElement('div', 'resume-spelling-correction', {
                        addTo: spellingCorrectionContainer,
                        text: `${correctionHTML}`
                    })

                    let denySpellingCorrection = utils.addElement('div', 'deny-spelling-correction', {addTo: spellingCorrectionContainer})
                    const tooltipDeny = new Tooltip(denySpellingCorrection, {
                        'title': "Revenir au texte d'origine"
                    })

                    let acceptSpellingCorrection = utils.addElement('div', 'accept-spelling-correction', {addTo: spellingCorrectionContainer})
                    const tooltipAccept = new Tooltip(acceptSpellingCorrection, {
                        'title': "Accepter toute les modifications"
                    })

                    acceptSpellingCorrection.addEventListener('click', () => {
                        inputForm.value = data.correction;
                        if (instanceTinyMce) {
                            instanceTinyMce.setContent(data.correction);
                        }
                        inputForm.parentNode.classList.remove('waiting-spelling-correction');
                        spellingCorrectionContainer.remove();
                    })

                    denySpellingCorrection.addEventListener('click', () => {
                        if (instanceTinyMce) {
                            instanceTinyMce.setContent(oldValue);
                        }

                        inputForm.value = oldValue;
                        inputForm.parentNode.classList.remove('waiting-spelling-correction');
                        spellingCorrectionContainer.remove();
                    })

                    //ON CLICK REPLACE
                    let spanWithSpellingWrong = resumeSpellingCorrection.querySelectorAll('span[id^="corr"]');
                    spanWithSpellingWrong.forEach((element) => {
                        element.addEventListener('click', ev => {
                            let id = element.getAttribute('id');
                            let elemAssociated = searchHighlight(data, id);
                            if (elemAssociated) {
                                let textToAdd = noCorrectionHtml;
                                noCorrectionHtml = noCorrectionHtml.replace(`<span id="${elemAssociated.id}">${elemAssociated.before}</span>`, `<span id="${elemAssociated.id}">${elemAssociated.after}</span>`);
                                textToAdd = textToAdd.replace(`<span id="${elemAssociated.id}">${elemAssociated.before}</span>`, `${elemAssociated.after}`);
                                // CLEAN spans
                                data.highlight.forEach(spellingWrong => {
                                    textToAdd = textToAdd.replace(`<span id="${spellingWrong.id}">${spellingWrong.before}</span>`, `${spellingWrong.before}`);
                                    textToAdd = textToAdd.replace(`<span id="${spellingWrong.id}">${spellingWrong.after}</span>`, `${spellingWrong.after}`);
                                });
                                if (instanceTinyMce) {
                                    instanceTinyMce.setContent(textToAdd);
                                }
                                inputForm.value = textToAdd;
                                element.classList.add('added')
                                let allSpanSelected = resumeSpellingCorrection.querySelectorAll('span.added')
                                if (allSpanSelected && allSpanSelected.length === data.highlight.length) {
                                    inputForm.parentNode.classList.remove('waiting-spelling-correction');
                                    spellingCorrectionContainer.remove();
                                }
                            }
                        })
                    })
                }

                function searchHighlight(data, id) {
                    return data.highlight.find(element => element.id === id) || false;
                }
            }
        }
    }

    function spinnerHandler(chatgptBtn, mode) {

        // SPINNER LOADER
        let aiIcon = chatgptBtn.querySelector('.ai-icon');
        let aiSpinner = chatgptBtn.querySelector('.spinner-grow');

        if (aiIcon && aiSpinner) {
            if (mode === 'show-spinner') {
                aiIcon.classList.add('d-none');
                aiSpinner.classList.remove('d-none');
            }
            if (mode === 'hide-spinner') {
                aiIcon.classList.remove('d-none');
                aiSpinner.classList.add('d-none');
            }
        }
    }
}
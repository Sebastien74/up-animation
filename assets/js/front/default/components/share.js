/**
 * To manage shares links
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */

import ClipboardJS from 'clipboard'
import { createPopper } from '@popperjs/core';

export default function () {

    async function AndroidNativeShare(Title, URL, Description) {
        if (typeof navigator.share === 'undefined' || !navigator.share) {
            alert('Your browser does not support Android Native Share, it\'s tested on chrome 63+');
        } else if (window.location.protocol !== 'https:') {
            alert('Android Native Share support only on Https:// protocol');
        } else {
            if (typeof URL === 'undefined') {
                URL = window.location.href;
            }
            if (typeof Title === 'undefined') {
                Title = document.title;
            }
            if (typeof Description === 'undefined') {
                Description = 'Share your thoughts about ' + Title;
            }
            const TitleConst = Title;
            const URLConst = URL;
            const DescriptionConst = Description;

            try {
                await navigator.share({title: TitleConst, text: DescriptionConst, url: URLConst});
            } catch (error) {
                console.log('Error sharing: ' + error);
                return;
            }
        }
    }

    let shareBtn = document.querySelector('.share-content');
    if (shareBtn) {
        if (typeof navigator.share === 'undefined' || !navigator.share) {
            let clipboard = new ClipboardJS(shareBtn);
            let clipboardEl = shareBtn.querySelector('.copy-clipboard');
            clipboard.on('success', function(e) {
                createPopper(shareBtn, clipboardEl, {
                    placement: 'top',
                });
                window.setTimeout(ev => {
                    clipboardEl.style.transform = 'initial';
                    clipboardEl.classList.remove('d-none');
                    clipboardEl.classList.add('d-flex');
                    window.setTimeout(ev => {
                        clipboardEl.classList.add('d-none');
                        clipboardEl.classList.remove('d-flex');
                    }, 4000)
                }, 200)
            });
        } else {
            shareBtn.addEventListener('click', BodyEvent => {
                var meta_desc, meta_title, meta_url
                if (document.querySelector('meta[property="og:description"]') != null) {
                    meta_desc = document.querySelector('meta[property="og:description"]').content;
                }
                if (document.querySelector('meta[property="og:title"]') != null) {
                    meta_title = document.querySelector('meta[property="og:title"]').content;
                }
                if (document.querySelector('meta[property="og:url"]') != null) {
                    meta_url = document.querySelector('meta[property="og:url"]').content;
                }
                AndroidNativeShare(meta_title, meta_url, meta_desc);
            });
        }
    }
}
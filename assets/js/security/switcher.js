import "../../scss/security/switcher.scss";

import Choices from "choices.js";

/**
 * Users Switcher
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function () {

    let switcher = document.getElementById('users-switcher-btn');
    let trans = document.getElementById('data-translation');

    let removeBox = function () {
        let box = document.getElementById('users-switcher-box');
        if (typeof (box) != 'undefined' && box != null) {
            box.parentNode.remove();
        }
    }

    if (typeof (switcher) != 'undefined' && switcher != null) {

        let showModal = function (el) {
            removeBox();
            let xHttp = new XMLHttpRequest();
            xHttp.open("GET", el.dataset.path, true);
            xHttp.setRequestHeader("Content-Type", "application/json");
            xHttp.onload = function () {
                if (this.readyState === 4 && this.status === 200) {
                    let response = JSON.parse(this.response);
                    if (response.html) {
                        let usersBox = document.createElement('div');
                        usersBox.innerHTML = response.html;
                        document.body.appendChild(usersBox);
                        const select = document.getElementById('users-switcher-select');
                        const choice = new Choices(select, {
                            noResultsText: trans.getAttribute('data-choices-no-result'),
                            itemSelectText: '',
                            shouldSort: false,
                            classNames: {
                                containerOuter: 'choices-users-select'
                            }
                        });
                        choice.passedElement.element.addEventListener('change', function (event) {
                            window.location.replace(event.detail.value)
                        }, false);
                        document.getElementById('users-switcher-close').onclick = function () {
                            removeBox();
                        }
                    }
                }
            }
            xHttp.send();
        }

        switcher.onclick = function (event) {
            event.preventDefault();
            showModal(this);
        }
    }

    /** To change user Front */
    let usersFrontSwitcher = document.getElementById('users-front-switcher-select');
    if (typeof (usersFrontSwitcher) != 'undefined' && usersFrontSwitcher != null) {
        const frontChoice = new Choices(usersFrontSwitcher, {
            noResultsText: trans.getAttribute('data-choices-no-result'),
            itemSelectText: '',
            shouldSort: false,
            classNames: {
                containerOuter: 'choices-users-select'
            }
        });
        frontChoice.passedElement.element.addEventListener('change', function (event) {
            window.location.replace(event.detail.value)
        }, false);
    }
}
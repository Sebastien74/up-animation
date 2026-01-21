let Masonry = require('masonry-layout')

/**
 * Masonry
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
export default function (item = null) {

    let wraps = document.getElementsByClassName('masonry-wrap')

    if (wraps.length > 0) {

        for (let i = 0; i < wraps.length; i++) {

            let wrap = wraps[i]
            let masonryContainer = wrap.getElementsByClassName('masonry-container')[0]
            let asMasonry = masonryContainer.classList.contains('masonry')
            let filtersContainer = wrap.getElementsByClassName('masonry-filters')
            let haveFilters = filtersContainer.length > 0
            let grid = null

            if (asMasonry) {
                grid = new Masonry(masonryContainer, {
                    itemSelector: '.grid-item',
                    columnWidth: '.grid-item',
                    percentPosition: true
                })
                if (item) {
                    grid.append(item);
                }
            }

            if (haveFilters) {

                let filters = filtersContainer[0].getElementsByClassName('filter')

                for (let i = 0; i < filters.length; i++) {

                    let filter = filters[i]

                    filter.onclick = function () {

                        for (let j = 0; j < filters.length; j++) {
                            filters[j].classList.remove('active')
                        }
                        filter.classList.toggle('active')

                        /** To remove previous clones items */
                        let clonesItems = document.querySelectorAll('.grid-item.item-clone')
                        for (let j = 0; j < clonesItems.length; j++) {
                            clonesItems[j].remove()
                        }

                        let items = document.getElementsByClassName('grid-item')
                        let dataFilter = filter.dataset.filter
                        for (let j = 0; j < items.length; j++) {
                            let item = items[j]
                            item.classList.add('d-none')
                            item.classList.add('h-0')
                            if (item.dataset.category === dataFilter) {
                                if (!item.classList.contains('item-to-clone')) {
                                    item.classList.add('item-to-clone')
                                }
                            } else {
                                item.classList.remove('item-to-clone')
                            }
                        }

                        let itemsToClone = document.getElementsByClassName('item-to-clone')
                        let itemsArray = []
                        let fragment = document.createDocumentFragment();
                        for (let j = 0; j < itemsToClone.length; j++) {
                            let item = itemsToClone[j].cloneNode(true)
                            if (!item.classList.contains('item-clone')) {
                                item.classList.remove('d-none')
                                item.classList.remove('h-0')
                                item.classList.add('item-clone')
                                item.setAttribute('style', '')
                                fragment.appendChild(item)
                                itemsArray.push(item)
                            }
                        }

                        masonryContainer.appendChild(fragment)

                        if (asMasonry) {
                            grid.prepended(itemsArray)
                        }
                    }
                }
            }
        }
    }
}
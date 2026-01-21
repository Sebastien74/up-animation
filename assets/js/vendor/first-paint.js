/**
 * Nonce
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 *
 * 1 - Preloader
 * 2 - Browsers
 * 3 - Lazy load
 */

/** 1 - Preloader */
import './components/preloader';

/** 2 - Browsers */
import './browsers';

/** 4 - Lazy load */
import(/* webpackPreload: true */ './components/lazy-load').then(({default: lazyLoad}) => {
    new lazyLoad();
}).catch(error => console.error(error.message));
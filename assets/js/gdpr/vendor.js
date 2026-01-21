import 'simplebar'

import scriptsLoader from './scripts/scripts-loader'
import modal from './scripts/modal'
import management from './scripts/management'
import services from './scripts/services'
// import removeData from './scripts/remove-data'

/**
 * GDPR Module
 *
 * @copyright 2020
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 * @version 1.0
 * @licence under the MIT License (LICENSE.txt)
 *
 *  1 - Script loader
 *  2 - Modal
 *  3 - Management
 *  4 - Services
 *  5 - Remove data ( Uncomment if cron is not planned )
 */

scriptsLoader()
modal()
management()
services()
// removeData()
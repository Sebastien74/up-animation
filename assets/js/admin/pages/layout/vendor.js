import setSortableZones from './zones'
import setSortableCols from './cols'
import setSortableBlocks from './blocks'
import resizeBlocks from './resize-blocks'
import resizeCols from './resize-cols'
import resizeZones from './resize-zones'
import colsStandardizeZone from './cols-center-zone'
import blocksStandardizeCol from './blocks-center-col'
import editElement from "./edit-element"

/**
 * Active layout plugins & scripts
 */
export default function (Routing) {
    setSortableZones(Routing)
    setSortableCols(Routing)
    setSortableBlocks(Routing)
    resizeBlocks(Routing)
    resizeCols(Routing)
    resizeZones(Routing)
    colsStandardizeZone(Routing)
    blocksStandardizeCol(Routing)
    editElement(Routing)
}
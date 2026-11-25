<?php

namespace App\Extensions;

use SilverStripe\Assets\Image_Backend;
use SilverStripe\Assets\Storage\AssetContainer;
use SilverStripe\Core\Extension;

/**
 * AnchorFill(): Fill() that keeps the left or right side of the image instead of the centre. Applied to Image
 * by app/_config/extensions.yml. The hero templates serve cropped images below 992px, and Fill() always crops
 * to the centre, so a subject near one edge would be cut away or slid under the headline whatever the page's
 * HeroFocusX says. Page::HeroImageFill() calls this with that setting.
 *
 * The variant is named after this method (__FUNCTION__), as SilverStripe's own manipulations are. The name
 * deliberately does not start with a core Image method's name: ImageManipulation::variantParts() reads a
 * variant name back by matching core method names at its start, so "FillFocus" would read back as Fill()
 * with garbage arguments, where "AnchorFill" fails loudly. (In SilverStripe 6 variantParts() knows only core
 * methods and nothing calls it; the name keeps this safe if either changes.) "FocusFill" is taken by
 * jonom/focuspoint.
 */
class ImageAnchorFillExtension extends Extension
{
    /**
     * @param int|string $width
     * @param int|string $height
     * @param string $anchor 'left', 'center' or 'right'; anything else is treated as 'center'
     */
    public function AnchorFill($width, $height, $anchor = 'center'): ?AssetContainer
    {
        $owner = $this->getOwner();
        $anchor = in_array($anchor, ['left', 'right'], true) ? $anchor : 'center';
        if ($anchor === 'center') {
            return $owner->Fill($width, $height);
        }
        $width = (int) $width;
        $height = (int) $height;
        if ($width <= 0 || $height <= 0) {
            return null;
        }
        $variant = $owner->variantName(__FUNCTION__, $width, $height, $anchor);
        return $owner->manipulateImage($variant, function (Image_Backend $backend) use ($width, $height, $anchor) {
            return $backend->croppedResize($width, $height, $anchor);
        });
    }
}

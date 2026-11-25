<?php

namespace App\Admin;

use SilverStripe\Admin\ModelAdmin;
use App\Models\BlockStyle;

class BlockStyleAdmin extends ModelAdmin
{
    private static $url_segment = 'blockstyles';
    private static $menu_title = 'Block Styles';
    public $showImportForm = false;
    public $showSearchForm = false;
    private static $menu_icon_class = 'font-icon-block-layout';

    private static $managed_models = [
        BlockStyle::class
    ];
}

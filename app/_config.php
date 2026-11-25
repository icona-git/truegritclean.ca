<?php

use SilverStripe\TinyMCE\TinyMCEConfig;
use SilverStripe\View\Parsers\ShortcodeParser;

// TinyMCEConfig::get('cms')
//     ->enablePlugins('hr');
TinyMCEConfig::get('cms')
    ->enablePlugins('hr')
    ->setButtonsForLine(1, ['styles','|','bold', 'italic','alignleft','aligncenter','alignright','|','removeformat','visualblocks','code' ])
    ->setButtonsForLine(2, ['bullist','numlist','outdent','indent','|','blockquote','hr','|','sslink', 'unlink', 'ssmedia'] )
    ->setOption('importcss_append', true)
    ->setOption('extended_valid_elements', 'small');


ShortcodeParser::get('default')->register('youtube', ['Page', 'YouTubeShortCode']);

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
// [claim key=minimum]...[/claim]: the wrapped text renders only while that claim is ticked in Settings > Claims.
ShortcodeParser::get('default')->register('claim', [\App\Models\Claims::class, 'shortcode']);
// [missing note="..."]stand-in text[/missing]: content the client hasn't sent yet, shown highlighted on every
// environment until an editor replaces it. site/LAUNCH.md counts these before launch.
ShortcodeParser::get('default')->register('missing', [\App\Models\MissingContent::class, 'shortcode']);

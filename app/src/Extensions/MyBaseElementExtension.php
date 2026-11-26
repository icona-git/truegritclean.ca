<?php

namespace App\Extensions;

use ReflectionClass;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Controller;
use App\Models\BlockStyle;

class MyBaseElementExtension extends Extension
{

    private static $db = [
        'PaddingTop' => 'Varchar(255)',
        'PaddingBottom' => 'Varchar(255)',
        // The small capitals line above a section heading in the Figma design ("The process"). Empty: none.
        'Eyebrow' => 'Varchar(150)',
    ];

    private static $has_one = [
        'BlockStyle' => BlockStyle::class
    ];

    public function updateCMSFields( FieldList $fields )
    {
        $fields->removeByName('Eyebrow');
        $fields->insertBefore('Title', TextField::create('Eyebrow', 'Eyebrow')
            ->setDescription('Optional short line shown above the heading, e.g. "The process". Leave empty for none.'));

        // User Option to control the padding.
        $src = [
            'padding-top-medium' => 'Medium Padding (3rem)',
            'padding-top-slim' => 'Slim Padding (1.5rem)',
            'padding-top-none' => 'No Padding'
        ];
        $f = DropdownField::create('PaddingTop', 'Top Padding', $src);
        $f->setEmptyString('Default Padding (6rem)');
        $f->setDescription('Optionally override the top padding of this block (only implemented on Person Block)');
        $fields->addFieldToTab('Root.Settings', $f);

        $src = [
            'padding-bottom-medium' => 'Medium Padding (3rem)',
            'padding-bottom-slim' => 'Slim Padding (1.5rem)',
            'padding-bottom-none' => 'No Padding'
        ];
        $f = DropdownField::create('PaddingBottom', 'Bottom Padding', $src);
        $f->setEmptyString('Default Padding (6rem)');
        $f->setDescription('Optionally override the bottom padding of this block (only implemented on Person Block)');
        $fields->addFieldToTab('Root.Settings', $f);

        $src = BlockStyle::get()->filter(['BlockType' => get_class($this->owner)])->map()->toArray();
        $f = DropdownField::create('BlockStyleID', 'Block Style', $src);
        $f->setEmptyString('Default Style');
        $fields->addFieldToTab('Root.Settings', $f);


    }

    // The Templates list is locked into the BaseElement forTemplate methods
    // Using a CustomRender allows us to bypass and insert our own Style template paths
    public function CustomRender(){

        $templates = $this->getOwner()->getRenderTemplates();

        $this->getOwner()->ClassName;

        // Add our own Style Template file to the stack if it is set by Admin
        if($this->getOwner()->BlockStyleID){
            $style = $this->getOwner()->BlockStyle();
            if($tpl = $style->TemplateName){
                $templates = array_merge(["{$this->getOwner()->ClassName}_{$tpl}"],$templates);
            }
        }
     
        if (count($templates)>0) {
            return $this->getOwner()->renderWith($templates);
        }

        return "no template found";
    }

     public function getCssLabel(){

        $css_label = new ReflectionClass($this->getOwner())->getShortName();
        if($this->getOwner()->BlockStyleID && $tpl = $this->getOwner()->BlockStyle()->TemplateName){
            $css_label .= " {$css_label}_{$tpl}";
        }

        return $css_label;
    }

     // We use a different field name on our custom blocks for background image (BgImage),
    // so to keep it consistent, we return the existing "File" field here.
    public function getBackgroundImage(){
        if($this->getOwner()->hasMethod('File')){
            return $this->getOwner()->File();
        }
    }


    function getTopLevelPage(){
        return Controller::curr()->Level(1);
    }
    function getThisPage(){
        return Controller::curr();
    }

}

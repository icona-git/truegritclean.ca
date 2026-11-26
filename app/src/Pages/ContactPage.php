<?php
namespace App\Pages;

use Override;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;

class ContactPage extends UserDefinedForm
{
    private static $cms_icon_class = 'font-icon-p-mail';
    private static $class_description = 'The Contact Page';
    private static $table_name = 'ContactPage';

    /**
     * The block style (BlockStyle.TemplateName) whose blocks sit beside the form, in the Figma quote page's
     * reassurance panel. Every other block renders under the form.
     */
    private const PANEL_STYLE = 'Assurance';

    private static $db = [
    ];
    private static $has_one = [];

    #[Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        if (!$fields->dataFieldByName('Content')) {
            $fields->addFieldToTab('Root.Main', HTMLEditorField::create('Content'), 'ElementalArea');
        }

        // The theme prints Content under the submit button (UserFormActionNav.ss), not above the form, and userforms
        // swaps it for the On complete message after a submission.
        $fields->dataFieldByName('Content')
            ->setTitle('Text under the button')
            ->setDescription('Shown under the submit button, e.g. when the quote will arrive and the privacy line. '
                . 'The thank-you message after a submission is the On complete message (Form Options tab).')
            ->setRows(5);

        return $fields;
    }

    /** Controllers of the blocks shown beside the form (the reassurance panel), in page order. */
    public function QuotePanelBlocks(): ArrayList
    {
        return $this->quoteBlocks(true);
    }

    /** Controllers of the page's other blocks (the FAQ), shown under the form, in page order. */
    public function QuoteMainBlocks(): ArrayList
    {
        return $this->quoteBlocks(false);
    }

    private function quoteBlocks(bool $panel): ArrayList
    {
        $blocks = ArrayList::create();
        $area = $this->ElementalArea();
        if (!$area || !$area->exists()) {
            return $blocks;
        }
        // ElementControllers() keeps only the blocks the visitor may see.
        foreach ($area->ElementControllers() as $controller) {
            $element = $controller->getElement();
            $isPanel = $element->BlockStyleID && $element->BlockStyle()->TemplateName === self::PANEL_STYLE;
            if ($isPanel === $panel) {
                $blocks->push($controller);
            }
        }
        return $blocks;
    }

}

<?php

namespace App\Extensions;

use App\Models\SeedKey;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\Forms\FieldList;

/**
 * Stable seed identity on the records a seed task creates. Applied by app/_config/seedkeys.yml to every
 * block (DNADesign\Elemental\Models\BaseElement) and to App\Models\Bucket. This is the fleet convention, first used on
 * an ICONA client build, which the ICONA build-job template relies on (STARTER-CONTRACT S1).
 *
 * Seeds set SeedKey on create and find records with App\Models\SeedKey::findBlock() / findBucket(), never
 * by Title. The key is task bookkeeping, not content, so it never appears in the CMS.
 */
class SeedKeyExtension extends Extension
{
    private static $db = [
        'SeedKey' => 'Varchar(120)',
    ];

    // Plain, not unique: the same key repeats across pages (every page may have a "page-intro" block).
    // Uniqueness is per parent and is checked in updateValidate().
    private static $indexes = [
        'SeedKey' => true,
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName('SeedKey');
    }

    protected function updateValidate(ValidationResult $result): void
    {
        SeedKey::validate($this->getOwner(), $result);
    }

    /** A copy is a new record: it must not answer to its original's seed key. */
    protected function onBeforeDuplicate($original, $doWrite, $relations): void
    {
        $this->getOwner()->SeedKey = null;
    }

    /**
     * A block an editor moves to another page's area is no longer where its seed put it, so it drops its
     * key. BaseElement::moveTo() writes with validation skipped, so the per-parent uniqueness check would
     * never see a clash in the destination; this hook runs before that write. Blocks only: moveTo() does
     * not exist on buckets, and it returns early when the area does not change.
     */
    protected function onAfterMoveTo($elementalArea): void
    {
        $this->getOwner()->SeedKey = null;
    }
}

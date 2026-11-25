<?php

namespace App\Models;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Validation\ValidationResult;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * Stable identity for records that a seed task creates: content blocks and buckets. The field itself is
 * added by App\Extensions\SeedKeyExtension (app/_config/seedkeys.yml); this class holds the rules.
 *
 * A seed task must find the block it made last time by a key that editors never see or change.
 * The block Title is display copy, so matching on it breaks the first time an editor retitles a
 * block, or when two sections share a heading (Step 3 review, 2026-09-20). Seeds therefore set
 * SeedKey on create and look records up with findBlock() / findBucket(), never by Title.
 *
 * A key is lower-case letters, digits and single hyphens ("home-hero", "services-faq"), unique
 * within its parent: the page's elemental area for a block, the Buckets block for a bucket.
 * Records an editor creates have no key. A duplicated record never inherits its original's key.
 */
final class SeedKey
{
    /** Matches the field size in SeedKeyExtension. */
    private const MAX_LENGTH = 120;

    private const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** The draft block with this key in this area, or null. Any block type. */
    public static function findBlock(ElementalArea $area, string $key): ?BaseElement
    {
        return self::inDraft(fn () => BaseElement::get()
            ->filter(['ParentID' => $area->ID, 'SeedKey' => $key])
            ->first());
    }

    /** The draft bucket with this key in this Buckets block, or null. */
    public static function findBucket(DataObject $bucketsBlock, string $key): ?Bucket
    {
        return self::inDraft(fn () => Bucket::get()
            ->filter(['ParentBlockID' => $bucketsBlock->ID, 'SeedKey' => $key])
            ->first());
    }

    /**
     * Adds an error to $result when $record's key is malformed or already used by a sibling: another block
     * in the same elemental area, or another bucket in the same Buckets block.
     */
    public static function validate(DataObject $record, ValidationResult $result): void
    {
        $key = (string) $record->SeedKey;
        if ($key === '') {
            return;
        }
        if (strlen($key) > self::MAX_LENGTH || !preg_match(self::PATTERN, $key)) {
            $result->addFieldError('SeedKey', sprintf(
                'Seed key "%s" must be lower-case letters, digits and single hyphens, at most %d characters.',
                $key,
                self::MAX_LENGTH
            ));
            return;
        }
        [$baseClass, $parentField] = match (true) {
            $record instanceof BaseElement => [BaseElement::class, 'ParentID'],
            $record instanceof Bucket => [Bucket::class, 'ParentBlockID'],
            default => [null, null],
        };
        if ($baseClass === null) {
            return;
        }
        $clash = self::inDraft(fn () => $baseClass::get()
            ->filter([$parentField => (int) $record->$parentField, 'SeedKey' => $key])
            ->exclude('ID', (int) $record->ID)
            ->exists());
        if ($clash) {
            $result->addFieldError('SeedKey', sprintf(
                'Seed key "%s" is already used by another record in the same parent. Keys must be unique there.',
                $key
            ));
        }
    }

    /** Seeds write drafts, and a clash must count unpublished records, so read the draft stage. */
    private static function inDraft(callable $query): mixed
    {
        return Versioned::withVersionedMode(function () use ($query) {
            Versioned::set_stage(Versioned::DRAFT);
            return $query();
        });
    }
}

<?php

namespace App\Tasks;

use RuntimeException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DataObject;
use SilverStripe\PolyExecution\PolyOutput;
use SilverStripe\SpamProtection\EditableSpamProtectionField;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\EditableFormField\EditableEmailField;
use SilverStripe\UserForms\Model\EditableFormField\EditableFieldGroup;
use SilverStripe\UserForms\Model\EditableFormField\EditableFieldGroupEnd;
use SilverStripe\UserForms\Model\EditableFormField\EditableFormStep;
use SilverStripe\UserForms\Model\EditableFormField\EditableMultipleOptionField;
use SilverStripe\UserForms\Model\EditableFormField\EditableOption;
use SilverStripe\UserForms\Model\Recipient\EmailRecipient;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Versioned\Versioned;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds the quote form on /quote/ from app/seed/form.yml: its sections and fields (userforms Field Groups), the
 * spam check, the email recipient, and the page's button text and the text under the button.
 *
 *   vendor/bin/sake tasks:seed-form              drafts only
 *   vendor/bin/sake tasks:seed-form --publish    also publishes the page (and with it the form fields)
 *
 * Safe by default and on every re-run: fields are added only to a form that has none yet (apart from the first step
 * userforms creates itself), a recipient only when the form has none, and a page setting only while it is empty. Once
 * the form exists it belongs to the editor; the task never changes or deletes a field, a recipient or a setting
 * someone filled in. There is no --force: rebuilding a form means deleting its fields in the CMS first.
 *
 * The whole file is checked before anything is written: an unknown page, field type or field setting, a duplicate or
 * badly formed field name, an option list on a field that takes none (or none on one that needs it), a reply_to that
 * is not an email field of the form, or a {page:...} link to a page that does not exist stops the task with nothing
 * changed.
 */
class SeedFormTask extends BuildTask
{
    // Writes content: CLI only, so no CMS user can run it from /dev/tasks on a live site.
    private static bool $can_run_in_browser = false;

    protected static string $commandName = 'seed-form';

    protected string $title = 'Seed the quote form';

    protected static string $description = 'Build the quote form (fields, recipient, button text) from app/seed/form.yml. '
        . 'Adds fields only to a form that has none. Drafts only unless --publish.';

    private const LIST_FILE = 'app/seed/form.yml';

    /** Short field type names in the file resolve to this namespace; a type outside it is written in full. */
    private const USERFORMS_NS = 'SilverStripe\\UserForms\\Model\\EditableFormField\\';

    private const FIELD_KEYS = ['class', 'options'];

    private const RECIPIENT_KEYS = ['reply_to'];

    /** Field names are the keys of submitted data and merge fields: plain PascalCase, never "Field" (userforms). */
    private const NAME_PATTERN = '/^[A-Z][A-Za-z0-9]{0,49}$/';

    private const PAGE_LINK = '/\{page:([^}]+)\}/';

    private PolyOutput $output;

    public function getOptions(): array
    {
        return [
            new InputOption('publish', null, InputOption::VALUE_NONE, 'Publish the quote page and its form after the run (default: drafts only)'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $this->output = $output;
        $publish = (bool) $input->getOption('publish');

        $path = BASE_PATH . '/' . self::LIST_FILE;
        if (!is_file($path)) {
            $output->writeln("[stop] {$path} not found");
            return 1;
        }
        $def = Yaml::parseFile($path);

        return Versioned::withVersionedMode(function () use ($def, $publish): int {
            // Form fields are versioned and live in Draft first, like the pages and blocks the content seed wrote.
            Versioned::set_stage(Versioned::DRAFT);

            try {
                $page = $this->check($def);
            } catch (RuntimeException $e) {
                $this->output->writeln('[stop] ' . $e->getMessage() . ' Nothing was written.');
                return 1;
            }

            $this->output->writeln('--- Page settings ---');
            $changed = $this->seedSettings($page, $def['settings'] ?? []);

            $this->output->writeln('--- Fields ---');
            $changed = $this->seedFields($page, $def) || $changed;

            $this->output->writeln('--- Recipients ---');
            $changed = $this->seedRecipients($page, $def['recipients'] ?? []) || $changed;

            if ($publish) {
                $this->output->writeln('--- Publishing ---');
                $page->publishRecursive();
                $this->output->writeln("  [published] {$def['page']}");
            }

            $this->output->writeln($changed ? 'Done.' : 'Done: nothing to change.');
            return 0;
        });
    }

    // ─── Checks (before any write) ───────────────────────────────────────────

    private function check(array $def): UserDefinedForm
    {
        $page = SiteTree::get_by_link((string) ($def['page'] ?? ''));
        if (!$page instanceof UserDefinedForm) {
            throw new RuntimeException('page: no userforms page at ' . ($def['page'] ?? '(no path)') . '.');
        }

        foreach ($def['settings'] ?? [] as $field => $value) {
            if (!DataObject::getSchema()->fieldSpec(get_class($page), (string) $field)) {
                throw new RuntimeException("settings: {$page->ClassName} has no field \"{$field}\".");
            }
            $this->resolvePageLinks((string) $value, "settings {$field}");
        }

        $names = [];
        $emailFields = [];
        if (empty($def['sections'])) {
            throw new RuntimeException('sections: the form has no sections.');
        }
        foreach ($def['sections'] as $i => $section) {
            $where = 'Section ' . ($section['title'] ?? '#' . ($i + 1));
            if (trim((string) ($section['title'] ?? '')) === '') {
                throw new RuntimeException("{$where}: title is empty.");
            }
            if (empty($section['fields'])) {
                throw new RuntimeException("{$where}: has no fields.");
            }
            foreach ($section['fields'] as $field) {
                $at = "{$where}, field " . ($field['Name'] ?? '?');
                $class = $this->fieldClass((string) ($field['class'] ?? ''), $at);
                $this->checkName((string) ($field['Name'] ?? ''), $names, $at);
                if (trim((string) ($field['Title'] ?? '')) === '') {
                    throw new RuntimeException("{$at}: Title is empty.");
                }
                $this->checkFields($field, self::FIELD_KEYS, $class, $at);

                $takesOptions = is_a($class, EditableMultipleOptionField::class, true);
                if ($takesOptions && empty($field['options'])) {
                    throw new RuntimeException("{$at}: a {$class} needs options.");
                }
                if (!$takesOptions && isset($field['options'])) {
                    throw new RuntimeException("{$at}: a {$class} takes no options.");
                }
                if (is_a($class, EditableEmailField::class, true)) {
                    $emailFields[$field['Name']] = true;
                }
            }
        }

        if (!empty($def['spam'])) {
            if (!class_exists(EditableSpamProtectionField::class)) {
                throw new RuntimeException('spam: silverstripe/spamprotection is not installed.');
            }
            $this->checkName((string) ($def['spam']['Name'] ?? ''), $names, 'spam');
            $this->checkFields($def['spam'], [], EditableSpamProtectionField::class, 'spam');
        }

        foreach ($def['recipients'] ?? [] as $i => $recipient) {
            $at = 'Recipient #' . ($i + 1);
            if (!filter_var((string) ($recipient['EmailAddress'] ?? ''), FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException("{$at}: EmailAddress is not an email address.");
            }
            $this->checkFields($recipient, self::RECIPIENT_KEYS, EmailRecipient::class, $at);
            if (isset($recipient['reply_to']) && !isset($emailFields[$recipient['reply_to']])) {
                throw new RuntimeException("{$at}: reply_to \"{$recipient['reply_to']}\" is not an email field of the form.");
            }
            // userforms' own rules (a sender address, valid addresses), checked on an unsaved copy so a refusal
            // happens here and not halfway through the writes.
            $probe = EmailRecipient::create();
            foreach (array_diff_key($recipient, array_flip(self::RECIPIENT_KEYS)) as $key => $value) {
                $probe->setField($key, $value);
            }
            $result = $probe->validate();
            if (!$result->isValid()) {
                $messages = array_column($result->getMessages(), 'message');
                throw new RuntimeException("{$at}: " . implode(' ', $messages));
            }
        }

        return $page;
    }

    /** @return class-string<EditableFormField> */
    private function fieldClass(string $name, string $where): string
    {
        $class = str_contains($name, '\\') ? $name : self::USERFORMS_NS . $name;
        $structural = [EditableFormStep::class, EditableFieldGroup::class, EditableFieldGroupEnd::class];
        if ($name === '' || !class_exists($class) || !is_a($class, EditableFormField::class, true)
            || in_array($class, $structural, true)) {
            throw new RuntimeException("{$where}: \"{$name}\" is not a userforms field type.");
        }
        return $class;
    }

    private function checkName(string $name, array &$names, string $where): void
    {
        if (!preg_match(self::NAME_PATTERN, $name) || $name === 'Field') {
            throw new RuntimeException("{$where}: Name must be a plain name such as ProjectAddress.");
        }
        if (isset($names[$name])) {
            throw new RuntimeException("{$where}: Name \"{$name}\" is used twice.");
        }
        $names[$name] = true;
    }

    private function checkFields(array $def, array $reserved, string $class, string $where): void
    {
        foreach (array_keys($def) as $key) {
            if (in_array($key, $reserved, true)) {
                continue;
            }
            if (!DataObject::getSchema()->fieldSpec($class, (string) $key)) {
                throw new RuntimeException("{$where}: {$class} has no field \"{$key}\".");
            }
        }
    }

    /** Replaces {page:/path/} with SilverStripe's page link shortcode, which follows the page if it is renamed. */
    private function resolvePageLinks(string $html, string $where): string
    {
        return (string) preg_replace_callback(self::PAGE_LINK, function (array $m) use ($where): string {
            $target = SiteTree::get_by_link($m[1]);
            if (!$target) {
                throw new RuntimeException("{$where}: no page at {$m[1]} for {$m[0]}.");
            }
            return '[sitetree_link,id=' . $target->ID . ']';
        }, $html);
    }

    // ─── Writes ──────────────────────────────────────────────────────────────

    private function seedSettings(UserDefinedForm $page, array $settings): bool
    {
        $changed = false;
        foreach ($settings as $field => $value) {
            if (trim(strip_tags((string) $page->getField($field))) !== '') {
                $this->output->writeln("  [keep] {$field}: already filled in");
                continue;
            }
            $page->setField($field, $this->resolvePageLinks((string) $value, "settings {$field}"));
            $changed = true;
            $this->output->writeln("  [set] {$field}");
        }
        if ($changed) {
            $page->write();
        }
        return $changed;
    }

    private function seedFields(UserDefinedForm $page, array $def): bool
    {
        $existing = EditableFormField::get()->filter(['ParentID' => $page->ID, 'ParentClass' => $page->ClassName]);
        if ($existing->exclude('ClassName', EditableFormStep::class)->exists()) {
            $this->output->writeln('  [keep] the form already has fields; nothing added');
            return false;
        }
        // userforms owns Sort 1: the form's first record is its initial step (README "Seeding content").
        $page->createInitialFormStep(true);
        $sort = (int) EditableFormField::get()->filter(['ParentID' => $page->ID, 'ParentClass' => $page->ClassName])->max('Sort');

        foreach ($def['sections'] as $section) {
            $group = $this->newField(EditableFieldGroup::class, ['Title' => $section['title']], $page, ++$sort);
            $this->output->writeln("  [add] section: {$section['title']}");
            foreach ($section['fields'] as $field) {
                $class = $this->fieldClass((string) $field['class'], '');
                $values = array_diff_key($field, array_flip(self::FIELD_KEYS));
                $record = $this->newField($class, $values, $page, ++$sort);
                foreach (array_values($field['options'] ?? []) as $i => $option) {
                    $opt = EditableOption::create();
                    $opt->Title = (string) $option;
                    $opt->Value = (string) $option;
                    $opt->Sort = $i + 1;
                    $opt->ParentID = $record->ID;
                    $opt->write();
                }
                $this->output->writeln("    [add] {$field['Name']} ({$field['class']})");
            }
            $end = $this->newField(EditableFieldGroupEnd::class, [], $page, ++$sort);
            $group->EndID = $end->ID;
            $group->write();
        }

        if (!empty($def['spam'])) {
            $this->newField(EditableSpamProtectionField::class, $def['spam'], $page, ++$sort);
            $this->output->writeln('  [add] spam check');
        }
        return true;
    }

    private function newField(string $class, array $values, UserDefinedForm $page, int $sort): EditableFormField
    {
        /** @var EditableFormField $record */
        $record = $class::create();
        foreach ($values as $key => $value) {
            $record->setField($key, $value);
        }
        $record->ParentID = $page->ID;
        $record->ParentClass = $page->ClassName;
        $record->Sort = $sort;
        $record->write();
        return $record;
    }

    private function seedRecipients(UserDefinedForm $page, array $recipients): bool
    {
        if ($page->EmailRecipients()->exists()) {
            $this->output->writeln('  [keep] the form already has a recipient; nothing added');
            return false;
        }
        foreach ($recipients as $def) {
            $recipient = EmailRecipient::create();
            foreach (array_diff_key($def, array_flip(self::RECIPIENT_KEYS)) as $key => $value) {
                $recipient->setField($key, $value);
            }
            $recipient->FormID = $page->ID;
            $recipient->FormClass = $page->ClassName;
            if (isset($def['reply_to'])) {
                $from = EditableFormField::get()->filter([
                    'ParentID' => $page->ID,
                    'ParentClass' => $page->ClassName,
                    'Name' => $def['reply_to'],
                ])->first();
                $recipient->SendEmailFromFieldID = $from ? $from->ID : 0;
            }
            $recipient->write();
            $this->output->writeln("  [add] {$def['EmailAddress']}");
        }
        return $recipients !== [];
    }
}

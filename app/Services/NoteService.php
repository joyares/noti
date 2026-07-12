<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;
use App\Models\Notebook;
use App\Models\Tag;

final class NoteService
{
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'p', 'b', 'i', 'u', 'ul', 'ol', 'li', 'input',
        'code', 'pre', 'a', 'blockquote', 'br',
    ];

    private Note $notes;
    private Tag $tags;
    private Notebook $notebooks;

    public function __construct()
    {
        $this->notes     = new Note();
        $this->tags      = new Tag();
        $this->notebooks = new Notebook();
    }

    public function create(int $userId, int $notebookId, string $title = '', string $body = ''): int
    {
        $clean  = $this->sanitize($body);
        $noteId = $this->notes->create($userId, $notebookId, mb_substr($title, 0, 255), $clean, $this->toText($clean));

        $notebook = $this->notebooks->find($notebookId);
        if ($notebook !== null && (int) $notebook['is_fixed'] === 1) {
            $this->tags->attach($noteId, $this->tags->ensure($userId, $notebook['name']));
        }

        return $noteId;
    }

    /**
     * Autosave / partial update. Returns ['saved_at' => ..., 'size_bytes' => ...].
     */
    public function update(int $userId, array $note, array $input): array
    {
        $fields = [];
        if (array_key_exists('title', $input)) {
            $fields['title'] = mb_substr((string) $input['title'], 0, 255);
        }
        if (array_key_exists('body', $input)) {
            $clean                = $this->sanitize((string) $input['body']);
            $fields['body']       = $clean;
            $fields['body_text']  = $this->toText($clean);
            $fields['size_bytes'] = strlen($clean);
        }
        if (array_key_exists('is_pinned', $input)) {
            $fields['is_pinned'] = (int) (bool) $input['is_pinned'];
        }
        if (array_key_exists('notebook_id', $input)) {
            $target = $this->notebooks->findOwned((int) $input['notebook_id'], $userId);
            if ($target !== null) {
                $fields['notebook_id'] = (int) $target['id'];
                // Auto-tag: moving into a fixed notebook attaches its tag.
                if ((int) $target['is_fixed'] === 1) {
                    $this->tags->attach((int) $note['id'], $this->tags->ensure($userId, $target['name']));
                }
            }
        }
        if ($fields !== []) {
            $this->notes->update((int) $note['id'], $fields);
        }

        $fresh = $this->notes->find((int) $note['id']);

        return [
            'saved_at'   => $fresh['updated_at'],
            'size_bytes' => (int) $fresh['size_bytes'],
        ];
    }

    /** Whitelist-based HTML sanitizer (DOM walk, no regex). */
    public function sanitize(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML(
            '<?xml encoding="utf-8"?><body>' . $html . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }
        $this->cleanNode($body, $doc);

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return trim($out);
    }

    private function cleanNode(\DOMNode $node, \DOMDocument $doc): void
    {
        // Iterate over a static copy: we mutate the child list while walking.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof \DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            // Normalize editor output.
            if (in_array($tag, ['strong'], true)) {
                $tag = 'b';
            } elseif ($tag === 'em') {
                $tag = 'i';
            } elseif (in_array($tag, ['div', 'h3', 'h4', 'h5', 'h6'], true)) {
                $tag = 'p';
            }

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                $node->removeChild($child);
                continue;
            }

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Unwrap: keep children, drop the element itself.
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                $this->cleanNode($node, $doc);
                continue;
            }

            // Rebuild with the normalized tag if it changed.
            if ($tag !== strtolower($child->tagName)) {
                $replacement = $doc->createElement($tag);
                while ($child->firstChild !== null) {
                    $replacement->appendChild($child->firstChild);
                }
                $node->replaceChild($replacement, $child);
                $child = $replacement;
            }

            $this->cleanAttributes($child, $tag);
            $this->cleanNode($child, $doc);
        }
    }

    private function cleanAttributes(\DOMElement $el, string $tag): void
    {
        foreach (iterator_to_array($el->attributes) as $attr) {
            $name = strtolower($attr->name);
            $keep = false;
            if ($tag === 'a' && $name === 'href') {
                $href = trim($attr->value);
                if (preg_match('#^https?://#i', $href) || str_starts_with($href, '/')) {
                    $keep = true;
                }
            } elseif ($tag === 'input' && in_array($name, ['type', 'checked'], true)) {
                $keep = $name === 'checked' || strtolower($attr->value) === 'checkbox';
            }
            if (!$keep) {
                $el->removeAttribute($attr->name);
            }
        }
        if ($tag === 'a') {
            $el->setAttribute('rel', 'noopener noreferrer');
        }
        if ($tag === 'input') {
            $el->setAttribute('type', 'checkbox');
        }
    }

    public function toText(string $html): string
    {
        $text = preg_replace('#<(br|/p|/li|/h1|/h2)>#i', "\n", $html) ?? $html;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return trim(preg_replace('/[ \t]+/', ' ', $text) ?? '');
    }
}

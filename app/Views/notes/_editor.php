<?php
/** Editor pane. Expects $currentNote (full row with tags + attachments), $notebooks. */
$note = $currentNote;
?>
<div class="editor" data-editor data-note-id="<?= (int) $note['id'] ?>">

  <!-- Header: breadcrumb · size · saved pill · share · dots -->
  <header class="editor-header">
    <a class="editor-back" href="<?= url('/notes?notebook=' . $note['notebook_id']) ?>"><?= icon('back', 18) ?></a>
    <div class="breadcrumb">
      <a class="crumb-notebook" href="<?= url('/notes?notebook=' . $note['notebook_id']) ?>"
         style="color:<?= e($note['notebook_color']) ?>">
        <?= icon($note['notebook_icon'], 13) ?><span><?= e($note['notebook_name']) ?></span>
      </a>
      <span class="crumb-sep">/</span>
      <span class="crumb-title" data-crumb-title><?= $note['title'] !== '' ? e($note['title']) : 'Untitled' ?></span>
    </div>
    <div class="editor-header-right">
      <span class="note-size" data-note-size><?= human_bytes((int) $note['size_bytes']) ?></span>
      <span class="saved-pill" data-saved-pill><?= icon('check', 11) ?><span>Saved</span></span>
      <button type="button" class="icon-btn" data-share title="Copy share link"><?= icon('share', 15) ?></button>
      <div class="menu-wrap">
        <button type="button" class="icon-btn" data-menu-toggle title="More"><?= icon('dots', 15) ?></button>
        <div class="menu" hidden data-menu>
          <button type="button" data-pin-toggle data-pinned="<?= (int) $note['is_pinned'] ?>">
            <?= icon('pin', 14) ?><span><?= (int) $note['is_pinned'] === 1 ? 'Unpin' : 'Pin note' ?></span>
          </button>
          <label class="menu-move">
            <?= icon('book', 14) ?>
            <select data-move-notebook>
              <?php foreach ($notebooks as $nb): ?>
                <option value="<?= (int) $nb['id'] ?>" <?= (int) $nb['id'] === (int) $note['notebook_id'] ? 'selected' : '' ?>>
                  <?= e($nb['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <label class="menu-attach">
            <?= icon('paperclip', 14) ?><span>Attach file</span>
            <input type="file" data-attach-input hidden>
          </label>
          <button type="button" class="danger" data-trash-note><?= icon('trash', 14) ?><span>Move to Trash</span></button>
        </div>
      </div>
    </div>
  </header>

  <!-- Toolbar -->
  <div class="editor-toolbar" data-toolbar>
    <button type="button" data-cmd="bold" title="Bold"><b>B</b></button>
    <button type="button" data-cmd="italic" title="Italic"><i>I</i></button>
    <button type="button" data-cmd="underline" title="Underline"><u>U</u></button>
    <span class="tb-sep"></span>
    <button type="button" data-block="h1" title="Heading 1">H1</button>
    <button type="button" data-block="h2" title="Heading 2">H2</button>
    <span class="tb-sep"></span>
    <button type="button" data-cmd="insertUnorderedList" title="Bullet list">• List</button>
    <button type="button" data-todo title="Checklist">☐ Todo</button>
    <button type="button" data-code title="Inline code">&lt;/&gt;</button>
    <button type="button" data-link title="Link"><?= icon('link', 13) ?></button>
    <button type="button" class="tb-collapse" data-toolbar-collapse title="Hide toolbar"><?= icon('chevron-down', 14) ?></button>
  </div>

  <div class="editor-scroll">
    <input class="editor-title" data-editor-title type="text" placeholder="Untitled"
           value="<?= e($note['title']) ?>" maxlength="255">

    <div class="editor-meta">
      Edited <span data-edited-at><?= full_date($note['updated_at']) ?></span>
      <span class="meta-dot">·</span>
      <span data-meta-size><?= human_bytes((int) $note['size_bytes']) ?></span>
    </div>

    <div class="editor-body" data-editor-body contenteditable="true" spellcheck="true"><?= $note['body'] ?></div>

    <?php if (!empty($note['attachments'])): ?>
      <div class="attachment-row" data-attachments>
        <?php foreach ($note['attachments'] as $att): ?>
          <span class="att-pill" data-att-id="<?= (int) $att['id'] ?>">
            <?= icon($att['kind'] === 'image' ? 'image' : ($att['kind'] === 'audio' ? 'mic' : 'paperclip'), 12) ?>
            <a href="<?= url('/files/' . $att['id']) ?>" target="_blank" rel="noopener"><?= e($att['file_name']) ?></a>
            <button type="button" class="att-remove" data-att-remove aria-label="Remove">×</button>
          </span>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="attachment-row" data-attachments hidden></div>
    <?php endif; ?>
  </div>

  <!-- Footer: tag chips + add tag -->
  <footer class="editor-footer">
    <div class="editor-tags" data-note-tags>
      <?php foreach ($note['tags'] as $t): ?>
        <span class="tag-chip tag-chip-lg<?= tag_slug($note['notebook_name']) === $t['name'] ? ' tag-chip-primary' : '' ?>"
              <?= tag_slug($note['notebook_name']) === $t['name'] ? 'style="background:' . e($note['notebook_color']) . '"' : '' ?>
              data-tag-id="<?= (int) $t['id'] ?>">
          #<?= e($t['name']) ?><button type="button" class="tag-remove" data-tag-remove aria-label="Remove tag">×</button>
        </span>
      <?php endforeach; ?>
    </div>
    <div class="add-tag-wrap">
      <input type="text" class="add-tag-input" data-add-tag placeholder="+ Add tag" maxlength="60">
    </div>
  </footer>
</div>

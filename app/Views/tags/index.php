<section class="tags-view<?= $activeTag ? ' has-active-tag' : '' ?>">

  <!-- Middle pane: filterable tag list -->
  <div class="tags-list-pane">
    <header class="list-header">
      <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
      <h2 class="list-title">Tags</h2>
      <span class="list-count"><?= count($tags) ?></span>
    </header>
    <div class="tags-filter">
      <?= icon('search', 13) ?>
      <input type="text" placeholder="Filter tags…" data-tag-filter>
    </div>
    <div class="tags-list" data-tag-list>
      <?php foreach ($tags as $t): ?>
        <a class="tag-row<?= $activeTag && (int) $activeTag['id'] === (int) $t['id'] ? ' active' : '' ?>"
           href="<?= url('/tags/' . rawurlencode($t['name'])) ?>" data-tag-name="<?= e($t['name']) ?>">
          <span class="tag-row-name">#<?= e($t['name']) ?></span>
          <span class="tag-row-count"><?= (int) $t['note_count'] ?></span>
        </a>
      <?php endforeach; ?>
      <?php if ($tags === []): ?><div class="empty-state"><p>No tags yet.</p></div><?php endif; ?>
    </div>
  </div>

  <!-- Right pane: results for the active tag -->
  <div class="tags-results-pane">
    <?php if ($activeTag): ?>
      <header class="tags-results-header">
        <a class="m-back" href="<?= url('/tags') ?>"><?= icon('back', 18) ?></a>
        <h2 class="tag-title">#<?= e($activeTag['name']) ?></h2>
        <span class="list-count"><?= count($results) ?> notes</span>
      </header>
      <div class="tag-results-grid">
        <?php foreach ($results as $note): ?>
          <article class="result-card" data-note-id="<?= (int) $note['id'] ?>">
            <div class="result-card-top">
              <a class="result-nb" href="<?= url('/notes?notebook=' . $note['notebook_id']) ?>">
                <span class="nb-dot" style="background:<?= e($note['notebook_color']) ?>"></span>
                <span style="color:<?= e($note['notebook_color']) ?>"><?= e($note['notebook_name']) ?></span>
              </a>
              <div class="result-actions">
                <button type="button" class="icon-btn" data-copy-note
                        data-body-text="<?= e(mb_substr($note['body_text'], 0, 8000)) ?>" title="Copy as text"><?= icon('copy', 13) ?></button>
                <button type="button" class="icon-btn" data-share data-note-id="<?= (int) $note['id'] ?>" title="Copy share link"><?= icon('share', 13) ?></button>
              </div>
            </div>
            <a class="result-body" href="<?= url('/notes/' . $note['id']) ?>">
              <h3 class="result-title"><?= $note['title'] !== '' ? e($note['title']) : 'Untitled' ?></h3>
              <p class="result-snippet"><?= e(mb_substr($note['body_text'], 0, 220)) ?></p>
            </a>
            <?php if (!empty($note['attachments'])): ?>
              <div class="result-atts">
                <?php
                  $images = array_filter($note['attachments'], static fn ($a) => $a['kind'] === 'image');
                  $others = array_filter($note['attachments'], static fn ($a) => $a['kind'] !== 'image');
                ?>
                <?php if (count($images) > 0): ?>
                  <span class="att-pill"><?= icon('image', 11) ?><?= count($images) ?> photo<?= count($images) > 1 ? 's' : '' ?></span>
                <?php endif; ?>
                <?php foreach ($others as $att): ?>
                  <span class="att-pill">
                    <?= icon($att['kind'] === 'audio' ? 'mic' : 'paperclip', 11) ?><?= e($att['file_name']) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <div class="result-tags">
              <?php foreach ($note['tags'] as $t): ?>
                <span class="tag-chip<?= (int) $t['id'] === (int) $activeTag['id'] ? ' tag-chip-active' : '' ?>">#<?= e($t['name']) ?></span>
              <?php endforeach; ?>
              <span class="result-date"><?= nice_date($note['updated_at']) ?></span>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if ($results === []): ?><div class="empty-state"><p>No notes with this tag.</p></div><?php endif; ?>
      </div>
    <?php else: ?>
      <div class="editor-empty">
        <span class="tag-empty-icon"><?= icon('tag', 26) ?></span>
        <p>Pick a tag to see its notes.</p>
      </div>
    <?php endif; ?>
  </div>
</section>

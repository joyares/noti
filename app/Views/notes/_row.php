<?php
/** Note row. Expects $row, optional $currentNote. */
$isActive = isset($currentNote) && $currentNote && (int) $currentNote['id'] === (int) $row['id'];
$snippet  = mb_substr($row['body_text'] ?? '', 0, 160);
?>
<a href="<?= url('/notes/' . $row['id']) ?>"
   class="note-row<?= $isActive ? ' active' : '' ?>" data-note-id="<?= (int) $row['id'] ?>">
  <div class="note-row-top">
    <span class="note-row-title">
      <?php if ((int) $row['is_pinned'] === 1): ?><span class="pin-mark"><?= icon('pin', 11) ?></span><?php endif; ?>
      <?= $row['title'] !== '' ? e($row['title']) : '<span class="untitled">Untitled</span>' ?>
    </span>
    <span class="note-row-date"><?= nice_date($row['updated_at']) ?></span>
  </div>
  <?php if ($snippet !== ''): ?>
    <div class="note-row-snippet"><?= e($snippet) ?></div>
  <?php endif; ?>
  <?php if (!empty($row['tags'])): ?>
    <div class="note-row-tags">
      <?php foreach (array_slice($row['tags'], 0, 4) as $t): ?>
        <span class="tag-chip">#<?= e($t['name']) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</a>

<?php
/**
 * The main app view: mobile home + list pane + editor pane.
 * CSS decides what is visible per breakpoint and body.screen-* class.
 */
$activeNotebookId = $activeNotebook ? (int) $activeNotebook['id'] : null;
$sortLabel = 'Updated';
?>

<!-- ── Mobile home (visible <768px on screen-home) ─────────────────── -->
<section class="m-home">
  <header class="m-home-header">
    <div class="brand">
      <span class="logo-tile">N</span>
      <span class="logo-word">Noti</span>
    </div>
    <a href="<?= url('/profile') ?>" class="avatar-link" title="Profile"><?= avatar_html($user) ?></a>
  </header>

  <button type="button" class="m-search-field" data-open-search>
    <?= icon('search', 15) ?><span>Search notes…</span>
  </button>

  <div class="m-section-row">
    <span class="m-section-label">Notebooks</span>
    <a class="m-section-action" href="<?= url('/notebooks') ?>"><?= icon('plus', 13) ?> New</a>
  </div>

  <div class="m-notebook-grid">
    <?php foreach ($notebooks as $nb): ?>
      <a class="m-nb-card" href="<?= url('/notes?notebook=' . $nb['id']) ?>" style="--nb-color:<?= e($nb['color']) ?>">
        <span class="m-nb-icon" style="color:<?= e($nb['color']) ?>"><?= icon($nb['icon'], 18) ?></span>
        <span class="m-nb-name" style="color:<?= e($nb['color']) ?>"><?= e($nb['name']) ?></span>
        <span class="m-nb-count"><?= (int) $nb['note_count'] ?> notes</span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<!-- ── List pane ───────────────────────────────────────────────────── -->
<section class="list-pane">
  <header class="list-header">
    <a class="m-back" href="<?= $activeNotebook ? url('/') : url('/') ?>"><?= icon('back', 18) ?></a>
    <?php if ($activeNotebook): ?>
      <span class="list-nb-icon" style="color:<?= e($activeNotebook['color']) ?>"><?= icon($activeNotebook['icon'], 16) ?></span>
      <h2 class="list-title" style="color:<?= e($activeNotebook['color']) ?>"><?= e($activeNotebook['name']) ?></h2>
    <?php else: ?>
      <h2 class="list-title"><?= e($listTitle) ?></h2>
    <?php endif; ?>
    <span class="list-count"><?= count($notes) ?></span>
    <span class="list-sort"><?= icon('chevron-down', 12) ?> <?= $sortLabel ?></span>
    <button type="button" class="icon-btn m-list-more"><?= icon('dots', 15) ?></button>
  </header>

  <?php if ($activeNotebook): ?>
    <?php
      $isTodo = $activeNotebook['name'] === 'TODO';
      $chips  = $isTodo
          ? [['all', 'All'], ['open', 'Open'], ['done', 'Done']]
          : [['all', 'All'], ['pinned', 'Pinned'], ['recent', 'Recent']];
    ?>
    <div class="filter-chips">
      <?php foreach ($chips as [$key, $label]): ?>
        <a class="filter-chip<?= $filter === $key ? ' active' : '' ?>"
           href="<?= url('/notes?notebook=' . $activeNotebook['id'] . '&filter=' . $key) ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="note-list">
    <?php if ($locked): ?>
      <div class="locked-state">
        <span class="locked-icon"><?= icon('lock', 26) ?></span>
        <h3>Notes are locked</h3>
        <p>Re-enter your password to view <?= e($activeNotebook['name']) ?> for 10 minutes.</p>
        <?php if ($this_unlock_error = ($_GET['unlock_error'] ?? null)): ?>
          <div class="auth-error">Wrong password, try again.</div>
        <?php endif; ?>
        <form method="post" action="<?= url('/notebooks/' . $activeNotebook['id'] . '/unlock') ?>" class="unlock-form">
          <?= App\Core\Csrf::field() ?>
          <input type="password" name="password" placeholder="Account password" required autocomplete="current-password">
          <button type="submit" class="btn-primary"><?= icon('unlock', 13) ?> Unlock</button>
        </form>
        <div class="locked-preview" aria-hidden="true">
          <div class="blur-row"></div><div class="blur-row"></div><div class="blur-row"></div>
        </div>
      </div>
    <?php elseif ($notes === []): ?>
      <div class="empty-state">
        <p>No notes here yet.</p>
        <form method="post" action="<?= url('/notes') ?>">
          <?= App\Core\Csrf::field() ?>
          <input type="hidden" name="notebook_id" value="<?= $activeNotebookId ?? (int) ($notebooks[0]['id'] ?? 0) ?>">
          <button type="submit" class="btn-primary"><?= icon('plus', 13) ?> New note</button>
        </form>
      </div>
    <?php else: ?>
      <?php foreach ($notes as $row): ?>
        <?= App\Core\View::partial('notes/_row', ['row' => $row, 'currentNote' => $currentNote]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if (!$locked && $activeNotebook): ?>
    <form method="post" action="<?= url('/notes') ?>" class="m-fab-form">
      <?= App\Core\Csrf::field() ?>
      <input type="hidden" name="notebook_id" value="<?= $activeNotebookId ?>">
      <button type="submit" class="m-fab" aria-label="New note"><?= icon('plus', 22) ?></button>
    </form>
  <?php endif; ?>
</section>

<!-- ── Editor pane ─────────────────────────────────────────────────── -->
<section class="editor-pane">
  <?php if ($currentNote): ?>
    <?= App\Core\View::partial('notes/_editor', ['currentNote' => $currentNote, 'notebooks' => $notebooks]) ?>
  <?php else: ?>
    <div class="editor-empty">
      <span class="logo-tile logo-tile-lg">N</span>
      <p>Select a note, or create a new one.</p>
      <p class="editor-empty-hint">Press <kbd>⌘K</kbd> to search</p>
    </div>
  <?php endif; ?>
</section>

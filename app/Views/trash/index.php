<section class="trash-view">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Trash</h1>
    <span class="page-header-note">Notes are deleted forever after <?= (int) $days ?> days</span>
  </header>

  <div class="trash-list">
    <?php if ($notes === []): ?>
      <div class="empty-state"><p>Trash is empty.</p></div>
    <?php endif; ?>
    <?php foreach ($notes as $row): ?>
      <div class="trash-row">
        <div class="trash-row-main">
          <span class="trash-row-title"><?= $row['title'] !== '' ? e($row['title']) : 'Untitled' ?></span>
          <span class="trash-row-meta">
            <span class="nb-dot" style="background:<?= e($row['notebook_color']) ?>"></span>
            <?= e($row['notebook_name']) ?>
            <span class="meta-dot">·</span> trashed <?= nice_date($row['trashed_at'] ?? $row['updated_at']) ?>
          </span>
        </div>
        <div class="trash-row-actions">
          <form method="post" action="<?= url('/notes/' . $row['id'] . '/restore') ?>">
            <?= App\Core\Csrf::field() ?>
            <button type="submit" class="btn-ghost"><?= icon('restore', 13) ?> Restore</button>
          </form>
          <form method="post" action="<?= url('/notes/' . $row['id'] . '/forever') ?>"
                onsubmit="return confirm('Delete this note forever?')">
            <?= App\Core\Csrf::field() ?>
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn-ghost btn-danger"><?= icon('trash', 13) ?> Delete forever</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="search-page">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Search</h1>
  </header>

  <form method="get" action="<?= url('/search') ?>" class="search-page-form">
    <?= icon('search', 15) ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search notes… (try tag:shopping)" autofocus>
  </form>

  <div class="search-page-results">
    <?php if ($q !== '' && $results === []): ?>
      <div class="empty-state"><p>Nothing found for “<?= e($q) ?>”.</p></div>
    <?php endif; ?>
    <?php foreach ($results as $row): ?>
      <a href="<?= url('/notes/' . $row['id']) ?>" class="note-row">
        <div class="note-row-top">
          <span class="note-row-title"><?= $row['title'] !== '' ? e($row['title']) : 'Untitled' ?></span>
          <span class="note-row-date"><?= nice_date($row['updated_at']) ?></span>
        </div>
        <div class="note-row-snippet"><?= e(mb_substr($row['body_text'], 0, 160)) ?></div>
        <div class="note-row-tags">
          <span class="tag-chip" style="color:<?= e($row['notebook_color']) ?>"><?= e($row['notebook_name']) ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

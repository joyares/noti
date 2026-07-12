<?php
$fixed = array_values(array_filter($notebooks, static fn ($nb) => (int) $nb['is_fixed'] === 1));
$mine  = array_values(array_filter($notebooks, static fn ($nb) => (int) $nb['is_fixed'] === 0));
?>
<section class="nb-overview">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Notebooks</h1>
    <button type="button" class="btn-primary" data-open-nb-modal><?= icon('plus', 13) ?><span>New notebook</span></button>
  </header>

  <?php if ($err = ($_GET['error'] ?? null)): ?>
    <div class="auth-error"><?= e($err) ?></div>
  <?php endif; ?>

  <div class="nb-section-label">Fixed notebooks</div>
  <div class="nb-grid">
    <?php foreach ($fixed as $nb): ?>
      <a class="nb-card" href="<?= url('/notes?notebook=' . $nb['id']) ?>" style="--nb-color:<?= e($nb['color']) ?>">
        <div class="nb-card-head">
          <span class="nb-card-icon" style="color:<?= e($nb['color']) ?>"><?= icon($nb['icon'], 17) ?></span>
          <span class="nb-card-name" style="color:<?= e($nb['color']) ?>"><?= e($nb['name']) ?></span>
          <?php if ((int) $nb['is_locked'] === 1): ?><span class="nb-lock"><?= icon('lock', 12) ?></span><?php endif; ?>
        </div>
        <div class="nb-card-meta">
          <span><?= (int) $nb['note_count'] ?> notes</span>
          <span><?= $nb['last_note_at'] ? nice_date($nb['last_note_at']) : '—' ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <div class="nb-section-label">Your notebooks</div>
  <div class="nb-grid">
    <?php foreach ($mine as $nb): ?>
      <a class="nb-card" href="<?= url('/notes?notebook=' . $nb['id']) ?>" style="--nb-color:<?= e($nb['color']) ?>">
        <div class="nb-card-head">
          <span class="nb-card-icon" style="color:<?= e($nb['color']) ?>"><?= icon($nb['icon'], 17) ?></span>
          <span class="nb-card-name" style="color:<?= e($nb['color']) ?>"><?= e($nb['name']) ?></span>
        </div>
        <div class="nb-card-meta">
          <span><?= (int) $nb['note_count'] ?> notes</span>
          <span><?= $nb['last_note_at'] ? nice_date($nb['last_note_at']) : '—' ?></span>
        </div>
      </a>
    <?php endforeach; ?>
    <button type="button" class="nb-card nb-card-new" data-open-nb-modal>
      <?= icon('plus', 16) ?><span>New notebook</span>
    </button>
  </div>
</section>

<!-- New-notebook modal -->
<div class="modal-overlay" hidden data-nb-modal>
  <div class="modal">
    <header class="modal-header">
      <h2>New notebook</h2>
      <button type="button" class="icon-btn" data-close-nb-modal><?= icon('close', 15) ?></button>
    </header>
    <form method="post" action="<?= url('/notebooks') ?>">
      <?= App\Core\Csrf::field() ?>
      <label class="field">
        <span class="field-label">Name</span>
        <input type="text" name="name" required maxlength="100" placeholder="e.g. Recipes">
      </label>
      <span class="field-label">Color</span>
      <div class="swatch-grid">
        <?php foreach ($palette as $i => $color): ?>
          <label class="swatch">
            <input type="radio" name="color" value="<?= e($color) ?>" <?= $i === 4 ? 'checked' : '' ?>>
            <span style="background:<?= e($color) ?>"></span>
          </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn-primary btn-block">Create notebook</button>
    </form>
  </div>
</div>

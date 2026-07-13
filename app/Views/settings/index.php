<section class="settings-view">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Settings</h1>
  </header>

  <div class="settings-card">
    <a class="settings-user" href="<?= url('/profile') ?>">
      <?= avatar_html($user, 'avatar avatar-lg') ?>
      <div>
        <div class="settings-name"><?= e($user['display_name']) ?></div>
        <div class="settings-email"><?= e($user['email']) ?></div>
      </div>
      <span class="settings-edit-hint">Edit <?= icon('back', 12, 'flip-h') ?></span>
    </a>
    <div class="settings-row">
      <span>Member since</span>
      <span class="mono"><?= nice_date($user['created_at']) ?></span>
    </div>
    <div class="settings-row">
      <span>Sync</span>
      <span class="user-sync"><?= icon('sync', 11) ?> Synced</span>
    </div>
  </div>

  <form method="post" action="<?= url('/logout') ?>">
    <?= App\Core\Csrf::field() ?>
    <button type="submit" class="btn-ghost btn-danger btn-block">Sign out</button>
  </form>
</section>

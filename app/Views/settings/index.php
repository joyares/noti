<section class="settings-view">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Settings</h1>
  </header>

  <div class="settings-card">
    <div class="settings-user">
      <span class="avatar avatar-lg"><?= e(mb_strtoupper(mb_substr($user['display_name'], 0, 1))) ?></span>
      <div>
        <div class="settings-name"><?= e($user['display_name']) ?></div>
        <div class="settings-email"><?= e($user['email']) ?></div>
      </div>
    </div>
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

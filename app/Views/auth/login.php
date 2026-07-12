<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-tile">N</span>
    <span class="logo-word">Noti</span>
  </div>
  <h1 class="auth-title">Welcome back</h1>
  <p class="auth-sub">Sign in to your notes</p>

  <?php if (!empty($error)): ?>
    <div class="auth-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= url('/login') ?>" class="auth-form">
    <?= App\Core\Csrf::field() ?>
    <label class="field">
      <span class="field-label">Email</span>
      <input type="email" name="email" value="<?= e($email ?? '') ?>" required autofocus autocomplete="email">
    </label>
    <label class="field">
      <span class="field-label">Password</span>
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn-primary btn-block">Sign in</button>
  </form>

  <p class="auth-switch">No account yet? <a href="<?= url('/register') ?>">Create one</a></p>
</div>

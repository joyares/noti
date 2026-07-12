<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-tile">N</span>
    <span class="logo-word">Noti</span>
  </div>
  <h1 class="auth-title">Create your account</h1>
  <p class="auth-sub">18 notebooks, ready to fill</p>

  <?php if (!empty($error)): ?>
    <div class="auth-error"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= url('/register') ?>" class="auth-form">
    <?= App\Core\Csrf::field() ?>
    <label class="field">
      <span class="field-label">Display name</span>
      <input type="text" name="display_name" value="<?= e($display_name ?? '') ?>" required autofocus maxlength="100" autocomplete="name">
    </label>
    <label class="field">
      <span class="field-label">Email</span>
      <input type="email" name="email" value="<?= e($email ?? '') ?>" required autocomplete="email">
    </label>
    <label class="field">
      <span class="field-label">Password</span>
      <input type="password" name="password" required minlength="8" autocomplete="new-password">
      <span class="field-hint">At least 8 characters</span>
    </label>
    <button type="submit" class="btn-primary btn-block">Create account</button>
  </form>

  <p class="auth-switch">Already have an account? <a href="<?= url('/login') ?>">Sign in</a></p>
</div>

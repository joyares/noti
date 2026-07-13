<section class="profile-view">
  <header class="page-header">
    <a class="m-back" href="<?= url('/') ?>"><?= icon('back', 18) ?></a>
    <h1>Profile</h1>
  </header>

  <?php if (!empty($error)): ?>
    <div class="auth-error"><?= e($error) ?></div>
  <?php elseif (!empty($success)): ?>
    <div class="profile-success"><?= icon('check', 13) ?> <?= e($success) ?></div>
  <?php endif; ?>

  <!-- Profile picture -->
  <div class="settings-card">
    <div class="profile-avatar-row">
      <?= avatar_html($user, 'avatar avatar-xl') ?>
      <div class="profile-avatar-actions">
        <div class="profile-card-title">Profile picture</div>
        <p class="profile-hint">JPG, PNG, GIF or WebP · up to 5 MB</p>
        <div class="profile-avatar-buttons">
          <form method="post" action="<?= url('/profile/avatar') ?>" enctype="multipart/form-data" data-avatar-form>
            <?= App\Core\Csrf::field() ?>
            <label class="btn-ghost">
              <?= icon('image', 13) ?> Upload new
              <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" hidden data-avatar-input>
            </label>
          </form>
          <?php if (!empty($user['avatar_path'])): ?>
            <form method="post" action="<?= url('/profile/avatar/remove') ?>">
              <?= App\Core\Csrf::field() ?>
              <button type="submit" class="btn-ghost btn-danger">Remove</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Name + email -->
  <div class="settings-card">
    <div class="profile-card-title">Account</div>
    <form method="post" action="<?= url('/profile') ?>">
      <?= App\Core\Csrf::field() ?>
      <label class="field">
        <span class="field-label">Display name</span>
        <input type="text" name="display_name" value="<?= e($user['display_name']) ?>" required maxlength="100" autocomplete="name">
      </label>
      <label class="field">
        <span class="field-label">Email</span>
        <input type="email" name="email" value="<?= e($user['email']) ?>" required autocomplete="email">
      </label>
      <button type="submit" class="btn-primary">Save changes</button>
    </form>
  </div>

  <!-- Password -->
  <div class="settings-card">
    <div class="profile-card-title">Change password</div>
    <form method="post" action="<?= url('/profile/password') ?>">
      <?= App\Core\Csrf::field() ?>
      <label class="field">
        <span class="field-label">Current password</span>
        <input type="password" name="current_password" required autocomplete="current-password">
      </label>
      <label class="field">
        <span class="field-label">New password</span>
        <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        <span class="field-hint">At least 8 characters</span>
      </label>
      <label class="field">
        <span class="field-label">Confirm new password</span>
        <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
      </label>
      <button type="submit" class="btn-primary">Change password</button>
    </form>
  </div>
</section>

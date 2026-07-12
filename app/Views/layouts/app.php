<?php
/** @var array $user, $notebooks, string $content, $nav, $screen */
$nav    = $nav ?? '';
$screen = $screen ?? '';
$activeNotebookId = isset($activeNotebook) && $activeNotebook ? (int) $activeNotebook['id'] : null;
$defaultNotebookId = $activeNotebookId ?? (int) ($notebooks[0]['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Noti</title>
<meta name="csrf-token" content="<?= App\Core\Csrf::token() ?>">
<meta name="base-url" content="<?= e(url('/')) ?>">
<meta name="theme-color" content="#0c0e0d">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body class="app-body screen-<?= e($screen) ?>" data-default-notebook="<?= $defaultNotebookId ?>">

<div class="shell">

  <!-- ── Sidebar (desktop / tablet rail) ─────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-top">
      <a class="brand" href="<?= url('/') ?>">
        <span class="logo-tile">N</span>
        <span class="logo-word">Noti</span>
        <kbd class="kbd-hint">⌘K</kbd>
      </a>

      <button type="button" class="sidebar-search" data-open-search>
        <?= icon('search', 14) ?>
        <span>Search notes…</span>
      </button>

      <form method="post" action="<?= url('/notes') ?>" class="newnote-form">
        <?= App\Core\Csrf::field() ?>
        <input type="hidden" name="notebook_id" value="<?= $defaultNotebookId ?>">
        <button type="submit" class="btn-primary btn-newnote"><?= icon('plus', 14) ?><span>New note</span></button>
      </form>
    </div>

    <nav class="sidebar-nav">
      <a href="<?= url('/') ?>" class="nav-item<?= $nav === 'home' ? ' active' : '' ?>"><?= icon('home', 15) ?><span>Home</span></a>
      <a href="<?= url('/notes') ?>" class="nav-item<?= $nav === 'notes' ? ' active' : '' ?>"><?= icon('notes', 15) ?><span>All notes</span></a>
      <a href="<?= url('/tags') ?>" class="nav-item<?= $nav === 'tags' ? ' active' : '' ?>"><?= icon('tag', 15) ?><span>Tags</span></a>
      <a href="<?= url('/trash') ?>" class="nav-item<?= $nav === 'trash' ? ' active' : '' ?>"><?= icon('trash', 15) ?><span>Trash</span></a>
    </nav>

    <div class="sidebar-section-label">
      <span>Notebooks</span>
      <a href="<?= url('/notebooks') ?>" class="section-action" title="All notebooks"><?= icon('plus', 12) ?></a>
    </div>

    <div class="sidebar-notebooks">
      <?php foreach ($notebooks as $nb): ?>
        <a href="<?= url('/notes?notebook=' . $nb['id']) ?>"
           class="nb-item<?= $activeNotebookId === (int) $nb['id'] ? ' active' : '' ?>">
          <span class="nb-icon" style="color:<?= e($nb['color']) ?>"><?= icon($nb['icon'], 15) ?></span>
          <span class="nb-name" style="color:<?= e($nb['color']) ?>"><?= e($nb['name']) ?></span>
          <?php if ((int) $nb['is_locked'] === 1): ?><span class="nb-lock"><?= icon('lock', 11) ?></span><?php endif; ?>
          <span class="nb-count"><?= (int) $nb['note_count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="sidebar-user">
      <span class="avatar"><?= e(mb_strtoupper(mb_substr($user['display_name'], 0, 1))) ?></span>
      <span class="user-meta">
        <span class="user-name"><?= e($user['display_name']) ?></span>
        <span class="user-sync"><?= icon('sync', 10) ?> Synced</span>
      </span>
      <form method="post" action="<?= url('/logout') ?>" class="logout-form">
        <?= App\Core\Csrf::field() ?>
        <button type="submit" class="icon-btn" title="Sign out"><?= icon('close', 13) ?></button>
      </form>
    </div>
  </aside>

  <!-- ── Main content ────────────────────────────────────────────── -->
  <main class="content">
<?= $content ?>
  </main>
</div>

<!-- ── Mobile tab bar ──────────────────────────────────────────────── -->
<nav class="tabbar">
  <a href="<?= url('/') ?>" class="tab<?= $nav === 'home' ? ' active' : '' ?>"><?= icon('home', 20) ?><span>Home</span></a>
  <a href="<?= url('/notebooks') ?>" class="tab<?= $nav === 'notebooks' ? ' active' : '' ?>"><?= icon('book', 20) ?><span>Notebooks</span></a>
  <form method="post" action="<?= url('/notes') ?>" class="tab-fab-form">
    <?= App\Core\Csrf::field() ?>
    <input type="hidden" name="notebook_id" value="<?= $defaultNotebookId ?>">
    <button type="submit" class="tab-fab" aria-label="New note"><?= icon('plus', 22) ?></button>
  </form>
  <a href="<?= url('/tags') ?>" class="tab<?= $nav === 'tags' ? ' active' : '' ?>"><?= icon('tag', 20) ?><span>Tags</span></a>
  <a href="<?= url('/settings') ?>" class="tab<?= $nav === 'settings' ? ' active' : '' ?>"><?= icon('settings', 20) ?><span>Settings</span></a>
</nav>

<!-- ── ⌘K search overlay ───────────────────────────────────────────── -->
<div class="search-overlay" hidden data-search-overlay>
  <div class="search-panel">
    <div class="search-input-row">
      <?= icon('search', 16) ?>
      <input type="text" placeholder="Search notes… (try tag:shopping)" data-search-input autocomplete="off">
      <kbd>esc</kbd>
    </div>
    <div class="search-results" data-search-results></div>
  </div>
</div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>

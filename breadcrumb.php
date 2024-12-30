
<nav class="text-gray-400 text-sm">
  <?php if (!empty($categoryPath) && !empty($categoryName)): ?>
    <a href="<?= $categoryPath ?>"
      class="text-sunset-yellow hover:underline"><?= htmlspecialchars($categoryName) ?></a>
    /
    <h1 class="inline-block title"><?= htmlspecialchars($contentTitle) ?></h1>
  <?php else: ?>
    <h1 class="inline-block title"><?= htmlspecialchars($contentTitle) ?></h1>
  <?php endif; ?>
</nav>
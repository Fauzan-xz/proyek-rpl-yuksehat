<nav aria-label="breadcrumb">
  <ol class="breadcrumb mb-0 bg-transparent p-0">
    <?php foreach($data['breadcrumb'] as $name => $link): ?>
        <?php if($link == '#'): ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $name ?></li>
        <?php else: ?>
            <li class="breadcrumb-item"><a href="<?= $link ?>"><?= $name ?></a></li>
        <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>

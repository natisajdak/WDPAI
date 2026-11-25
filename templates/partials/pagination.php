<?php
/**
 * Nawigacja między stronami wyników z zachowaniem bieżących filtrów.
 *
 * @var \App\View\View $this
 * @var \App\Domain\Query\Page<mixed> $page
 * @var string $path
 * @var array<string, string|int|null> $query
 */

if ($page->totalPages() <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="<?= $this->t('pagination.label') ?>">
    <?php if ($page->hasPrevious()): ?>
        <a class="pagination__link" rel="prev" href="<?= $this->url($path, $query + ['page' => $page->page - 1]) ?>"><?= $this->t('pagination.previous') ?></a>
    <?php endif; ?>

    <span class="pagination__status" aria-current="page"><?= $this->t('pagination.status', ['page' => $page->page, 'pages' => $page->totalPages()]) ?></span>

    <?php if ($page->hasNext()): ?>
        <a class="pagination__link" rel="next" href="<?= $this->url($path, $query + ['page' => $page->page + 1]) ?>"><?= $this->t('pagination.next') ?></a>
    <?php endif; ?>
</nav>

<?php
/** Bootstrap pagination partial. Usage: $paginator => ['total','page','perPage','lastPage'] */
if (empty($paginator) || (int) $paginator['total'] <= (int) $paginator['perPage']) return;
$current = (int) $paginator['page'];
$lastPage = (int) $paginator['lastPage'];
$query = $_GET;
unset($query['page']);
$qs = http_build_query($query);
$qs = $qs !== '' ? '&' . $qs : '';
$href = fn (int $p) => '?page=' . $p . $qs;
?>
<div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2 pt-3 border-top mt-3">
    <div class="text-muted small">
        Showing <strong><?php echo number_format(min($current * (int) $paginator['perPage'], (int) $paginator['total'])); ?></strong>
        of <strong><?php echo number_format((int) $paginator['total']); ?></strong> records
    </div>
    <nav aria-label="Pagination">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?php echo $current <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo e($href(max(1, $current - 1))); ?>"><i class="fa-solid fa-chevron-left"></i></a>
            </li>
            <?php
            $start = max(1, $current - 2);
            $end = min($lastPage, $current + 2);
            if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="<?php echo e($href(1)); ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?php echo $p === $current ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo e($href($p)); ?>"><?php echo $p; ?></a>
                </li>
            <?php endfor; ?>
            <?php if ($end < $lastPage): ?>
                <?php if ($end < $lastPage - 1): ?><li class="page-item disabled"><span class="page-link">&hellip;</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="<?php echo e($href($lastPage)); ?>"><?php echo $lastPage; ?></a></li>
            <?php endif; ?>
            <li class="page-item <?php echo $current >= $lastPage ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo e($href(min($lastPage, $current + 1))); ?>"><i class="fa-solid fa-chevron-right"></i></a>
            </li>
        </ul>
    </nav>
</div>

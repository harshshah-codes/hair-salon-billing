<?php
/**
 * Empty state partial.
 * Usage: $emptyIcon, $emptyTitle, $emptyText, $emptyAction (html)
 */
?>
<div class="empty-state">
    <div class="empty-icon"><i class="<?php echo e($emptyIcon ?? 'fa-solid fa-inbox'); ?>"></i></div>
    <h5><?php echo e($emptyTitle ?? 'Nothing here yet'); ?></h5>
    <p><?php echo e($emptyText ?? 'No records found. Create your first entry to get started.'); ?></p>
    <?php if (!empty($emptyAction)): ?>
        <div class="mt-2"><?php echo $emptyAction; ?></div>
    <?php endif; ?>
</div>

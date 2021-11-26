<?php
/**
 * @var $paginator \Illuminate\Pagination\LengthAwarePaginator
 */
?>
<?php if ($paginator->hasPages()): ?>
    <div class="zui-pager">
        <ol>
            <?php foreach ($paginator as $page): ?>

            <?php endforeach; ?>
        </ol>
        <form method="get" class="zui-pager__input">
            <input type="text" name="page"/>
        </form>
    </div>

<?php endif; ?>

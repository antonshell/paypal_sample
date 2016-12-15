<?php $config = Config::get(); ?>
<?php foreach($config['menu'] as $item): ?>
    <a href="/?page=<?php echo $item['page']; ?>" class="btn btn-primary">
        <?php echo $item['label']; ?>
    </a>

    <br>
    <br>
<?php endforeach; ?>
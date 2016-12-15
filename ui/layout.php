<!DOCTYPE html>
<html>
<head>
    <?php include('_head.php'); ?>
</head>
<body>
<div class="container">
    <?php include('_menu.php'); ?>
    <?php //include($inc);?>

    <?php echo $this->render(Base::instance()->get('content')); ?>
</div>
</body>
</html>
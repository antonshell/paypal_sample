<?php
$userService = new UserService();

//get selected user
$selectedUserId = $userService->getSelectedUser();

//get users list
$users = $userService->getUsers();

$config = Config::get();
?>

<nav class="navbar navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button aria-controls="navbar" aria-expanded="false" data-target="#navbar" data-toggle="collapse" class="navbar-toggle collapsed" type="button">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a href="/" class="navbar-brand">Paypal Sample</a>
        </div>
        <div class="navbar-collapse collapse" id="navbar">
            <!-- menu -->
            <ul class="nav navbar-nav">
                <?php foreach($config['menu'] as $item){
                    $active = ($item['page'] == $page) ? 'class="active"' : '';
                    ?>
                    <li <?php echo $active ?>>
                        <a href="/?page=<?php echo $item['page']; ?>">
                            <?php echo $item['label']; ?>
                        </a>
                    </li>
                    <?php
                } ?>
            </ul>
            <!-- / menu -->
            <ul class="nav navbar-nav navbar-right">
                <li class="dropdown">
                    <a aria-expanded="false" aria-haspopup="true" role="button" data-toggle="dropdown" class="dropdown-toggle" href="#">User <span class="caret"></span></a>
                    <ul class="dropdown-menu">
                        <!-- select user -->
                        <?php foreach($users as $user): ?>
                            <li>
                                <a href="/?page=select_user&userId=<?php echo $user['id']; ?>"><?php echo $user['firstname'] . ' ' . $user['lastname']; ?></a>
                            </li>
                        <?php endforeach; ?>
                        <!-- / select user -->
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php view('common.header')?>

(IndexController) Hi there!

<?php if (isset($userid)) :?>

    UserID: <?php echo $userid; ?>!

<?php endif; ?>

<?php view('common.footer')?>

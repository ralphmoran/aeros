<?php view('common.header')?>

(IndexController) Hi there!

<?php if (isset($userid)) :?>

    UserID: <?php echo $userid; ?>!

<?php endif; ?>

<?php component('component1', ['foo'=> 'bar']); ?>

<?php csrf(); ?>

<?php view('common.footer')?>

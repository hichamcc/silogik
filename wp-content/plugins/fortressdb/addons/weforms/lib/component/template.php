<?php if (!fortressdb_is_connected()) {?>
<div>
    <p>FortressDB plugin is not connected!</p>
    <?php
    printf("<p>Please, <a href='%s'>connect</a> FortressDB first.</p>", admin_url( 'admin.php?page=fortressdb_plugin' ));
    ?>
</div>
<?php
}
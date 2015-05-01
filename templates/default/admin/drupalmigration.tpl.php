<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>

</div>
<form action="/admin/drupalmigration/" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">
        <h2>Migration Settings</h2>
        </div>
    </div>
    <div class="row">
        <div class="span10 offset1">
          <p class="js-controls"><strong>MySQL Host</strong></p>
            <input type="text" name="drupal_migration_mysql_host" class="span10" rows="10" value="<?=htmlspecialchars(\Idno\Core\site()->config()->drupal_migration_mysql_host)?>" />
        </div>
    </div>
    <div class="row">
        <div class="span10 offset1">
          <p class="js-controls"><strong>MySQL Database</strong></p>
            <input type="text" name="drupal_migration_mysql_db" class="span10" rows="10" value="<?=htmlspecialchars(\Idno\Core\site()->config()->drupal_migration_mysql_db)?>" />
        </div>
    </div>
    <div class="row">
        <div class="span10 offset1">
          <p class="js-controls"><strong>MySQL User</strong></p>
            <input type="text" name="drupal_migration_mysql_user" class="span10" rows="10" value="<?=htmlspecialchars(\Idno\Core\site()->config()->drupal_migration_mysql_user)?>" />
        </div>
    </div>
    <div class="row">
        <div class="span10 offset1">
          <p class="js-controls"><strong>MySQL Password</strong></p>
            <input type="password" name="drupal_migration_mysql_password" class="span10" rows="10" value="<?=htmlspecialchars(\Idno\Core\site()->config()->drupal_migration_mysql_password)?>" />
        </div>
    </div>

    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Save settings</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/')?>
</form>

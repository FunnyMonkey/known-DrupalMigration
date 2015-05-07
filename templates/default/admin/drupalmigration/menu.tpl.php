<li <?php if (strpos($_SERVER['REQUEST_URI'], '/admin/drupalmigration/') === 0) echo 'class="active dropdown"'; else echo'class="dropdown"'; ?>>
<a class="dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
      Drupal Migration<span class="caret"></span>
    </a>
    <ul class="dropdown-menu" role="menu">
      <li <?php if ($_SERVER['REQUEST_URI'] == '/admin/drupalmigration/') echo 'class="active";'?>><a href="<?=\Idno\Core\site()->config()->url?>admin/drupalmigration/">Overview</a></li>
      <li <?php if ($_SERVER['REQUEST_URI'] == '/admin/drupalmigration/files') echo 'class="active";'?>><a href="<?=\Idno\Core\site()->config()->url?>admin/drupalmigration/files">Files</a></li>
      <li <?php if ($_SERVER['REQUEST_URI'] == '/admin/drupalmigration/users') echo 'class="active";'?>><a href="<?=\Idno\Core\site()->config()->url?>admin/drupalmigration/users">Users</a></li>
      <li <?php if ($_SERVER['REQUEST_URI'] == '/admin/drupalmigration/nodes') echo 'class="active";'?>><a href="<?=\Idno\Core\site()->config()->url?>admin/drupalmigration/nodes">Nodes</a></li>
      <li <?php if ($_SERVER['REQUEST_URI'] == '/admin/drupalmigration/comments') echo 'class="active";'?>><a href="<?=\Idno\Core\site()->config()->url?>admin/drupalmigration/comments">Comments</a></li>
    </ul>
</li>
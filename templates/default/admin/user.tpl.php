<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>
</div>

    <div class="row">
        <div class="span10 offset1">
        <h2>User Migration Details</h2>
        </div>
    </div>

<?php
if (!empty($vars['migrateinfo'])) {
print '<div class="row">
    <div class="table-responsive">
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>User ID</th>
            <th>User Name</th>
            <th>New ID</th>
        </tr>
    </thead>
    <tbody>';
    $rewrites = array();
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['uid'] . "</td>\n";
        print '  <td>' . $row['name'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . \Idno\Core\site()->config()->getDisplayURL() . 'profile/' . strtolower(trim($row['name'])) . '/">' . $row['newid'] . "</a></td>\n";
            $rewrites[] = 'RewriteRule "^user/' . $row['uid'] . '$" "profile/' . strtolower(trim($row['name'])) . '" [L,R=301]';
        }
        else {
            print '<td>Not imported</td><td>N/A</td>';
        }
        print '</tr>';
    }
    print '</tbody></table></div></div>';

    print '<h3>Rewrites</h3>';
    print '<pre><code>';
    $lines = implode("\n", $rewrites);
    print $lines;
    print '</code></pre>';
}?>

<form action="/admin/drupalmigration/users" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Import Users</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/users')?>
</form>

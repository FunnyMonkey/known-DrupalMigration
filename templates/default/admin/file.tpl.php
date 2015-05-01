<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>

</div>
<form action="/admin/drupalmigration/files" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">
        <h2>File Migration Details</h2>
        </div>
    </div>

<?php
if (!empty($vars['migrateinfo'])) {
print '<div class="row">
    <div class="span10 offset1">
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>File ID</th>
            <th>File Name</th>
            <th>File URI</th>
            <th>New ID</th>
            <th>mod_rewrite</th>
        </tr>
    </thead>
    <tbody>';
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['fid'] . "</td>\n";
        print '  <td>' . $row['filename'] . "</td>\n";
        print '  <td>' . $row['uri'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . \Idno\Core\site()->config()->getDisplayURL() . 'file/' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            print '  <td><pre>RewriteRule "^' . $row['uri'] .'" "file/' . $row['newid'] . '" [L,R=301]</pre></td>';
        }
        else {
            print '<td>Not imported</td><td>N/A</td>';
        }
        print '</tr>';
    }
    print '</tbody></table></div></div>';
}?>

    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Import Files</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/files')?>
</form>

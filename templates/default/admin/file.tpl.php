<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>

</div>

    <div class="row">
        <div class="span10 offset1">
        <h2>File Migration Details</h2>
        </div>
    </div>

<?php
if (!empty($vars['migrateinfo'])) {
print '
    <div class="table-responsive">
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>File ID</th>
            <th>File Name</th>
            <th>File URI</th>
            <th>New ID</th>
        </tr>
    </thead>
    <tbody>';
    $rewrites = array();
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['fid'] . "</td>\n";
        print '  <td>' . $row['filename'] . "</td>\n";
        print '  <td>' . $row['uri'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . \Idno\Core\site()->config()->getDisplayURL() . 'file/' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            $rewrites[] = 'RewriteRule "^' . $row['uri'] .'" "file/' . $row['newid'] . '" [L,R=301]';
        }
        else {
            print '<td>Not imported</td><td>N/A</td>';
        }
        print '</tr>';
    }
    print '</tbody></table></div>';

    print '<h3>Rewrites</h3>';
    print '<pre><code>';
    $lines = implode("\n", $rewrites);
    print $lines;
    print '</code></pre>';
}?>
<form action="/admin/drupalmigration/files" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Import Files</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/files')?>
</form>

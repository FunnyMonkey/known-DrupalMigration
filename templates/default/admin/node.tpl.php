<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>

</div>
<form action="/admin/drupalmigration/nodes" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">
        <h2>Node Migration Details</h2>
        </div>
    </div>

<?php
if (!empty($vars['migrateinfo'])) {
print '<div class="row">
    <div class="span10 offset1">
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>Node ID</th>
            <th>Title</th>
            <th>New ID</th>
            <th>mod_rewrite</th>
        </tr>
    </thead>
    <tbody>';
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['nid'] . "</td>\n";
        print '  <td>' . $row['title'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . \Idno\Core\site()->config()->getDisplayURL() . 'entry/' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            print '  <td><pre>RewriteRule "^node/' . $row['nid'] . '" "entry/' . $row['newid'] . '" [L,R=301]</pre></td>';
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

            <button type="submit" class="btn btn-primary code">Import Nodes</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/nodes')?>
</form>

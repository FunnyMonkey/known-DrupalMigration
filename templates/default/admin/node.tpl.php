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
    <div class="table-responsive">
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>Node ID</th>
            <th>Title</th>
            <th>New ID</th>
        </tr>
    </thead>
    <tbody>';
    $rewrites = array();
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['nid'] . "</td>\n";
        print '  <td>' . $row['title'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . \Idno\Core\site()->config()->getDisplayURL() . 'entry/' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            $entity = \Idno\Common\Entity::getByID($row['newid']);
            $rewrites[] = 'RewriteRule "^node/' . $row['nid'] . '$" "' . str_replace(\Idno\Core\site()->config()->getDisplayURL(), '/', $entity->getURL()) . '" [L,R=301]';
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

    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Import Nodes</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/nodes')?>
</form>

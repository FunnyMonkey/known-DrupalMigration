<div class="row">

    <div class="span10 offset1">
              <?=$this->draw('admin/menu')?>
    </div>

</div>
<form action="/admin/drupalmigration/comments" class="form-horizontal" method="post">
    <div class="row">
        <div class="span10 offset1">
        <h2>Comment Migration Details</h2>
        </div>
    </div>

<?php
if (!empty($vars['migrateinfo'])) {
print '<div class="row">
    <div class="span10 offset1">
    <p>Note that the comment new ID might be incorrect as the addAnnotation() function does not return the newID and we can\'t specify it.</p>
    <table class="table-striped table-bordered table-condensed table-hover" >
    <thead>
        <tr>
            <th>Comment ID</th>
            <th>Comment Name</th>
            <th>Source Post</th>
            <th>Comment URI</th>
        </tr>
    </thead>
    <tbody>';
    $rewrites = array();
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['cid'] . "</td>\n";
        print '  <td>' . $row['subject'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . $row['posturl'] . '/">' . $row['posttitle'] . "</a></td>\n";
            print '  <td><a href="' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            if (!empty($row['rewrite'])) {
                $rewrites[] = 'RewriteRule "^comment/' . $row['cid'] . '$" "' . $row['rewrite'] . '" [L,R=301]';
            }
        }
        else {
            print '<td>N/A</td><td>Not imported</td>';
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

            <button type="submit" class="btn btn-primary code">Import Comments</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/comments')?>
</form>

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
            <th>mod_rewrite</th>
        </tr>
    </thead>
    <tbody>';
    foreach ($vars['migrateinfo'] as $row) {
        print '<tr>';
        print '  <td>' . $row['cid'] . "</td>\n";
        print '  <td>' . $row['subject'] . "</td>\n";
        if ($row['newid']) {
            print '  <td><a href="' . $row['posturl'] . '/">' . $row['posttitle'] . "</a></td>\n";
            print '  <td><a href="' . $row['newid'] . '/">' . $row['newid'] . "</a></td>\n";
            print '  <td><pre>RewriteRule "^comment/' . $row['cid'] . '" "' . $row['newid'] . '" [L,R=301]</pre></td>';
        }
        else {
            print '<td>N/A</td><td>Not imported</td><td>N/A</td>';
        }
        print '</tr>';
    }
    print '</tbody></table></div></div>';
}?>

    <div class="row">
        <div class="span10 offset1">

            <button type="submit" class="btn btn-primary code">Import Comments</button>

        </div>
    </div>
    <?= \Idno\Core\site()->actions()->signForm('/admin/drupalmigration/comments')?>
</form>

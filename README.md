Install in the IdnoPlugins directory under the name 'DrupalMigration'

Then go to 'Site Configuration' -> 'Plugins' and then enable 'Funnymonkey.com Drupal Migration 0.1'

Now select "Drupal Migration" and add Drupal database details for Overview.

The import should proceed in the following order;

1. Files
2. Users
3. Nodes
4. Comments

Note that the node migration can take quite a while so it is limited to 20 nodes
per import request.

There is also a blog post explaining this code over here: https://funnymonkey.com/2015/migrating-from-drupal-7-to-known

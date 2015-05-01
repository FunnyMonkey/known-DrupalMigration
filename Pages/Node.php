<?php

/**
 * Drupal migration administration: Nodes
 */

namespace IdnoPlugins\DrupalMigration\Pages {

    // The URL rewriting is not optimized and can take quite a bit of time.
    // This constant minimizes the number of nodes we import per request.
    define('NODE_MAX_IMPORT', 20);

    /**
     * Default class to serve Drupal migration settings in administration
     */
    class Node extends \Idno\Common\Page
    {
        function getContent()
        {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $nodes = $migration->getNodes();
            $migrateinfo = array();
            $nodemap = \Idno\Core\site()->config()->drupal_migration_node_map;
            if (empty($nodemap)) {
                $nodemap = array();
            }

            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    $row = array();
                    $row['nid'] = $node->nid;
                    $row['title'] = $node->title;
                    if (in_array($node->nid, $nodemap)) {
                        $row['newid'] = array_search($node->nid, $nodemap);
                    }
                    else {
                        $row['newid'] = '';
                    }
                    $migrateinfo[$node->nid] = $row;
                }
            }

            $t = \Idno\Core\site()->template();
            $t->migrateinfo = $migrateinfo;
            $body = $t->draw('admin/node');
            $t->__(array('title' => 'Drupal Migration', 'body' => $body))->drawPage();
        }

        function postContent() {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $nodes = $migration->getNodes();
            $nodemap = \Idno\Core\site()->config()->drupal_migration_node_map;
            if (empty($nodemap)) {
                $nodemap = array();
            }
            $i = 0;
            if (!empty($nodes)) {
                foreach ($nodes as $node) {
                    if (!in_array($node->nid, $nodemap)) {
                        $newnode = $migration->addNode($node);
                        if ($newnode) {
                            $nodemap[$newnode] = $node->nid;
                            \Idno\Core\site()->config->config['drupal_migration_node_map'] = $nodemap;
                            \Idno\Core\site()->config()->save();
                            $i++;
                            if ($i >= NODE_MAX_IMPORT) {
                                break;
                            }
                        }
                    }
                }
            }

            if (!empty($i)) {
                \Idno\Core\site()->session()->addMessage('Added: ' . $i . ' nodes.', 'alert-success');
            }

            $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/drupalmigration/nodes');
        }
    }
}
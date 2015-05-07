<?php

/**
 * Drupal migration administration: Comments
 */

namespace IdnoPlugins\DrupalMigration\Pages {

    // The URL rewriting is not optimized and can take quite a bit of time.
    // This constant minimizes the number of nodes we import per request.
    define('COMMENT_MAX_IMPORT', 50);

    /**
     * Default class to serve Drupal migration settings in administration
     */
    class Comment extends \Idno\Common\Page
    {
        function getContent()
        {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $comments = $migration->getComments();

            $nodemap = \Idno\Core\site()->config()->drupal_migration_node_map;

            $migrateinfo = array();
            $commentmap = \Idno\Core\site()->config()->drupal_migration_comment_map;
            if (empty($commentmap)) {
                $commentmap = array();
            }
            if (!empty($comments)) {
                foreach ($comments as $comment) {
                    $row = array();
                    $row['cid'] = $comment->cid;
                    $row['subject'] = $comment->subject;
                    if ($objectID = array_search($comment->nid, $nodemap) ) {
                        if ($post = \Idno\Common\Entity::getByID($objectID)) {
                            $row['posttitle'] = $post->getTitle();
                            $row['posturl'] = $post->getURL();
                        }
                        else {
                            $row['posttitle'] = 'Could not load';
                            $row['posturl'] = 'http://example.com';
                        }
                    }
                    else {
                        $row['posttitle'] = 'Not found';
                        $row['posturl'] = 'http://example.com';
                    }

                    if (isset($commentmap[$row['cid']])) {
                        $row['newid'] = $commentmap[$row['cid']];
                    }
                    else {
                        $row['newid'] = '';
                    }
                    $migrateinfo[$row['cid']] = $row;
                }
            }

            $t = \Idno\Core\site()->template();
            $t->migrateinfo = $migrateinfo;
            $body = $t->draw('admin/comment');
            $t->__(array('title' => 'Drupal Migration', 'body' => $body))->drawPage();
        }

        function postContent() {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $comments = $migration->getComments();
            $commentmap = \Idno\Core\site()->config()->drupal_migration_comment_map;
            if (empty($commentmap)) {
                $commentmap = array();
            }

            $i = 0;
            if (!empty($comments)) {
                foreach ($comments as $comment) {
                    if (!isset($commentmap[$comment->cid])) {
                        if ($newcomment = $migration->addComment($comment)) {
                            $commentmap[$comment->cid] = $newcomment;
                            $i++;
                            if ($i >= COMMENT_MAX_IMPORT) {
                                break;
                            }
                        }
                        else {
                            \Idno\Core\site()->session()->addMessage('Failed to insert comment: ' . $comment->cid, 'alert-danger');
                        }
                    }
                }
                \Idno\Core\site()->config->config['drupal_migration_comment_map'] = $commentmap;
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Added: ' . $i . ' comments.', 'alert-success');
            }
            $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/drupalmigration/comments');
        }
    }
}
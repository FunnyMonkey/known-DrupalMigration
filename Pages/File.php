<?php

/**
 * Drupal migration administration: Files
 */

namespace IdnoPlugins\DrupalMigration\Pages {

    /**
     * Default class to serve Drupal migration settings in administration
     */
    class File extends \Idno\Common\Page
    {
        function getContent()
        {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $files = $migration->getFiles();
            $migrateinfo = array();
            $filemap = \Idno\Core\site()->config()->drupal_migration_file_map;
            if (empty($filemap)) {
                $filemap = array();
            }

            if (!empty($files)) {
                foreach ($files as $file) {
                    $row = array();
                    $row['fid'] = $file->fid;
                    $row['filename'] = $file->filename;
                    $row['uri'] = 'sites/default/files/' . $file->uri;
                    if (isset($filemap[$row['uri']])) {
                        $row['newid'] = $filemap[$row['uri']];
                    }
                    else {
                        $row['newid'] = '';
                    }
                    $migrateinfo[$file->uri] = $row;
                }
            }

            $t = \Idno\Core\site()->template();
            $t->migrateinfo = $migrateinfo;
            $body = $t->draw('admin/file');
            $t->__(array('title' => 'Drupal Migration', 'body' => $body))->drawPage();
        }

        function postContent() {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $files = $migration->getFiles();
            $filemap = \Idno\Core\site()->config()->drupal_migration_file_map;
            if (empty($filemap)) {
                $filemap = array();
            }

            $i = 0;
            if (!empty($files)) {
                foreach ($files as $file) {
                    $uri = 'sites/default/files/' . $file->uri;
                    if (!isset($filemap[$uri])) {
                        $newfile = $migration->addFile($file);
                        $filemap[$uri] = $newfile;
                        $i++;
                    }
                }
                \Idno\Core\site()->config->config['drupal_migration_file_map'] = $filemap;
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Added: ' . $i . 'files.', 'alert-success');
            }
            $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/drupalmigration/files');
        }
    }
}
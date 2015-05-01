<?php

/**
 * Drupal migration administration
 */

namespace IdnoPlugins\DrupalMigration\Pages {

    /**
     * Default class to serve Drupal migration settings in administration
     */
    class Admin extends \Idno\Common\Page
    {
        private $drupaldbh = FALSE;
        function getContent()
        {
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $this->drupaldbh = $migration->getDbh();
            if (!empty($this->drupaldbh)) {
                \Idno\Core\site()->session()->addMessage('Drupal database connection successful!', 'alert-success');
            }

            $this->adminGatekeeper(); // Admins only
            $t = \Idno\Core\site()->template();
            $body = $t->draw('admin/drupalmigration');
            $t->__(array('title' => 'Drupal Migration', 'body' => $body))->drawPage();
        }


        function postContent() {
            $this->adminGatekeeper(); // Admins only
            $drupal_migration_mysql_host = $this->getInput('drupal_migration_mysql_host');
            \Idno\Core\site()->config->config['drupal_migration_mysql_host'] = $drupal_migration_mysql_host;
            $drupal_migration_mysql_db = $this->getInput('drupal_migration_mysql_db');
            \Idno\Core\site()->config->config['drupal_migration_mysql_db'] = $drupal_migration_mysql_db;
            $drupal_migration_mysql_user = $this->getInput('drupal_migration_mysql_user');
            \Idno\Core\site()->config->config['drupal_migration_mysql_user'] = $drupal_migration_mysql_user;
            $drupal_migration_mysql_password = $this->getInput('drupal_migration_mysql_password');
            \Idno\Core\site()->config->config['drupal_migration_mysql_password'] = $drupal_migration_mysql_password;

            \Idno\Core\site()->config()->save();
            \Idno\Core\site()->session()->addMessage('The settings were saved.');
            $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/drupalmigration/');
        }
    }

}




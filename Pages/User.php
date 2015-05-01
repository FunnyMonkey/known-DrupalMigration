<?php

/**
 * Drupal migration administration: Users
 */

namespace IdnoPlugins\DrupalMigration\Pages {

    /**
     * Default class to serve Drupal migration settings in administration
     */
    class User extends \Idno\Common\Page
    {
        function getContent()
        {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $users = $migration->getUsers();
            $migrateinfo = array();
            $usermap = \Idno\Core\site()->config()->drupal_migration_user_map;
            if (empty($usermap)) {
                $usermap = array();
            }

            if (!empty($users)) {
                foreach ($users as $user) {
                    $row = array();
                    $row['uid'] = $user->uid;
                    $row['name'] = $user->name;
                    if (in_array($user->uid, $usermap)) {
                        $row['newid'] = array_search($user->uid, $usermap);
                    }
                    else {
                        $row['newid'] = '';
                    }
                    $migrateinfo[$user->uid] = $row;
                }
            }

            $t = \Idno\Core\site()->template();
            $t->migrateinfo = $migrateinfo;
            $body = $t->draw('admin/user');
            $t->__(array('title' => 'Drupal Migration', 'body' => $body))->drawPage();
        }

        function postContent() {
            $this->adminGatekeeper(); // Admins only
            $migration = \Idno\Core\site()->plugins()->get('DrupalMigration');
            $users = $migration->getUsers();
            $usermap = \Idno\Core\site()->config()->drupal_migration_user_map;
            if (empty($usermap)) {
                $usermap = array();
            }
            $i = 0;
            if (!empty($users)) {
                foreach ($users as $user) {
                    if (!in_array($user->uid, $usermap)) {
                        $newuser = $migration->addUser($user);
                        $usermap[$newuser] = $user->uid;
                        $i++;
                    }
                }
                \Idno\Core\site()->config->config['drupal_migration_user_map'] = $usermap;
                \Idno\Core\site()->config()->save();
                \Idno\Core\site()->session()->addMessage('Added: ' . $i . 'users.', 'alert-success');
            }
            $this->forward(\Idno\Core\site()->config()->getDisplayURL() . 'admin/drupalmigration/users');
        }
    }
}
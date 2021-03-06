<?php

    namespace IdnoPlugins\DrupalMigration {
        // Where to find legacy files. Full path to 'sites/default/files'
        define('LEGACY_FILES_DIR', '/srv/www/legacy/files');

        // Define the tables (comma separated) that nodes may have referenced file fields with
        define('FILE_TABLES', 'field_data_field_fm_image,field_data_field_fm_file');

        // Define the SQL to get our nodes from.
        define('NODE_SQL', 'SELECT
          n.title AS title,
          n.uid AS uid,
          n.nid AS nid,
          n.vid AS vid,
          n.created AS created,
          b.body_value AS body,
          GROUP_CONCAT(td.name) as terms
        FROM node n
        LEFT JOIN field_data_body b ON (n.nid = b.entity_id AND n.vid = b.revision_id AND b.entity_type = "node" AND b.bundle = n.type)
        LEFT JOIN field_data_field_fm_topics t ON (n.nid = t.entity_id AND n.vid = t.revision_id AND t.entity_type = "node" AND t.bundle = n.type)
        LEFT JOIN taxonomy_term_data td ON (t.field_fm_topics_tid = td.tid)
        WHERE n.type IN ("fm_blog")
        GROUP BY n.nid
        ORDER BY n.created');

        define('USER_SQL', 'SELECT
          u.uid AS uid,
          u.name AS name,
          u.created AS created,
          fn.field_fm_first_name_value AS firstname,
          ln.field_fm_last_name_value AS lastname,
          u.mail AS mail,
          d.field_fm_drupal_url AS drupal,
          g.field_fm_github_url AS github,
          t.field_fm_twitter_url AS twitter,
          b.field_fm_bio_value AS bio,
          REPLACE(f.uri, "public://", "") as uri,
          f.filename,
          f.filemime
        FROM users u
        LEFT JOIN field_data_field_fm_bio b ON (b.entity_id = u.uid AND b.bundle = "user")
        LEFT JOIN field_data_field_fm_drupal d ON (d.entity_id = u.uid AND d.bundle = "user")
        LEFT JOIN field_data_field_fm_github g ON (g.entity_id = u.uid AND g.bundle = "user")
        LEFT JOIN field_data_field_fm_twitter t ON (t.entity_id = u.uid AND t.bundle = "user")
        LEFT JOIN field_data_field_fm_image i ON (i.entity_id = u.uid AND i.bundle = "user")
        LEFT JOIN file_managed f ON (f.fid = i.field_fm_image_fid)
        LEFT JOIN field_data_field_fm_last_name ln ON (ln.entity_id = u.uid AND ln.bundle = "user")
        LEFT JOIN field_data_field_fm_first_name fn ON (fn.entity_id = u.uid AND fn.bundle = "user")
        WHERE u.uid NOT IN (0,1)');

        define('FILE_SQL', 'SELECT
          fm.fid,
          fm.filename,
          REPLACE(fm.uri, "public://", "") as uri,
          fm.filemime,
          fm.timestamp
        FROM file_managed fm
        ORDER BY fm.timestamp');

        define('COMMENT_SQL', 'SELECT
            c.cid,
            c.nid,
            c.uid,
            c.subject,
            c.created,
            c.name,
            c.homepage,
            cb.comment_body_value as body
        FROM comment c
        LEFT JOIN field_data_comment_body cb ON (cb.entity_id = c.cid)
        LEFT JOIN node n ON (n.nid = c.nid)
        WHERE c.status = 1 AND
            n.type IN ("fm_blog")
        ORDER BY c.nid,c.created');

        class Main extends \Idno\Common\Plugin {

            function registerPages() {
                // Administration page
                \Idno\Core\site()->addPageHandler('admin/drupalmigration','\IdnoPlugins\DrupalMigration\Pages\Admin');
                \Idno\Core\site()->addPageHandler('admin/drupalmigration/users','\IdnoPlugins\DrupalMigration\Pages\User');
                \Idno\Core\site()->addPageHandler('admin/drupalmigration/nodes','\IdnoPlugins\DrupalMigration\Pages\Node');
                \Idno\Core\site()->addPageHandler('admin/drupalmigration/files','\IdnoPlugins\DrupalMigration\Pages\File');
                \Idno\Core\site()->addPageHandler('admin/drupalmigration/comments','\IdnoPlugins\DrupalMigration\Pages\Comment');
                \Idno\Core\site()->template()->extendTemplate('admin/menu/items','admin/drupalmigration/menu');
            }

            function getDbh() {
              static $drupaldbh;

              if (empty($drupaldbh)) {
                try {
                  $host = \Idno\Core\site()->config()->drupal_migration_mysql_host;
                  $db = \Idno\Core\site()->config()->drupal_migration_mysql_db;
                  $user = \Idno\Core\site()->config()->drupal_migration_mysql_user;
                  $password = \Idno\Core\site()->config()->drupal_migration_mysql_password;

                  $connect = 'mysql:';
                  if (!empty($host)) {
                    $connect .= 'host=' . $host . ';';
                  }
                  if (!empty($db)) {
                    $connect .= 'dbname=' . $db . ';';
                  }

                  if (!empty($user) && !empty($password)) {
                    $drupaldbh = new \PDO($connect, $user, $password, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
                  }
                  else {
                    throw new \Exception('Empty username or password.');
                  }
                }
                catch (\Exception $e) {
                  \Idno\Core\site()->session()->addMessage('Database connection failed with <pre>' . $e->getMessage()  . '</pre>', 'alert-danger');
                  $drupaldbh = FALSE;
                }
              }

              return $drupaldbh;
            }

            function getDrupalObjects($sql, $params = array()) {
              $drupaldbh = $this->getDbh();
              $stmt = $drupaldbh->prepare($sql);
              $result = $stmt->execute($params);
              $nodes = array();
              if ($result) {
                $nodes = $stmt->fetchAll(\PDO::FETCH_CLASS);
              }
              else {
                $code = $stmt->errorCode();
                $info = $stmt->errorInfo();
                \Idno\Core\site()->session()->addMessage('Sql error(' . $code . '): ' . var_export($info[2], 1), 'alert-danger');
              }
              return $nodes;
            }

            function getNodes() {
              return $this->getDrupalObjects(NODE_SQL);
            }

            function getUsers() {
              return $this->getDrupalObjects(USER_SQL);
            }

            function getComments() {
                return $this->getDrupalObjects(COMMENT_SQL);
            }

            /**
             * Drupal has the concept of managed_files, that is files that
             * Drupal "knows" about. Due to this site having migrated through
             * several versions of content we have some unmanaged files as well.
             *
             * The array of $unmanaged files was obtained from a list of files
             * in the files dir. We want to normalize all file usage using
             * Known's method for filehandling. So we dummy up the unmanged files
             * to present a consistent data structure.
             */
            function getFiles() {
              $managed_files = $this->getDrupalObjects(FILE_SQL);

              // Commented files are in file_managed table.
              $unmanaged_files = array(
                '1000.jpg',
                '100x100_transparent.png',
                '16x16_transparent.png',
                '500px.png',
                'add_feed.jpg',
                'add_to_channel.jpg',
                'allwords.gif',
                'atrium.jpg',
                'authoritah.jpg',
                'badges.png',
                'baneberry.jpg',
                'bird_stealing_meat.jpg',
                'blog.png',
                'blogsite463.tar.gz',
                'book_dead.jpg',
                'book_perms.png',
                'broken.jpg',
                'bubble.jpg',
                'by-sa.png',
                'camera.jpg',
                'cas_alert5-640x475.jpg',
                'case8.ppt',
                'CASE_NAIS.ppt',
                'cats.jpg',
                'cc_license.png',
                'cc_nc_by_sa.png',
                'characteristic.png',
                'cheater_pen.jpg',
                'classic_phone_zazou.png',
                'classroom20.png',
                'class.tar.gz',
                'climb.jpg',
                'code_review.jpg',
                'comcast_cares.png',
                'comcast_no.png',
                'confused.jpg',
                'contact.png',
                'container.gif',
                'context.jpg',
                //'contextog.png',
                'contradiction.jpg',
                'create_channel.jpg',
                'crow.jpg',
                'denied.jpg',
                'devils_tower.jpg',
                'did_you_know.jpg',
                'discipline_xml.png',
                'display_options.jpg',
                'Dr_El_Mo.doc',
                'Dr_El_Mo.gif',
                'drupaled-5.4-0.tar.gz',
                'drupal_in_ed.png',
                'edsurge_edited.png',
                'edsurge_original.png',
                'empty-desks.jpg',
                'Energy_Tour.png',
                //'epub.png',
                'facebook_sad.jpg',
                'facepalm.jpg',
                'facets.jpg',
                'FAQ_on_State_Testing_Participation.pdf',
                //'featureset.png',
                //'files/17_july.png',
                //'files/23_aug.png',
                //'files/320px-MannGlassEye1999crop.jpg',
                //'files/35_million.png',
                //'files/all_rights_cc_2.png',
                //'files/apartheid_pre_deletes.png',
                //'files/aspire_google.png',
                //'files/aspire.png',
                //'files/bps_google.png',
                //'files/chi_intl_google.png',
                //'files/concept_google.png',
                //'files/cps_google.png',
                //'files/dallas_google.png',
                //'files/democ_google.png',
                //'files/digedu_privacy.pdf',
                //'files/digedu_tos.pdf',
                //'files/document_checklist.pdf',
                //'files/edmodo_transfer.png',
                //'files/ELSI_excel_export_6354568118362318308717.xls',
                //'files/engage_ny.png',
                //'files/ESBOCES_CaseStudy.pdf',
                //'files/ferguson_donate.png',
                //'files/ferpa_pps.png',
                //'files/first_version.zip',
                //'files/full_comments.zip',
                //'files/G2-M1.pdf',
                //'files/greendot_google.png',
                //'files/greendot.png',
                //'files/icansoar_google.png',
                //'files/icansoar.png',
                //'files/i_heart_rigor.png',
                //'files/inbloom_partners.pdf',
                //'files/inbloom_white.pdf',
                //'files/Kansas_CaseStudy.pdf',
                //'files/kipp_google.png',
                //'files/kipp.png',
                //'files/kipp_sat.png',
                //'files/la_course_choice.png',
                //'files/lausd_google.png',
                //'files/letter_no_contact.jpg',
                //'files/mevoked_convo_twitter.png',
                //'files/mevoked_privacy_10_30_2014.png',
                //'files/namecalling.jpg',
                //'files/noble_google.png',
                //'files/noble.png',
                //'files/no_results.png',
                //'files/nypd_tshirts.jpg',
                //'files/paper_rater_discussion.png',
                //'files/pearson_socialmedia.png',
                //'files/Pennsylvania_CaseStudy.pdf',
                //'files/portland_google.png',
                //'files/redirect_loop.png',
                //'files/remind_real_safe.png',
                //'files/remind_sm1_clean_up.png',
                //'files/remind_truste_cert.png',
                //'files/rewrite_1.png',
                //'files/rocket_google.png',
                //'files/rsdla_google.png',
                //'files/share_my_lesson_pp.png',
                //'files/succad_google.png',
                //'files/succad.png',
                //'files/TeachingAbouttheJordanDavisMurderTrial.odt',
                //'files/TeachingAbouttheJordanDavisMurderTrial.pdf',
                //'files/the_standards.png',
                //'files/uno_0.png',
                //'files/uno_google_0.png',
                //'files/uno_google.png',
                //'files/uno.png',
                //'files/urban_google.png',
                //'files/urban.png',
                //'files/wiley.png',
                //'files/yesprep_google.png',
                //'files/yesprep.png',
                'flip.jpg',
                'fm_logo_2.png',
                'fm_logo.png',
                'focused_coherent.jpg',
                'forumperms.gif',
                'frame.jpg',
                'freerange.jpg',
                'friendly.gif',
                'funkymonkey_favicon_0.png',
                'funkymonkey_favicon_1.png',
                'funkymonkey_favicon.png',
                'funkymonkey_logo.png',
                'GeoServer_300.png',
                'glossary.jpg',
                'gootube.png',
                'grilling.png',
                'group.png',
                'group_thumb.png',
                'hack_schools_2.png',
                'hall.jpg',
                'hat_only.png',
                'headscratcher.jpg',
                'homepage.jpg',
                'hsa_pics.zip',
                'identity_verification.png',
                'i_love_books.jpg',
                'imagecache_sample.png',
                'ink_water.jpg',
                'in_our_time.png',
                'input_screen.gif',
                'ipad_head.png',
                'isenet.png',
                'Julio_Logo_Web.png',
                //'julio.png',
                'k12open.png',
                'kdi_app.png',
                'kdi_app_small.png',
                'khan_exercises.png',
                'khan_focus.png',
                'khan_reinforcement.png',
                'kindergarten_common_core.png',
                'lazy_cow.png',
                'learner.jpg',
                'learn_from_kids.jpg',
                'leaves_berry.jpg',
                'lemonade.jpg',
                'lessons_400.png',
                'lessons_full.png',
                'light.jpg',
                'list_titles.png',
                'lock.jpg',
                'lock_open.jpg',
                //'logo_0.png',
                'logo.gif',
                //'logo.png',
                'louisck_200.png',
                'mind_the_gap.jpg',
                'minimal_full.jpg',
                'mission_0.png',
                'mission.png',
                'mn_intro_3.jpg',
                'modules-on-do_0.png',
                'modules-on-do.png',
                'modules.swf',
                'money.jpg',
                'monkey_only.png',
                'monkey_test.jpg',
                'moo.jpg',
                'necc.gif',
                'netp2010-model-of-learning.png',
                'newmonkey_bw_550px.png',
                'newmonkey_favicon.png',
                'newmonkey_logo.png',
                'newmonkey_orange.png',
                'newseum_star_ledger.jpg',
                'ning.png',
                'no_new_button.gif',
                'no_ssn.png',
                'notes.gif',
                'not_tested.png',
                'nwp_logo.png',
                'nyscate_cc_nc_sa.jpg',
                'oer.jpg',
                'olpc.gif',
                'open-content.png',
                'OpenContentRoadmap.png',
                'openlearning.png',
                'openlearning_thumb.png',
                'orange_man.png',
                'oregonian_front_page.jpg',
                'os_bb1.jpg',
                'os_bb2.jpg',
                'our-team.png',
                'pare.jpg',
                'peas.png',
                'pencils.jpg',
                'peri.gif',
                'philly_pres.zip',
                'pictures/picture-1.gif',
                'pictures/picture-1.jpg',
                'pictures/picture-2.jpg',
                'pictures/picture-358.jpg',
                'pictures/picture-358.png',
                'pictures/picture-361.jpg',
                'pictures/picture-364.png',
                'pictures/picture-365.jpg',
                'pictures/picture-366.jpg',
                'pictures/picture-367.png',
                'portfolio.avi',
                'portfolio.png',
                'pout_face.jpg',
                'prism.jpg',
                'profile.png',
                'profile_thumb2.png',
                'protein.jpg',
                'purchase_not_allowed.png',
                'question_mark.jpg',
                'red_herring.jpg',
                'restraint.png',
                'rip_privacy.jpg',
                'rocketship_300.png',
                'rotten_apple.jpg',
                'rotten_to_the_core.jpg',
                //'sally.png',
                'save_search.jpg',
                'screenshot_008.png',
                'services_0.png',
                'services.png',
                'sharing_content.jpg',
                'sponsor.png',
                'stanley_cup.jpg',
                'steal_book.jpg',
                'steam.jpg',
                'steenkin_badges.jpg',
                'step1.png',
                'stinkin_badges.jpg',
                'studentid.png',
                'style_tile.png',
                'surveillance.jpg',
                'tag1 draft2.png',
                'tangled_wires.jpg',
                'teacher.jpg',
                'terrain.png',
                'top_level_nav.jpg',
                'tubes.jpg',
                'Tuxcrystal.png',
                'ui_features.jpg',
                'upload/categories.gif',
                'upload/portfolio.gif',
                'upload/role_perms.gif',
                'upload/roles.gif',
                'upload/tax_structure.gif',
                'upload/workflow.gif',
                'useful_places_groups.png',
                'useful_places.png',
                'userlink.zip',
                'userplus463.tar.gz',
                //'users/andrea_0.png',
                //'users/bill.png',
                //'users/jeff.png',
                'variables.jpg',
                'virtualbox-renamed-ova-error.png',
                'voicebox_1_sm.png',
                'voicebox_features_full.jpg',
                'voicebox_features_no_ui.jpg',
                'voicebox_logo_0.png',
                'voicebox_logo.png',
                'voted.jpg',
                'wall.jpg',
                'wes-moore-book.jpg',
                'wfaa_taylor_santos.png',
                'wh_home_page.jpg',
                'wh_rdfa.jpg',
                'wh_solr.jpg',
                'window.jpg',
                'wish-list_0.jpg',
                'work.png',
                'wtc_memorial_400.png',
                'xtracker.gif',
                'yard.jpg',
                'yelp_ratings.jpg',
                'youll_get_nothing.jpg',
                'youre_out.jpg',
              );
              foreach ($unmanaged_files as $file) {
                $filepath = LEGACY_FILES_DIR . '/' . $file;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $entry = new \stdClass;
                $entry->fid = '-1';
                $entry->filename = basename($file);
                $entry->uri = $file;
                $entry->filemime = finfo_file($finfo, $filepath);
                $entry->timestampe = filemtime($filepath);
                finfo_close($finfo);
                $managed_files[] = $entry;
              }
              return $managed_files;
            }

            /**
             * Given a node return a list of drupal file ids that it references.
             */
            function getNodeFids($node) {
              $tables = explode(',', FILE_TABLES);
              $files = array();
              if (!empty($tables)) {
                $drupaldbh = $this->getDbh();
                foreach ($tables as $table) {
                  $parts = explode('_', $table);
                  $type = end($parts);
                  $field_prefix = str_replace('field_data_', '', $table);
                  $sql = 'SELECT f.fid, REPLACE(f.uri, "public://", "") as uri, f.filename FROM ' . $table . ' s LEFT JOIN file_managed f ON (f.fid = s.' . $field_prefix . '_fid)
                    WHERE (s.entity_type = "node" AND s.entity_id = :entity_id AND s.revision_id = :revision_id)';

                  $stmt = $drupaldbh->prepare($sql);
                  $result = $stmt->execute(array(':entity_id' => $node->nid, ':revision_id' => $node->vid));
                  if ($result) {
                    while ( $file = $stmt->fetchObject()) {
                      $files[$type][$file->fid] = $file;
                    }
                  }
                  else {
                    $code = $stmt->errorCode();
                    $info = $stmt->errorInfo();
                    \Idno\Core\site()->session()->addMessage('Sql error(' . $code . '): ' . var_export($info[2], 1), 'alert-danger');
                  }
                }
              }
              return $files;
            }

            /**
             * Add a file to known
             */
            function addFile($file) {
              $src = LEGACY_FILES_DIR . '/' . $file->uri;
              $dir = \Idno\Core\site()->config()->getTempDir();
              $name = md5($src);
              $newname = $dir . $name . basename($src);
              if (@file_put_contents($newname , fopen($src, 'r'))) {
                if ($newfile = \Idno\Entities\File::createFromFile($newname, basename($src), $file->filemime, true)) {
                  $newsrc = \Idno\Core\site()->config()->getURL() . 'file/' . $newfile->file['_id'];
                  @unlink($newname);
                  return $newfile->file['_id'];
                }
              }
              return FALSE;
            }

            /**
             * Add a node to known
             */
            function addNode($node) {
              $usermap = \Idno\Core\site()->config()->drupal_migration_user_map;
              if (empty($usermap)) {
                $usermap = array();
              }
              $filemap = \Idno\Core\site()->config()->drupal_migration_file_map;
              $files = $this->getNodeFids($node);
              $iconlink = '';
              $links = array();
              if (!empty($files)) {
                foreach($files as $type => $finfo) {
                  foreach ($finfo as $fid => $fileinfo) {
                    $fileid = $filemap['sites/default/files/' . $fileinfo->uri];
                    if ($file = \Idno\Entities\File::getByID($fileid)) {
                        if ($type == 'image' && empty($iconlink)) {
                          // take the first image as the icon.
                          $iconlink = '<img class="img-responsive" src="/file/' . $fileid . '">';
                        }
                        $links[$fid] = '<a href="/file/' . $fileid . '">' . htmlspecialchars($fileinfo->filename). '</a>';
                    }
                    else {
                        // @TODO audit these messages if they show up.
                        \Idno\Core\site()->session()->addMessage('Unable to load file with ID: ' . $fileid, 'alert-danger');
                    }
                  }
                }
              }

              $node->body = $this->nodeBody($node);

              $node->body = $iconlink . $node->body;

              if (!empty($links)) {
                $node->body .= '<ul class="file-list"><li>' . implode('<li><li>', $links) . '</li></ul>';
              }

              $hashtags = array();
              $terms = explode(',', $node->terms);

              if (!empty($terms)) {
                foreach ($terms as $term) {
                    // remove whitespace
                    $trimmed = str_replace(' ', '', trim($term));
                    if (!empty($trimmed)) {
                        $hashtags[] = $trimmed;
                    }
                }
              }

              if (!empty($hashtags)) {
                $node->body = $node->body . '<p class="tags">#' . implode(', #', $hashtags) . '</p>';
              }

              // Add the node
              $object = new \IdnoPlugins\Text\Entry();
              $object->title = html_entity_decode($this->fixEncoding($node->title), ENT_COMPAT, 'UTF-8');
              $object->created = $node->created;
              $object->body = $this->rewriteContentLinks($this->fixEncoding($node->body));

              if (!$object->body) {
                return FALSE;
              }
              $owner = array_search($node->uid, $usermap);
              if ($owner) {
                $user = \Idno\Entities\User::getByID($owner);
                $object->setOwner($user);
              }
              return $object->save(true);
            }

            /**
             * Drupal automatically rewrites double newlines as <p> tags.
             * we replicated that behavior to keep content consistent.
             */
            function nodeBody($node) {
                $body = str_replace("\r", "", $node->body);
                $paragraphs = explode("\n\n", $body);
                $body = implode("</p><p>", $paragraphs);
                $body = '<p>' . $body . '</p>';
                return $body;
            }

            /**
             * Add a user to known.
             */
            function addUser($user) {
              $newuser         = new \Idno\Entities\User();
              $newuser->email  = $user->mail;
              $newuser->handle = strtolower(trim($user->name)); // Trim the handle and set it to lowercase
              $newuser->setPassword(openssl_random_pseudo_bytes(rand(68, 127)));
              $newuser->notifications['email'] = 'none';
              $newuser->profile['description'] = $user->bio;
              $newuser->setTitle($user->firstname . ' ' . $user->lastname);
              $newuser->created = $user->created;
              if (!empty($user->twitter)) {
                $newuser->profile['url'][] = $user->twitter;
              }
              if (!empty($user->github)) {
                $newuser->profile['url'][] = $user->github;
              }
              if (!empty($user->drupal)) {
                $newuser->profile['url'][] = $user->drupal;
              }

              if ($newuser->email == 'bill@funnymonkey.com' || $newuser->email == 'jeff@funnymonkey.com') {
                $newuser->setAdmin(true);
              }
              $newuser->robot_state = 1; // State for our happy robot helper
              \Idno\Core\site()->triggerEvent('site/newuser', array('user' => $newuser)); // Event hook for new user

              $filemap = \Idno\Core\site()->config()->drupal_migration_file_map;
              if (!empty($user->uri)) {
                $newuser->icon = $filemap['sites/default/files/' . $user->uri];
              }
              return $newuser->save();
            }

            /**
             * Add a comment to known.
             */
            function addComment($comment) {
                $nodemap = \Idno\Core\site()->config()->drupal_migration_node_map;
                $usermap = \Idno\Core\site()->config()->drupal_migration_user_map;

                if ($objectID = array_search($comment->nid, $nodemap)) {
                    if ($post = \Idno\Common\Entity::getByID($objectID)) {
                        $userID = array_search($comment->uid, $usermap);
                        $user = \Idno\Entities\User::getByID($userID);

                        if (!empty($comment->uid)) {
                            $name = strtolower(trim($comment->name));
                            $icon = $user->getIcon();
                            $url = $user->getURL();
                        }
                        else {
                            $name = $comment->name;
                            if (!empty($comment->homepage)) {
                                if ($url = \Idno\Core\Webservice::sanitizeURL($comment->homepage)) {
                                    if ($content = \Idno\Core\Webservice::get($url)) {
                                        if ($content['response'] == '200') {
                                            if (!($icon = \Idno\Core\Webmention::getIconFromWebsiteContent($content['content'], $url))) {
                                                $bn = hexdec(substr(md5($url), 0, 15));
                                                $number = 1 + ($bn % 5);
                                                $icon = \Idno\Core\site()->config()->url . 'gfx/users/default-'. str_pad($number, 2, '0', STR_PAD_LEFT) .'.png';
                                            }
                                        }
                                    }
                                }
                            }

                            if (empty($url)) {
                                $url = '';
                            }
                            if (empty($icon)) {
                                $bn = hexdec(substr(md5($url), 0, 15));
                                $number = 1 + ($bn % 5);
                                $icon = \Idno\Core\site()->config()->url . 'gfx/users/default-'. str_pad($number, 2, '0', STR_PAD_LEFT) .'.png';
                            }
                        }


                        $annotation_url = $post->getURL() . '/annotations/' . md5(time() . $comment->body);
                        $body = strip_tags($this->fixEncoding($comment->body));
                        $name = strip_tags($this->fixEncoding($name));
                        if ($post->addAnnotation('reply', $name, $url, $icon, $body, null, $comment->created)) {
                            return $annotation_url;
                        }
                    }
                    else {
                        return FALSE;
                    }
                }
                return FALSE;
            }

            /**
             * Rewrite content URLs from the old site.
             */
            function rewriteURL($url) {
              $usermap = \Idno\Core\site()->config()->drupal_migration_user_map;
              $filemap = \Idno\Core\site()->config()->drupal_migration_file_map;

              static $rewrites = array();

              if (empty($rewrites)) {
                foreach ($filemap as $path => $newid) {
                    if ($file = \Idno\Entities\File::getByID($newid)) {
                        $newpath = \Idno\Core\site()->config()->url . 'file/' . $file->_id . '/' . urlencode($file->getFilename());
                        $rewrites[$path] = $newpath;
                    }
                    else {
                        \Idno\Core\site()->session()->addMessage('Could not load file with ID: ' . $newid, 'alert-danger');
                    }
                }
              }

              // Fix some broken link styles before we do the other rewrites.
              $bad = array(
                'http:/funnymonkey.com' => 'http://funnymonkey.com',
                'http:/www.funnymonkey.com' => 'http://funnymonkey.com',
                'http://www.funnymonkey.com' => 'http://funnymonkey.com',
                'http://funnymonkey.com/files/' => 'http://funnymonkey.com/sites/default/files/',
              );

              foreach ($bad as $source => $new) {
                if (strpos($url, $source) !== FALSE) {
                    $url = str_replace($source, $new, $url);
                }
              }

              foreach ($rewrites as $path => $newpath) {
                $url = str_replace($path, $newpath, $url);
              }

              // Remove domain(s)
              $url = str_replace(\Idno\Core\site()->config()->url, '/', $url);
              $url = str_replace('http://funnymonkey.com/', '/', $url);
              $url = str_replace('http://www.funnymonkey.com/', '/', $url);


              // Remove leading double slashes
              if (strpos($url, '//') === 0) {
                $url = preg_replace('/^\/\//', '/', $url);
              }

              return $url;
            }

            /**
             * Rip open the content and rewrite old links with the new format
             */
            function rewriteContentLinks($markup) {
              $dom = new \DOMDocument;
              @$dom->loadHTML($markup);

              $xpath = new \DOMXPath($dom);
              $xpaths = array(
                array('query' => '//a[@href]', 'attr' => 'href'),
                array('query' => '//img[@src]', 'attr' => 'src'),
              );

              foreach ($xpaths as $path) {
                foreach ($xpath->query($path['query']) as $node) {
                  $url = $node->getAttribute($path['attr']);
                  $node->setAttribute($path['attr'], $this->rewriteURL($url));
                }
              }

              return $dom->saveHTML();
            }

            /**
             * Some content columns in Drupal were not encoded UTF8.
             * #DrupalWTF
             */
            function fixEncoding($string) {
                $encoding = mb_detect_encoding($string, "UTF-8, ASCII, ISO-8859-1", true);
                if (!empty($encoding)) {
                    return mb_convert_encoding($string, "UTF-8", $encoding);
                }
                return $string;
            }

        }


    }
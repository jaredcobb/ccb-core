Customizations have been made to the core files in ReduxFramework.
Note these customizations will be lost when updating the submodule and need to be done again.

ReduxFramework/ReduxCore/framework.php

Removed lines 328-332
-                    // Tracking
-                    if ( true != Redux_Helpers::isTheme( __FILE__ ) || ( true == Redux_Helpers::isTheme( __FILE__ ) && ! $this->args['disable_tracking'] ) ) {
-                        $this->_tracking();
-                    }

Removed lines 405-421
-                    if ( $this->args['dev_mode'] == true || Redux_Helpers::isLocalHost() == true ) {
-                        include_once 'core/dashboard.php';
-
-                        if ( ! isset ( $GLOBALS['redux_notice_check'] ) ) {
-                            include_once 'core/newsflash.php';
-
-                            $params = array(
-                                'dir_name'    => 'notice',
-                                'server_file' => 'http://www.reduxframework.com/' . 'wp-content/uploads/redux/redux_notice.json',
-                                'interval'    => 3,
-                                'cookie_id'   => 'redux_blast',
-                            );
-
-                            new reduxNewsflash( $this, $params );
-                            $GLOBALS['redux_notice_check'] = 1;
-                        }
-                    }

This disables both the tracking and spam

I also deleted the /tests and /samples folders and tracking.php

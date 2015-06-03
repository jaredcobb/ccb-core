Customizations have been made to the core files in ReduxFramework.

The specific changes are listed below, but in general, these changes completely remove
the tracking feature of Redux, the notifications features (calling home), and the 
global notice checks.

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

Removed lines 612-618
-            private function _tracking() {
-                require_once( dirname( __FILE__ ) . '/inc/tracking.php' );
-                $tracking = Redux_Tracking::get_instance();
-                $tracking->load( $this );
-            }
-// _tracking()
-

DELETED ReduxFramework/ReduxCore/inc/tracking.php
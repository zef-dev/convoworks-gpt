<?php
/**
 * PHPStan stubs for Convoworks WP classes
 * These are minimal stubs to satisfy static analysis
 */

namespace Convo\Wp {

    /**
     * WordPress admin user implementation
     * @see https://github.com/zef-dev/convoworks-wp
     */
    class AdminUser implements \Convo\Core\IAdminUser
    {
        /**
         * @param \WP_User $user WordPress user object
         */
        public function __construct(\WP_User $user) {}

        /**
         * @return bool
         */
        public function isSystem() {}

        /**
         * @return int
         */
        public function getId() {}

        /**
         * @return string
         */
        public function getUsername() {}

        /**
         * @return string
         */
        public function getEmail() {}

        /**
         * @return string
         */
        public function getName() {}

        /**
         * @return string
         */
        public function getPassword() {}

        /**
         * @return \WP_User
         */
        public function getWpUser() {}

        /**
         * @return array{id: int, username: string, email: string, name: string, wpUser: \WP_User}
         */
        public function toArray() {}

        /**
         * @return string
         */
        public function __toString() {}
    }
}


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
         * @param int $userId WordPress user ID
         */
        public function __construct($userId) {}

        /**
         * @return string
         */
        public function getId() {}

        /**
         * @return string
         */
        public function getName() {}

        /**
         * @return string
         */
        public function getEmail() {}

        /**
         * @return string
         */
        public function getUsername() {}

        /**
         * @return bool
         */
        public function isAdmin() {}

        /**
         * @return bool
         */
        public function isSystem() {}
    }
}


<?php
declare(strict_types=1);

use Zencart\PluginSupport\ScriptedInstaller as ScriptedInstallBase;

class ScriptedInstaller extends ScriptedInstallBase
{
    protected string $configGroupTitle = 'Social Login';

    protected function executeInstall()
    {
        $this->addAuthColumns();
        $this->insertConfigurationKeys();
        $this->registerAdminPages();
        return true;
    }

    protected function executeUninstall()
    {
        if (function_exists('zen_deregister_admin_pages')) {
            zen_deregister_admin_pages(['configSocialLogin']);
        }

        $gid = $this->getConfigGroupId(false);
        $this->dbConn->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'SOCIAL\_LOGIN\_%'");

        if ($gid !== null) {
            $this->dbConn->Execute("DELETE FROM " . TABLE_CONFIGURATION_GROUP . " WHERE configuration_group_id = " . (int)$gid);
        }

        // Customer data column (google_auth_id) is intentionally left intact to prevent orphaning data
        return true;
    }

    private function addAuthColumns(): void
    {
        $columns = ['google_auth_id', 'facebook_auth_id', 'apple_auth_id'];

        foreach ($columns as $col) {
            $sql = "SHOW COLUMNS FROM " . TABLE_CUSTOMERS . " LIKE '{$col}'";
            $result = $this->dbConn->Execute($sql);

            if ($result->EOF) {
                $this->dbConn->Execute("ALTER TABLE " . TABLE_CUSTOMERS . " ADD COLUMN {$col} VARCHAR(255) DEFAULT NULL");
                $this->dbConn->Execute("ALTER TABLE " . TABLE_CUSTOMERS . " ADD INDEX idx_{$col} ({$col})");
            }
        }
    }

    protected function getConfigGroupId(bool $createIfMissing = true): ?int
    {
        $check = $this->dbConn->Execute(
            "SELECT configuration_group_id FROM " . TABLE_CONFIGURATION_GROUP . "
             WHERE configuration_group_title = '" . zen_db_input($this->configGroupTitle) . "' LIMIT 1"
        );

        if (!$check->EOF) return (int)$check->fields['configuration_group_id'];
        if (!$createIfMissing) return null;

        $this->dbConn->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION_GROUP . "
             (configuration_group_title, configuration_group_description, sort_order, visible)
             VALUES ('" . zen_db_input($this->configGroupTitle) . "', 'Social Login Settings', 1, 1)"
        );

        $gid = (int)$this->dbConn->Insert_ID();
        $this->dbConn->Execute("UPDATE " . TABLE_CONFIGURATION_GROUP . " SET sort_order = configuration_group_id WHERE configuration_group_id = " . $gid);

        return $gid;
    }

    protected function insertConfigurationKeys(): void
    {
        $gid = $this->getConfigGroupId(true);

        $keys = [
            // Core
            ['SOCIAL_LOGIN_SKIP_FREE_CHECKOUT', 'Skip Checkout for Free Orders', 'true', 'Automatically skip shipping/payment pages for zero-balance orders?', 10, "zen_cfg_select_option(array('true','false'),"],

            // Google
            ['SOCIAL_LOGIN_GOOGLE_STATUS', 'Enable Google Login', 'true', 'Enable Google Authentication?', 20, "zen_cfg_select_option(array('true','false'),"],
            ['SOCIAL_LOGIN_GOOGLE_CLIENT_ID', 'Google Client ID', '', 'OAuth Client ID', 21, null],
            ['SOCIAL_LOGIN_GOOGLE_CLIENT_SECRET', 'Google Client Secret', '', 'OAuth Client Secret', 22, null],

            // Facebook
            ['SOCIAL_LOGIN_FACEBOOK_STATUS', 'Enable Facebook Login', 'false', 'Enable Facebook Authentication?', 30, "zen_cfg_select_option(array('true','false'),"],
            ['SOCIAL_LOGIN_FACEBOOK_CLIENT_ID', 'Facebook App ID', '', 'Facebook App ID', 31, null],
            ['SOCIAL_LOGIN_FACEBOOK_CLIENT_SECRET', 'Facebook App Secret', '', 'Facebook App Secret', 32, null],

            // Apple (Requires a physical .p8 key file hosted securely on the server)
            ['SOCIAL_LOGIN_APPLE_STATUS', 'Enable Apple Login', 'false', 'Enable Apple Authentication?', 40, "zen_cfg_select_option(array('true','false'),"],
            ['SOCIAL_LOGIN_APPLE_CLIENT_ID', 'Apple Service ID', '', 'The Service ID (Client ID) from Apple Developer', 41, null],
            ['SOCIAL_LOGIN_APPLE_TEAM_ID', 'Apple Team ID', '', 'Your 10-character Apple Team ID', 42, null],
            ['SOCIAL_LOGIN_APPLE_KEY_ID', 'Apple Key ID', '', 'The Key ID for your .p8 file', 43, null],
            ['SOCIAL_LOGIN_APPLE_KEY_FILE', 'Apple Key File Path', '', 'Absolute server path to your AuthKey.p8 file (must be outside public_html)', 44, null],
        ];

        foreach ($keys as [$key, $title, $value, $desc, $sort, $setFunc]) {
            $exists = $this->dbConn->Execute("SELECT configuration_id FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = '" . zen_db_input($key) . "' LIMIT 1");
            if (!$exists->EOF) {
                $this->dbConn->Execute("UPDATE " . TABLE_CONFIGURATION . " SET configuration_group_id = " . (int)$gid . " WHERE configuration_key = '" . zen_db_input($key) . "'");
                continue;
            }
            $setFuncSql = ($setFunc === null) ? 'NULL' : "'" . zen_db_input($setFunc) . "'";
            $this->dbConn->Execute(
                "INSERT INTO " . TABLE_CONFIGURATION . "
                 (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
                 VALUES ('" . zen_db_input($title) . "', '" . zen_db_input($key) . "', '" . zen_db_input($value) . "', '" . zen_db_input($desc) . "', " . (int)$gid . ", " . (int)$sort . ", " . $setFuncSql . ", now())"
            );
        }
    }

    protected function registerAdminPages(): void
    {
        $gid = $this->getConfigGroupId(true);
        if (function_exists('zen_page_key_exists') && function_exists('zen_register_admin_page')) {
            if (!zen_page_key_exists('configSocialLogin')) {
                zen_register_admin_page('configSocialLogin', 'BOX_CONFIGURATION_SOCIAL_LOGIN', 'FILENAME_CONFIGURATION', 'gID=' . (int)$gid, 'configuration', 'Y', (int)$gid);
            }
        }
    }
}

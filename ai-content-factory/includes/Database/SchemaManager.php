<?php
namespace AICF\Database;

if (!defined('ABSPATH')) {
    exit;
}

class SchemaManager {

    // Bumped: schema below now matches the columns actually used by
    // CampaignManager / KeywordManager / ArticleRepository / Logger.
    // Previous versions only had a small subset of these columns, which
    // caused every $wpdb->insert()/update() in those classes to silently
    // fail (unknown column), even though the AJAX/UI wiring looked fine.
    const DB_VERSION = '1.2.0';

    public static function init() {
        $installed_ver = get_option('aicf_db_version');
        if ($installed_ver !== self::DB_VERSION) {
            self::create_tables();
            update_option('aicf_db_version', self::DB_VERSION);
        }
    }

    // Phương thức Alias phòng trường hợp Activator hoặc code cũ gọi
    public static function check_update() {
        self::init();
    }

    public static function activate() {
        self::init();
    }

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $table_campaigns = $wpdb->prefix . 'aicf_campaigns';
        $sql_campaigns = "CREATE TABLE {$table_campaigns} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text DEFAULT NULL,
            website varchar(255) DEFAULT '',
            language varchar(20) DEFAULT 'vi',
            country varchar(10) DEFAULT 'VN',
            target_location varchar(255) DEFAULT '',
            target_audience varchar(255) DEFAULT '',
            tone_of_voice varchar(100) DEFAULT 'professional',
            category_id bigint(20) unsigned DEFAULT 0,
            author_id bigint(20) unsigned DEFAULT 0,
            publishing_mode varchar(20) DEFAULT 'draft',
            ai_provider varchar(50) DEFAULT 'openai',
            ai_model varchar(100) DEFAULT 'gpt-4o-mini',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        $table_keywords = $wpdb->prefix . 'aicf_keywords';
        $sql_keywords = "CREATE TABLE {$table_keywords} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            keyword varchar(255) NOT NULL,
            intent varchar(50) DEFAULT 'informational',
            cluster varchar(255) DEFAULT '',
            priority tinyint(2) DEFAULT 3,
            target_url varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id)
        ) {$charset_collate};";

        $table_articles = $wpdb->prefix . 'aicf_articles';
        $sql_articles = "CREATE TABLE {$table_articles} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            keyword_id bigint(20) unsigned NOT NULL,
            wp_post_id bigint(20) unsigned DEFAULT 0,
            title varchar(255) DEFAULT '',
            content longtext DEFAULT NULL,
            excerpt text DEFAULT NULL,
            meta_title varchar(255) DEFAULT '',
            meta_description text DEFAULT NULL,
            brief longtext DEFAULT NULL,
            outline longtext DEFAULT NULL,
            seo_score int(3) DEFAULT 0,
            generation_state varchar(50) DEFAULT 'queued',
            error_message text DEFAULT NULL,
            status varchar(20) DEFAULT 'draft',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY campaign_id (campaign_id),
            KEY keyword_id (keyword_id)
        ) {$charset_collate};";

        $table_logs = $wpdb->prefix . 'aicf_logs';
        $sql_logs = "CREATE TABLE {$table_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            level varchar(20) DEFAULT 'info',
            object_type varchar(50) DEFAULT 'system',
            object_id bigint(20) unsigned DEFAULT 0,
            provider varchar(50) DEFAULT NULL,
            model varchar(100) DEFAULT NULL,
            request_type varchar(50) DEFAULT 'general',
            status varchar(20) DEFAULT 'info',
            duration float DEFAULT 0,
            input_tokens int(11) DEFAULT 0,
            output_tokens int(11) DEFAULT 0,
            total_tokens int(11) DEFAULT 0,
            estimated_cost decimal(10,6) DEFAULT 0,
            message text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        dbDelta($sql_campaigns);
        dbDelta($sql_keywords);
        dbDelta($sql_articles);
        dbDelta($sql_logs);
    }
}

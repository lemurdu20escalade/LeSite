<?php
/**
 * Documents Custom Post Type
 *
 * Manages private documents for members: PVs, reports, statutes, etc.
 * Documents are only accessible via REST API with permission check.
 *
 * @package Lemur\CustomPostTypes
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes;

use Carbon_Fields\Container;
use Carbon_Fields\Field;
use Lemur\CustomPostTypes\Traits\HasDefaultTerms;
use Lemur\MemberArea\Access\Capabilities;

/**
 * Documents CPT for member area
 */
class Documents
{
    use HasDefaultTerms;

    /**
     * Post type slug
     */
    public const POST_TYPE = 'lemur_documents';

    /**
     * Taxonomy slug
     */
    public const TAXONOMY = 'type-document';

    /**
     * Field keys
     */
    public const FIELD_FILE = 'document_file';
    public const FIELD_DESCRIPTION = 'document_description';
    public const FIELD_VISIBILITY = 'document_visibility';
    public const FIELD_YEAR = 'document_year';
    public const FIELD_DOWNLOAD_COUNT = 'document_downloads';

    /**
     * Visibility constants
     */
    public const VISIBILITY_MEMBERS = 'members';
    public const VISIBILITY_BUREAU = 'bureau';

    /**
     * Download token lifetime in seconds (1 hour)
     */
    private const DOWNLOAD_TOKEN_LIFETIME = 3600;

    /**
     * Allowed MIME types for uploads
     */
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
    ];

    /**
     * Initialize the Documents CPT
     */
    public static function init(): void
    {
        add_action('init', [self::class, 'register'], 10);
        add_action('init', [self::class, 'registerTaxonomy'], 10);
        add_action('carbon_fields_register_fields', [self::class, 'registerFields'], 10);
        add_action('rest_api_init', [self::class, 'registerRestRoutes'], 10);

        // Admin columns
        add_filter('manage_' . self::POST_TYPE . '_posts_columns', [self::class, 'addAdminColumns'], 10);
        add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [self::class, 'renderAdminColumns'], 10, 2);

        // Restrict access to edit documents
        add_filter('user_has_cap', [self::class, 'filterCapabilities'], 10, 4);
    }

    /**
     * Register the post type
     */
    public static function register(): void
    {
        $labels = [
            'name'                  => __('Documents', 'lemur'),
            'singular_name'         => __('Document', 'lemur'),
            'menu_name'             => __('Documents membres', 'lemur'),
            'add_new'               => __('Ajouter', 'lemur'),
            'add_new_item'          => __('Ajouter un document', 'lemur'),
            'edit_item'             => __('Modifier le document', 'lemur'),
            'new_item'              => __('Nouveau document', 'lemur'),
            'view_item'             => __('Voir le document', 'lemur'),
            'search_items'          => __('Rechercher un document', 'lemur'),
            'not_found'             => __('Aucun document trouvé', 'lemur'),
            'not_found_in_trash'    => __('Aucun document dans la corbeille', 'lemur'),
            'all_items'             => __('Tous les documents', 'lemur'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => false, // Custom REST endpoint only
            'query_var'           => false,
            'rewrite'             => false,
            'capability_type'     => 'lemur_document',
            'map_meta_cap'        => true,
            'has_archive'         => false,
            'hierarchical'        => false,
            'menu_position'       => 25,
            'menu_icon'           => 'dashicons-media-document',
            'supports'            => ['title'],
        ];

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Register the document type taxonomy
     */
    public static function registerTaxonomy(): void
    {
        $labels = [
            'name'          => __('Types de document', 'lemur'),
            'singular_name' => __('Type de document', 'lemur'),
            'search_items'  => __('Rechercher un type', 'lemur'),
            'all_items'     => __('Tous les types', 'lemur'),
            'edit_item'     => __('Modifier le type', 'lemur'),
            'update_item'   => __('Mettre à jour', 'lemur'),
            'add_new_item'  => __('Ajouter un type', 'lemur'),
            'new_item_name' => __('Nom du type', 'lemur'),
            'menu_name'     => __('Types', 'lemur'),
        ];

        $args = [
            'labels'            => $labels,
            'hierarchical'      => true,
            'public'            => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => false,
            'rewrite'           => false,
        ];

        register_taxonomy(self::TAXONOMY, [self::POST_TYPE], $args);

        // Add default terms
        self::insertDefaultTerms();
    }

    /**
     * Insert default taxonomy terms
     */
    private static function insertDefaultTerms(): void
    {
        self::insertTermsOnce(self::TAXONOMY, [
            'PV',
            'Comptes-rendus',
            'Statuts',
            'Documents internes',
            'Formulaires',
            'Guides',
        ]);
    }

    /**
     * Register Carbon Fields for documents
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Détails du document', 'lemur'))
            ->where('post_type', '=', self::POST_TYPE)
            ->add_fields([
                Field::make('file', self::FIELD_FILE, __('Fichier', 'lemur'))
                    ->set_type(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'])
                    ->set_required(true)
                    ->set_help_text(__('Formats acceptés : PDF, Word, Excel, PowerPoint, texte, images', 'lemur')),

                Field::make('textarea', self::FIELD_DESCRIPTION, __('Description', 'lemur'))
                    ->set_rows(3)
                    ->set_help_text(__('Brève description du contenu du document', 'lemur')),

                Field::make('select', self::FIELD_VISIBILITY, __('Visibilité', 'lemur'))
                    ->set_options([
                        self::VISIBILITY_MEMBERS => __('Tous les membres', 'lemur'),
                        self::VISIBILITY_BUREAU  => __('Bureau uniquement', 'lemur'),
                    ])
                    ->set_default_value(self::VISIBILITY_MEMBERS)
                    ->set_width(50),

                Field::make('text', self::FIELD_YEAR, __('Année', 'lemur'))
                    ->set_attribute('type', 'number')
                    ->set_attribute('min', '2000')
                    ->set_attribute('max', (string) ((int) date('Y') + 1))
                    ->set_default_value(date('Y'))
                    ->set_width(25),

                Field::make('text', self::FIELD_DOWNLOAD_COUNT, __('Téléchargements', 'lemur'))
                    ->set_attribute('type', 'number')
                    ->set_attribute('readOnly', true)
                    ->set_default_value('0')
                    ->set_width(25)
                    ->set_help_text(__('Compteur automatique', 'lemur')),
            ]);
    }

    /**
     * Register REST API routes for document downloads
     */
    public static function registerRestRoutes(): void
    {
        register_rest_route('lemur/v1', '/documents', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'restGetDocuments'],
            'permission_callback' => [self::class, 'canViewDocuments'],
        ]);

        // Secure download with token (prevents enumeration and adds time-limit)
        register_rest_route('lemur/v1', '/download/(?P<token>[a-zA-Z0-9_-]+)', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'restDownload'],
            'permission_callback' => [self::class, 'canDownload'],
            'args'                => [
                'token' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($param) {
                        return is_string($param) && strlen($param) >= 32;
                    },
                ],
            ],
        ]);
    }

    /**
     * Check if user can view documents list
     */
    public static function canViewDocuments(): bool
    {
        return Capabilities::canAccessMemberArea();
    }

    /**
     * Check if user can download a specific document
     *
     * @param \WP_REST_Request $request REST request
     */
    public static function canDownload(\WP_REST_Request $request): bool
    {
        if (!Capabilities::canAccessMemberArea()) {
            return false;
        }

        // Resolve token to get document ID and validate
        $token = $request->get_param('token');
        $token_data = self::resolveDownloadToken($token);

        if ($token_data === null) {
            return false; // Invalid or expired token
        }

        $document_id = $token_data['document_id'];
        $visibility = carbon_get_post_meta($document_id, self::FIELD_VISIBILITY);

        // Bureau-only documents require bureau capability
        if ($visibility === self::VISIBILITY_BUREAU) {
            return Capabilities::isBureau();
        }

        return true;
    }

    /**
     * REST handler: Get documents list
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response
     */
    public static function restGetDocuments(\WP_REST_Request $request): \WP_REST_Response
    {
        $args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filter by category if provided
        $category = $request->get_param('category');
        if (!empty($category)) {
            $args['tax_query'] = [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field($category),
                ],
            ];
        }

        // Filter by year if provided
        $year = $request->get_param('year');
        if (!empty($year)) {
            $args['meta_query'] = [
                [
                    'key'   => '_' . self::FIELD_YEAR,
                    'value' => (int) $year,
                ],
            ];
        }

        // Filter visibility based on user role
        if (!Capabilities::isBureau()) {
            $args['meta_query'] = $args['meta_query'] ?? [];
            $args['meta_query'][] = [
                'relation' => 'OR',
                [
                    'key'     => '_' . self::FIELD_VISIBILITY,
                    'value'   => self::VISIBILITY_MEMBERS,
                ],
                [
                    'key'     => '_' . self::FIELD_VISIBILITY,
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        $documents = get_posts($args);

        $data = array_map(function ($doc) {
            return self::formatDocumentForApi($doc);
        }, $documents);

        return new \WP_REST_Response($data, 200);
    }

    /**
     * REST handler: Download document
     *
     * @param \WP_REST_Request $request REST request
     * @return \WP_REST_Response|void
     */
    public static function restDownload(\WP_REST_Request $request)
    {
        // Resolve secure token
        $token = $request->get_param('token');
        $token_data = self::resolveDownloadToken($token);

        if ($token_data === null) {
            return new \WP_REST_Response(['error' => 'Invalid or expired download link'], 403);
        }

        $document_id = $token_data['document_id'];

        // Verify document exists
        $document = get_post($document_id);

        if (!$document || $document->post_type !== self::POST_TYPE) {
            return new \WP_REST_Response(['error' => 'Document not found'], 404);
        }

        // Get file
        $file_id = carbon_get_post_meta($document_id, self::FIELD_FILE);

        if (empty($file_id)) {
            return new \WP_REST_Response(['error' => 'No file attached'], 404);
        }

        $file_path = get_attached_file((int) $file_id);

        if (!$file_path || !file_exists($file_path)) {
            return new \WP_REST_Response(['error' => 'File not found'], 404);
        }

        // Increment download counter
        $count = (int) carbon_get_post_meta($document_id, self::FIELD_DOWNLOAD_COUNT);
        carbon_set_post_meta($document_id, self::FIELD_DOWNLOAD_COUNT, $count + 1);

        // Get file info
        $filename = basename($file_path);
        $filesize = filesize($file_path);
        $mime_type = mime_content_type($file_path) ?: 'application/octet-stream';

        // Validate MIME type
        if (!in_array($mime_type, self::ALLOWED_MIME_TYPES, true)) {
            return new \WP_REST_Response(['error' => 'Invalid file type'], 403);
        }

        // Sanitize filename for Content-Disposition header (prevent header injection)
        $safe_filename = preg_replace('/[\r\n\t"\\\\]/', '', $filename);
        $safe_filename = mb_substr($safe_filename, 0, 200); // Limit length

        // Send file
        header('Content-Type: ' . $mime_type);
        header('Content-Disposition: attachment; filename="' . $safe_filename . '"');
        header('Content-Length: ' . $filesize);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-cache, no-store, must-revalidate');

        readfile($file_path);
        exit;
    }

    /**
     * Format document for API response
     *
     * @param \WP_Post $document Document post
     * @return array<string, mixed>
     */
    private static function formatDocumentForApi(\WP_Post $document): array
    {
        $file_id = carbon_get_post_meta($document->ID, self::FIELD_FILE);
        $file_path = $file_id ? get_attached_file((int) $file_id) : null;

        $terms = wp_get_post_terms($document->ID, self::TAXONOMY);
        $category = !empty($terms) && !is_wp_error($terms) ? $terms[0]->name : null;

        // Generate secure download token (time-limited, user-bound)
        $download_token = self::generateDownloadToken($document->ID);

        return [
            'id'           => self::obfuscateDocumentId($document->ID),
            'title'        => get_the_title($document),
            'description'  => carbon_get_post_meta($document->ID, self::FIELD_DESCRIPTION),
            'category'     => $category,
            'year'         => carbon_get_post_meta($document->ID, self::FIELD_YEAR),
            'visibility'   => carbon_get_post_meta($document->ID, self::FIELD_VISIBILITY) ?: self::VISIBILITY_MEMBERS,
            'downloads'    => (int) carbon_get_post_meta($document->ID, self::FIELD_DOWNLOAD_COUNT),
            'file_size'    => $file_path && file_exists($file_path) ? size_format(filesize($file_path)) : null,
            'file_type'    => $file_path ? strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)) : null,
            'download_url' => rest_url('lemur/v1/download/' . $download_token),
            'date'         => get_the_date('c', $document),
        ];
    }

    /**
     * Add custom admin columns
     *
     * @param array<string, string> $columns Existing columns
     * @return array<string, string>
     */
    public static function addAdminColumns(array $columns): array
    {
        $new_columns = [];

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            if ($key === 'title') {
                $new_columns['doc_type'] = __('Type', 'lemur');
                $new_columns['doc_year'] = __('Année', 'lemur');
                $new_columns['doc_downloads'] = __('Téléchargements', 'lemur');
            }
        }

        return $new_columns;
    }

    /**
     * Render custom admin columns
     *
     * @param string $column  Column key
     * @param int    $post_id Post ID
     */
    public static function renderAdminColumns(string $column, int $post_id): void
    {
        switch ($column) {
            case 'doc_type':
                $file_id = carbon_get_post_meta($post_id, self::FIELD_FILE);
                if ($file_id) {
                    $file_path = get_attached_file((int) $file_id);
                    if ($file_path) {
                        echo esc_html(strtoupper(pathinfo($file_path, PATHINFO_EXTENSION)));
                    }
                }
                break;

            case 'doc_year':
                $year = carbon_get_post_meta($post_id, self::FIELD_YEAR);
                echo esc_html($year ?: '—');
                break;

            case 'doc_downloads':
                $count = carbon_get_post_meta($post_id, self::FIELD_DOWNLOAD_COUNT);
                echo esc_html($count ?: '0');
                break;
        }
    }

    /**
     * Filter capabilities for document management
     *
     * @param array<string, bool> $allcaps All capabilities
     * @param array<string>       $caps    Required capabilities
     * @param array<mixed>        $args    Arguments
     * @param \WP_User            $user    User object
     * @return array<string, bool>
     */
    public static function filterCapabilities(array $allcaps, array $caps, array $args, \WP_User $user): array
    {
        // Map lemur_document capabilities to our custom capabilities
        $cap_mapping = [
            'edit_lemur_document'    => Capabilities::CAP_EDIT_DOCUMENTS,
            'edit_lemur_documents'   => Capabilities::CAP_EDIT_DOCUMENTS,
            'delete_lemur_document'  => Capabilities::CAP_EDIT_DOCUMENTS,
            'delete_lemur_documents' => Capabilities::CAP_EDIT_DOCUMENTS,
            'publish_lemur_documents' => Capabilities::CAP_EDIT_DOCUMENTS,
        ];

        foreach ($cap_mapping as $doc_cap => $lemur_cap) {
            if (in_array($doc_cap, $caps, true)) {
                // Check directly in $allcaps to avoid recursion (user_can triggers user_has_cap filter)
                if (!empty($allcaps[$lemur_cap]) || !empty($allcaps['manage_options'])) {
                    $allcaps[$doc_cap] = true;
                }
            }
        }

        return $allcaps;
    }

    /**
     * Get all documents for current user
     *
     * @param array<string, mixed> $args Additional query args
     * @return array<\WP_Post>
     */
    public static function getDocuments(array $args = []): array
    {
        $default_args = [
            'post_type'      => self::POST_TYPE,
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        // Filter visibility for non-bureau users
        if (!Capabilities::isBureau()) {
            $default_args['meta_query'] = [
                'relation' => 'OR',
                [
                    'key'     => '_' . self::FIELD_VISIBILITY,
                    'value'   => self::VISIBILITY_MEMBERS,
                ],
                [
                    'key'     => '_' . self::FIELD_VISIBILITY,
                    'compare' => 'NOT EXISTS',
                ],
            ];
        }

        return get_posts(array_merge($default_args, $args));
    }

    /**
     * Get document categories with counts
     *
     * @return array<\WP_Term>
     */
    public static function getCategories(): array
    {
        return get_terms([
            'taxonomy'   => self::TAXONOMY,
            'hide_empty' => true,
        ]);
    }

    /**
     * Obfuscate document ID to prevent enumeration
     *
     * @param int $document_id Document post ID
     * @return string Obfuscated ID (12 chars)
     */
    private static function obfuscateDocumentId(int $document_id): string
    {
        $salt = defined('NONCE_SALT') ? NONCE_SALT : 'lemur_default_salt';

        return substr(hash('sha256', 'doc_' . $salt . $document_id), 0, 12);
    }

    /**
     * Generate secure download token
     *
     * Token is time-limited and bound to the current user.
     * Format: base64(document_id:user_id:expiry:signature)
     *
     * @param int $document_id Document post ID
     * @return string Secure download token
     */
    private static function generateDownloadToken(int $document_id): string
    {
        $user_id = get_current_user_id();
        $expiry = time() + self::DOWNLOAD_TOKEN_LIFETIME;

        // Create payload
        $payload = $document_id . ':' . $user_id . ':' . $expiry;

        // Sign with secret key
        $secret = defined('NONCE_KEY') ? NONCE_KEY : 'lemur_default_key';
        $signature = hash_hmac('sha256', $payload, $secret);

        // Encode token (URL-safe base64)
        $token = $payload . ':' . $signature;

        return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
    }

    /**
     * Resolve and validate download token
     *
     * @param string $token Download token
     * @return array{document_id: int, user_id: int, expiry: int}|null Token data or null if invalid
     */
    private static function resolveDownloadToken(string $token): ?array
    {
        // Decode URL-safe base64
        $decoded = base64_decode(strtr($token, '-_', '+/'));

        if ($decoded === false) {
            return null;
        }

        // Parse token
        $parts = explode(':', $decoded);

        if (count($parts) !== 4) {
            return null;
        }

        [$document_id, $user_id, $expiry, $provided_signature] = $parts;

        $document_id = (int) $document_id;
        $user_id = (int) $user_id;
        $expiry = (int) $expiry;

        // Verify expiry
        if ($expiry < time()) {
            return null; // Token expired
        }

        // Verify user (token is bound to the user who requested it)
        if ($user_id !== get_current_user_id()) {
            return null; // Token belongs to different user
        }

        // Verify signature
        $payload = $document_id . ':' . $user_id . ':' . $expiry;
        $secret = defined('NONCE_KEY') ? NONCE_KEY : 'lemur_default_key';
        $expected_signature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expected_signature, $provided_signature)) {
            return null; // Invalid signature
        }

        return [
            'document_id' => $document_id,
            'user_id'     => $user_id,
            'expiry'      => $expiry,
        ];
    }
}

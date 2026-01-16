<?php
/**
 * Secure SVG Upload Handler
 *
 * Enables SVG uploads with comprehensive sanitization to prevent XSS attacks.
 * Only users with 'upload_files' capability can upload SVGs.
 *
 * Security measures:
 * - Validates XML structure
 * - Removes script elements
 * - Strips event handlers (onclick, onload, etc.)
 * - Removes javascript: URLs
 * - Strips dangerous elements (foreignObject, use with external refs)
 * - Removes data: URIs in hrefs (including nested SVG base64)
 * - Sanitizes xlink:href attributes
 * - File size limits (DoS prevention)
 * - Magic bytes validation
 *
 * @package Lemur\Core
 */

declare(strict_types=1);

namespace Lemur\Core;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMComment;
use DOMProcessingInstruction;
use DOMAttr;

/**
 * Handle secure SVG uploads
 */
class SvgUpload
{
    /**
     * Maximum file size for SVG uploads (2MB)
     */
    private const MAX_FILE_SIZE = 2 * 1024 * 1024;

    /**
     * Maximum decompressed size for SVGZ (5MB)
     */
    private const MAX_DECOMPRESSED_SIZE = 5 * 1024 * 1024;

    /**
     * SVG magic bytes patterns
     *
     * @var array<string>
     */
    private const SVG_SIGNATURES = [
        '<?xml',
        '<svg',
        '<!--',
    ];

    /**
     * Allowed SVG elements (whitelist approach)
     * Use filter 'lemur_svg_allowed_elements' to extend
     *
     * @var array<string>
     */
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
        'polygon', 'text', 'tspan', 'textPath', 'defs', 'symbol', 'use',
        'clipPath', 'mask', 'pattern', 'image', 'switch', 'linearGradient',
        'radialGradient', 'stop', 'title', 'desc', 'metadata', 'a',
        'animate', 'animateMotion', 'animateTransform', 'set', 'mpath',
        'marker', 'filter', 'feBlend', 'feColorMatrix', 'feComponentTransfer',
        'feComposite', 'feConvolveMatrix', 'feDiffuseLighting', 'feDisplacementMap',
        'feDropShadow', 'feFlood', 'feFuncA', 'feFuncB', 'feFuncG', 'feFuncR',
        'feGaussianBlur', 'feImage', 'feMerge', 'feMergeNode', 'feMorphology',
        'feOffset', 'feSpecularLighting', 'feTile', 'feTurbulence',
        'feDistantLight', 'fePointLight', 'feSpotLight',
    ];

    /**
     * Dangerous elements to remove (blacklist for extra safety)
     *
     * @var array<string>
     */
    private const DANGEROUS_ELEMENTS = [
        'script',
        'foreignObject',
        'iframe',
        'object',
        'embed',
        'handler',
        'listener',
    ];

    /**
     * Event handler attributes to remove
     *
     * @var array<string>
     */
    private const EVENT_ATTRIBUTES = [
        'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
        'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus',
        'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate',
        'onbegin', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick',
        'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable',
        'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate',
        'ondrag', 'ondragend', 'ondragleave', 'ondragenter', 'ondragover',
        'ondragdrop', 'ondragstart', 'ondrop', 'onend', 'onerror', 'onerrorupdate',
        'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout',
        'onhashchange', 'onhelp', 'oninput', 'onkeydown', 'onkeypress', 'onkeyup',
        'onlayoutcomplete', 'onload', 'onlosecapture', 'onmessage', 'onmousedown',
        'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover',
        'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart',
        'onoffline', 'ononline', 'onoutofsync', 'onpaste', 'onpause', 'onpopstate',
        'onprogress', 'onpropertychange', 'onreadystatechange', 'onredo', 'onrepeat',
        'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onresume',
        'onreverse', 'onrowsenter', 'onrowexit', 'onrowdelete', 'onrowinserted',
        'onscroll', 'onseek', 'onselect', 'onselectionchange', 'onselectstart',
        'onstart', 'onstop', 'onstorage', 'onsyncrestored', 'onsubmit', 'ontimeerror',
        'ontrackchange', 'onundo', 'onunload', 'onurlflip', 'onzoom',
    ];

    /**
     * Initialize SVG upload support
     */
    public static function init(): void
    {
        // Allow SVG mime type
        add_filter('upload_mimes', [self::class, 'allowSvgMime']);

        // Fix SVG mime type detection
        add_filter('wp_check_filetype_and_ext', [self::class, 'fixSvgMimeType'], 10, 5);

        // Sanitize SVG on upload
        add_filter('wp_handle_upload_prefilter', [self::class, 'sanitizeSvgUpload']);

        // Generate SVG metadata (dimensions)
        add_filter('wp_generate_attachment_metadata', [self::class, 'generateSvgMetadata'], 10, 3);

        // Fix SVG display in media library and admin
        add_filter('wp_get_attachment_image_src', [self::class, 'fixSvgImageSrc'], 10, 4);
        add_filter('wp_prepare_attachment_for_js', [self::class, 'prepareSvgForJs'], 10, 3);

        // Add SVG support in admin
        add_action('admin_head', [self::class, 'adminSvgStyles']);

        // Carbon Fields SVG preview support
        add_filter('carbon_fields_attachment_metadata', [self::class, 'carbonFieldsSvgMetadata'], 10, 3);
    }

    /**
     * Allow SVG mime type for users with upload capability
     *
     * @param array<string, string> $mimes Allowed mime types
     * @return array<string, string>
     */
    public static function allowSvgMime(array $mimes): array
    {
        if (current_user_can('upload_files')) {
            $mimes['svg'] = 'image/svg+xml';
            $mimes['svgz'] = 'image/svg+xml';
        }

        return $mimes;
    }

    /**
     * Fix SVG mime type detection (WordPress doesn't detect it properly)
     *
     * @param array<string, mixed> $data     File data
     * @param string               $file     File path
     * @param string               $filename File name
     * @param array<string>|null   $mimes    Allowed mimes
     * @param string|false         $real_mime Real mime type
     * @return array<string, mixed>
     */
    public static function fixSvgMimeType(
        array $data,
        string $file,
        string $filename,
        ?array $mimes = null,
        $real_mime = false
    ): array {
        if (!current_user_can('upload_files')) {
            return $data;
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if ($ext === 'svg' || $ext === 'svgz') {
            $data['ext'] = $ext;
            $data['type'] = 'image/svg+xml';
        }

        return $data;
    }

    /**
     * Sanitize SVG file before upload
     *
     * @param array<string, mixed> $file Uploaded file data
     * @return array<string, mixed>
     */
    public static function sanitizeSvgUpload(array $file): array
    {
        if (!isset($file['type']) || $file['type'] !== 'image/svg+xml') {
            // Check by extension as fallback
            $ext = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['svg', 'svgz'], true)) {
                return $file;
            }
        }

        // Check user capability
        if (!current_user_can('upload_files')) {
            $file['error'] = __('Vous n\'avez pas la permission d\'uploader des fichiers SVG.', 'lemur');
            return $file;
        }

        // Read the file content
        $file_path = $file['tmp_name'] ?? '';
        if (!$file_path || !file_exists($file_path)) {
            return $file;
        }

        // Check file size (DoS prevention)
        $file_size = filesize($file_path);
        if ($file_size === false || $file_size > self::MAX_FILE_SIZE) {
            $file['error'] = sprintf(
                /* translators: %s: maximum file size */
                __('Le fichier SVG dépasse la taille maximale autorisée (%s).', 'lemur'),
                size_format(self::MAX_FILE_SIZE)
            );
            return $file;
        }

        // Handle gzipped SVG
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if ($ext === 'svgz') {
            $content = self::readGzippedFile($file_path);
        } else {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $content = file_get_contents($file_path);
        }

        if ($content === false) {
            $file['error'] = __('Impossible de lire le fichier SVG.', 'lemur');
            return $file;
        }

        // Validate SVG magic bytes
        if (!self::isValidSvgContent($content)) {
            $file['error'] = __('Le fichier ne semble pas être un SVG valide.', 'lemur');
            return $file;
        }

        // Sanitize the SVG content
        $sanitized = self::sanitize($content);

        if ($sanitized === null) {
            $file['error'] = __('Le fichier SVG n\'est pas valide ou contient du contenu non autorisé.', 'lemur');
            return $file;
        }

        // Write sanitized content back
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        if (file_put_contents($file_path, $sanitized) === false) {
            $file['error'] = __('Impossible de sauvegarder le fichier SVG sanitisé.', 'lemur');
            return $file;
        }

        return $file;
    }

    /**
     * Validate SVG content by checking magic bytes/signatures
     *
     * @param string $content File content
     * @return bool
     */
    private static function isValidSvgContent(string $content): bool
    {
        $content = ltrim($content);

        // Check for BOM and remove it
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($content, $bom)) {
            $content = substr($content, 3);
        }

        // Check against known SVG signatures
        foreach (self::SVG_SIGNATURES as $signature) {
            if (stripos($content, $signature) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize SVG content
     *
     * @param string $content Raw SVG content
     * @return string|null Sanitized SVG or null if invalid
     */
    public static function sanitize(string $content): ?string
    {
        // Remove any PHP tags
        $content = preg_replace('/<\?(?!xml).*?\?>/s', '', $content) ?? $content;

        // Remove DOCTYPE (can be used for XXE attacks)
        $content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $content) ?? $content;

        // Remove XML external entities declarations
        $content = preg_replace('/<!ENTITY[^>]*>/i', '', $content) ?? $content;

        // Remove CDATA sections that might contain malicious content
        $content = preg_replace('/<!\[CDATA\[.*?\]\]>/s', '', $content) ?? $content;

        // Try to load as XML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        // LIBXML_NONET prevents network access (XXE prevention)
        // LIBXML_NOENT substitutes entities (prevents entity expansion attacks)
        // Note: libxml_disable_entity_loader() is deprecated in PHP 8.0+
        // LIBXML_NONET is sufficient for XXE prevention in PHP 8+
        $flags = LIBXML_NONET;

        // Suppress external entity loading via libxml options
        if (LIBXML_VERSION >= 20900) {
            // libxml 2.9.0+ disables external entities by default
            $flags |= LIBXML_NOENT;
        }

        $loaded = $dom->loadXML($content, $flags);

        if (!$loaded) {
            libxml_clear_errors();
            return null;
        }

        libxml_clear_errors();

        // Check if root element is SVG
        $root = $dom->documentElement;
        if (!$root || strtolower($root->localName) !== 'svg') {
            return null;
        }

        // Get allowed elements (with filter for extensibility)
        $allowedElements = apply_filters('lemur_svg_allowed_elements', self::ALLOWED_ELEMENTS);

        // Sanitize the DOM tree
        self::sanitizeNode($root, $allowedElements);

        // Get the sanitized content
        $output = $dom->saveXML($root);

        if ($output === false) {
            return null;
        }

        // Add XML declaration
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $output;
    }

    /**
     * Recursively sanitize a DOM node
     *
     * @param DOMNode       $node            DOM node to sanitize
     * @param array<string> $allowedElements Whitelist of allowed elements
     */
    private static function sanitizeNode(DOMNode $node, array $allowedElements): void
    {
        // Process child nodes first (collect to avoid modification during iteration)
        $children = [];
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $children[] = $child;
            }
        }

        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tagName = strtolower($child->localName);

                // Remove dangerous elements (blacklist takes priority)
                if (in_array($tagName, self::DANGEROUS_ELEMENTS, true)) {
                    $node->removeChild($child);
                    continue;
                }

                // Remove unknown elements (not in whitelist)
                if (!in_array($tagName, $allowedElements, true)) {
                    $node->removeChild($child);
                    continue;
                }

                // Sanitize attributes
                self::sanitizeAttributes($child);

                // Recurse into children
                self::sanitizeNode($child, $allowedElements);
            } elseif ($child instanceof DOMComment) {
                // Remove comments (can contain malicious content)
                $node->removeChild($child);
            } elseif ($child instanceof DOMProcessingInstruction) {
                // Remove processing instructions
                $node->removeChild($child);
            }
        }
    }

    /**
     * Sanitize element attributes
     *
     * @param DOMElement $element Element to sanitize
     */
    private static function sanitizeAttributes(DOMElement $element): void
    {
        $attributesToRemove = [];

        /** @var DOMAttr $attr */
        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->name);
            $attrValue = $attr->value;

            // Remove event handlers
            if (in_array($attrName, self::EVENT_ATTRIBUTES, true)) {
                $attributesToRemove[] = $attr->name;
                continue;
            }

            // Remove attributes starting with 'on' (catch-all for events)
            if (str_starts_with($attrName, 'on')) {
                $attributesToRemove[] = $attr->name;
                continue;
            }

            // Check for dangerous URL values
            if (self::isDangerousUrl($attrValue)) {
                $attributesToRemove[] = $attr->name;
                continue;
            }

            // Special handling for href and xlink:href
            if ($attrName === 'href' || $attrName === 'xlink:href') {
                if (!self::isAllowedHref($attrValue, $element->localName)) {
                    $attributesToRemove[] = $attr->name;
                    continue;
                }
            }

            // Check for style attribute with dangerous content
            if ($attrName === 'style') {
                $cleanedStyle = self::sanitizeStyleAttribute($attrValue);
                if ($cleanedStyle !== null) {
                    $element->setAttribute($attr->name, $cleanedStyle);
                } else {
                    $attributesToRemove[] = $attr->name;
                }
            }
        }

        // Remove flagged attributes
        foreach ($attributesToRemove as $attrName) {
            $element->removeAttribute($attrName);
        }
    }

    /**
     * Check if a URL value is dangerous
     *
     * @param string $value Attribute value
     * @return bool
     */
    private static function isDangerousUrl(string $value): bool
    {
        $value = strtolower(trim($value));

        // Remove whitespace and control characters
        $value = preg_replace('/[\s\x00-\x1f]+/', '', $value);

        // Check for dangerous protocols
        $dangerousProtocols = [
            'javascript:',
            'vbscript:',
            'livescript:',
            'mocha:',
            'data:text/html',
            'data:application',
        ];

        foreach ($dangerousProtocols as $protocol) {
            if (str_starts_with($value, $protocol)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if href value is allowed
     *
     * @param string $value   Href value
     * @param string $tagName Element tag name
     * @return bool
     */
    private static function isAllowedHref(string $value, string $tagName): bool
    {
        $value = trim($value);
        $tagName = strtolower($tagName);

        // Allow internal references (#id)
        if (str_starts_with($value, '#')) {
            return true;
        }

        // For <a> elements, allow http/https URLs and mailto
        if ($tagName === 'a') {
            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                return true;
            }
            if (str_starts_with($value, 'mailto:')) {
                return true;
            }
            return false;
        }

        // For <use> elements, only allow internal references
        if ($tagName === 'use') {
            // Only allow internal fragment references - no external URLs
            return str_starts_with($value, '#');
        }

        // For <image> elements, allow safe data:image URLs (NOT svg+xml to prevent recursion)
        if ($tagName === 'image') {
            // Allow only raster image formats in data URIs (NO SVG - prevents nested XSS)
            if (preg_match('/^data:image\/(png|jpeg|jpg|gif|webp);base64,/i', $value)) {
                return true;
            }
            // Allow http/https for external images
            if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                return true;
            }
            return false;
        }

        // For other elements (filters, etc.), only allow internal refs
        return false;
    }

    /**
     * Sanitize style attribute
     *
     * @param string $style Style attribute value
     * @return string|null Sanitized style or null if should be removed
     */
    private static function sanitizeStyleAttribute(string $style): ?string
    {
        // Remove url() with dangerous protocols
        if (preg_match('/url\s*\([^)]*(?:javascript|vbscript|data:text|data:application)/i', $style)) {
            return null;
        }

        // Remove expression() (IE)
        if (preg_match('/expression\s*\(/i', $style)) {
            return null;
        }

        // Remove behavior: (IE)
        if (preg_match('/behavior\s*:/i', $style)) {
            return null;
        }

        // Remove -moz-binding (Firefox)
        if (preg_match('/-moz-binding\s*:/i', $style)) {
            return null;
        }

        return $style;
    }

    /**
     * Read gzipped SVG file with size limit (prevents zip bomb)
     *
     * @param string $path File path
     * @return string|false File content or false on failure/size exceeded
     */
    private static function readGzippedFile(string $path)
    {
        $gz = gzopen($path, 'rb');
        if ($gz === false) {
            return false;
        }

        $content = '';
        $totalSize = 0;

        while (!gzeof($gz)) {
            $chunk = gzread($gz, 8192);
            if ($chunk === false) {
                gzclose($gz);
                return false;
            }

            $totalSize += strlen($chunk);

            // Prevent decompression bomb
            if ($totalSize > self::MAX_DECOMPRESSED_SIZE) {
                gzclose($gz);
                return false;
            }

            $content .= $chunk;
        }

        gzclose($gz);

        return $content;
    }

    /**
     * Generate metadata for SVG attachments
     *
     * Extracts dimensions from SVG viewBox or width/height attributes.
     *
     * @param array<string, mixed> $metadata      Attachment metadata
     * @param int                  $attachment_id Attachment ID
     * @param string               $context       Context (create, update)
     * @return array<string, mixed>
     */
    public static function generateSvgMetadata(array $metadata, int $attachment_id, string $context = ''): array
    {
        $file = get_attached_file($attachment_id);

        if (!$file || !file_exists($file)) {
            return $metadata;
        }

        $mime_type = get_post_mime_type($attachment_id);
        if ($mime_type !== 'image/svg+xml') {
            return $metadata;
        }

        $dimensions = self::getSvgDimensions($file);

        if ($dimensions) {
            $metadata['width'] = $dimensions['width'];
            $metadata['height'] = $dimensions['height'];
            $metadata['file'] = wp_basename($file);
        }

        return $metadata;
    }

    /**
     * Fix SVG image source for wp_get_attachment_image_src
     *
     * @param array<mixed>|false $image         Image data or false
     * @param int                $attachment_id Attachment ID
     * @param string|int[]       $size          Image size
     * @param bool               $icon          Whether to use icon
     * @return array<mixed>|false
     */
    public static function fixSvgImageSrc($image, int $attachment_id, $size, bool $icon)
    {
        $mime_type = get_post_mime_type($attachment_id);

        if ($mime_type !== 'image/svg+xml') {
            return $image;
        }

        // Get the SVG URL
        $url = wp_get_attachment_url($attachment_id);

        if (!$url) {
            return $image;
        }

        // Get dimensions from metadata or file
        $metadata = wp_get_attachment_metadata($attachment_id);
        $width = $metadata['width'] ?? 100;
        $height = $metadata['height'] ?? 100;

        return [$url, $width, $height, false];
    }

    /**
     * Prepare SVG attachment data for JavaScript (media library)
     *
     * @param array<string, mixed> $response   Attachment data
     * @param \WP_Post             $attachment Attachment post object
     * @param array<mixed>|false   $meta       Attachment metadata
     * @return array<string, mixed>
     */
    public static function prepareSvgForJs(array $response, \WP_Post $attachment, $meta): array
    {
        if ($response['mime'] !== 'image/svg+xml') {
            return $response;
        }

        $file = get_attached_file($attachment->ID);

        if (!$file || !file_exists($file)) {
            return $response;
        }

        // Get dimensions
        $dimensions = self::getSvgDimensions($file);
        $width = $dimensions['width'] ?? 100;
        $height = $dimensions['height'] ?? 100;

        // Set dimensions
        $response['width'] = $width;
        $response['height'] = $height;

        // Create sizes array with SVG URL
        $url = $response['url'];

        $response['sizes'] = [
            'full' => [
                'url' => $url,
                'width' => $width,
                'height' => $height,
                'orientation' => $width >= $height ? 'landscape' : 'portrait',
            ],
            'thumbnail' => [
                'url' => $url,
                'width' => 150,
                'height' => 150,
                'orientation' => 'portrait',
            ],
            'medium' => [
                'url' => $url,
                'width' => $width,
                'height' => $height,
                'orientation' => $width >= $height ? 'landscape' : 'portrait',
            ],
        ];

        // Mark as image type
        $response['type'] = 'image';
        $response['subtype'] = 'svg+xml';

        // Set icon to the SVG itself
        $response['icon'] = $url;

        return $response;
    }

    /**
     * Get SVG dimensions from file
     *
     * Parses SVG viewBox or width/height attributes.
     *
     * @param string $file Path to SVG file
     * @return array{width: int, height: int}|null
     */
    private static function getSvgDimensions(string $file): ?array
    {
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = file_get_contents($file);

        if (!$content) {
            return null;
        }

        // Try to get viewBox first
        if (preg_match('/viewBox=["\']?\s*(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s*["\']?/i', $content, $matches)) {
            return [
                'width' => (int) round((float) $matches[3]),
                'height' => (int) round((float) $matches[4]),
            ];
        }

        // Try width and height attributes
        $width = null;
        $height = null;

        if (preg_match('/\bwidth=["\']?(\d+(?:\.\d+)?)(px|em|%)?["\']?/i', $content, $matches)) {
            $width = (int) round((float) $matches[1]);
        }

        if (preg_match('/\bheight=["\']?(\d+(?:\.\d+)?)(px|em|%)?["\']?/i', $content, $matches)) {
            $height = (int) round((float) $matches[1]);
        }

        if ($width && $height) {
            return [
                'width' => $width,
                'height' => $height,
            ];
        }

        // Default dimensions
        return ['width' => 100, 'height' => 100];
    }

    /**
     * Fix SVG metadata for Carbon Fields image/file fields
     *
     * Carbon Fields uses its own REST API endpoint which doesn't benefit from
     * wp_prepare_attachment_for_js. This filter ensures SVG previews work.
     *
     * @param array<string, mixed> $metadata Attachment metadata from Carbon Fields
     * @param int|string           $id       Attachment ID or URL
     * @param string               $type     Type: 'id' or 'url'
     * @return array<string, mixed>
     */
    public static function carbonFieldsSvgMetadata(array $metadata, $id, string $type): array
    {
        // Only process if we have an ID
        if (!is_numeric($id) || (int) $id <= 0) {
            return $metadata;
        }

        $attachment_id = (int) $id;
        $mime_type = get_post_mime_type($attachment_id);

        if ($mime_type !== 'image/svg+xml') {
            return $metadata;
        }

        // Get the SVG URL
        $url = wp_get_attachment_url($attachment_id);

        if (!$url) {
            return $metadata;
        }

        // Set thumb_url to the SVG URL itself (SVGs don't have generated thumbnails)
        // Carbon Fields uses thumb_url for the preview image
        $metadata['thumb_url'] = $url;

        // Also set file_url if not already set
        if (empty($metadata['file_url'])) {
            $metadata['file_url'] = $url;
        }

        // Ensure file_type is correctly set as 'image'
        $metadata['file_type'] = 'image';

        // Add sizes array for consistency with standard attachment data
        $file = get_attached_file($attachment_id);
        if ($file && file_exists($file)) {
            $dimensions = self::getSvgDimensions($file);
            if ($dimensions) {
                $metadata['width'] = $dimensions['width'];
                $metadata['height'] = $dimensions['height'];
                $metadata['sizes'] = [
                    'full' => [
                        'url' => $url,
                        'width' => $dimensions['width'],
                        'height' => $dimensions['height'],
                    ],
                    'thumbnail' => [
                        'url' => $url,
                        'width' => 150,
                        'height' => 150,
                    ],
                ];
            }
        }

        return $metadata;
    }

    /**
     * Add SVG styles for admin media library
     *
     * Only loads on relevant admin pages for performance.
     */
    public static function adminSvgStyles(): void
    {
        $screen = get_current_screen();

        // Only load on media-related screens and theme options
        $allowed_screens = ['upload', 'media', 'post', 'page', 'edit', 'toplevel_page_crb_carbon_fields_container_lemur_options'];
        if (!$screen || !in_array($screen->base, $allowed_screens, true)) {
            // Also check for Carbon Fields pages
            if (!$screen || strpos($screen->base, 'carbon') === false) {
                return;
            }
        }

        echo '<style>
            /* SVG thumbnails in media library grid */
            .attachment-266x266,
            .thumbnail img {
                width: 100% !important;
                height: auto !important;
            }

            /* SVG in attachment details */
            .attachment-info .thumbnail img[src$=".svg"],
            .media-frame-content .attachment img[src$=".svg"],
            .media-sidebar .thumbnail img[src$=".svg"] {
                width: 100%;
                height: auto;
                max-height: 200px;
                object-fit: contain;
            }

            /* Carbon Fields image field preview */
            .cf-field .cf-field__head img[src$=".svg"],
            .cf-field .cf-thumb img[src$=".svg"],
            .carbon-field .carbon-attachment .thumbnail img[src$=".svg"],
            .cf-file__inner img[src$=".svg"],
            .cf-image__image[src$=".svg"] {
                width: 100% !important;
                height: auto !important;
                max-width: 150px;
                max-height: 150px;
                object-fit: contain;
                background: #f0f0f0;
                padding: 10px;
                border-radius: 4px;
            }

            /* SVG in media modal */
            .media-frame .attachments-browser .attachment img[src$=".svg"] {
                padding: 10px;
                background: #f6f7f7;
            }

            /* Fix icon display for SVG files */
            .attachment .icon[src$=".svg"],
            .media-icon img[src$=".svg"] {
                width: 100%;
                height: auto;
            }
        </style>';
    }
}

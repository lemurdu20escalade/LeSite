<?php
/**
 * Post Fields - Custom fields for blog posts
 *
 * Adds gallery and additional metadata to standard WordPress posts.
 *
 * @package Lemur\Fields
 */

declare(strict_types=1);

namespace Lemur\Fields;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Register custom fields for blog posts
 */
class PostFields
{
    /**
     * Field keys
     */
    public const FIELD_GALLERY = 'post_gallery';
    public const FIELD_LOCATION = 'post_location';
    public const FIELD_PARTICIPANTS = 'post_participants';

    /**
     * Initialize post fields
     */
    public static function init(): void
    {
        add_action('carbon_fields_register_fields', [self::class, 'registerFields']);
    }

    /**
     * Register post meta container and fields
     */
    public static function registerFields(): void
    {
        Container::make('post_meta', __('Galerie photos', 'lemur'))
            ->where('post_type', '=', 'post')
            ->set_priority('high')
            ->add_fields([
                Field::make('media_gallery', self::FIELD_GALLERY, __('Photos de la sortie', 'lemur'))
                    ->set_type(['image'])
                    ->set_help_text(__('Ajoutez les photos de la sortie ou de l\'événement', 'lemur')),
            ]);

        Container::make('post_meta', __('Informations sortie', 'lemur'))
            ->where('post_type', '=', 'post')
            ->set_priority('default')
            ->add_fields([
                Field::make('text', self::FIELD_LOCATION, __('Lieu', 'lemur'))
                    ->set_attribute('placeholder', 'Fontainebleau, Bourgogne, Pen-Hir...')
                    ->set_width(50),

                Field::make('text', self::FIELD_PARTICIPANTS, __('Participants', 'lemur'))
                    ->set_attribute('placeholder', '12 lémuriens')
                    ->set_width(50),
            ]);
    }

    /**
     * Get post gallery images
     *
     * @param int $post_id Post ID
     * @return array<int, int> Array of attachment IDs
     */
    public static function getGallery(int $post_id): array
    {
        $gallery = carbon_get_post_meta($post_id, self::FIELD_GALLERY);
        return is_array($gallery) ? $gallery : [];
    }

    /**
     * Get post meta data
     *
     * @param int $post_id Post ID
     * @return array<string, mixed>
     */
    public static function getPostMeta(int $post_id): array
    {
        return [
            'gallery'      => self::getGallery($post_id),
            'location'     => carbon_get_post_meta($post_id, self::FIELD_LOCATION),
            'participants' => carbon_get_post_meta($post_id, self::FIELD_PARTICIPANTS),
        ];
    }
}

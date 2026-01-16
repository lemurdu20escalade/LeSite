<?php
/**
 * HasDefaultTerms Trait
 *
 * Provides optimized default terms insertion for taxonomies.
 *
 * @package Lemur\CustomPostTypes\Traits
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes\Traits;

/**
 * Trait for CPTs that need default taxonomy terms
 */
trait HasDefaultTerms
{
    /**
     * Insert default terms only once
     *
     * Uses an option flag to prevent repeated DB queries on every init.
     *
     * @param string   $taxonomy      Taxonomy slug
     * @param string[] $default_terms Array of term names
     */
    protected static function insertTermsOnce(string $taxonomy, array $default_terms): void
    {
        $option_key = "lemur_{$taxonomy}_terms_installed";

        if (get_option($option_key)) {
            return;
        }

        foreach ($default_terms as $term) {
            if (!term_exists($term, $taxonomy)) {
                wp_insert_term($term, $taxonomy);
            }
        }

        update_option($option_key, true, false); // autoload = false
    }

    /**
     * Reset terms installation flag
     *
     * Useful for testing or when terms need to be re-checked.
     *
     * @param string $taxonomy Taxonomy slug
     */
    public static function resetTermsFlag(string $taxonomy): void
    {
        delete_option("lemur_{$taxonomy}_terms_installed");
    }
}

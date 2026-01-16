<?php
/**
 * HasOrderField Trait
 *
 * Provides order field options for Custom Post Types.
 *
 * @package Lemur\CustomPostTypes\Traits
 */

declare(strict_types=1);

namespace Lemur\CustomPostTypes\Traits;

/**
 * Trait for CPTs that need an order/priority field
 */
trait HasOrderField
{
    /**
     * Get order options for select field
     *
     * @param int $max Maximum order value
     * @return array<string, string>
     */
    protected static function getOrderOptions(int $max = 50): array
    {
        $options = [];

        for ($i = 1; $i <= $max; $i++) {
            $options[(string) $i] = (string) $i;
        }

        return $options;
    }
}

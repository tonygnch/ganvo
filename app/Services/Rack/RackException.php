<?php

namespace App\Services\Rack;

use RuntimeException;

/**
 * A configuration the yard cannot build or price.
 *
 * Carries a translation key rather than a sentence, because this reaches the
 * customer: the configurator turns it into Bulgarian, the JSON endpoints
 * return it as `reason`.
 */
class RackException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonKey,
        public readonly array $replacements = [],
    ) {
        parent::__construct($reasonKey);
    }

    public function translate(): string
    {
        return __('site.storefront.sankevi.'.$this->reasonKey, $this->replacements);
    }
}

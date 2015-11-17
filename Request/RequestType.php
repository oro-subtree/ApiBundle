<?php

namespace Oro\Bundle\ApiBundle\Request;

/**
 * Supported API request types.
 */
final class RequestType
{
    /**
     * REST API conforms JSON API specification
     * @see http://jsonapi.org
     */
    const REST = 'rest';
}
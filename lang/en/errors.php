<?php

declare(strict_types=1);

/*
 * Titles and details of RFC 9457 problem responses, keyed by HTTP status.
 */
return [
    '400' => ['title' => 'Bad request', 'detail' => 'The request could not be understood.'],
    '401' => ['title' => 'Unauthenticated', 'detail' => 'Authentication is required to access this resource.'],
    '403' => ['title' => 'Forbidden', 'detail' => 'You are not allowed to perform this action.'],
    '404' => ['title' => 'Not found', 'detail' => 'The requested resource does not exist.'],
    '405' => ['title' => 'Method not allowed', 'detail' => 'This method is not supported for the requested resource.'],
    '409' => ['title' => 'Conflict', 'detail' => 'The request conflicts with the current state of the resource.'],
    '422' => ['title' => 'Validation failed', 'detail' => 'The submitted data is invalid.'],
    '429' => ['title' => 'Too many requests', 'detail' => 'Rate limit exceeded. Retry after the delay indicated by the Retry-After header.'],
    '500' => ['title' => 'Server error', 'detail' => 'An unexpected error occurred. Please try again later.'],
    '503' => ['title' => 'Service unavailable', 'detail' => 'The service is temporarily unavailable.'],
    'default' => ['title' => 'Request failed', 'detail' => 'The request could not be completed.'],
];

<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\SecurityScheme;
use ApiPlatform\OpenApi\OpenApi;

final class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $inner) {}

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->inner)($context);

        $securityScheme = new SecurityScheme(
            type: 'http',
            scheme: 'bearer',
            bearerFormat: 'JWT',
            description: 'Enter your JWT token',
        );

        $openApi->getComponents()->getSecuritySchemes()['bearerAuth'] = $securityScheme;

        // Apply globally to all operations
        $openApi = $openApi->withSecurity([['bearerAuth' => []]]);

        return $openApi;
    }
}

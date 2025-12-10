<?php

namespace App\Serializer\Normalizer;

use App\Entity\ApiToken;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ApiTokenNormalizer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $objectNormalizer
    ) {
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!$object instanceof ApiToken) {
            return [];
        }

        // We can manually build the array or delegate?
        // Since attributes are failing, let's manually build it for the specific groups we care about.
        
        $groups = $context['groups'] ?? [];
        if (!is_array($groups)) {
            $groups = [$groups];
        }

        if (in_array('api_token:read', $groups)) {
            return [
                'id' => $object->getId(),
                'maskedToken' => $object->getMaskedToken(),
                'description' => $object->getDescription(),
                'createdAt' => $object->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'lastUsedAt' => $object->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
                'expiresAt' => $object->getExpiresAt()?->format(\DateTimeInterface::ATOM),
            ];
        }

        return [];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof ApiToken;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            ApiToken::class => true,
        ];
    }
}

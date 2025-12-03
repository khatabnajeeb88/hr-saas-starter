<?php

namespace App\Doctrine\Filter;

use App\Entity\Traits\TenantAwareTrait;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Check if the entity has the TenantAwareTrait
        if (!$targetEntity->reflClass->hasTrait(TenantAwareTrait::class)) {
            return '';
        }

        // The parameter name is 'tenant_id'
        try {
            $tenantId = $this->getParameter('tenant_id');
        } catch (\InvalidArgumentException $e) {
            // Parameter might not be set yet (e.g. during migration or command line)
            return '';
        }

        if (empty($tenantId)) {
            return '';
        }

        return sprintf('%s.tenant_id = %s', $targetTableAlias, $tenantId);
    }
}

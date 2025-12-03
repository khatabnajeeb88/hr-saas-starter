<?php

namespace App\Entity\Traits;

use App\Entity\Team;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait TenantAwareTrait
{
    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(name: 'tenant_id', referencedColumnName: 'id', nullable: true)]
    #[Groups(['notification:read'])] // Add other groups as needed
    private ?Team $tenant = null;

    public function getTenant(): ?Team
    {
        return $this->tenant;
    }

    public function setTenant(?Team $tenant): static
    {
        $this->tenant = $tenant;

        return $this;
    }
}

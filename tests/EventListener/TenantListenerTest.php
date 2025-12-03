<?php

namespace App\Tests\EventListener;

use App\Entity\Notification;
use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\EventListener\TenantListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

class TenantListenerTest extends TestCase
{
    public function testOnKernelRequestSetsFilterParameter(): void
    {
        $security = $this->createMock(Security::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $filterCollection = $this->createMock(FilterCollection::class);
        $filter = $this->createMock(SQLFilter::class);

        $user = new User();
        $team = new Team();
        // Reflection to set ID for testing
        $reflection = new \ReflectionClass($team);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($team, 123);

        $member = new TeamMember();
        $member->setUser($user);
        $member->setTeam($team);
        
        // Mock User::getTeamMembers to return collection with member
        // Since we can't easily mock the getter of the entity without partial mock or setting private property,
        // let's rely on the fact that User has getTeamMembers() which returns the collection.
        // We need to add the member to the user.
        // But User::addTeamMember is not defined in the snippet I saw, it has removeTeamMember.
        // It has $teamMembers collection.
        // Let's use reflection to set the collection if needed, or just rely on the side effect of $member->setUser($user) if it handles it?
        // Looking at TeamMember code: $member->setUser($user) just sets the property.
        // Looking at User code: $this->teamMembers = new ArrayCollection();
        // It's OneToMany mappedBy 'user'.
        // We need to manually add to the collection in the test environment since ORM isn't doing it.
        $reflectionUser = new \ReflectionClass($user);
        $propTeamMembers = $reflectionUser->getProperty('teamMembers');
        $propTeamMembers->setAccessible(true);
        $propTeamMembers->setValue($user, new ArrayCollection([$member]));

        $security->method('getUser')->willReturn($user);

        $entityManager->method('getFilters')->willReturn($filterCollection);
        
        // Use a real filter instance (or subclass) to test setParameter
        // SQLFilter constructor needs EntityManager
        $filter = new \App\Doctrine\Filter\TenantFilter($entityManager);
        $filterCollection->method('getFilter')->with('tenant_filter')->willReturn($filter);

        $listener = new TenantListener($security, $entityManager);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener->onKernelRequest($event);
        
        // Use reflection to check private property 'parameters' in SQLFilter
        $refFilter = new \ReflectionClass(\Doctrine\ORM\Query\Filter\SQLFilter::class);
        $propParams = $refFilter->getProperty('parameters');
        $propParams->setAccessible(true);
        $parameters = $propParams->getValue($filter);

        $this->assertArrayHasKey('tenant_id', $parameters);
        $this->assertEquals(123, $parameters['tenant_id']['value']);
    }

    public function testPrePersistSetsTenant(): void
    {
        $security = $this->createMock(Security::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $user = new User();
        $team = new Team();
        $member = new TeamMember();
        $member->setUser($user);
        $member->setTeam($team);

        $reflectionUser = new \ReflectionClass($user);
        $propTeamMembers = $reflectionUser->getProperty('teamMembers');
        $propTeamMembers->setAccessible(true);
        $propTeamMembers->setValue($user, new ArrayCollection([$member]));

        $security->method('getUser')->willReturn($user);

        $notification = new Notification();
        // Notification uses TenantAwareTrait

        $args = $this->createMock(LifecycleEventArgs::class);
        $args->method('getObject')->willReturn($notification);

        $listener = new TenantListener($security, $entityManager);

        $listener->prePersist($args);

        $this->assertSame($team, $notification->getTenant());
    }
}

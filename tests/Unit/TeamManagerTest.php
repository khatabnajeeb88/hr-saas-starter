<?php

namespace App\Tests\Unit;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Event\TeamMemberAddedEvent;
use App\Repository\TeamMemberRepository;
use App\Repository\TeamRepository;
use App\Service\TeamManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class TeamManagerTest extends TestCase
{
    private $entityManager;
    private $teamRepository;
    private $teamMemberRepository;
    private $slugger;
    private $eventDispatcher;
    private $teamManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->teamRepository = $this->createMock(TeamRepository::class);
        $this->teamMemberRepository = $this->createMock(TeamMemberRepository::class);
        $this->slugger = $this->createMock(SluggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->teamManager = new TeamManager(
            $this->entityManager,
            $this->teamRepository,
            $this->teamMemberRepository,
            $this->slugger,
            $this->eventDispatcher
        );
    }

    public function testCreateTeam()
    {
        $user = new User();
        $name = 'Test Team';
        $slug = 'test-team';

        $this->teamRepository->method('isSlugAvailable')->willReturn(true);
        $this->slugger->method('slug')->willReturn(new UnicodeString($slug));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $team = $this->teamManager->createTeam($user, $name, $slug);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertEquals($name, $team->getName());
        $this->assertEquals($slug, $team->getSlug());
        $this->assertEquals($user, $team->getOwner());
        $this->assertCount(1, $team->getMembers());
    }

    public function testAddMember()
    {
        $team = new Team();
        $user = new User();
        
        // Ensure user is not already a member
        // In a real scenario, we might need to mock Team methods if they were not simple entities
        // But since Team is an entity, we rely on its state. Initially empty.

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->eventDispatcher->expects($this->once())->method('dispatch')->with($this->isInstanceOf(TeamMemberAddedEvent::class));

        $member = $this->teamManager->addMember($team, $user);

        $this->assertInstanceOf(TeamMember::class, $member);
        $this->assertEquals($user, $member->getUser());
        $this->assertEquals($team, $member->getTeam());
        $this->assertEquals(TeamMember::ROLE_MEMBER, $member->getRole());
    }

    public function testAddMemberAlreadyExists()
    {
        $this->expectException(\InvalidArgumentException::class);

        $team = new Team();
        $user = new User();
        
        // Manually add member to simulate existing state
        $member = new TeamMember();
        $member->setUser($user);
        $member->setTeam($team);
        $team->addMember($member);

        $this->teamManager->addMember($team, $user);
    }
}

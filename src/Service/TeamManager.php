<?php

namespace App\Service;

use App\Entity\Team;
use App\Entity\TeamMember;
use App\Entity\User;
use App\Repository\TeamRepository;
use App\Repository\TeamMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class TeamManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TeamRepository $teamRepository,
        private TeamMemberRepository $teamMemberRepository,
        private SluggerInterface $slugger,
    ) {
    }

    /**
     * Create a new team with the given owner
     *
     * @throws \InvalidArgumentException if the slug is already taken
     */
    public function createTeam(User $owner, string $name, ?string $slug = null): Team
    {
        // Generate slug from name if not provided
        if ($slug === null) {
            $slug = $this->generateUniqueSlug($name);
        } else {
            // Validate provided slug
            if (!$this->teamRepository->isSlugAvailable($slug)) {
                throw new \InvalidArgumentException(sprintf('The slug "%s" is already in use.', $slug));
            }
        }

        $team = new Team();
        $team->setName($name);
        $team->setSlug($slug);
        $team->setOwner($owner);

        // Add the owner as a team member with owner role
        $ownerMember = new TeamMember();
        $ownerMember->setTeam($team);
        $ownerMember->setUser($owner);
        $ownerMember->setRole(TeamMember::ROLE_OWNER);

        $team->addMember($ownerMember);

        $this->entityManager->persist($team);
        $this->entityManager->persist($ownerMember);
        $this->entityManager->flush();

        return $team;
    }

    /**
     * Add a member to a team with the specified role
     *
     * @throws \InvalidArgumentException if the user is already a member
     */
    public function addMember(Team $team, User $user, string $role = TeamMember::ROLE_MEMBER): TeamMember
    {
        // Check if user is already a member
        if ($team->hasMember($user)) {
            throw new \InvalidArgumentException('User is already a member of this team.');
        }

        $teamMember = new TeamMember();
        $teamMember->setTeam($team);
        $teamMember->setUser($user);
        $teamMember->setRole($role);

        $team->addMember($teamMember);

        $this->entityManager->persist($teamMember);
        $this->entityManager->flush();

        return $teamMember;
    }

    /**
     * Remove a member from a team
     *
     * @throws \InvalidArgumentException if trying to remove the owner
     */
    public function removeMember(Team $team, User $user): void
    {
        // Prevent removing the owner
        if ($team->isOwner($user)) {
            throw new \InvalidArgumentException('Cannot remove the team owner. Transfer ownership first or delete the team.');
        }

        $teamMember = $team->getMemberByUser($user);

        if ($teamMember === null) {
            throw new \InvalidArgumentException('User is not a member of this team.');
        }

        $team->removeMember($teamMember);
        $this->entityManager->remove($teamMember);
        $this->entityManager->flush();
    }

    /**
     * Update a member's role
     *
     * @throws \InvalidArgumentException if trying to change the owner's role
     */
    public function updateMemberRole(Team $team, User $user, string $newRole): TeamMember
    {
        $teamMember = $team->getMemberByUser($user);

        if ($teamMember === null) {
            throw new \InvalidArgumentException('User is not a member of this team.');
        }

        // Prevent changing the owner's role directly
        if ($team->isOwner($user) && $newRole !== TeamMember::ROLE_OWNER) {
            throw new \InvalidArgumentException('Cannot change the owner\'s role. Transfer ownership first.');
        }

        $teamMember->setRole($newRole);
        $this->entityManager->flush();

        return $teamMember;
    }

    /**
     * Transfer team ownership to another member
     *
     * @throws \InvalidArgumentException if the new owner is not a member
     */
    public function transferOwnership(Team $team, User $newOwner): void
    {
        $newOwnerMember = $team->getMemberByUser($newOwner);

        if ($newOwnerMember === null) {
            throw new \InvalidArgumentException('New owner must be a member of the team.');
        }

        $oldOwner = $team->getOwner();
        $oldOwnerMember = $team->getMemberByUser($oldOwner);

        // Update team owner
        $team->setOwner($newOwner);

        // Update roles
        $newOwnerMember->setRole(TeamMember::ROLE_OWNER);
        
        if ($oldOwnerMember !== null) {
            $oldOwnerMember->setRole(TeamMember::ROLE_ADMIN);
        }

        $this->entityManager->flush();
    }

    /**
     * Delete a team and all its members
     */
    public function deleteTeam(Team $team): void
    {
        $this->entityManager->remove($team);
        $this->entityManager->flush();
    }

    /**
     * Update team details
     */
    public function updateTeam(Team $team, string $name, ?string $slug = null): Team
    {
        $team->setName($name);

        if ($slug !== null && $slug !== $team->getSlug()) {
            if (!$this->teamRepository->isSlugAvailable($slug, $team->getId())) {
                throw new \InvalidArgumentException(sprintf('The slug "%s" is already in use.', $slug));
            }
            $team->setSlug($slug);
        }

        $this->entityManager->flush();

        return $team;
    }

    /**
     * Generate a unique slug from a team name
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = $this->slugger->slug($name)->lower()->toString();
        $slug = $baseSlug;
        $counter = 1;

        while (!$this->teamRepository->isSlugAvailable($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get all teams for a user (as owner or member)
     *
     * @return Team[]
     */
    public function getUserTeams(User $user): array
    {
        return $this->teamRepository->findByMember($user);
    }

    /**
     * Get all teams owned by a user
     *
     * @return Team[]
     */
    public function getOwnedTeams(User $user): array
    {
        return $this->teamRepository->findByOwner($user);
    }

    /**
     * Check if a user has access to a team (is owner or member)
     */
    public function hasAccess(Team $team, User $user): bool
    {
        return $team->isOwner($user) || $team->hasMember($user);
    }

    /**
     * Check if a user has admin privileges in a team (owner or admin role)
     */
    public function hasAdminPrivileges(Team $team, User $user): bool
    {
        if ($team->isOwner($user)) {
            return true;
        }

        $member = $team->getMemberByUser($user);
        return $member !== null && $member->hasAdminPrivileges();
    }
}

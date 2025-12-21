<?php

namespace App\Command;

use App\Entity\Employee;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:manage-sample-employees',
    description: 'Adds or removes sample employees for testing.',
)]
class ManageSampleEmployeesCommand extends Command
{
    private const SAMPLE_TEAM_NAME = 'Sample Team';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('action', null, InputOption::VALUE_REQUIRED, 'Action to perform: add or remove', 'add')
            ->addOption('count', null, InputOption::VALUE_REQUIRED, 'Number of employees to add', 50)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getOption('action');
        $count = (int) $input->getOption('count');

        if ($action === 'remove') {
            return $this->removeSampleEmployees($io);
        }

        return $this->addSampleEmployees($io, $count);
    }

    private function addSampleEmployees(SymfonyStyle $io, int $count): int
    {
        $io->title('Adding Sample Employees');

        // 1. Get or Create Sample Team
        $team = $this->entityManager->getRepository(Team::class)->findOneBy(['name' => self::SAMPLE_TEAM_NAME]);

        if (!$team) {
            $owner = $this->entityManager->getRepository(User::class)->findOneBy([]);
            if (!$owner) {
                $io->error('No users found in the database. Cannot create a team without an owner.');
                return Command::FAILURE;
            }

            $team = new Team();
            $team->setName(self::SAMPLE_TEAM_NAME);
            $team->setSlug('sample-team-' . uniqid());
            $team->setOwner($owner);
            $this->entityManager->persist($team);
            $io->info('Created "Sample Team"');
        }

        // 2. Create Employees
        for ($i = 0; $i < $count; $i++) {
            $employee = new Employee();
            $randomId = substr(md5(uniqid()), 0, 6);
            
            $employee->setFirstName('User' . $i);
            $employee->setLastName('Sample' . $randomId);
            $employee->setEmail('user' . $i . '_' . $randomId . '@sample.local');
            $employee->setTeam($team);
            $employee->setEmploymentStatus('active');
            $employee->setJoinedAt(new \DateTimeImmutable());
            $employee->setJobTitle('Sample Role');
            
            // Required simple fields
            $employee->setMobile('555-' . rand(100000, 999999));
            $employee->setGender(rand(0, 1) ? 'Male' : 'Female');
            
            $this->entityManager->persist($employee);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Successfully added %d sample employees to "%s".', $count, self::SAMPLE_TEAM_NAME));

        return Command::SUCCESS;
    }

    private function removeSampleEmployees(SymfonyStyle $io): int
    {
        $io->title('Removing Sample Employees');

        $team = $this->entityManager->getRepository(Team::class)->findOneBy(['name' => self::SAMPLE_TEAM_NAME]);

        if (!$team) {
            $io->warning('Sample team not found. Nothing to remove.');
            return Command::SUCCESS;
        }

        $employees = $this->entityManager->getRepository(Employee::class)->findBy(['team' => $team]);
        $count = count($employees);

        foreach ($employees as $employee) {
            $this->entityManager->remove($employee);
        }

        // Optional: Remove the team as well if desired, but strategy said "remove employees"
        // Let's keep the team for now or remove it if empty? 
        // Plan said "remove all employees". Let's stick to that.
        // If we want to clean up completely:
        // $this->entityManager->remove($team); 

        $this->entityManager->flush();

        $io->success(sprintf('Successfully removed %d sample employees from "%s".', $count, self::SAMPLE_TEAM_NAME));

        return Command::SUCCESS;
    }
}

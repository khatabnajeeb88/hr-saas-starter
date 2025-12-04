<?php

namespace App\Command;

use App\Entity\PlanFeature;
use App\Entity\SubscriptionFeature;
use App\Entity\SubscriptionPlan;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:subscription:seed',
    description: 'Seed default subscription plans and features',
)]
class SeedSubscriptionDataCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Seeding Subscription Data');

        // Create features first
        $io->section('Creating Features');
        $features = $this->createFeatures($io);

        // Create plans
        $io->section('Creating Plans');
        $this->createPlans($io, $features);

        $io->success('Subscription data seeded successfully!');

        return Command::SUCCESS;
    }

    private function createFeatures(SymfonyStyle $io): array
    {
        $featuresData = [
            [
                'name' => 'API Access',
                'slug' => 'api-access',
                'description' => 'Access to REST API endpoints',
                'type' => 'boolean',
                'default' => 'false',
            ],
            [
                'name' => 'Storage Limit',
                'slug' => 'storage-limit',
                'description' => 'Maximum storage space in GB',
                'type' => 'quota',
                'default' => '1',
            ],
            [
                'name' => 'Custom Branding',
                'slug' => 'custom-branding',
                'description' => 'Customize with your own branding',
                'type' => 'boolean',
                'default' => 'false',
            ],
            [
                'name' => 'Priority Support',
                'slug' => 'priority-support',
                'description' => '24/7 priority customer support',
                'type' => 'boolean',
                'default' => 'false',
            ],
            [
                'name' => 'Advanced Analytics',
                'slug' => 'advanced-analytics',
                'description' => 'Advanced analytics and reporting',
                'type' => 'boolean',
                'default' => 'false',
            ],
            [
                'name' => 'Team Collaboration',
                'slug' => 'team-collaboration',
                'description' => 'Real-time team collaboration features',
                'type' => 'boolean',
                'default' => 'true',
            ],
        ];

        $features = [];
        foreach ($featuresData as $data) {
            $feature = new SubscriptionFeature();
            $feature->setName($data['name']);
            $feature->setSlug($data['slug']);
            $feature->setDescription($data['description']);
            $feature->setFeatureType($data['type']);
            $feature->setDefaultValue($data['default']);
            $feature->setIsActive(true);

            $this->entityManager->persist($feature);
            $features[$data['slug']] = $feature;

            $io->writeln('  ✓ Created feature: ' . $data['name']);
        }

        $this->entityManager->flush();

        return $features;
    }

    private function createPlans(SymfonyStyle $io, array $features): void
    {
        $plansData = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Perfect for getting started',
                'price' => '0.00',
                'interval' => 'monthly',
                'member_limit' => 3,
                'display_order' => 1,
                'features' => [
                    'storage-limit' => '1',
                    'team-collaboration' => 'true',
                ],
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Great for small teams',
                'price' => '19.00',
                'interval' => 'monthly',
                'member_limit' => 10,
                'display_order' => 2,
                'features' => [
                    'api-access' => 'true',
                    'storage-limit' => '10',
                    'team-collaboration' => 'true',
                ],
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Best for growing businesses',
                'price' => '49.00',
                'interval' => 'monthly',
                'member_limit' => 50,
                'display_order' => 3,
                'features' => [
                    'api-access' => 'true',
                    'storage-limit' => '100',
                    'custom-branding' => 'true',
                    'advanced-analytics' => 'true',
                    'team-collaboration' => 'true',
                ],
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'For large organizations',
                'price' => '199.00',
                'interval' => 'monthly',
                'member_limit' => null, // unlimited
                'display_order' => 4,
                'features' => [
                    'api-access' => 'true',
                    'storage-limit' => 'unlimited',
                    'custom-branding' => 'true',
                    'priority-support' => 'true',
                    'advanced-analytics' => 'true',
                    'team-collaboration' => 'true',
                ],
            ],
        ];

        foreach ($plansData as $data) {
            $plan = new SubscriptionPlan();
            $plan->setName($data['name']);
            $plan->setSlug($data['slug']);
            $plan->setDescription($data['description']);
            $plan->setPrice($data['price']);
            $plan->setBillingInterval($data['interval']);
            $plan->setTeamMemberLimit($data['member_limit']);
            $plan->setIsActive(true);
            $plan->setDisplayOrder($data['display_order']);

            $this->entityManager->persist($plan);

            // Add features to plan
            foreach ($data['features'] as $featureSlug => $value) {
                if (isset($features[$featureSlug])) {
                    $planFeature = new PlanFeature();
                    $planFeature->setPlan($plan);
                    $planFeature->setFeature($features[$featureSlug]);
                    $planFeature->setValue($value);
                    $planFeature->setEnabled(true);

                    $this->entityManager->persist($planFeature);
                }
            }

            $io->writeln('  ✓ Created plan: ' . $data['name'] . ' ($' . $data['price'] . '/' . $data['interval'] . ')');
        }

        $this->entityManager->flush();
    }
}

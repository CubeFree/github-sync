<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;

/**
 * Synchronizes milestones.
 */
class MilestoneSynchronizer extends AbstractSynchronizer
{

    public function synchronize($from, $to)
    {
        $fromMilestones = $this->github->getMilestones($from);
        $toMilestones = $this->github->getMilestones($to);

        $comparator = new ArrayComparator();

        // Milestones identity is their name, so they are the same if they have the same name
        $comparator->setItemIdentityComparator(function ($key1, $key2, $milestone1, $milestone2) {
            return strcasecmp($milestone1['title'], $milestone2['title']) === 0;
        });

        // Same issues have differences if they have a different body
        $comparator->setItemComparator(function ($milestone1, $milestone2) {
            return $milestone1['description'] === $milestone2['description1'];
        });
        $comparator
            ->whenDifferent(function ($milestone1, $milestone2) use ($to) {
                $this->output->writeln(sprintf(
                    'Same milestone <info>%s</info>',
                    $milestone2['title']
                ));
                $this->updateMilestone($to, $milestone2['number'], $milestone1['title'], $milestone1['description'], $milestone1['state'], $milestone1['due_on']);
            })
            ->whenMissingRight(function ($milestone) use ($to) {
                $this->output->writeln(sprintf('Missing milestone <info>%s</info> from %s', $milestone['title'], $to));
                $this->createMilestone($to, $milestone['title'], $milestone['description'], $milestone['state'], $milestone['due_on']);

            });


        $comparator->compare($fromMilestones, $toMilestones);
    }

    private function createMilestone($repository, $title, $description, $state, $due_on)
    {
        try {
            $this->github->createMilestone($repository, $title, $description, $state, $due_on);

            $this->output->writeln('<info>Milestone created</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function updateMilestone($repository, $id, $title, $description, $state, $due_on)
    {
        try {
            $this->github->updateMilestone($repository, $id, $title, $description, $state, $due_on);

            $this->output->writeln('<info>Milestone updated</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function deleteMilestone($repository, $milestone)
    {
        try {
            $this->github->deleteMilestone($repository, $milestone['number']);

            $this->output->writeln('<info>Milestone deleted</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }
}

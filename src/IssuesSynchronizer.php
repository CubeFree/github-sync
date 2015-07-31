<?php

namespace Piwik\GithubSync;

use ArrayComparator\ArrayComparator;

/**
 * Synchronizes Issues.
 */
class IssuesSynchronizer extends AbstractSynchronizer
{
    public function synchronize($from, $to)
    {
        $fromIssues = $this->github->getIssues($from);
        $toIssues = $this->github->getIssues($to);
        $toMilestones = $this->github->getMilestones($to);

        $comparator = new ArrayComparator();

        // Issues identity is their title, so they are the same if they have the same title
        $comparator->setItemIdentityComparator(function ($key1, $key2, $issue1, $issue2) {
            return strcasecmp($issue1['title'], $issue2['title']) === 0;
        });
        // Same issues have differences if they have a different body
        $comparator->setItemComparator(function ($issue1, $issue2) {
            return $issue1['body'] === $issue2['body1'];
        });

        $comparator
            ->whenDifferent(function ($issue1, $issue2) use ($to, $toMilestones) {
                $this->output->writeln(sprintf(
                    'Same issue but different title/body for <info>%s</info> (%s -> %s, %s -> %s)',
                    $issue2['number'],
                    $issue1['title'],
                    $issue2['title'],
                    $issue1['body'],
                    $issue2['body']
                ));

                $milestoneNumer = $this->getMilestoneNumer($toMilestones, $issue1['milestone']);

                $this->updateIssue($to, $issue2['number'], $issue1['title'], $issue1['body'], $milestoneNumer, $issue1['labels']);
            })
            ->whenMissingRight(function ($issue) use ($to , $toMilestones) {
                $this->output->writeln(sprintf('Missing issue <info>%s</info> from %s', $issue['title'], $to));

                if ($this->checkMigrate($issue)){

                    $milestoneNumer = $this->getMilestoneNumer($toMilestones, $issue['milestone']);
                    $this->createIssue($to, $issue['title'], $issue['body'], $milestoneNumer, $issue['labels']);
                }else{
                     $this->output->writeln(sprintf('Issue not migrated'));
                }
            });

        $comparator->compare($fromIssues, $toIssues);
    }

    private function checkMigrate($issue) 
    {
        $excludedLabels = ["ios","android","parse"];

        for ($i = 0; $i < sizeof($issue['labels']); $i++) {
            $label = $issue['labels'][$i];

            $name = strtolower($label["name"]);

            if (in_array($name, $excludedLabels)) {
                return false;
            }

        } 

        return true;

    }

    private function getMilestoneNumer($arrMiletones, $milestone) {

        for($i = 0; $i < sizeof($arrMiletones); $i++) {
            if ($arrMiletones[$i]["title"] == $milestone["title"]) {
                return $arrMiletones[$i]["number"];
            }
        }
    }

    private function createIssue($repository, $title, $body, $milestone, $labels)
    {
        try {
            $this->github->createIssue($repository, $title, $body, $milestone, $labels);

            $this->output->writeln('<info>Issue created</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }

    private function updateIssue($repository, $id, $title, $body, $milestone, $labels)
    {
        try {
            $this->github->updateIssue($repository, $id, $title, $body, $milestone, $labels);

            $this->output->writeln('<info>Issue updated</info>');
        } catch (AuthenticationRequiredException $e) {
            // We show the error but don't stop the app
            $this->output->writeln(sprintf('<error>Skipped: %s</error>', $e->getMessage()));
        }
    }
}

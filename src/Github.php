<?php

namespace Piwik\GithubSync;

use Github\Client;
use Github\Exception\RuntimeException;
use Github\ResultPager;

/**
 * GitHub API.
 */
class Github
{
    /**
     * @var Client
     */
    private $github;

    /**
     * @var bool
     */
    private $authenticated = false;

    /**
     * @param string|null $token
     */
    public function __construct($token = null)
    {
        $this->github = new Client();

        if ($token) {
            $this->authenticate($token);
        }
    }

    public function getUserRepositoriesMatching($pattern)
    {
        $this->assertAuthenticated();

        // Temporary header https://developer.github.com/v3/repos/#list-your-repositories
        $this->github->setHeaders(['Accept' => 'application/vnd.github.moondragon+json']);

        $paginator = new ResultPager($this->github);
        $repositories = $paginator->fetchAll($this->github->currentUser(), 'repositories');

        $repositories = array_map(function ($repository) {
            return $repository['full_name'];
        }, $repositories);

        $pattern = '/^' . preg_quote($pattern, '/') . '$/';
        $pattern = str_replace('\*', '.+', $pattern);

        return array_filter($repositories, function ($repository) use ($pattern) {
            return preg_match($pattern, $repository) === 1;
        });
    }
    public function getIssues($repository)
    {
        $array = explode('/', $repository, 2);

        try {
            $apiIssues = $this->github->issue();
            $apiIssues->setPerPage(100);
            return $apiIssues->all($array[0], $array[1]);
        } catch (RuntimeException $e) {
            throw new \RuntimeException('Error getting Issues from repository ' . $repository, 0, $e);
        }
    }

    public function createIssue($repository, $title, $body, $milestone, $labels)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->create($array[0], $array[1], [
            'title'  => $title,
            'body' => $body,
            'milestone' => $milestone,
            'labels' => $labels,
        ]);
    }
    
    public function updateIssue($repository, $id, $title, $body, $milestone, $labels)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->update($array[0], $array[1], $id, [
            'title'  => $title,
            'body' => $body,
            'labels' => $labels,
            'milestone' => $milestone,
        ]);
            
    }

    public function getLabels($repository)
    {
        $array = explode('/', $repository, 2);

        try {
            $apiLabels = $this->github->issue()->labels();
            $apiLabels->setPerPage(100);
            return $apiLabels->all($array[0], $array[1]);
        } catch (RuntimeException $e) {
            throw new \RuntimeException('Error getting labels from repository ' . $repository, 0, $e);
        }
    }

    public function createLabel($repository, $name, $color)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->labels()->create($array[0], $array[1], [
            'name'  => $name,
            'color' => $color,
        ]);
    }

    public function deleteLabel($repository, $name)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->labels()->deleteLabel($array[0], $array[1], $name);
    }

    public function updateLabel($repository, $name, $newName, $color)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->labels()->update($array[0], $array[1], $name, $newName, $color);
    }

    public function getMilestones($repository)
    {
        $array = explode('/', $repository, 2);

        try {
            $apiMilestones = $this->github->issue()->milestones();
            $apiMilestones->setPerPage(100);
            return $apiMilestones->all($array[0], $array[1]);
        } catch (RuntimeException $e) {
            throw new \RuntimeException('Error getting milestones from repository ' . $repository, 0, $e);
        }
    }

    public function createMilestone($repository, $title, $description, $state, $due_on)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        return $this->github->issue()->milestones()->create($array[0], $array[1], [
            'title' => $title,
            'description' => $description,
            'state' => $state,
            'due_on' => $due_on,
        ]);
    }

    public function deleteMilestone($repository, $number)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->milestones()->remove($array[0], $array[1], $number);
    }

    public function updateMilestone($repository, $id, $title, $description, $state, $due_on)
    {
        $this->assertAuthenticated();

        $array = explode('/', $repository, 2);

        $this->github->issue()->milestones()->update($array[0], $array[1], $id, [
            'title' => $title,
            'description' => $description,
            'state' => $state,
            'due_on' => $due_on,
        ]);
    }

    private function authenticate($token)
    {
        $this->github->authenticate($token, null, Client::AUTH_HTTP_TOKEN);
        $this->authenticated = true;
    }

    private function assertAuthenticated()
    {
        if (! $this->authenticated) {
            throw new AuthenticationRequiredException;
        }
    }
}

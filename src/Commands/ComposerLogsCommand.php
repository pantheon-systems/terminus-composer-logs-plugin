<?php

namespace Pantheon\TerminusComposerLogs\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Workflow;

class ComposerLogsCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    /**
     * Show composer logs for a given commit.
     *
     * @command composer:logs
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $commit Commit hash to show logs for (default to the most recent commit)
     *
     * @authenticated
     */
    public function composerLogs(string $site_env, array $options = [
        'commit' => null,
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $commits = $this->getEnv($site_env)->getCommits()->getData();

        if (!empty($options['commit'])) {
            if (strlen($options['commit']) < 7) {
                throw new TerminusException('Commit hash must be at least 7 characters.');
            }
        }

        $hash_to_find = $options['commit'];
        $logs_url = null;
        $commit_to_search_in_fallback = null;

        foreach ($commits as $commit) {
            $hash = $commit->hash;

            if (!$hash_to_find) {
                // If we don't have a hash to find, we'll use the most recent commit.
                if (!empty($commit->build_logs_url)) {
                    $logs_url = $commit->build_logs_url;
                    break;
                }
                $commit_to_search_in_fallback = $hash;
                break;
            } else {
                // It should start with the hash we're looking for.
                if (strpos($hash, $hash_to_find) === 0) {
                    if (!empty($commit->build_logs_url)) {
                        $logs_url = $commit->build_logs_url;
                        break;
                    }
                    $commit_to_search_in_fallback = $hash;
                    break;
                }
            }
        }

        if (!$logs_url && $commit_to_search_in_fallback) {
            // If we didn't find a commit with a build logs url, we'll try to find a workflow with the same commit hash and build the logs url from there.
            $logs_url = $this->getLogsUrlForCommitInWorkflows($site_env, $commit_to_search_in_fallback);
        }

        if (!$logs_url) {
            if ($hash_to_find) {
                $this->log()->notice('No composer logs found for commit {hash}', ['hash' => $hash_to_find]);
                return;
            }
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        $data = $this->getComposerLogsFromUrl($logs_url);
        if (!$data) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        $this->output()->write($data);
    }

    /**
     * Get composer logs from a given url.
     *
     * @param string $logs_url Logs url to get logs from.
     *
     * @return string|null
     */
    public function getComposerLogsFromUrl(string $logs_url): string|null
    {
        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $logs_url = sprintf('%s://%s%s', $protocol, $host, $logs_url);

        $options = [
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];
        $result = $this->request->request($logs_url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            return null;
        }

        $data = $result->getData();
        if (empty($data)) {
            return null;
        }

        // Now, some manipulation for the data.
        $data = html_entity_decode($data, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
        $data = str_replace('<br />', "\n", $data);
        $data = str_replace('<br>', "\n", $data);
        $data = str_replace('&nbsp;', ' ', $data);
        $data = strip_tags($data);

        return $data;
    }

    /**
     * Search for build logs url in workflow as a fallback mechanism.
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $commit Commit hash to show logs for (default to the most recent commit)
     *
     * @return string|null
     */
    public function getLogsUrlForCommitInWorkflows(string $site_env, string $commit): string|null
    {
        $site_env_parts = explode('.', $site_env);
        if (count($site_env_parts) != 2) {
            throw new TerminusException('Invalid site environment specified.');
        }
        $env_id = array_pop($site_env_parts);

        $valid_workflows = [
            'sync_code',
            'sync_code_with_build',
        ];

        $workflows = $this->getSite($site_env)->getWorkflows()->all();
        $sync_code_workflow = null;

        foreach ($workflows as $workflow) {
            if (in_array($workflow->get('type'), $valid_workflows)) {
                if ($workflow->get('environment_id') == $env_id && $workflow->get('params')->target_commit === $commit) {
                    $sync_code_workflow = $workflow;
                    break;
                }
            }
        }
        if (!$sync_code_workflow) {
            return null;
        }

        return $this->getBuildLogsUrlFromWorkflow($sync_code_workflow, $site_env);
    }

    /**
     * Get build logs url from workflow.
     *
     * @param object $workflow \Pantheon\Terminus\Models\Workflow object.
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @return string|null
     */
    public function getBuildLogsUrlFromWorkflow(Workflow $workflow, string $site_env): string|null
    {
        $workflow_id = $workflow->get('id');
        $workflow_base_url = $this->getSite($site_env)->getWorkflows()->getUrl();

        $workflow_url = sprintf('%s/%s?hydrate=tasks', $workflow_base_url, $workflow_id);

        $options = [
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];
        $result = $this->request->request($workflow_url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            return null;
        }

        $data = $result->getData();

        if (empty($data->tasks)) {
            return null;
        }

        $logs_url = null;

        $valid_tasks = [
            'job_runner_artifact_update',
            'job_runner_artifact_install',
        ];

        $job_id = null;

        foreach ($data->tasks as $task) {
            if ($task->fn_name === 'queue_job_runner_task' && in_array($task->params->task_type, $valid_tasks)) {
                $responses = $task->responses;
                foreach ($responses as $response) {
                    // We have no other way to get the job id and we need it to get the logs url, then parse the responses.
                    $body = $response->body;
                    $job_id_regex = '/Job\sID:\s([a-z0-9\-]{36})/';
                    preg_match($job_id_regex, $body, $matches);
                    if (!empty($matches[1])) {
                        $job_id = $matches[1];
                        break;
                    }
                }
                break;
            }
        }

        if (!$job_id) {
            return null;
        }

        $logs_url = sprintf('/api/sites/%s/environments/%s/build/logs-v3/%s', $this->getSite($site_env)->id, $this->getEnv($site_env)->id, $job_id);

        return $logs_url;
    }

    /**
     * Show composer logs for recent update attempt.
     *
     * @command composer:logs-update
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @authenticated
     */
    public function composerLogsUpdate(string $site_env)
    {
        $site_env_parts = explode('.', $site_env);
        if (count($site_env_parts) != 2) {
            throw new TerminusException('Invalid site environment specified.');
        }
        $env_id = array_pop($site_env_parts);

        $valid_workflows = [
            'check_upstream_updates',
            'apply_upstream_updates',
        ];

        $this->requireSiteIsNotFrozen($site_env);
        $workflows = $this->getSite($site_env)->getWorkflows()->all();
        $update_workflow = null;

        foreach ($workflows as $workflow) {
            if (in_array($workflow->get('type'), $valid_workflows)) {
                if ($workflow->get('environment_id') == $env_id) {
                    $update_workflow = $workflow;
                    break;
                }
            }
        }

        if (!$update_workflow) {
            $this->log()->notice('No composer update logs found for this environment.');
            return;
        }

        $logs_url = $this->getBuildLogsUrlFromWorkflow($update_workflow, $site_env);

        if (!$logs_url) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }


        $data = $this->getComposerLogsFromUrl($logs_url);
        if (!$data) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        $this->output()->write($data);
    }
}

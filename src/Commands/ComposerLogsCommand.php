<?php
/**
 * This variation on the Hello command shows how use the `@authenticated`
 * attribute to signal Terminus to require an authenticated session to
 * use this command.
 */

namespace Pantheon\TerminusHello\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Say hello to the user
 *
 * When you rename this class, make sure the new name ends with "Command" so that Terminus can find it.
 */
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
    public function composerLogs($site_env, $options = [
        'commit' => null,
    ])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $commits = $this->getEnv($site_env)->getCommits()->getData();

        $hash_to_find = $options['commit'];
        $logs_url = null;

        foreach ($commits as $commit) {
            $hash = $commit->hash;
            
            if (!$hash_to_find) {
                if (!empty($commit->build_logs_url)) {
                    $logs_url = $commit->build_logs_url;
                    break;
                }
            } else {
                // It should start with the hash we're looking for.
                if (strpos($hash, $hash_to_find) === 0) {
                    if (!empty($commit->build_logs_url)) {
                        $logs_url = $commit->build_logs_url;
                        break;
                    }
                }
            }
        }

        if (!$logs_url) {
            if ($hash_to_find) {
                $this->log()->notice('No composer logs found for commit {hash}', ['hash' => $hash_to_find]);
                return;
            }
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

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
            $this->log()->notice('Could not retrieve composer logs for this environment.');
            return;
        }
        
        $data = $result->getData();
        if (empty($data)) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        // Now, some manipulation for the data.
        $data = html_entity_decode($data);
        $data = str_replace('<br />', "\n", $data);
        $data = str_replace('<br>', "\n", $data);
        $data = str_replace('&nbsp;', ' ', $data);
        $data = strip_tags($data);

        $this->output()->write($data);

    }

    /**
     * Show composer logs for recent update attempt.
     *
     * @command composer:logs-update
     * @param string $site_env Site & environment in the format `site-name.env`
     *
     * @authenticated
     */
    public function composerLogsUpdate($site_env)
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

        $workflow_id = $update_workflow->get('id');
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
            $this->log()->notice('Could not retrieve composer logs for this environment.');
            return;
        }
        
        $data = $result->getData();

        if (empty($data->tasks)) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        $logs_url = null;

        foreach ($data->tasks as $task) {
            if ($task->fn_name === 'queue_job_runner_task' && $task->params->task_type === 'job_runner_artifact_update') {
                if (empty($task->build_url)) {
                    $this->log()->notice('No composer logs found for this environment.');
                }
                $logs_url = $task->build_url;
            }
        }

        if (!$logs_url) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }


        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $logs_url = sprintf('%s://%s%s', $protocol, $host, $logs_url);

        $result = $this->request->request($logs_url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            $this->log()->notice('Could not retrieve composer logs for this environment.');
            return;
        }
        
        $data = $result->getData();
        if (empty($data)) {
            $this->log()->notice('No composer logs found for this environment.');
            return;
        }

        // Now, some manipulation for the data.
        $data = html_entity_decode($data);
        $data = str_replace('<br />', "\n", $data);
        $data = str_replace('<br>', "\n", $data);
        $data = str_replace('&nbsp;', ' ', $data);
        $data = strip_tags($data);

        $this->output()->write($data);

    }
}

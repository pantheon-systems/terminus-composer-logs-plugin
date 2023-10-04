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

        $dashboard_protocol = $this->getConfig()->get('dashboard_protocol');
        $dashboard_host = $this->getConfig()->get('dashboard_host');

        $logs_url = sprintf('%s://%s%s', $dashboard_protocol, $dashboard_host, $logs_url);

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
}

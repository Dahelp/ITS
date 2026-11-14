'use strict';

const {execFileSync} = require('node:child_process');

function validateDeployment({repository, ref, event, sha, checkout, master}) {
  if (repository !== 'Dahelp/ITS') throw new Error('Unexpected repository');
  if (ref !== 'refs/heads/master') throw new Error('Only the master branch may deploy');
  if (!['push', 'workflow_dispatch'].includes(event)) throw new Error('Unsupported deployment event');
  if (!/^[0-9a-f]{40}$/.test(sha || '')) throw new Error('Invalid event commit');
  if (checkout !== sha) throw new Error('Checkout differs from the event commit');
  if (master !== sha) throw new Error('Stale commit: deploy only the current master HEAD');
}

async function checkCurrentMaster(env = process.env, fetchImpl = fetch) {
  const checkout = execFileSync('git', ['rev-parse', 'HEAD'], {encoding: 'utf8'}).trim();
  const response = await fetchImpl('https://api.github.com/repos/Dahelp/ITS/git/ref/heads/master', {
    headers: {
      Accept: 'application/vnd.github+json',
      Authorization: `Bearer ${env.GH_TOKEN || ''}`,
      'Cache-Control': 'no-cache',
      'User-Agent': 'its-center-production-guard',
    },
    signal: AbortSignal.timeout(20000),
  });
  if (!response.ok) throw new Error(`Cannot verify master HEAD (HTTP ${response.status})`);
  const data = await response.json();
  if (data.ref !== 'refs/heads/master' || data.object?.type !== 'commit') {
    throw new Error('Unexpected master reference response');
  }
  validateDeployment({repository: env.GITHUB_REPOSITORY, ref: env.GITHUB_REF,
    event: env.GITHUB_EVENT_NAME, sha: env.GITHUB_SHA, checkout, master: data.object.sha});
  console.log(`Production guard passed: current master ${data.object.sha}`);
}

module.exports = {validateDeployment, checkCurrentMaster};
if (require.main === module) checkCurrentMaster().catch(error => {
  console.error(`Deployment blocked: ${error.message}`);
  process.exitCode = 1;
});

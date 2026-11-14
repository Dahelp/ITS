'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const {validateDeployment} = require('../.github/scripts/require-current-master.cjs');
const sha = 'a'.repeat(40);
const valid = {repository: 'Dahelp/ITS', ref: 'refs/heads/master', event: 'push',
  sha, checkout: sha, master: sha};
assert.doesNotThrow(() => validateDeployment(valid));
assert.doesNotThrow(() => validateDeployment({...valid, event: 'workflow_dispatch'}));
for (const change of [
  {ref: 'refs/heads/codex/old-release'}, {ref: 'refs/tags/master'},
  {master: 'b'.repeat(40)}, {checkout: 'b'.repeat(40)},
  {event: 'pull_request'}, {repository: 'other/ITS'}, {sha: ''},
  {master: undefined},
]) assert.throws(() => validateDeployment({...valid, ...change}));
const workflow = fs.readFileSync('.github/workflows/deploy-beget.yml', 'utf8');
assert(workflow.includes('environment: production'));
assert(workflow.includes('group: its-center-production'));
assert(workflow.includes('cancel-in-progress: false'));
assert(!workflow.includes('secrets.BEGET_SSH_KEY'));
assert(workflow.includes('secrets.PRODUCTION_SSH_KEY'));
assert(workflow.indexOf('require-current-master.cjs') < workflow.indexOf('secrets.PRODUCTION_SSH_KEY'));
const steps = workflow.split(/\n      - name: /).slice(1);
for (const step of steps) {
  if (!/\n\s+(?:rsync |ssh -i)/.test(step)) continue;
  assert(step.includes('require-current-master.cjs'), `Missing fresh HEAD check: ${step.split('\n')[0]}`);
  if (!step.startsWith('Check SSH connection')) assert(step.includes('inputs.verify_only != true'));
}
console.log('Deployment guard: current master allowed; branches, tags, stale commits, checkout drift and lookup failures blocked; every server step guarded');

'use strict';
const fs=require('node:fs'),path=require('node:path'),crypto=require('node:crypto'),{execFileSync}=require('node:child_process');
const root=path.resolve(__dirname,'..'),out=path.join(root,'outputs/cr10-release'),snapshot=path.join(out,'snapshot');
const git=(...args)=>execFileSync('git',['-c','safe.directory=C:/xampp82/htdocs/its-center',...args],{cwd:root});
const sha=b=>crypto.createHash('sha256').update(b).digest('hex');
const runtime=['app/controllers/CartController.php','app/controllers/UserController.php','app/controllers/admin/OrderController.php','app/models/Order.php','app/models/User.php','app/services/CheckoutAccount.php','app/views/itscenter/Cart/view.php','app/views/itscenter/Cart/complete.php','app/views/itscenter/User/login.php','app/views/itscenter/User/recover.php','app/views/itscenter/User/recover-pass.php','app/views/itscenter/User/setup-password.php','app/views/itscenter/mail/mail_order.php','app/views/itscenter/mail/mail_recover.php','app/views/itscenter/partials/modals.php','public/js/checkout-progress.js','public/js/main.js'];
const tests=['tests/CheckoutAccountTest.php','tests/CheckoutGuestFlowTest.php','tests/CheckoutAccountMailTest.php','tests/CheckoutLoginFlowTest.php','tests/CheckoutLoginResumeTest.cjs','tests/CheckoutRecoverRoutesTest.php'];
const support=['.github/scripts/package-checkout-account.php','.github/scripts/smoke-checkout-account.php','.github/scripts/checkout-account-maintenance.php','docs/cr10-checkout-account.md','scripts/prepare_cr10_release.cjs',...tests];
function write(p,b){const dest=path.join(snapshot,p);fs.mkdirSync(path.dirname(dest),{recursive:true});fs.writeFileSync(dest,b);}
fs.mkdirSync(out,{recursive:true});
const manifest={};
for(const p of runtime){
    let before=null;
    try{before=git('show','origin/master:'+p);}catch(e){if(!/exists on disk, but not in|does not exist/.test(String(e.stderr)))throw e;}
    const content=fs.readFileSync(path.join(root,p),'utf8').replace(/\r\n/g,'\n');
    write(p,content);
    manifest[p]={before:before?sha(before):null,after:sha(Buffer.from(content))};
    if(before)manifest[p].before_lf=sha(Buffer.from(before.toString('utf8').replace(/\r\n/g,'\n')));
}
for(const p of support)write(p,fs.readFileSync(path.join(root,p)));
// Inherited dependencies are copied for local package checks, not committed again.
for(const p of ['.github/scripts/deploy-lead-forms.php','.github/scripts/require-current-master.cjs','tests/DeploymentGuardTest.cjs','app/services/CheckoutPayment.php'])write(p,git('show','origin/master:'+p));
write('.github/checkout-account-manifest.json',JSON.stringify(manifest,null,2)+'\n');
// Build on current master, preserving later gallery and checkout-resume releases.
let workflow=git('show','origin/master:.github/workflows/deploy-beget.yml').toString('utf8');
if(!workflow.includes('      checkout_account_only:')){
    workflow=workflow.replace('      verify_only:',`      checkout_account_only:
        description: Publish only CR-10 checkout account files with backup and drift checks
        type: boolean
        default: false

      verify_only:`);
    workflow=workflow.replace(/^        if: (.+)$/gm,(line,condition)=>condition.includes('inputs.verify_only != true')?'        if: inputs.checkout_account_only != true && '+condition:line);
    workflow+=`
      - name: Publish bounded checkout account release
        if: inputs.verify_only != true && inputs.checkout_account_only == true
        shell: bash
        run: |
          set -euo pipefail
          node .github/scripts/require-current-master.cjs
          php .github/scripts/package-checkout-account.php
          release="checkout-account-\${GITHUB_RUN_ID}-\${GITHUB_RUN_ATTEMPT}"
          ssh -i ~/.ssh/beget_deploy_key -p "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}" "umask 077; mkdir '$release'"
          node .github/scripts/require-current-master.cjs
          tar -C /tmp/checkout-account-package -czf /tmp/checkout-account.tar.gz .
          scp -i ~/.ssh/beget_deploy_key -P "\${{ secrets.BEGET_PORT }}" /tmp/checkout-account.tar.gz "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}:$release/package.tar.gz"
          node .github/scripts/require-current-master.cjs
          ssh -i ~/.ssh/beget_deploy_key -p "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}" "cd '$release' && tar -xzf package.tar.gz && php8.2 -d display_errors=stderr -d log_errors=0 install.php '\${{ secrets.BEGET_PATH }}'"
          node .github/scripts/require-current-master.cjs
          mkdir -p /tmp/checkout-account-reports
          scp -i ~/.ssh/beget_deploy_key -P "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}:$release/receipt.json" /tmp/checkout-account-reports/
          scp -i ~/.ssh/beget_deploy_key -P "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}:$release/smoke.json" /tmp/checkout-account-reports/

      - name: Save checkout account publication receipt
        if: inputs.verify_only != true && inputs.checkout_account_only == true
        uses: actions/upload-artifact@v4
        with:
          name: checkout-account-publication
          path: /tmp/checkout-account-reports/*.json
          retention-days: 30
`;
}
if(!workflow.includes('      checkout_account_maintenance:')){
    workflow=workflow.replace('      verify_only:',`      checkout_account_maintenance:
        description: Inspect or reset the explicitly authorized CR-10 test mailbox account
        type: choice
        default: none
        options: [none, inspect, reset]

      verify_only:`);
    workflow=workflow.replace(/^        if: (.+)$/gm,(line,condition)=>condition.includes('inputs.verify_only != true')?"        if: inputs.checkout_account_maintenance != 'inspect' && inputs.checkout_account_maintenance != 'reset' && "+condition:line);
    workflow+=`
      - name: Maintain authorized checkout test account
        if: inputs.verify_only != true && (inputs.checkout_account_maintenance == 'inspect' || inputs.checkout_account_maintenance == 'reset')
        shell: bash
        env:
          CR10_MODE: \${{ inputs.checkout_account_maintenance }}
        run: |
          set -euo pipefail
          case "$CR10_MODE" in inspect|reset) ;; *) exit 1 ;; esac
          node .github/scripts/require-current-master.cjs
          release="checkout-account-maintenance-\${GITHUB_RUN_ID}-\${GITHUB_RUN_ATTEMPT}"
          ssh -i ~/.ssh/beget_deploy_key -p "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}" "umask 077; mkdir '$release'"
          node .github/scripts/require-current-master.cjs
          scp -i ~/.ssh/beget_deploy_key -P "\${{ secrets.BEGET_PORT }}" .github/scripts/checkout-account-maintenance.php "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}:$release/maintenance.php"
          node .github/scripts/require-current-master.cjs
          ssh -i ~/.ssh/beget_deploy_key -p "\${{ secrets.BEGET_PORT }}" "\${{ secrets.BEGET_USER }}@\${{ secrets.BEGET_HOST }}" "php8.2 -d display_errors=stderr -d log_errors=0 '$release/maintenance.php' '\${{ secrets.BEGET_PATH }}' '$CR10_MODE'"
`;
}
write('.github/workflows/deploy-beget.yml',workflow);
const files=[...runtime,...support,'.github/checkout-account-manifest.json','.github/workflows/deploy-beget.yml'];
fs.writeFileSync(path.join(out,'files.json'),JSON.stringify(files,null,2));
fs.writeFileSync(path.join(out,'base.txt'),git('rev-parse','origin/master'));
console.log(JSON.stringify({files:files.length,runtime:runtime.length,snapshot,base:git('rev-parse','origin/master').toString().trim()}));

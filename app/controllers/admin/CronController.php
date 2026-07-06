<?php

namespace app\controllers\admin;

use app\models\admin\Cron;
use app\models\AppModel;
use ishop\App;

class CronController extends AppController {

    public function indexAction(){
		
		$crons = \R::getAll("SELECT * FROM cron ORDER BY name");
		
		$this->set(compact('crons'));
		
		$this->setMeta('CRON файлы');
	}
	
    public function redirectSeoAction()
    {
        if ((int)($_SESSION['user']['groups'] ?? 0) !== 1) {
            throw new \Exception('Forbidden', 403);
        }

        $result = null;
        $commandLabel = '';

        if (!empty($_POST['task'])) {
            $task = (string)$_POST['task'];
            $argsByTask = [
                'audit' => ['--audit-redirects'],
                'plan' => [],
                'apply' => ['--apply'],
                'cleanup-plan' => ['--cleanup-catalog-source-redirects'],
                'cleanup-apply' => ['--cleanup-catalog-source-redirects', '--apply'],
            ];

            if (!isset($argsByTask[$task])) {
                throw new \Exception('Unknown task', 400);
            }

            $result = $this->runRedirectSeoScript($argsByTask[$task]);
            $commandLabel = $task;
        }

        $this->setMeta('SEO редиректы фильтров');
        $this->set(compact('result', 'commandLabel'));
    }

	public function addAction() {
		
		if(!empty($_POST)){
            $cron = new Cron();
            $data = $_POST;
            $cron->load($data);

            if(!$cron->validate($data) || !$cron->checkUnique()){
                $cron->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($cron->save('cron', false)){
                $_SESSION['success'] = 'CRON добавлен';
            }
            redirect();
        }

        $this->setMeta('Добавить CRON задание');
		
	}
	
	public function editAction() {
		
		if(!empty($_POST)){
			$id = $this->getRequestID(false);
            $cron = new Cron();
            $data = $_POST;
            $cron->load($data);

            if(!$cron->validate($data)){
                $cron->getErrors();
                $_SESSION['form_data'] = $data;
                redirect();
            }

            if($cron->update('cron', $id)){
                $_SESSION['success'] = 'Изменения сохранены';
            }
            redirect();
        }
		$id = $this->getRequestID();
        $cron = \R::load('cron', $id);
        $this->setMeta('Редактировать CRON задание');
		$this->set(compact('cron'));
	}
	
	public function deleteAction(){
        $id = $this->getRequestID();        
        $cron = \R::load('cron', $id);
        \R::trash($cron);
        $_SESSION['success'] = 'Задание удалено';
        redirect();
    }
    private function runRedirectSeoScript(array $args): array
    {
        if (!function_exists('proc_open')) {
            return [
                'ok' => false,
                'command' => '',
                'output' => 'proc_open is disabled on this server',
                'code' => 1,
            ];
        }

        $phpCandidates = [
            '/usr/local/bin/php8.2',
            PHP_BINARY,
            'php',
        ];

        $phpBin = 'php';
        foreach ($phpCandidates as $candidate) {
            if ($candidate === 'php' || is_file($candidate)) {
                $phpBin = $candidate;
                break;
            }
        }

        $script = ROOT . '/scripts/fill_filter_parent_redirects.php';
        $cmdParts = [escapeshellarg($phpBin), escapeshellarg($script)];
        foreach ($args as $arg) {
            $cmdParts[] = escapeshellarg($arg);
        }

        $command = implode(' ', $cmdParts);
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, ROOT);
        if (!is_resource($process)) {
            return [
                'ok' => false,
                'command' => $command,
                'output' => 'Failed to start process',
                'code' => 1,
            ];
        }

        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $code = proc_close($process);
        $output = trim($stdout . ($stderr !== '' ? PHP_EOL . $stderr : ''));

        return [
            'ok' => $code === 0,
            'command' => $command,
            'output' => $output,
            'code' => $code,
        ];
    }

}

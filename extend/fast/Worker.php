<?php
namespace fast;
class Worker {

    const LOG_FILE_PATH = 'log/worker.log';
    const DAEMON_FILE = 'daemon.pid';

    private $pidPath;
    private $workerNum;
    private $logFp;
    private $pids = array();
    private $jobs = array();

    public function __construct($workerNum=4) {
        $this->curPath = basename(__FILE__);
        $this->workerNum = $workerNum;
    }

    private function checkPcntlModule() {

        if (!extension_loaded('pcntl')) {
            $msg = 'PHP not compiled with pcntl extension';
            throw new Exception($msg);
        }

    }

    private function daemon() {

        if (php_sapi_name != 'cli') {
            $msg = 'if you want run it, pls use cli';
            throw new Exception($msg);
        }

        if (file_exists($this->curPath . self::DAEMON_FILE)) {
            $msg = 'worker is running';
            throw new Exception($msg);
        }

        $pid = pcntl_fork();
        if ($pid < 0) {
            $msg = 'fork process failed';
            throw new Exception($msg);
        }
        if ($pid > 0) {
            exit;
        }
        posix_setsid();
        chdir('/');
        umask(0);
        fclose(STDIN);
        fclose(STDOUT);
        fclose(STDERR);

        $this->logFp = fopen($this->curPath . self::LOG_FILE_PATH, 'a');

        $this->createPidFile();
    }

    private function setSignal() {
        pcntl_signal(SIGUSR1, array($this, 'signalHandler'));
        pcntl_signal(SIGUSR2, array($this, 'signalHandler'));
    }

    public function signalHandler($signum) {
        switch ($signum) {
            case SIGUSR1 :
                if (!empty($this->pids)) {
                    foreach ($this->pids as $pid) {
                        posix_kill($pid, SIGKILL);
                    }
                }
                break;
            case SIGUSR2 :
                posix_kill(posix_getpid(), SIGKILL);
                break;
        }
    }

    private function createPidFile() {
        $fp = fopen($this->curPath . self::DAEMON_FILE, 'w');
        fwrite($fp, posix_getpid());
        fclose($fp);
    }

    private function forkWorker() {
        if (empty($this->jobs)) {
            $msg = 'give the empty jobs';
            $this->writeLog($msg);
            return false;
        }
        for ($i=0; $i<$this->workerNum; ++$i) {
            $pid = pcntl_fork();
            if ($pid < 0) {
                $msg = 'fork process failed';
                $this->writeLog($msg);
                exit;
            } elseif ($pid > 0) {
                $this->pids[] = $pid;
            } else {
                $job = $this->jobs[$i];
                call_user_func_array($job['func'], $job['args']);
                unset($this->jobs[$i]);
                exit;
            }
        }
    }

    private function destroyWorker() {
        foreach ($this->pids as $pid) {
            pcntl_waitpid($pid, $status, WUNTRACED);
        }
        if (file_exists($this->curPath . self::DAEMON_FILE)) {
            unlink($this->curPath . self::DAEMON_FILE);
        }
        fclose($this->fp);
    }

    public function addJob($job) {
        if (!isset($job['func']) || !isset($job['args'])) {
            return false;
        }
        $this->jobs[] = $jobs;
    }

    public function run() {
        $this->checkPcntlModule();
        $this->daemon();
        $this->setSignal();
        $this->forkWorker();
        $this->destroyWorker();
    }

    private function writeLog($strMsg) {
        $date = date('Y-m-d H:i:s', time());
        $msg = sprintf('[%s] [%s] ', $date, $strMsg);
        fwrite($this->logFp, $msg);
    }
}

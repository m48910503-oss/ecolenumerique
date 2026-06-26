<?php
declare(strict_types=1);

namespace App\Services\Web;

final class ProcessRunner
{
    /**
     * @return array{exitCode:int, output:string}
     */
    public function run(string $command): array
    {
        $command = trim($command);
        if ($command === '') {
            return ['exitCode' => 1, 'output' => ''];
        }

        if (function_exists('proc_open')) {
            $result = $this->runWithProcOpen($command);
            if ($result['exitCode'] === 0 || $result['output'] !== '') {
                return $result;
            }
        }

        if (function_exists('exec')) {
            return $this->runWithExec($command);
        }

        if (function_exists('shell_exec')) {
            return $this->runWithShellExec($command);
        }

        return ['exitCode' => 1, 'output' => ''];
    }

    /**
     * @return array{exitCode:int, output:string}
     */
    private function runWithProcOpen(string $command): array
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            return ['exitCode' => 1, 'output' => ''];
        }

        if (isset($pipes[0]) && is_resource($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = '';
        if (isset($pipes[1]) && is_resource($pipes[1])) {
            $stdout = (string) stream_get_contents($pipes[1]);
            fclose($pipes[1]);
        }

        $stderr = '';
        if (isset($pipes[2]) && is_resource($pipes[2])) {
            $stderr = (string) stream_get_contents($pipes[2]);
            fclose($pipes[2]);
        }

        $exitCode = (int) proc_close($process);
        $output = trim($stdout . ($stderr !== '' ? "\n" . $stderr : ''));

        return ['exitCode' => $exitCode, 'output' => $output];
    }

    /**
     * @return array{exitCode:int, output:string}
     */
    private function runWithExec(string $command): array
    {
        $output = [];
        $exitCode = 1;
        @exec($command . ' 2>&1', $output, $exitCode);

        return [
            'exitCode' => (int) $exitCode,
            'output' => trim(implode("\n", array_map('strval', $output))),
        ];
    }

    /**
     * @return array{exitCode:int, output:string}
     */
    private function runWithShellExec(string $command): array
    {
        $output = @shell_exec($command . ' 2>&1');

        return [
            'exitCode' => 0,
            'output' => is_string($output) ? trim($output) : '',
        ];
    }
}

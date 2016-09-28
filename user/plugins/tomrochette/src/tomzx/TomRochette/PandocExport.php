<?php

namespace tomzx\TomRochette;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

class PandocExport
{
	/**
	 * @param string $source
	 * @param string $destination
	 * @param string $from
	 * @param string $to
	 * @param array  $args
	 * @return int
	 */
	public function exportFile($source, $destination, $from, $to, $args = [])
	{
		if ( ! is_readable($source)) {
			throw new RuntimeException('Cannot open ' . $source . ' for reading.');
		}

		$executableName = 'pandoc';
		$executableFinder = new ExecutableFinder();
		$executable = $executableFinder->find($executableName);

		if ( ! $executable) {
			throw new RuntimeException('Cannot find executable ' . $executableName);
		}

		$args = array_merge([
			'--from=' . $from,
			'--to=' . $to,
			'--output=' . $destination,
			$source,
		], $args);

		$processBuilder = new ProcessBuilder();

		$process = null;
		if (strtolower(substr(PHP_OS, 0, 3)) === 'win') {
			$commandLine = $processBuilder->setPrefix($executable)
				->setArguments($args)
				->getProcess()
				->getCommandLine();

			$process = new Process($commandLine, dirname($source));
		} else {
			$process = $processBuilder->setPrefix($executable)
				->setArguments($args)
				->inheritEnvironmentVariables()
				->getProcess();
		}

		$process->run();

		return $process->getExitCode();
	}
}

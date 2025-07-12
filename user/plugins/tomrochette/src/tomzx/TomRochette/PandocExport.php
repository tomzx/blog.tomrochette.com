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
	 * @param bool   $preprocessLinks Whether to preprocess local links to remote URLs
	 * @return int
	 */
	public function exportFile($source, $destination, $from, $to, $args = [], $preprocessLinks = false)
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

		// If preprocessing is enabled, create a temporary file with processed content
		$tempSource = $source;
		if ($preprocessLinks) {
			$content = file_get_contents($source);
			$processedContent = $this->preprocessLinks($content);
			$tempSource = tempnam(sys_get_temp_dir(), 'pandoc_');
			file_put_contents($tempSource, $processedContent);
		}

		$args = array_merge([
			'--from=' . $from,
			'--to=' . $to,
			'--output=' . $destination,
			$tempSource,
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
				->setWorkingDirectory(dirname($source))
				->inheritEnvironmentVariables()
				->getProcess();
		}

		$process->run();

		// Clean up temporary file if it was created
		if ($preprocessLinks && $tempSource !== $source) {
			unlink($tempSource);
		}

		return $process->getExitCode();
	}

	/**
	 * Preprocess markdown content to replace local links with remote URLs
	 * 
	 * @param string $content The markdown content to process
	 * @return string The processed content with remote URLs
	 */
	private function preprocessLinks($content)
	{
		// Replace local markdown links with remote URLs
		// Pattern: [text](local-file.md) -> [text](https://blog.tomrochette.com/local-file)
		$content = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)]+\.md)\)/',
			function($matches) {
				$linkText = $matches[1];
				$localFile = $matches[2];
				
				// Remove .md extension and convert to remote URL
				$remotePath = str_replace('.md', '', $localFile);
				$remoteUrl = 'https://blog.tomrochette.com/' . $remotePath;
				
				return "[{$linkText}]({$remoteUrl})";
			},
			$content
		);

		// Also handle relative links without .md extension
		// Pattern: [text](local-file) -> [text](https://blog.tomrochette.com/local-file)
		$content = preg_replace_callback(
			'/\[([^\]]+)\]\(([^)]+)\)/',
			function($matches) {
				$linkText = $matches[1];
				$localPath = $matches[2];
				
				// Skip if it's already a remote URL or external link
				if (preg_match('/^https?:\/\//', $localPath) || preg_match('/^mailto:/', $localPath) || preg_match('/^#/', $localPath)) {
					return "[{$linkText}]({$localPath})";
				}
				
				// Skip if it's an anchor link or special link
				if (preg_match('/^[#!]/', $localPath)) {
					return "[{$linkText}]({$localPath})";
				}
				
				// Convert to remote URL
				$remoteUrl = 'https://blog.tomrochette.com/' . $localPath;
				
				return "[{$linkText}]({$remoteUrl})";
			},
			$content
		);

		return $content;
	}
}

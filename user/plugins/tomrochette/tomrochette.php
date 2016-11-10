<?php

namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Symfony\Component\Process\Process;
use tomzx\TomRochette\PandocExport;

class TomRochettePlugin extends Plugin
{
	public function onPageInitialized()
	{
		if ( ! array_key_exists('pdf', $_GET)) {
			return;
		}

		require_once __DIR__ . '/vendor/autoload.php';

		/** @var \Grav\Common\Page\Page $page */
		$page = $this->grav['page'];

		if ( ! $this->isCached($page)) {
			$this->generatePdf($page);
		}

		if ( ! $this->isCached($page)) {
			echo 'FAIL';
			die;
		}

		//Saves ie https problems
		header("Cache-Control: public");
		header("Content-Type: application/pdf");
		header('Pragma: private');
		header('Expires: 0');
		header('Content-Disposition: inline; filename="blog.tomrochette.com - ' . $page->title() . '.pdf"');
		echo $this->getCached($page);
		exit;
	}

	/**
	 * @param \Grav\Common\Page\Page $page
	 * @return string
	 */
	private function getCachedFile(Page $page)
	{
		// We can't use $page->modified() as it returns the cache mtime and not the page mtime
		$modifiedTime = filemtime($page->filePath());
		return $page->path() . '/' . $page->slug() . '-' . $modifiedTime . '.pdf';
	}

	/**
	 * @param \Grav\Common\Page\Page $page
	 * @return bool
	 */
	private function isCached(Page $page)
	{
		$cacheFilename = $this->getCachedFile($page);
		return is_readable($cacheFilename);
	}

	/**
	 * @param \Grav\Common\Page\Page $page
	 * @return int
	 */
	private function getCached(Page $page)
	{
		$cacheFilename = $this->getCachedFile($page);
		return readfile($cacheFilename);
	}

	/**
	 * @param \Grav\Common\Page\Page $page
	 * @return int
	 */
	private function generatePdf(Page $page)
	{
		$hash = $this->getHash($page);
		$args = [
			'--variable=author:Tom Rochette <tom.rochette@coreteks.org>',
			'--metadata=author:Tom Rochette',
			'--variable=colorlinks',
			'--number-sections',
			'--variable=date:' . date('F j, Y', $page->date()),
			'--variable=commit-url:https://github.com/tomzx/blog.tomrochette.com-content/blob/'.$hash.$page->url().'/'.$page->name(),
			'--variable=commit:'.$hash,
			'--template='.__DIR__.'/assets/latex/default.tex',
		];
		$pandocExporter = new PandocExport();
		return $pandocExporter->exportFile($page->filePath(), $this->getCachedFile($page), 'markdown+lists_without_preceding_blankline', 'latex', $args);
	}

	private function getHash(Page $page)
	{
		$path = $page->filePath();

		$process = new Process('git log -1 --pretty=format:%h ' . $path, $page->path());
		$process->run();

		return $process->getOutput();
	}
}

<?php

namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
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

		$filename = $page->slug();
		//Saves ie https problems
		header("Cache-Control: public");
		header("Content-Type: application/pdf");
		header('Pragma: private');
		header('Expires: 0');
		header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
		echo $this->getCached($page);
	}

	/**
	 * @param \Grav\Common\Page\Page $page
	 * @return string
	 */
	private function getCachedFile(Page $page)
	{
		return $page->path() . '/' . $page->slug() . '-' . $page->modified() . '.pdf';
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
		$args = [
			'--variable=author:Tom Rochette',
			'--metadata=author:Tom Rochette',
			'--variable=colorlinks',
			'--number-sections',
		];
		$pandocExporter = new PandocExport();
		return $pandocExporter->exportFile($page->filePath(), $this->getCachedFile($page), 'markdown', 'latex', $args);
	}
}

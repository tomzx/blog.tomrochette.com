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
        if ( ! isset($_GET['format'])) {
            return;
        }

        $format = strtolower($_GET['format']);
        $formats = ['pdf', 'epub'];
        if ( ! in_array($format, $formats)) {
            return;
        }

        require_once __DIR__ . '/vendor/autoload.php';

        /** @var \Grav\Common\Page\Page $page */
        $page = $this->grav['page'];

        if ( ! $this->isCached($page, $format)) {
            if ($format === 'pdf') {
                $this->generatePdf($page);
            } elseif ($format === 'epub') {
                $this->generateEpub($page);
            }
        }

        if ( ! $this->isCached($page, $format)) {
            echo 'FAIL';
            die;
        }

        //Saves ie https problems
        header("Cache-Control: public");
        if ($format === 'pdf') {
            header("Content-Type: application/pdf");
        } elseif ($format === 'epub') {
            header("Content-Type: application/epub");
        }
        header('Pragma: private');
        header('Expires: 0');
        header('Content-Disposition: inline; filename="blog.tomrochette.com - ' . $page->title() . '.'.$format.'"');
        echo $this->getCached($page, $format);
        exit;
    }

    /**
     * @param \Grav\Common\Page\Page $page
     * @param string $format
     * @return string
     */
    private function getCachedFile(Page $page, $format)
    {
        // We can't use $page->modified() as it returns the cache mtime and not the page mtime
        $modifiedTime = filemtime($page->filePath());
        return $page->path() . '/' . $page->slug() . '-' . $modifiedTime . '.' . $format;
    }

    /**
     * @param \Grav\Common\Page\Page $page
     * @param string $format
     * @return bool
     */
    private function isCached(Page $page, $format)
    {
        $cacheFilename = $this->getCachedFile($page, $format);
        return is_readable($cacheFilename);
    }

    /**
     * @param \Grav\Common\Page\Page $page
     * @param string $format
     * @return int
     */
    private function getCached(Page $page, $format)
    {
        $cacheFilename = $this->getCachedFile($page, $format);
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
            '--variable=commit-url:https://github.com/tomzx/blog.tomrochette.com-content/blob/' . $hash . $page->url() . '/' . $page->name(),
            '--variable=commit:' . $hash,
            '--template=' . __DIR__ . '/assets/latex/default.tex',
            '--variable=geometry:margin=1in',
        ];
        $pandocExporter = new PandocExport();
        return $pandocExporter->exportFile($page->filePath(), $this->getCachedFile($page, 'pdf'), 'markdown+lists_without_preceding_blankline', 'latex', $args);
    }

    /**
     * @param \Grav\Common\Page\Page $page
     * @return int
     */
    private function generateEpub(Page $page)
    {
        $hash = $this->getHash($page);
        $args = [
            '--variable=author:Tom Rochette &lt;<a href="mailto:tom.rochette@coreteks.org">tom.rochette@coreteks.org</a>&gt;',
            '--metadata=author:Tom Rochette',
            '--variable=colorlinks',
            '--number-sections',
            '--variable=date:' . date('F j, Y', $page->date()),
            '--variable=commit-url:https://github.com/tomzx/blog.tomrochette.com-content/blob/' . $hash . $page->url() . '/' . $page->name(),
            '--variable=commit:' . $hash,
            '--template=' . __DIR__ . '/assets/epub/default.html',
            '--epub-stylesheet=' . __DIR__ . '/assets/epub/default.css',
        ];
        $pandocExporter = new PandocExport();
        return $pandocExporter->exportFile($page->filePath(), $this->getCachedFile($page, 'epub'), 'markdown+lists_without_preceding_blankline', 'epub3', $args);
    }

    /**
     * @param \Grav\Common\Page\Page $page
     * @return string
     */
    private function getHash(Page $page)
    {
        $path = $page->filePath();

        $process = new Process('git log -1 --pretty=format:%h ' . $path, $page->path());
        $process->run();

        return $process->getOutput();
    }
}

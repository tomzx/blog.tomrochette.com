<?php
namespace Grav\Plugin;

use Grav\Common\Data;
use Grav\Common\Plugin;

class DefaultTemplatePlugin extends Plugin
{
	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [
			'onPluginsInitialized' => ['onPluginsInitialized', 0],
		];
	}

	/**
	 * Activate feed plugin only if feed was requested for the current page.
	 *
	 * Also disables debugger.
	 */
	public function onPluginsInitialized()
	{
		if ($this->isAdmin()) {
			$this->active = false;
			return;
		}

		$this->enable([
			'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
		]);
	}
	/**
	 * Set needed variables to display the feed.
	 */
	public function onTwigSiteVariables()
	{
		/** @var \Grav\Common\Page\Page $page */
		$page = $this->grav['page'];
		$hasTemplate = isset($page->header()->template);
		if ($hasTemplate) {
			return;
		}

		$default_template = $this->config->get('plugins.defaulttemplate.default_template');
		$twig = $this->grav['twig'];
		$twig->template = $default_template.'.html.twig';
	}
}

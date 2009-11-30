<?php

/**
 * Base class for all application presenters.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
abstract class BasePresenter extends Presenter
{
	/**
	 * Menu structure (for simplycity just in assoc. array destination => title)
	 * @var array
	 */
	public $menu = array(
		'Page:about' => 'About',
		'Article:list' => 'Articles',
		'Page:foto' => 'Fotogalery',
		'Page:guestbook' => 'Guest book',
		'Page:contact' => 'Contact',
	);


	protected function startup() {
		$this->authCheck();
		parent::startup();
	}



	protected function beforeRender() {
		$this->template->user = $user = Environment::getUser();
		$this->template->menu = $this->menu;
	}


	/**
	 * @return Nette\Templates\ITemplate
	 */
	protected function createTemplate() {
		$translator = new GettextTranslator('%appDir%/locale/en.mo');
		$template = parent::createTemplate();
		$template->setTranslator($translator);
		return $template;
	}


	/**
	 * Provides an authentication check on methods and classed marked with annotation @secured.
	 * @return void
	 */
	protected function authCheck() {
		$annotation = 'secured';

		$actionMethod = $this->formatActionMethod($this->action);
		$signalMethod = $this->formatSignalMethod($this->signal);
		$renderMethod = $this->formatRenderMethod($this->view);

		if ($this->hasAnnotation($annotation)) {
			$authenticate = TRUE;
		} elseif ($this->hasMethodAnnotation($actionMethod, $annotation)) {
			$authenticate = TRUE;
		} elseif ($this->isSignalReceiver($this) && $this->hasMethodAnnotation($signalMethod, $annotation)) {
			$authenticate = TRUE;
		} elseif ($this->hasMethodAnnotation($renderMethod, $annotation)) {
			$authenticate = TRUE;
		} else {
			$authenticate = FALSE;
		}

		$user = Environment::getUser();
		if ($authenticate && !$user->isAuthenticated()) {
			if ($user->getSignOutReason() === User::INACTIVITY) {
				$this->flashMessage('You have been logged out due to inactivity. Please login again.', 'success');
			}
			$backlink = $this->getApplication()->storeRequest();
			$this->redirect('Auth:login', array('backlink' => $backlink));
		}
	}


	/**
	 * Checks if class has a given annotation.
	 * @param string $annotation
	 * @return bool
	 */
	protected function hasAnnotation($annotation) {
		return Annotations::has($this->getReflection(), $annotation);
	}


	/**
	 * Checks if given method has a given annotation.
	 * @param string $method
	 * @param string $annotation
	 * @return bool
	 */
	protected function hasMethodAnnotation($method, $annotation) {
		if (!$this->getReflection()->hasMethod($method)) return FALSE;

		$rm = new ReflectionMethod($this->getClass(), $method);
		return Annotations::has($rm, $annotation);
	}
}

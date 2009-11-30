<?php

/**
 * Authentication presenter.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
class AuthPresenter extends BasePresenter
{
	/** @persistent */
	public $backlink = '';



	/**
	 * Login form component factory.
	 * @return mixed
	 */
	protected function createComponentLoginForm($name)
	{
		$form = new AppForm($this, $name);
		$form->addText('login', 'Username:')
			->addRule(Form::FILLED, 'Please provide a username.');

		$form->addPassword('password', 'Password:')
			->addRule(Form::FILLED, 'Please provide a password.');

		$form->addSubmit('send', 'Log in!');
		$form->onSubmit[] = array($this, 'loginFormSubmitted');
		$form->addProtection('Please submit this form again (security token has expired).');
	}

	
	public function loginFormSubmitted($form)
	{
		try {
			$user = Environment::getUser();
			$user->authenticate($form['login']->value, $form['password']->value);
			$this->flashMessage('You was successfully logged in.', 'success');
			$this->getApplication()->restoreRequest($this->backlink);
			$this->redirect('Article:list');

		} catch (AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}


	public function actionLogout()
	{
		Environment::getUser()->signOut();
		$this->flashMessage('You was successfully logged out.');
		$this->redirect('Article:list');
	}
}

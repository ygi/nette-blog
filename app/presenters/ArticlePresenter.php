<?php

/**
 * Article presenter.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
class ArticlePresenter extends BasePresenter {
	
	/** @persistent int*/
	public $id;

	/** @var ArticlesModel */
	public $model;


	protected function startup() {
		$this->model = new ArticlesModel();
		parent::startup();
	}


	/** @secured */
	public function handleDelete($id) {
	
		$user = Environment::getUser();
		if (!$user->isAuthenticated()) {
			$this->redirect('Auth:login');
		}
	
		$this->invalidateControl('flashes');
		$this->invalidateControl('list');

		try {
			$this->model->delete($id);
			$this->flashMessage("Article successfully deleted.", 'success');

		} catch (DibiDriverException $e) {
			$this->flashMessage("There was an error during deleting of article, please try again later.", 'error');
		}

		if (!$this->isAjax()) $this->redirect('list');
	}


	/** @secured */
	public function handleDeleteComment($commentId) {

		$user = Environment::getUser();
		if (!$user->isAuthenticated()) {
			$this->redirect('Auth:login');
		}

		$this->invalidateControl('flashes');
		$this->invalidateControl('comments');

		try {
			$model = new CommentsModel();
			$model->delete($commentId);
			$this->flashMessage("Comment successfully deleted.", 'success');

		} catch (DibiDriverException $e) {
			$this->flashMessage("There was an error during deleting of comment, please try again later.", 'error');
		}

		if (!$this->isAjax()) $this->redirect('this');
	}


	/** @secured */
	public function renderEdit($id) {
		$data = $this->model->find($id)->fetch();
		$this->getComponent('articleForm')->setValues($data);
	}


	/** @secured */
	public function renderAdd() {}


	public function renderShow($id) {
		if ($this->model->articleExists($id)) {
			$this->template->article = $this->model->fetch($id);
			$this->template->comments = $this->model->fetchAllComments($id);

		} else {
			$this->flashMessage("Article $id not exists.", 'error');
			$this->id = NULL;
			$this->redirect('list');
		}
	}


	public function renderList() {
		if (isset($this->id) && !$this->isAjax()) {
			$this->redirect(303, 'list', array('id' => NULL));
		}

		$this->template->articles = $this->model->findAll();
	}


	public function createComponentArticleForm($name) {
		$form = new AppForm($this, $name);
		$form->addHidden('createdAt')->setDefaultValue(date('Y-m-d H:i:s', time()));
		$form->addText('title', 'Title:', 60)
			->addRule(Form::FILLED, "Please provide article's title.");
		$form->addText('author', 'Author:', 60)
			->addRule(Form::FILLED, "Please provide name of author.");
		$form->addTextArea('text', NULL, 48, 16)
			->addRule(Form::FILLED, "Please provide article's text.");

		$form->addSubmit('send', 'Save!');
		$form->onSubmit[] = array($this, 'handleArticleFormSubmitted');
	}


	public function handleArticleFormSubmitted(AppForm $form) {
		$model = new ArticlesModel();
		$data = $form->getValues();

		try {
			switch ($this->action) {
				case 'add':
					$this->id = $model->insert($data);
					$msg = "Article was successfully added.";
					break;

				case 'edit':
					$model->update($this->id, $data);
					$msg = "Article was successfully edited.";
					break;

				default: throw new InvalidStateException("Unknown action for article manipulation.");
			}
			
			$this->flashMessage($msg, 'success');
			$this->redirect(303, 'show', $this->id);

		} catch (DibiDriverException $e) {
			$this->flashMessage("There was an error during editing of article, please try again.", 'error');
		}

		if (!$this->isAjax()) $this->redirect('this');
	}


	public function createComponentCommentForm($name) {
		$form = new AppForm($this, $name);
		$form->addHidden('articleId')->setDefaultValue($this->id);
		$form->addHidden('createdAt')->setDefaultValue(date('Y-m-d H:i:s', time()));
		$form->addText('author', 'Your name:', 30)
			->addRule(Form::FILLED, "Please provide your name.");
		$form->addTextArea('text', NULL, 48, 5)
			->addRule(Form::FILLED, "Please fill the comment's text.");

		$form->addSubmit('send', 'Comment!');
		$form->onSubmit[] = array($this, 'handleCommentFormSubmitted');

		$form->getElementPrototype()->addClass('ajax');
	}


	public function handleCommentFormSubmitted(AppForm $form) {

		$comments = new CommentsModel();
		$data = $form->getValues();

		try {
			$comments->insert($data);
			$this->flashMessage("Your comment was successfully added.", 'success');

		} catch (DibiDriverException $e) {
			$this->flashMessage("There was an error during adding of your comment, please try again.", 'error');
		}

		if ($this->isAjax()) {
			$form['author']->value = NULL;
			$form['text']->value = NULL;

			$this->invalidateControl('flashes');
			$this->invalidateControl('comments');
			$this->invalidateControl('form');
			
		} else {
			$this->redirect('this');
		}
	}
}

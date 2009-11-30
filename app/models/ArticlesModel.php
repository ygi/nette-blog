<?php

/**
 * Articles model.
 *
 * @author     Roman Sklenář
 * @package    Blog
 */
class ArticlesModel extends BaseModel
{
	/** @var string */
	const TABLE = 'Articles';

	/** @var string  primary key column name */
	const PRIMARY = 'id';

	
	/**
	 * Does article with $id exist in database?
	 * @param int $id
	 * @return bool
	 */
	public function articleExists($id) {
		return (bool) $this->find($id)->count() == 1;
	}


	/**
	 * Has article any comment?
	 * @param int $articleId
	 * @return bool
	 */
	public function hasComments($articleId) {
		return (bool) $this->findAllComments($articleId)->count() > 0;
	}


	/**
	 * Finds all comments to relevant article id.
	 * @param int $articleId
	 * @return DibiFluent
	 */
	public function findAllComments($articleId) {
		return $this->getConnection()->select('*')->from(CommentsModel::TABLE)->where('[articleId]=%i', $articleId);
	}


	/**
	 * Gets all comments to relevant article id in decreasing order.
	 * @param int $articleId
	 * @return NULL|array of DibiRow
	 */
	public function fetchAllComments($articleId) {
		return $this->findAllComments($articleId)->orderBy('[createdAt]')->fetchAll();
	}

	
	/**
	 * Deletes all comments to relevant article id.
	 * @param int $articleId
	 * @return int
	 */
	public function deleteAllComments($articleId) {
		$comments = $this->findAllComments($articleId)->fetchAssoc(CommentsModel::PRIMARY);

		$model = new CommentsModel();
		return $model->delete($comments);
	}


	/**
	 * Deletes row(s) matching primary key.
	 * @param int $id
	 * @return int
	 */
	public function delete($id) {
		$articles = is_array($id) ? $id : array($id);
		$affected = 0;

		try {
			$this->getConnection()->begin();
			$affected = parent::delete($articles);

			foreach ($articles as $articleId) {
				$this->deleteAllComments($articleId);
			}
			$this->getConnection()->commit();

		} catch (DibiException $e) {
			$this->getConnection()->rollback();
			throw $e;
		}
		
		return $affected;
	}

}
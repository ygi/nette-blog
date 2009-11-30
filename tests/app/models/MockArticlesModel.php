<?php


class MockArticlesModel extends ArticlesModel
{
	public function findAllComments($articleId) {
		return parent::findAllComments($articleId);
	}

}
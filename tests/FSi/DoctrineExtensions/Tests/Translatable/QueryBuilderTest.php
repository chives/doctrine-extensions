<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\Tests\Translatable;

use Doctrine\ORM\Query\Expr;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Category;
use FSi\DoctrineExtensions\Tests\Translatable\Fixture\Comment;
use FSi\DoctrineExtensions\Translatable\Query\QueryBuilder;

class QueryBuilderTest extends BaseTranslatableTest
{
    protected function getUsedEntityFixtures()
    {
        return [
            self::CATEGORY,
            self::SECTION,
            self::COMMENT,
            self::ARTICLE,
            self::ARTICLE_TRANSLATION
        ];
    }

    public function testJoinTranslationWithWrongJoinType()
    {
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');

        $this->setExpectedException(
            'FSi\DoctrineExtensions\Exception\InvalidArgumentException',
            'Unknown join type "RIGHT"'
        );

        $qb->joinTranslations('a.translations', 'RIGHT');
    }

    public function testJoinTranslationWithAllDefaultArguments()
    {
        $qb = new QueryBuilder($this->_em);
        $qb->from(self::ARTICLE, 'a');
        $qb->select('a');
        $qb->joinTranslations('a.translations');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a LEFT JOIN a.translations atranslations',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $qb->getQuery()->execute();
    }

    public function testJoinTranslationWithDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsenloc'));

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testInnerJoinTranslationWithCustomLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, $this->_languagePl);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    INNER JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('atranslationsplloc'));

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testJoinTranslationWithAllCustomParameters()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->joinTranslations('a.translations', Expr\Join::INNER_JOIN, $this->_languagePl, 't', 'locale');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    INNER JOIN a.translations t WITH t.locale = :locale',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertNotNull($qb->getParameter('locale'));

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('locale')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithCurrentLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE atranslationsen.title = :atitleval',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languagePl);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE WHEN atranslationspl.id IS NOT NULL THEN atranslationspl.title ELSE atranslationsen.title END = :atitleval',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithCustomAndDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languagePl);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title', $this->_languageDe);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsde WITH atranslationsde.locale = :atranslationsdeloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE WHEN atranslationsde.id IS NOT NULL THEN atranslationsde.title ELSE atranslationsen.title END = :atitleval',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageDe,
            $qb->getParameter('atranslationsdeloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereWithSameCurrentAndDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'title', 'some title');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE atranslationsen.title = :atitleval',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithCurrentLocale()
    {
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableOrderBY('a', 'title', 'ASC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atranslationsen.title ASC',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languagePl);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a, CASE WHEN atranslationspl.id IS NOT NULL THEN atranslationspl.title ELSE atranslationsen.title END HIDDEN atitle
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atitle DESC',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithCustomAndDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languagePl);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC', $this->_languageDe);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a, CASE WHEN atranslationsde.id IS NOT NULL THEN atranslationsde.title ELSE atranslationsen.title END HIDDEN atitle
                FROM %s a
                    LEFT JOIN a.translations atranslationsde WITH atranslationsde.locale = :atranslationsdeloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atitle DESC',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageDe,
            $qb->getParameter('atranslationsdeloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableOrderByWithSameCurrentAndDefaultLocale()
    {
        $this->_translatableListener->setDefaultLocale($this->_languageEn);
        $this->_translatableListener->setLocale($this->_languageEn);
        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableOrderBy('a', 'title', 'DESC');

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                ORDER BY atranslationsen.title DESC',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNull()
    {
        $this->_translatableListener->setLocale($this->_languagePl);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE SIZE(atranslationspl.comments) = 0',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithNull()
    {
        $this->_translatableListener->setLocale($this->_languagePl);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                WHERE SIZE(a.categories) = 0',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObject()
    {
        $this->_translatableListener->setLocale($this->_languagePl);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE :acommentsval MEMBER OF atranslationspl.comments',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithObject()
    {
        $category = new Category();
        $category->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', $category);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                WHERE :acategoriesval MEMBER OF a.categories',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $category,
            $qb->getParameter('acategoriesval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArray()
    {
        $this->_translatableListener->setLocale($this->_languagePl);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                WHERE atranslationsplcommentsjoin IN(:acommentsval)',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnCollectionWithArray()
    {
        $category1 = new Category();
        $category1->setId(1);
        $category2 = new Category();
        $category2->setId(2);
        $whereCategories = [$category1, $category2];

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'categories', $whereCategories);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.categories acategoriesjoin
                WHERE acategoriesjoin IN(:acategoriesval)',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $whereCategories,
            $qb->getParameter('acategoriesval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNullAndSameDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languagePl);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE SIZE(atranslationspl.comments) = 0',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObjectAndSameDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languagePl);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                WHERE :acommentsval MEMBER OF atranslationspl.comments',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArrayAndSameDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languagePl);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                WHERE atranslationsplcommentsjoin IN(:acommentsval)',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithNullAndDifferentDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', null);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND SIZE(atranslationspl.comments) = 0 THEN TRUE
                    WHEN SIZE(atranslationsen.comments) = 0 THEN TRUE
                    ELSE FALSE
                END = TRUE',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithObjectAndDifferentDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $comment = new Comment();
        $comment->setId(1);

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $comment);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND :acommentsval MEMBER OF atranslationspl.comments THEN TRUE
                    WHEN :acommentsval MEMBER OF atranslationsen.comments THEN TRUE
                    ELSE FALSE
                END = TRUE',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $this->assertEquals(
            $comment,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    public function testTranslatableWhereOnTranslatableCollectionWithArrayAndDifferentDefaultLocale()
    {
        $this->_translatableListener->setLocale($this->_languagePl);
        $this->_translatableListener->setDefaultLocale($this->_languageEn);

        $comment1 = new Comment();
        $comment1->setId(1);
        $comment2 = new Comment();
        $comment2->setId(2);
        $whereComments = [$comment1, $comment2];

        $qb = new QueryBuilder($this->_em);
        $qb->select('a');
        $qb->from(self::ARTICLE, 'a');
        $qb->addTranslatableWhere('a', 'comments', $whereComments);

        $this->assertEquals(
            $this->normalizeDql(sprintf('
                SELECT a
                FROM %s a
                    LEFT JOIN a.translations atranslationspl WITH atranslationspl.locale = :atranslationsplloc
                    LEFT JOIN a.translations atranslationsen WITH atranslationsen.locale = :atranslationsenloc
                    LEFT JOIN atranslationspl.comments atranslationsplcommentsjoin
                    LEFT JOIN atranslationsen.comments atranslationsencommentsjoin
                WHERE CASE
                    WHEN atranslationspl.id IS NOT NULL AND atranslationsplcommentsjoin IN(:acommentsval) THEN TRUE
                    WHEN atranslationsencommentsjoin IN(:acommentsval) THEN TRUE
                    ELSE FALSE
                END = TRUE',
                self::ARTICLE
            )),
            $qb->getDQL()
        );

        $this->assertEquals(
            $this->_languagePl,
            $qb->getParameter('atranslationsplloc')->getValue()
        );

        $this->assertEquals(
            $this->_languageEn,
            $qb->getParameter('atranslationsenloc')->getValue()
        );

        $this->assertEquals(
            $whereComments,
            $qb->getParameter('acommentsval')->getValue()
        );

        $qb->getQuery()->execute();
    }

    private function normalizeDql($dql)
    {
        return preg_replace('/\s+/', ' ', trim($dql));
    }
}

<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;

/**
 * This provides access to the Contao database.
 */
class ContaoDatabase
{
    /** The connection. */
    private Connection $connection;

    /**
     * Create a new instance.
     *
     * @param Connection $connection The connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch the root pages from the database.
     *
     * @return list<array{language: string, id: int, fallback: string}>
     */
    public function getRootPages(): array
    {
        /** @psalm-suppress TooManyArguments - select accepts more than one argument. */
        $rows = $this->executeQuery($this->connection->createQueryBuilder()
            ->select('language', 'id', 'fallback')
            ->from('tl_page')
            ->where('type=:type')
            ->setParameter('type', 'root')
            ->orderBy('fallback')
            ->addOrderBy('sorting'));
        $result = [];
        while ($row = $rows->fetchAssociative()) {
            /** @var array{language: string, id: string, fallback: string} $row */
            $result[] = [
                'language' => $row['language'] ,
                'id' => (int) $row['id'],
                'fallback' => $row['fallback'],
            ];
        }
        return $result;
    }

    /**
     * Fetch pages by pid.
     *
     * @param list<int> $pidList The pid list.
     *
     * @return list<array{id: int, pid: int, languageMain: int, type: string}>
     */
    public function getPagesByPidList(array $pidList): array
    {
        /** @psalm-suppress TooManyArguments - select accepts more than one argument. */
        $rows = $this->executeQuery($this->connection->createQueryBuilder()
            ->select('id', 'pid', 'languageMain', 'type')
            ->from('tl_page')
            ->where('pid IN (:lookupQueue)')
            ->setParameter('lookupQueue', $pidList, Connection::PARAM_INT_ARRAY)
            ->orderBy('sorting'));
        $result = [];
        while ($row = $rows->fetchAssociative()) {
            /** @var array{id: string, pid: string, languageMain: string, type: string} $row */
            $result[] = [
                'id' => (int) $row['id'],
                'pid' => (int) $row['pid'],
                'languageMain' => (int) $row['languageMain'] ,
                'type' => $row['type'],
            ];
        }
        return $result;
    }

    /**
     * Fetch articles by pid.
     *
     * @param int         $pageId   The page id.
     * @param string|null $inColumn The optional column to filter by.
     *
     * @return list<array{id: int, pid: int, languageMain: int, inColumn: string}>
     */
    public function getArticlesByPid(int $pageId, ?string $inColumn = null): array
    {
        /** @psalm-suppress TooManyArguments - select accepts more than one argument. */
        $builder = $this->connection->createQueryBuilder()
            ->select('id', 'pid', 'languageMain', 'inColumn')
            ->from('tl_article')
            ->where('pid=:pid')
            ->setParameter('pid', $pageId)
            ->orderBy('inColumn')
            ->addOrderBy('sorting');
        if (null !== $inColumn) {
            $builder
                ->andWhere('inColumn=:inColumn')
                ->setParameter('inColumn', $inColumn);
        }

        $rows = $this->executeQuery($builder);
        $result = [];
        while ($row = $rows->fetchAssociative()) {
            /** @var array{id: string, pid: string, languageMain: string, inColumn: string} $row */
            $result[] = [
                'id' => (int) $row['id'],
                'pid' => (int) $row['pid'],
                'languageMain' => (int) $row['languageMain'] ,
                'inColumn' => $row['inColumn'],
            ];
        }
        return $result;
    }

    /**
     * Fetch all content elements from an article.
     *
     * @param int    $articleId The article id of the parenting article.
     * @param string $pTable    The parenting table.
     *
     * @return list<array{id: int, type: string}>
     */
    public function getContentByPidFrom(int $articleId, string $pTable): array
    {
        /** @psalm-suppress TooManyArguments - select accepts more than one argument. */
        $rows = $this->executeQuery($this->connection->createQueryBuilder()
            ->select('id', 'type')
            ->from('tl_content')
            ->where('pid=:pid')
            ->setParameter('pid', $articleId)
            ->andWhere('ptable=:ptable')
            ->setParameter('ptable', $pTable)
            ->orderBy('sorting'));

        $result = [];
        while ($row = $rows->fetchAssociative()) {
            /** @var array{id: string, type: string} $row */
            $result[] = [
                'id' => (int) $row['id'],
                'type' => $row['type'],
            ];
        }

        return $result;
    }

    public function getConnection(): Connection
    {
        return $this->connection;
    }

    /**
     * TODO: To be removed when we drop support for doctrine/dbal 2.13.
     */
    private function executeQuery(QueryBuilder $queryBuilder): Result
    {
        return $this
            ->connection
            ->executeQuery($queryBuilder->getSQL(), $queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
    }
}

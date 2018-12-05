<?php

/**
 * This file is part of cyberspectrum/i18n-contao.
 *
 * (c) 2018 CyberSpectrum.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    cyberspectrum/i18n-contao
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @copyright  2018 CyberSpectrum.
 * @license    https://github.com/cyberspectrum/i18n-contao/blob/master/LICENSE MIT
 * @filesource
 */

declare(strict_types = 1);

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use Doctrine\DBAL\Connection;

/**
 * This provides access to the Contao database.
 */
class ContaoDatabase
{
    /**
     * The connection.
     *
     * @var Connection
     */
    private $connection;

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
     * @return array
     */
    public function getRootPages(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('language', 'id', 'fallback')
            ->from('tl_page')
            ->where('type=:type')
            ->setParameter('type', 'root')
            ->orderBy('fallback')
            ->addOrderBy('sorting')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch pages by pids.
     *
     * @param array $pidList The pid list.
     *
     * @return array
     */
    public function getPagesByPidList(array $pidList): array
    {
        return $this->connection->createQueryBuilder()
            ->select('id', 'pid', 'languageMain', 'type')
            ->from('tl_page')
            ->where('pid IN (:lookupQueue)')
            ->setParameter('lookupQueue', $pidList, Connection::PARAM_INT_ARRAY)
            ->orderBy('sorting')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch articles by pid.
     *
     * @param int         $pageId   The page id.
     * @param string|null $inColumn The optional column to filter by.
     *
     * @return array
     */
    public function getArticlesByPid(int $pageId, string $inColumn = null): array
    {
        $builder = $this->connection->createQueryBuilder()
            ->select('id', 'pid', 'inColumn', 'languageMain')
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

        return $builder
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch all content elements from an article.
     *
     * @param int    $articleId The article id of the parenting article.
     * @param string $pTable    The parenting table.
     *
     * @return array
     */
    public function getContentByPidFrom(int $articleId, string $pTable): array
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id', 'type')
            ->from('tl_content')
            ->where('pid=:pid')
            ->setParameter('pid', $articleId)
            ->andWhere('ptable=:ptable')
            ->setParameter('ptable', $pTable)
            ->orderBy('sorting')
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Get the connection.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->connection;
    }
}

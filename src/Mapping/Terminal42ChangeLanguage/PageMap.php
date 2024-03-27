<?php

declare(strict_types=1);

namespace CyberSpectrum\I18N\Contao\Mapping\Terminal42ChangeLanguage;

use CyberSpectrum\I18N\Contao\Mapping\MappingInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * This maps page ids between a source language and a target language.
 */
class PageMap implements MappingInterface
{
    use MapTrait;

    /**
     * Buffer the page types.
     *
     * @var array<int, string>
     */
    private array $types = [];

    /**
     * Create a new instance.
     *
     * @param string          $sourceLanguage The source language.
     * @param string          $targetLanguage The target language.
     * @param ContaoDatabase  $database       The database connection.
     * @param LoggerInterface $logger         The logger to use.
     */
    public function __construct(
        string $sourceLanguage,
        string $targetLanguage,
        ContaoDatabase $database,
        LoggerInterface $logger
    ) {
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
        $this->logger         = $logger;
        $this->database       = $database;
        $this->buildMap();
    }

    /**
     * Obtain the page type for the passed page id.
     *
     * @param int $pageId The page id.
     */
    public function getTypeFor(int $pageId): string
    {
        return ($this->types[$pageId] ?? 'unknown');
    }

    /** Build the map. */
    private function buildMap(): void
    {
        $roots = $this->findRootPages();

        $this->buildPageMap($roots['main'], $roots['source'], $this->sourceMap, $this->sourceMapInverse);
        $this->buildPageMap($roots['main'], $roots['target'], $this->targetMap, $this->targetMapInverse);
        $this->combineSourceAndTargetMaps();
    }

    /**
     * Determine the root pages.
     *
     * @return array{main: int, source: int, target: int}
     *
     * @throws RuntimeException When a root page is missing.
     */
    private function findRootPages(): array
    {
        $result = [
            'source' => null,
            'target' => null,
            'main'   => null,
        ];

        $this->logger->debug(
            'Searching root pages for source "{source}" and target "{target}"',
            ['source' => $this->sourceLanguage, 'target' => $this->targetLanguage]
        );
        foreach ($this->database->getRootPages() as $root) {
            $language = $root['language'];

            if ('1' === $root['fallback']) {
                $this->mainLanguage = $language;
                $result['main']     = $root['id'];
            }

            if ($language === $this->sourceLanguage) {
                $result['source'] = $root['id'];
            }
            if ($language === $this->targetLanguage) {
                $result['target'] = $root['id'];
            }

            // Keep type for being able to filter unknown pages in i.e. articles.
            $this->types[$root['id']] = 'root';
        }

        $this->logger->debug(
            'Found root pages: source: {source}; target: {target}; main: {main}',
            $result
        );

        if (null === $result['source'] || null === $result['target'] || null === $result['main']) {
            throw new RuntimeException('Not all root pages could be found: ' . var_export($result, true));
        }

        return $result;
    }

    /**
     * Build a map for a language and returns the map from source to main.
     *
     * @param int             $mainRoot  The main language root page.
     * @param int             $otherRoot The root page of the other language.
     * @param array<int, int> $map       The mapping array to populate.
     * @param array<int, int> $inverse   The inverse mapping.
     */
    private function buildPageMap(int $mainRoot, int $otherRoot, array &$map, array &$inverse): void
    {
        // Root pages are mapped to each other.
        $map[$otherRoot]    = $mainRoot;
        $inverse[$mainRoot] = $otherRoot;

        // Now fetch all other.
        $isMain      = $mainRoot === $otherRoot;
        $lookupQueue = [$otherRoot];
        do {
            // Fetch children of parents in queue.
            $children = $this->database->getPagesByPidList($lookupQueue);
            // Nothing to do anymore, break it.
            if (empty($children)) {
                break;
            }

            // Reset pid list - we have the children.
            $lookupQueue = [];

            foreach ($children as $index => $child) {
                $childId = $child['id'];
                $main    = $isMain ? $childId : $child['languageMain'];
                // Try to determine automatically.
                if (!$isMain && empty($main)) {
                    if (null === ($main = $this->determineMapFor($index, $child['pid'], $map))) {
                        $this->logger->warning(
                            'Page {id} has no fallback set and unable to determine automatically. Page skipped.',
                            ['id' => $childId]
                        );
                        continue;
                    }

                    $this->logger->warning(
                        'Page {id} (index: {index}) has no fallback set, expect problems, I guess it is {guessed}',
                        ['id' => $childId, 'index' => $index, 'guessed' => $main]
                    );
                }

                $map[$childId]  = $main;
                $inverse[$main] = $childId;

                // Keep type for being able to filter unknown pages in i.e. articles.
                $this->types[$childId] = $child['type'];

                $lookupQueue[] = $childId;
            }
        } while (true);
    }

    /**
     * Determine the mapping for the passed index.
     *
     * @param int             $index       The index to look up.
     * @param int             $parentId    The parent id.
     * @param array<int, int> $inverseList The reverse lookup list.
     *
     * @throws InvalidArgumentException When the parent page has not been mapped.
     */
    private function determineMapFor(int $index, int $parentId, array $inverseList): ?int
    {
        if (!isset($inverseList[$parentId])) {
            throw new InvalidArgumentException(
                'Page id ' . $parentId . ' has not been mapped'
            );
        }

        // Lookup all children of parent page in main language.
        $mainSiblings = $this->database->getPagesByPidList([$inverseList[$parentId]]);

        return $mainSiblings[$index]['id'] ?? null;
    }
}

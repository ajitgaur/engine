<?php
namespace Minds\Core\Discovery;

use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\JsonResponse;
use Minds\Api\Exportable;

class Controllers
{
    /** @var Manager */
    protected $manager;

    public function __construct($manager = null)
    {
        $this->manager = $manager ?? new Manager();
    }

    /**
     * Controller for search requests
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getTrends(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $shuffle = $queryParams['shuffle'] ?? true;
        $tagLimit = 8;
        $postLimit = 5;

        $tagTrends = $this->manager->getTagTrends([ 'limit' => $tagLimit * 2 ]); //Return more tags than we need for posts to feed from
        $postTrends = $this->manager->getPostTrends(array_map(function ($trend) {
            return $trend->getHashtag();
        }, $tagTrends), [ 'limit' => $postLimit ]);

        $hero = array_shift($postTrends);

        $trends =  array_merge(array_slice($tagTrends, 0, $tagLimit), $postTrends);
        
        if ($shuffle) {
            shuffle($trends);
        }

        return new JsonResponse([
            'status' => 'success',
            'trends' => Exportable::_($trends),
            'hero' => $hero->export(),
        ]);
    }

    /**
     * Controller for search requests
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getSearch(ServerRequest $request): JsonResponse
    {
        $queryParams = $request->getQueryParams();
        $query = $queryParams['q'] ?? null;
        $filter = $queryParams['algorithm'] ?? 'latest';

        $entities = $this->manager->getSearch($query, $filter);

        return new JsonResponse([
            'status' => 'success',
            'entities' => Exportable::_($entities),
        ]);
    }

    /**
     * Controller for getting tags
     * @param ServerRequest $request
     * @return JsonResponse
     */
    public function getTags(ServerRequest $request): JsonResponse
    {
        $tags = $this->manager->getTags();
        return new JsonResponse([
            'status' => 'success',
            'tags' => $tags['tags'],
            'trending' => $tags['trending'],
        ]);
    }
}
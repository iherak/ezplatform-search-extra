<?php

namespace Netgen\EzPlatformSearchExtra\Core\Search\Solr\Query\Common;

use eZ\Publish\API\Repository\Values\Content\Query;
use EzSystems\EzPlatformSolrSearchEngine\Query\QueryConverter as BaseQueryConverter;

/**
 * Converts the query tree into an array of Solr query parameters.
 */
class HighlightedQueryConverter extends BaseQueryConverter
{
    private $innerQueryConverter;

    private $highlightingActive;

    private $highlightConfiguration = [];

    public function __construct(
        QueryConverter $queryConverter
    ) {
        $this->innerQueryConverter = $queryConverter;
    }

    public function setHiglightActive($highlightingActive = false) {
        $this->highlightingActive = $highlightingActive;
    }

    public function setHighlightConfiguration($highlightConfiguration = [])
    {
        $this->highlightConfiguration = $highlightConfiguration;
    }

    public function convert(Query $query)
    {
        $params = $this->innerQueryConverter->convert($query);

        if ($this->highlightingActive !== true) {
            return $params;
        }

        $fields = [];
        foreach ($this->highlightConfiguration as $contentType => $fieldIdentifiers) {
            foreach ($fieldIdentifiers as $fieldIdentifier) {
                // build name
            }
        }

        // @todo: temp for testing
        $fields = ['ng_article_full_intro_value_t', 'ng_article_line_intro_value_t'];

        $hlParams = [
            'hl' => 'true',
            'hl.fl' => implode( ' ', $fields),
            //'hl.requireFieldMatch' => 'true',
            //'hl.simple.pre' => $eZFindIni->variable( 'HighLighting', 'SimplePre' ), // @todo
            //'hl.simple.post' => $eZFindIni->variable( 'HighLighting', 'SimplePost' ), // @todo
        ];

        return $hlParams + $params;
    }
}

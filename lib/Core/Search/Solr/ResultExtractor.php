<?php

namespace Netgen\EzPlatformSearchExtra\Core\Search\Solr;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Search\SearchHit;
use eZ\Publish\API\Repository\Values\Content\Search\SearchResult;
use eZ\Publish\API\Repository\Values\ContentType\ContentType;
use eZ\Publish\Core\Search\Common\FieldNameGenerator;
use eZ\Publish\SPI\Search\FieldType\TextField;
use EzSystems\EzPlatformSolrSearchEngine\Query\FacetFieldVisitor;
use EzSystems\EzPlatformSolrSearchEngine\ResultExtractor as BaseResultExtractor;
use Netgen\EzPlatformSearchExtra\Core\Search\Solr\API\FacetBuilder\RawFacetBuilder;

/**
 * This DocumentMapper implementation adds support for handling RawFacetBuilders.
 *
 * @see \Netgen\EzPlatformSearchExtra\Core\Search\Solr\API\Facet\RawFacetBuilder
 */
final class ResultExtractor Extends BaseResultExtractor
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\ResultExtractor
     */
    private $nativeResultExtractor;

    /**
     * @var \eZ\Publish\Core\Search\Common\FieldNameGenerator
     */
    private $fieldNameGenerator;

    /**
     * @var \eZ\Publish\API\Repository\ContentTypeService
     */
    private $contentTypeService;

    /** @noinspection PhpMissingParentConstructorInspection */
    /** @noinspection MagicMethodsValidityInspection */
    /**
     * @param \EzSystems\EzPlatformSolrSearchEngine\ResultExtractor $nativeResultExtractor
     * @param \EzSystems\EzPlatformSolrSearchEngine\Query\FacetFieldVisitor $facetBuilderVisitor
     * @param \eZ\Publish\Core\Search\Common\FieldNameGenerator $fieldNameGenerator
     * @param \eZ\Publish\API\Repository\ContentTypeService $contentTypeService
     */
    public function __construct(
        BaseResultExtractor $nativeResultExtractor,
        FacetFieldVisitor $facetBuilderVisitor,
        FieldNameGenerator $fieldNameGenerator,
        ContentTypeService $contentTypeService
    ) {
        $this->nativeResultExtractor = $nativeResultExtractor;
        $this->facetBuilderVisitor = $facetBuilderVisitor;
        $this->fieldNameGenerator = $fieldNameGenerator;
        $this->contentTypeService = $contentTypeService;
    }

    public function extract($data, array $facetBuilders = [])
    {
        $searchResult = new SearchResult(
            array(
                'time' => $data->responseHeader->QTime / 1000,
                'maxScore' => $data->response->maxScore,
                'totalCount' => $data->response->numFound,
            )
        );

        if (isset($data->facet_counts)) {
            // We'll first need to generate id's for facet builders to match against fields, as also done for
            // visit stage in NativeQueryConverter.
            $facetBuildersById = [];
            foreach ($facetBuilders as $facetBuilder) {
                $facetBuildersById[spl_object_hash($facetBuilder)] = $facetBuilder;
            }

            foreach ($data->facet_counts as $facetCounts) {
                foreach ($facetCounts as $field => $facet) {
                    if (empty($facetBuildersById[$field])) {
                        @trigger_error(
                            'Not setting id of field using FacetFieldVisitor::visitBuilder will not be supported in 2.0'
                            . ', as it makes it impossible to exactly identify which facets belongs to which builder.'
                            . "\nMake sure to adapt your visitor for the following field: ${field}"
                            . "\nExample: 'facet.field' => \"{!ex=dt key=\${id}}${field}\",",
                            E_USER_DEPRECATED);
                    }

                    $searchResult->facets[] = $this->facetBuilderVisitor->mapField(
                        $field,
                        (array)$facet,
                        isset($facetBuildersById[$field]) ? $facetBuildersById[$field] : null
                    );
                }
            }
        }

        foreach ($data->response->docs as $doc) {
            $docId = $doc->id;
            $contentType = $this->contentTypeService->loadContentType($doc->content_type_id_id);
            $docHighlights = [];

            if (isset($data->highlighting) && isset($data->highlighting->$docId)) {
                foreach ($data->highlighting->$docId as $solrFieldIdentifier => $highlights) {
                    $fieldIdentifier = $this->resolveFieldIdentifier($solrFieldIdentifier, $contentType);
                    $docHighlights[$fieldIdentifier] = $highlights;
                }
            }

            $searchHit = new SearchHit(
                array(
                    'score' => $doc->score,
                    'index' => $this->nativeResultExtractor->getIndexIdentifier($doc),
                    'matchedTranslation' => $this->nativeResultExtractor->getMatchedLanguageCode($doc),
                    'valueObject' => $this->extractHit($doc),
                    'highlight' => $docHighlights
                )
            );
            $searchResult->searchHits[] = $searchHit;
        }

        if (!isset($data->facets) || $data->facets->count === 0) {
            return $searchResult;
        }

        foreach ($this->filterNewFacetBuilders($facetBuilders) as $facetBuilder) {
            $identifier = \spl_object_hash($facetBuilder);

            $searchResult->facets[] = $this->facetBuilderVisitor->mapField(
                $identifier,
                [$data->facets->{$identifier}],
                $facetBuilder
            );
        }

        return $searchResult;
    }

    private function resolveFieldIdentifier($solrFieldIdentifier, ContentType $contentType)
    {
        $pattern = "/^{$contentType->identifier}_/";
        $solrFieldIdentifier = preg_replace($pattern, '', $solrFieldIdentifier);
        // @todo: use mapping from configuration
        $fieldIdentifier = preg_replace('/_value_t$/', '', $solrFieldIdentifier);

        return $fieldIdentifier;
    }

    /**
     * @param \eZ\Publish\API\Repository\Values\Content\Query\FacetBuilder[] $facetBuilders
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\FacetBuilder[]
     */
    private function filterNewFacetBuilders(array $facetBuilders)
    {
        return array_filter(
            $facetBuilders,
            function ($facetBuilder) {
                return $facetBuilder instanceof RawFacetBuilder;
            }
        );
    }

    public function extractHit($hit)
    {
        return $this->nativeResultExtractor->extractHit($hit);
    }
}

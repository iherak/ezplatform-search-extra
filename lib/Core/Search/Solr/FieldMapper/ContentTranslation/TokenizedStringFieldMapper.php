<?php

namespace Netgen\EzPlatformSearchExtra\Core\Search\Solr\FieldMapper\ContentTranslation;

use eZ\Publish\Core\Persistence\FieldTypeRegistry;
use eZ\Publish\Core\Search\Common\FieldNameGenerator;
use eZ\Publish\Core\Search\Common\FieldRegistry;
use eZ\Publish\SPI\Persistence\Content;
use eZ\Publish\SPI\Persistence\Content\Field as PersistenceField;
use eZ\Publish\SPI\Persistence\Content\Type as ContentType;
use eZ\Publish\SPI\Persistence\Content\Type\Handler as ContentTypeHandler;
use eZ\Publish\SPI\Search\Field;
use eZ\Publish\SPI\Search\FieldType\StringField;
use eZ\Publish\SPI\Search\FieldType\TextField;
use EzSystems\EzPlatformSolrSearchEngine\FieldMapper\ContentTranslationFieldMapper;

/**
 * Indexes configured fields into tokenized string solr field.
 */
class TokenizedStringFieldMapper extends ContentTranslationFieldMapper
{
    /**
     * @var \eZ\Publish\SPI\Persistence\Content\Type\Handler
     */
    private $contentTypeHandler;

    /**
     * @var \eZ\Publish\Core\Search\Common\FieldNameGenerator
     */
    private $fieldNameGenerator;

    /**
     * @var \Netgen\EzPlatformSearchExtra\Core\Persistence\FieldTypeRegistry
     */
    private $fieldTypeRegistry;

    /**
     * @var \eZ\Publish\Core\Search\Common\FieldRegistry
     */
    private $fieldRegistry;

    /**
     * @var array
     */
    private $highlightingConfiguration;

    /**
     * @param \eZ\Publish\SPI\Persistence\Content\Type\Handler $contentTypeHandler
     * @param \eZ\Publish\Core\Search\Common\FieldNameGenerator $fieldNameGenerator
     * @param \eZ\Publish\Core\Persistence\FieldTypeRegistry $fieldTypeRegistry
     * @param \eZ\Publish\Core\Search\Common\FieldRegistry
     */
    public function __construct(
        ContentTypeHandler $contentTypeHandler,
        FieldNameGenerator $fieldNameGenerator,
        FieldTypeRegistry $fieldTypeRegistry,
        FieldRegistry $fieldRegistry
    ) {
        $this->contentTypeHandler = $contentTypeHandler;
        $this->fieldNameGenerator = $fieldNameGenerator;
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        $this->fieldRegistry = $fieldRegistry;
    }

    public function setHighlightingConfiguration($highlightingConfiguration = [])
    {
        $this->highlightingConfiguration = $highlightingConfiguration;
    }

    public function accept(Content $content, $languageCode)
    {
        $contentType = $this->contentTypeHandler->load(
            $content->versionInfo->contentInfo->contentTypeId
        );

        if (!array_key_exists($contentType->identifier, $this->highlightingConfiguration)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException
     */
    public function mapFields(Content $content, $languageCode)
    {
        $fieldsGrouped = [[]];
        $contentType = $this->contentTypeHandler->load(
            $content->versionInfo->contentInfo->contentTypeId
        );

        foreach ($content->fields as $field) {
            if ($field->languageCode !== $languageCode) {
                continue;
            }

            $fieldsGrouped[] = $this->mapField($contentType, $field);
        }

        return array_merge(...$fieldsGrouped);
    }

    private function mapField(ContentType $contentType, PersistenceField $field)
    {
        $fields = [];

        foreach ($contentType->fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition->id !== $field->fieldDefinitionId) {
                continue;
            }

            $fieldType = $this->fieldRegistry->getType($field->type);

            if (!in_array($fieldDefinition->identifier, $this->highlightingConfiguration[$contentType->identifier])) {
                continue;
            }

            $indexFields = $fieldType->getIndexData($field, $fieldDefinition);
            foreach ($indexFields as $indexField) {
                if ($indexField->value === null) {
                    continue;
                }

                if (!$indexField->type instanceof StringField) {
                    // we'll make a copy only of string fields
                    continue;
                }

                $fields[] = new Field(
                    $name = $this->fieldNameGenerator->getName(
                        $indexField->name,
                        $fieldDefinition->identifier,
                        $contentType->identifier
                    ),
                    $indexField->value,
                    new TextField()
                );
            }
        }

        return $fields;
    }
}

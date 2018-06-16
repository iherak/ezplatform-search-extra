<?php

namespace Netgen\EzPlatformSearchExtra\API\Values\Content\Query\Criterion;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator\Specifications;
use eZ\Publish\API\Repository\Values\Content\Query\CriterionInterface;

/**
 * ObjectStateIdentifier Criterion.
 *
 * Will match content that belongs to one of the given sections.
 */
class ObjectStateIdentifier extends Criterion implements CriterionInterface
{
    /**
     * Create new SectionIdentifier criterion.
     *
     * Content will be matched if it matches one of the Section identifiers in $value
     *
     * @param string|string[] $target One ObjectStateGroup identifier that $value must be matched for
     * @param string|string[] $value One ObjectState identifier that must be matched
     *
     * @throws \InvalidArgumentException if a non string identifier is given
     * @throws \InvalidArgumentException if the value type doesn't match the operator
     */
    public function __construct($target, $value)
    {
        parent::__construct($target, null, $value);
    }

    public function getSpecifications()
    {
        return [
            new Specifications(
                Operator::EQ,
                Specifications::FORMAT_SINGLE,
                Specifications::TYPE_STRING
            ),
        ];
    }

    public static function createFromQueryBuilder($target, $operator, $value)
    {
        return new self($target, $value);
    }
}

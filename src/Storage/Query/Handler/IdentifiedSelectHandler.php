<?php

namespace Bolt\Storage\Query\Handler;

use Bolt\Storage\Query\ContentQueryParser;
use Bolt\Storage\Query\QueryInterface;

/**
 *  Handler to fetch a single record if an identifier is set in the query.
 */
class IdentifiedSelectHandler
{
    /**
     * @param ContentQueryParser $contentQuery
     * @internal param $identifier
     * @return mixed
     */
    public function __invoke(ContentQueryParser $contentQuery)
    {
        if (is_numeric($contentQuery->getIdentifier())) {
            $contentQuery->setParameter('id', $contentQuery->getIdentifier());
        } else {
            $contentQuery->setParameter('slug', $contentQuery->getIdentifier());
        }
        if (count($contentQuery->getContentTypes()) === 1) {
            $contentQuery->setDirective('returnsingle', true);
        }


        return call_user_func_array($contentQuery->getHandler('select'), [$contentQuery]);
    }
}

<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 * (c) Jonathan H. Wage <jonwage@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Datagrid;

use Sonata\AdminBundle\Datagrid\Pager as BasePager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Doctrine pager class.
 *
 * @author     Jonathan H. Wage <jonwage@gmail.com>
 */
class Pager extends BasePager
{
    protected $queryBuilder = null;

    /**
     * {@inheritdoc}
     */
    public function computeNbResult()
    {
        $count = null;
        $countQuery = clone $this->getQuery();

        if (count($this->getParameters()) > 0) {
            $countQuery->setParameters($this->getParameters());
        }


        /*  
            these functions could potentially jam more target entity rows in one row,  
            making the total count over target entity incorrect
        */
        $exceptions = [
            'IFELSE'
        ];

        $moreRows = false;
        foreach($exceptions as $ex) {
            if(strstr($countQuery->getQuery()->getDQL(), $ex) ) {
                $moreRows = true;
            }
        }

        if(!$moreRows) {
            $countQuery->select(sprintf('count(DISTINCT %s.%s) as cnt', $countQuery->getRootAlias(), current($this->getCountColumn())));
            $count = $countQuery->resetDQLPart('orderBy')->getQuery()->getSingleScalarResult();
        } else {
            $count = count($countQuery->getQuery()->getResult());
        }

        return $count;
    }

    /**
     * {@inheritdoc}
     */
    public function getResults($hydrationMode = Query::HYDRATE_OBJECT)
    {
        return $this->getQuery()->execute(array(), $hydrationMode);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->resetIterator();

        $this->setNbResults($this->computeNbResult());

        $this->getQuery()->setFirstResult(null);
        $this->getQuery()->setMaxResults(null);

        if (count($this->getParameters()) > 0) {
            $this->getQuery()->setParameters($this->getParameters());
        }

        if (0 == $this->getPage() || 0 == $this->getMaxPerPage() || 0 == $this->getNbResults()) {
            $this->setLastPage(0);
        } else {
            $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

            $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($this->getMaxPerPage());
        }
    }
}

<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace MageWorx\FilterByStore\Model\ResourceModel\Order\Grid;

use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OriginalCollection;

class Collection extends OriginalCollection
{
    /**
     * Add the store column to filters map.
     */
    protected function _initSelect()
    {
        parent::_initSelect();

        $this->addFilterToMap('store', 'store_table.group_id');

        return $this;
    }

    /**
     * Join store table to the main table. Now all group ids will be available in the grid.
     *
     * @return void
     */
    protected function _renderFiltersBefore()
    {
        $storeTable = $this->getTable('store');
        $this->getSelect()->joinLeft(
            ['store_table' => $storeTable],                         // store_table is an alias
            'main_table.store_id = store_table.store_id ',    // join using store_id (store view id)
            ['store' => 'group_id']                                 // store is an alias
        );
        parent::_renderFiltersBefore();
    }
}

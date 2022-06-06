<h2>The example module for [the answer on StackExchange question](https://magento.stackexchange.com/q/356391/37497).</h3>

___

You should join the store table to the `sales_order_grid` table to add a store id (group id) to it:

> app/code/MageWorx/FilterByStore/etc/adminhtml/di.xml

    <?xml version="1.0"?>
    
    <config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="urn:magento:framework:ObjectManager/etc/config.xsd">
        <type name="Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory">
            <arguments>
                <argument name="collections" xsi:type="array">
                    <item name="sales_order_grid_data_source" xsi:type="string">MageWorx\FilterByStore\Model\ResourceModel\Order\Grid\Collection</item>
                </argument>
            </arguments>
        </type>
    </config>

> MageWorx\FilterByStore\Model\ResourceModel\Order\Grid\Collection

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

Now our grid has all the necessary data. A new filter has become available but has not been defined yet. The `store` column will be invisible because we didn't define it in `<columns>` section of UI listing.

We must define filter:

> app/code/MageWorx/FilterByStore/view/adminhtml/ui_component/sales_order_grid.xml

    <?xml version="1.0" encoding="UTF-8"?>
    <listing xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
             xsi:noNamespaceSchemaLocation="urn:magento:framework:Ui/etc/ui_configuration.xsd">
        <dataSource name="sales_order_grid_data_source">
            <argument name="dataProvider" xsi:type="configurableObject">
                <argument name="data" xsi:type="array">
                    <item name="config" xsi:type="array">
                        <item name="storageConfig" xsi:type="array">
                            <item name="indexField" xsi:type="string">entity_id</item>
                        </item>
                    </item>
                </argument>
            </argument>
        </dataSource>
        <listingToolbar name="listing_top">
            <filters name="listing_filters">
                <filterSelect name="store" class="Magento\Ui\Component\Filters\Type\Select" provider="${ $.parentName }">
                    <settings>
                        <options class="MageWorx\FilterByStore\Ui\Component\Listing\Column\Store\Options"/>
                        <caption translate="true">All Stores</caption>
                        <label translate="true">Stores</label>
                        <dataScope>store</dataScope>
                    </settings>
                </filterSelect>
            </filters>
        </listingToolbar>
    </listing>

Since we have not defined our column and its filter type, we must define it directly in the filter, and it must have the `Magento\Ui\Component\Filters\Type\Select` type.
`<dataScope>store</dataScope>` is our column alias (name) in the table.
For this filter we need to create an options provider that should display the labels of all available stores:

> MageWorx\FilterByStore\Ui\Component\Listing\Column\Store\Options

    <?php
    declare(strict_types=1);
    
    namespace MageWorx\FilterByStore\Ui\Component\Listing\Column\Store;
    
    use Magento\Framework\Escaper;
    use Magento\Framework\Data\OptionSourceInterface;
    use Magento\Store\Model\System\Store as SystemStore;
    
    /**
     * UI store options (not store views!)
     */
    class Options implements OptionSourceInterface
    {
        /**
         * Escaper
         *
         * @var Escaper
         */
        protected $escaper;
    
        /**
         * System store
         *
         * @var SystemStore
         */
        protected $systemStore;
    
        /**
         * @var array
         */
        protected $options;
    
        /**
         * @var array
         */
        protected $currentOptions = [];
    
        /**
         * Constructor
         *
         * @param SystemStore $systemStore
         * @param Escaper $escaper
         */
        public function __construct(SystemStore $systemStore, Escaper $escaper)
        {
            $this->systemStore = $systemStore;
            $this->escaper     = $escaper;
        }
    
        /**
         * Get options
         *
         * @return array
         */
        public function toOptionArray()
        {
            if ($this->options !== null) {
                return $this->options;
            }
    
            $this->generateCurrentOptions();
    
            $this->options = array_values($this->currentOptions);
    
            return $this->options;
        }
    
        /**
         * Sanitize website/store option name
         *
         * @param string $name
         *
         * @return string
         */
        protected function sanitizeName($name)
        {
            $matches = [];
            preg_match('/\$[:]*{(.)*}/', $name, $matches);
            if (count($matches) > 0) {
                $name = $this->escaper->escapeHtml($this->escaper->escapeJs($name));
            } else {
                $name = $this->escaper->escapeHtml($name);
            }
    
            return $name;
        }
    
        /**
         * Generate current options
         *
         * @return void
         */
        protected function generateCurrentOptions(): void
        {
            $websiteCollection = $this->systemStore->getWebsiteCollection();
            $groupCollection   = $this->systemStore->getGroupCollection();
    
            foreach ($websiteCollection as $website) {
                foreach ($groupCollection as $group) {
                    if ($group->getWebsiteId() === $website->getId()) {
                        $stores[] = [
                            'label' => str_repeat(' ', 4) . $this->sanitizeName($group->getName()),
                            'value' => $group->getId(),
                        ];
                    }
                }
    
                if (!empty($stores)) {
                    $this->currentOptions[] = [
                        'label' => $this->sanitizeName($website->getName()),
                        'value' => array_values($stores),
                    ];
                }
            }
        }
    }

Now, after refresh the page we will se something similar to this:

[![result filter 1][1]][1]
[![result filter 2][2]][2]

For the test I have create a new store, with one order. Total order number is 930 (929 in the Main Store and 1 in Store #2).

Without filter, total 930:

[![test without filter][3]][3]

Filter was set to Main Store, total orders 929:

[![test with main store filter][4]][4]

Filter was set to Store #2, total orders 1:

[![Test with store #2 fitler][5]][5]

Result with other filter active and with custom sort by:

[![custom filter + custom sort by][6]][6]

**PS: the [Victor's answer](https://magento.stackexchange.com/a/356558/37497) is also great solution. You can use it too.**

[1]: https://i.stack.imgur.com/dBPPP.png
[2]: https://i.stack.imgur.com/QlU54.png
[3]: https://i.stack.imgur.com/mpkL3.png
[4]: https://i.stack.imgur.com/137ZL.png
[5]: https://i.stack.imgur.com/YJQtO.png
[6]: https://i.stack.imgur.com/5VxlA.jpg

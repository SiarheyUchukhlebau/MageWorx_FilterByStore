<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */
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

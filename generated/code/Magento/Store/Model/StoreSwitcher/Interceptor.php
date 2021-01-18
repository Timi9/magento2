<?php
namespace Magento\Store\Model\StoreSwitcher;

/**
 * Interceptor class for @see \Magento\Store\Model\StoreSwitcher
 */
class Interceptor extends \Magento\Store\Model\StoreSwitcher implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(array $storeSwitchers)
    {
        $this->___init();
        parent::__construct($storeSwitchers);
    }

    /**
     * {@inheritdoc}
     */
    public function switch(\Magento\Store\Api\Data\StoreInterface $fromStore, \Magento\Store\Api\Data\StoreInterface $targetStore, string $redirectUrl) : string
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'switch');
        return $pluginInfo ? $this->___callPlugins('switch', func_get_args(), $pluginInfo) : parent::switch($fromStore, $targetStore, $redirectUrl);
    }
}

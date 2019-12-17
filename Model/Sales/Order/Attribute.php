<?php
/**
 * Ecomteck
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Ecomteck.com license that is
 * available through the world-wide-web at this URL:
 * https://ecomteck.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Ecomteck
 * @package     Ecomteck_OrderCustomAttributes
 * @copyright   Copyright (c) 2018 Ecomteck (https://ecomteck.com/)
 * @license     https://ecomteck.com/LICENSE.txt
 */
namespace Ecomteck\OrderCustomAttributes\Model\Sales\Order;

use Magento\Catalog\Api\Data\EavAttributeInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Customer attribute model
 *
 * @method int getSortOrder()
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Attribute extends \Magento\Eav\Model\Attribute
{
    /**
     * Name of the module
     */
    const MODULE_NAME = 'Ecomteck_OrderCustomAttributes';

    /**
     * Prefix of model events names
     *
     * @var string
     */
    protected $_eventPrefix = 'ecomteck_order_entity_attribute';

    /**
     * Prefix of model events object
     *
     * @var string
     */
    protected $_eventObject = 'attribute';

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Customer\Model\Metadata\AttributeMetadataCache
     */
    private $attributeMetadataCache;

    /**
     * @var \Magento\SalesRule\Model\RuleFactory
     */
    private $ruleFactory;

    protected $rule;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     * @since 100.2.0
     */
    protected $serializer;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Eav\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionDataFactory
     * @param \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Catalog\Model\Product\ReservedAttributeList $reservedAttributeList
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array|null $data
     * @param \Magento\SalesRule\Model\RuleFactory|null $ruleFactory
     * @param \Magento\Framework\Serialize\Serializer\Json $serializer
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Eav\Model\Entity\TypeFactory $eavTypeFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataObjectProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Catalog\Model\Product\ReservedAttributeList $reservedAttributeList,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        DateTimeFormatterInterface $dateTimeFormatter,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        \Magento\SalesRule\Model\RuleFactory $ruleFactory = null,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        $this->indexerRegistry = $indexerRegistry;
        $this->ruleFactory = $ruleFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\SalesRule\Model\RuleFactory::class);
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $eavConfig,
            $eavTypeFactory,
            $storeManager,
            $resourceHelper,
            $universalFactory,
            $optionDataFactory,
            $dataObjectProcessor,
            $dataObjectHelper,
            $localeDate,
            $reservedAttributeList,
            $localeResolver,
            $dateTimeFormatter,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Init resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Magento\Customer\Model\ResourceModel\Attribute::class);
    }

    /**
     * @inheritdoc
     */
    public function afterSave()
    {
        if ($this->isObjectNew() && (bool)$this->getData(EavAttributeInterface::IS_USED_IN_GRID)) {
            $this->_getResource()->addCommitCallback([$this, 'invalidate']);
        } elseif (!$this->isObjectNew() && $this->dataHasChangedFor(EavAttributeInterface::IS_USED_IN_GRID)) {
            $this->_getResource()->addCommitCallback([$this, 'invalidate']);
        }
        //$this->attributeMetadataCache->clean();
        return parent::afterSave();
    }

    /**
     * Prepare data before saving
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function beforeSave()
    {
        // Serialize conditions
        if ($this->getRule()->getConditions()) {
            $this->setConditionsSerialized($this->serializer->serialize($this->getRule()->getConditions()->asArray()));
        }

        parent::beforeSave();
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        //$this->attributeMetadataCache->clean();
        return parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterLoad()
    {
        $this->getRule()->setConditionsSerialized($this->getConditionsSerialized());
        return parent::afterLoad();
    }

    /**
     * Init indexing process after customer delete
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function afterDeleteCommit()
    {
        if ($this->getData(EavAttributeInterface::IS_USED_IN_GRID) == true) {
            $this->invalidate();
        }
        return parent::afterDeleteCommit();
    }

    /**
     * Init indexing process after customer save
     *
     * @return void
     */
    public function invalidate()
    {
        /** @var \Magento\Framework\Indexer\IndexerInterface $indexer */
        //$indexer = $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID);
        //$indexer->invalidate();
    }

    /**
     * Check whether attribute is searchable in admin grid and it is allowed
     *
     * @return bool
     */
    public function canBeSearchableInGrid()
    {
        return $this->getData('is_searchable_in_grid') && in_array($this->getFrontendInput(), ['text', 'textarea']);
    }

    /**
     * Check whether attribute is filterable in admin grid and it is allowed
     *
     * @return bool
     */
    public function canBeFilterableInGrid()
    {
        return $this->getData('is_filterable_in_grid')
            && in_array($this->getFrontendInput(), ['text', 'date', 'select', 'boolean']);
    }

    /**
     * @inheritdoc
     */
    public function __sleep()
    {
        $this->unsetData('entity_type');
        return array_diff(
            parent::__sleep(),
            ['indexerRegistry', '_website']
        );
    }

    /**
     * @inheritdoc
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->indexerRegistry = $objectManager->get(\Magento\Framework\Indexer\IndexerRegistry::class);
    }

    public function getRule()
    {
        if(!$this->rule){
            $this->rule = $this->ruleFactory->create();
            if($this->hasConditionsSerialized()){
                $this->rule->setConditionsSerialized($this->getConditionsSerialized());
            }
        }
        return $this->rule;
    }
}

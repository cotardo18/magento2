<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Model\Config\Structure;

use Magento\Framework\Store\StoreManagerInterface;

abstract class AbstractElement implements ElementInterface
{
    /**
     * Element data
     *
     * @var array
     */
    protected $_data = [];

    /**
     * Current configuration scope
     *
     * @var string
     */
    protected $_scope;

    /**
     * Store manager
     *
     * @var \Magento\Framework\Store\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\Store\StoreManagerInterface $storeManager
     */
    public function __construct(StoreManagerInterface $storeManager)
    {
        $this->_storeManager = $storeManager;
    }

    /**
     * Translate element attribute
     *
     * @param string $code
     * @return string
     */
    protected function _getTranslatedAttribute($code)
    {
        if (false == array_key_exists($code, $this->_data)) {
            return '';
        }
        return __($this->_data[$code]);
    }

    /**
     * Set element data
     *
     * @param array $data
     * @param string $scope
     * @return void
     */
    public function setData(array $data, $scope)
    {
        $this->_data = $data;
        $this->_scope = $scope;
    }

    /**
     * Retrieve flyweight data
     *
     * @return array
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Retrieve element id
     *
     * @return string
     */
    public function getId()
    {
        return isset($this->_data['id']) ? $this->_data['id'] : '';
    }

    /**
     * Retrieve element label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_getTranslatedAttribute('label');
    }

    /**
     * Retrieve element label
     *
     * @return string
     */
    public function getComment()
    {
        return $this->_getTranslatedAttribute('comment');
    }

    /**
     * Retrieve frontend model class name
     *
     * @return string
     */
    public function getFrontendModel()
    {
        return isset($this->_data['frontend_model']) ? $this->_data['frontend_model'] : '';
    }

    /**
     * Retrieve arbitrary element attribute
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        return array_key_exists($key, $this->_data) ? $this->_data[$key] : null;
    }

    /**
     * Check whether element should be displayed
     *
     * @return bool
     */
    public function isVisible()
    {
        $showInScope = [
            \Magento\Framework\Store\ScopeInterface::SCOPE_STORE => $this->_hasVisibilityValue('showInStore'),
            \Magento\Framework\Store\ScopeInterface::SCOPE_WEBSITE => $this->_hasVisibilityValue('showInWebsite'),
            \Magento\Framework\App\ScopeInterface::SCOPE_DEFAULT => $this->_hasVisibilityValue('showInDefault'),
        ];

        if ($this->_storeManager->isSingleStoreMode()) {
            $result = !$this->_hasVisibilityValue('hide_in_single_store_mode') && array_sum($showInScope);
            return $result;
        }

        return !empty($showInScope[$this->_scope]);
    }

    /**
     * Retrieve value of visibility flag
     *
     * @param string $key
     * @return bool
     */
    protected function _hasVisibilityValue($key)
    {
        return isset($this->_data[$key]) && $this->_data[$key];
    }

    /**
     * Retrieve css class of a tab
     *
     * @return string
     */
    public function getClass()
    {
        return isset($this->_data['class']) ? $this->_data['class'] : '';
    }

    /**
     * Retrieve config path for given id
     *
     * @param string $fieldId
     * @param string $fieldPrefix
     * @return string
     */
    protected function _getPath($fieldId, $fieldPrefix = '')
    {
        $path = isset($this->_data['path']) ? $this->_data['path'] : '';
        return $path . '/' . $fieldPrefix . $fieldId;
    }

    /**
     * Retrieve element config path
     *
     * @param string $fieldPrefix
     * @return string
     */
    public function getPath($fieldPrefix = '')
    {
        return $this->_getPath($this->getId(), $fieldPrefix);
    }
}

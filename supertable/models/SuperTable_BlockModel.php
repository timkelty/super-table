<?php
namespace Craft;

class SuperTable_BlockModel extends BaseElementModel
{
	// Static
	// =========================================================================

	private static $_preloadedFields = array();
    
	// Properties
	// =========================================================================

	protected $elementType = 'SuperTable_Block';
	private $_owner;

	// Public Methods
	// =========================================================================

	public function getFieldLayout()
	{
		$blockType = $this->getType();

		if ($blockType) {
			return $blockType->getFieldLayout();
		}
	}

	public function getLocales()
	{
		// If the SuperTable field is translatable, than each individual block is tied to a single locale, and thus aren't
		// translatable. Otherwise all blocks belong to all locales, and their content is translatable.

		if ($this->ownerLocale) {
			return array($this->ownerLocale);
		} else {
			$owner = $this->getOwner();

			if ($owner) {
				// Just send back an array of locale IDs -- don't pass along enabledByDefault configs
				$localeIds = array();

				foreach ($owner->getLocales() as $localeId => $localeInfo) {
					if (is_numeric($localeId) && is_string($localeInfo)) {
						$localeIds[] = $localeInfo;
					} else {
						$localeIds[] = $localeId;
					}
				}

				return $localeIds;
			} else {
				return array(craft()->i18n->getPrimarySiteLocaleId());
			}
		}
	}

	public function getType()
	{
		if ($this->typeId) {
			return craft()->superTable->getBlockTypeById($this->typeId);
		}
	}

	public function getOwner()
	{
		if (!isset($this->_owner) && $this->ownerId) {
			$this->_owner = craft()->elements->getElementById($this->ownerId, null, $this->locale);

			if (!$this->_owner) {
				$this->_owner = false;
			}
		}

		if ($this->_owner) {
			return $this->_owner;
		}
	}

	public function setOwner(BaseElementModel $owner)
	{
		$this->_owner = $owner;
	}

	public function getContentTable()
	{
		return craft()->superTable->getContentTableName($this->_getField());
	}

	public function getFieldColumnPrefix()
	{
		return 'field_';
	}

	public function getFieldContext()
	{
		return 'superTableBlockType:'.$this->typeId;
	}

	// Protected Methods
	// =========================================================================

	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'fieldId'     => AttributeType::Number,
			'ownerId'     => AttributeType::Number,
			'ownerLocale' => AttributeType::Locale,
			'typeId'      => AttributeType::Number,
			'sortOrder'   => AttributeType::Number,
		));
	}
	
	/**
	 * @inheritdoc
	 */
	protected function createContent()
	{
		$fieldId = $this->fieldId;

		if (!isset(self::$_preloadedFields[$fieldId]))
		{
			$blockTypes = craft()->superTable->getBlockTypesByFieldId($fieldId);

			if (count($blockTypes) > 1)
			{
				$contexts = array();

				foreach ($blockTypes as $blockType)
				{
					$contexts[] = 'superTableBlockType:'.$blockType->id;
				}

				// Preload them to save ourselves some DB queries, and discard
				craft()->fields->getAllFields(null, $contexts);
			}

			// Don't do this again for this field
			self::$_preloadedFields[$fieldId] = true;
		}

		return parent::createContent();
	}

	// Private Methods
	// =========================================================================

	private function _getField()
	{
		return craft()->fields->getFieldById($this->fieldId);
	}
}